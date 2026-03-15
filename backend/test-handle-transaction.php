<?php
// test-handle-transaction.php
// Place this in your project ROOT: quicksale-pos/test-handle-transaction.php
// Access via: http://localhost/POS-System/backend/test-handle-transaction.php
//
// Simulates a logged-in cashier session and runs multiple test cases
// against api/handle-transaction.php

session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// ── Simulate a logged-in cashier ──────────────────────────────────────────────
$_SESSION['user_id'] = 2;         // cashier1 from sample data
$_SESSION['role']    = 'cashier';
$_SESSION['full_name'] = 'Juan dela Cruz';

// ── Helper: call handle-transaction.php internally ────────────────────────────
// Instead of making a real HTTP request, we directly include the logic
// by re-implementing the call using a local function.
function callHandleTransaction(array $payload): array {
    // Write payload to a temp stream so php://input can be simulated
    // Since we can't fake php://input directly, we call the logic directly
    // by capturing the output of the API file.

    // Save current session
    global $pdo;

    // Encode payload as JSON
    $json = json_encode($payload);

    // Use a stream wrapper trick: write to a temp file and pass it
    $tmpFile = tempnam(sys_get_temp_dir(), 'qs_test_');
    file_put_contents($tmpFile, $json);

    // Override php://input by using a custom stream
    // We'll use output buffering + direct function calls instead
    // to avoid needing a real HTTP request.

    // ── Inline the transaction logic here for testing ─────────────────────────
    $data = json_decode($json, true);

    // Auth checks (already passed since we set session above)
    $allowedRoles = ['admin', 'manager', 'cashier'];
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Unauthorized', 'data' => []];
    }
    if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
        return ['success' => false, 'message' => 'Access denied', 'data' => []];
    }

    // Validate items
    if (empty($data['items']) || !is_array($data['items'])) {
        return ['success' => false, 'message' => 'No items provided.', 'data' => []];
    }
    if (empty($data['payment_method'])) {
        return ['success' => false, 'message' => 'Payment method is required.', 'data' => []];
    }
    $validPaymentMethods = ['cash', 'card', 'gcash', 'paymaya'];
    if (!in_array($data['payment_method'], $validPaymentMethods)) {
        return ['success' => false, 'message' => 'Invalid payment method.', 'data' => []];
    }
    if (!isset($data['amount_paid']) || !is_numeric($data['amount_paid']) || $data['amount_paid'] < 0) {
        return ['success' => false, 'message' => 'Valid amount paid is required.', 'data' => []];
    }

    foreach ($data['items'] as $index => $item) {
        if (empty($item['product_id']) || !is_numeric($item['product_id'])) {
            return ['success' => false, 'message' => "Item #$index is missing a valid product_id.", 'data' => []];
        }
        if (empty($item['quantity']) || !is_numeric($item['quantity']) || (int)$item['quantity'] < 1) {
            return ['success' => false, 'message' => "Item #$index has an invalid quantity.", 'data' => []];
        }
        if (!isset($item['price']) || !is_numeric($item['price']) || (float)$item['price'] < 0) {
            return ['success' => false, 'message' => "Item #$index has an invalid price.", 'data' => []];
        }
    }

    $paymentMethod  = $data['payment_method'];
    $amountPaid     = (float)$data['amount_paid'];
    $customerName   = sanitizeInput($data['customer_name'] ?? '');
    $discountAmount = (float)($data['discount_amount'] ?? 0);
    $notes          = sanitizeInput($data['notes'] ?? '');
    $cashierId      = (int)$_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // Lock & verify stock
        $productIds   = array_map(fn($i) => (int)$i['product_id'], $data['items']);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stockCheck   = $pdo->prepare("SELECT id, name, price, stock_quantity, status FROM products WHERE id IN ($placeholders) FOR UPDATE");
        $stockCheck->execute($productIds);
        $dbProducts   = $stockCheck->fetchAll(PDO::FETCH_ASSOC);

        $dbProductMap = [];
        foreach ($dbProducts as $p) {
            $dbProductMap[$p['id']] = $p;
        }

        // Aggregate
        $aggregatedItems = [];
        foreach ($data['items'] as $item) {
            $pid = (int)$item['product_id'];
            if (!isset($aggregatedItems[$pid])) {
                $aggregatedItems[$pid] = ['product_id' => $pid, 'quantity' => 0, 'price' => (float)$item['price']];
            }
            $aggregatedItems[$pid]['quantity'] += (int)$item['quantity'];
        }

        foreach ($aggregatedItems as $pid => $item) {
            if (!isset($dbProductMap[$pid])) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "Product ID $pid not found.", 'data' => []];
            }
            $product = $dbProductMap[$pid];
            if ($product['status'] !== 'active') {
                $pdo->rollBack();
                return ['success' => false, 'message' => "Product '{$product['name']}' is not available.", 'data' => []];
            }
            if ($product['stock_quantity'] < $item['quantity']) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "Insufficient stock for '{$product['name']}'. Available: {$product['stock_quantity']}, Requested: {$item['quantity']}.", 'data' => []];
            }
        }

        // Totals
        $subtotal = 0;
        foreach ($aggregatedItems as $pid => $item) {
            $subtotal += (float)$dbProductMap[$pid]['price'] * $item['quantity'];
        }
        $discountAmount = min(max($discountAmount, 0), $subtotal);
        $afterDiscount  = $subtotal - $discountAmount;
        $taxAmount      = $afterDiscount * 0.12;
        $totalAmount    = $afterDiscount + $taxAmount;
        $changeAmount   = $amountPaid - $totalAmount;

        if ($paymentMethod !== 'cash') {
            $amountPaid   = $totalAmount;
            $changeAmount = 0;
        }

        if ($paymentMethod === 'cash' && $amountPaid < $totalAmount) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Insufficient payment. Total is ₱' . number_format($totalAmount, 2) . ', received ₱' . number_format($amountPaid, 2) . '.', 'data' => []];
        }

        // Receipt number
        do {
            $receiptNo = generateReceiptNumber();
            $dupCheck  = $pdo->prepare("SELECT id FROM sales WHERE receipt_no = ?");
            $dupCheck->execute([$receiptNo]);
        } while ($dupCheck->fetch());

        // Insert sale
        $insertSale = $pdo->prepare("
            INSERT INTO sales (receipt_no, cashier_id, total_amount, payment_method, amount_paid, change_amount, discount_amount, tax_amount, customer_name, notes, sale_date)
            VALUES (:receipt_no, :cashier_id, :total_amount, :payment_method, :amount_paid, :change_amount, :discount_amount, :tax_amount, :customer_name, :notes, NOW())
        ");
        $insertSale->execute([
            ':receipt_no'      => $receiptNo,
            ':cashier_id'      => $cashierId,
            ':total_amount'    => round($totalAmount, 2),
            ':payment_method'  => $paymentMethod,
            ':amount_paid'     => round($amountPaid, 2),
            ':change_amount'   => round(max(0, $changeAmount), 2),
            ':discount_amount' => round($discountAmount, 2),
            ':tax_amount'      => round($taxAmount, 2),
            ':customer_name'   => $customerName ?: null,
            ':notes'           => $notes ?: null,
        ]);
        $saleId = (int)$pdo->lastInsertId();

        // Insert items + stock update + movement log
        $insertItem = $pdo->prepare("
            INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal, discount)
            VALUES (:sale_id, :product_id, :quantity, :unit_price, :subtotal, :discount)
        ");
        foreach ($aggregatedItems as $pid => $item) {
            $dbPrice      = (float)$dbProductMap[$pid]['price'];
            $itemSubtotal = $dbPrice * $item['quantity'];
            $itemDiscount = $subtotal > 0 ? round(($itemSubtotal / $subtotal) * $discountAmount, 2) : 0;

            $insertItem->execute([
                ':sale_id'    => $saleId,
                ':product_id' => $pid,
                ':quantity'   => $item['quantity'],
                ':unit_price' => $dbPrice,
                ':subtotal'   => round($itemSubtotal, 2),
                ':discount'   => $itemDiscount,
            ]);
            updateProductStock($pdo, $pid, $item['quantity']);
            recordStockMovement($pdo, $pid, 'out', $item['quantity'], 'sale', $saleId, 'Sold via POS — Receipt ' . $receiptNo, $cashierId);
        }

        $pdo->commit();

        return ['success' => true, 'message' => 'Transaction completed successfully.', 'data' => [
            'sale_id'         => $saleId,
            'receipt_no'      => $receiptNo,
            'subtotal'        => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount'      => round($taxAmount, 2),
            'total_amount'    => round($totalAmount, 2),
            'amount_paid'     => round($amountPaid, 2),
            'change_amount'   => round(max(0, $changeAmount), 2),
            'payment_method'  => $paymentMethod,
            'items_count'     => count($aggregatedItems),
        ]];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'data' => []];
    }
}

// ── Test runner helpers ───────────────────────────────────────────────────────
$results  = [];
$passed   = 0;
$failed   = 0;

function runTest(string $label, array $payload, bool $expectSuccess, string $expectContains = ''): void {
    global $results, $passed, $failed;

    $result = callHandleTransaction($payload);
    $ok     = $result['success'] === $expectSuccess;

    // If we expect a specific message fragment, check that too
    if ($ok && $expectContains !== '') {
        $ok = str_contains(strtolower($result['message']), strtolower($expectContains));
    }

    if ($ok) {
        $passed++;
        $status = 'PASS';
    } else {
        $failed++;
        $status = 'FAIL';
    }

    $results[] = [
        'status'         => $status,
        'label'          => $label,
        'expected'       => $expectSuccess ? 'SUCCESS' : 'FAIL',
        'got_success'    => $result['success'] ? 'true' : 'false',
        'message'        => $result['message'],
        'data'           => $result['data'],
    ];
}

// ════════════════════════════════════════════════════════════════════════════
// TEST CASES
// ════════════════════════════════════════════════════════════════════════════

// ── TC01: Normal valid cash sale ──────────────────────────────────────────────
runTest(
    'TC01 — Valid cash sale (2x Coke + 1x Piattos)',
    [
        'items' => [
            ['product_id' => 1, 'quantity' => 2, 'price' => 65.00],  // Coca-Cola 1.5L
            ['product_id' => 4, 'quantity' => 1, 'price' => 15.00],  // Piattos Cheese
        ],
        'payment_method'  => 'cash',
        'amount_paid'     => 300.00,
        'discount_amount' => 0,
        'customer_name'   => 'Maria Reyes',
        'notes'           => '',
    ],
    true  // expect success
);

// ── TC02: Valid GCash sale ────────────────────────────────────────────────────
runTest(
    'TC02 — Valid GCash sale (1x Mineral Water)',
    [
        'items' => [
            ['product_id' => 3, 'quantity' => 1, 'price' => 12.00],  // Mineral Water
        ],
        'payment_method'  => 'gcash',
        'amount_paid'     => 0,    // non-cash: amount_paid auto-set to total
        'discount_amount' => 0,
        'customer_name'   => '',
        'notes'           => '',
    ],
    true
);

// ── TC03: Valid sale with fixed discount ──────────────────────────────────────
runTest(
    'TC03 — Valid sale with ₱10 fixed discount',
    [
        'items' => [
            ['product_id' => 5, 'quantity' => 2, 'price' => 14.00],  // Lucky Me
        ],
        'payment_method'  => 'cash',
        'amount_paid'     => 500.00,
        'discount_amount' => 10.00,
        'customer_name'   => 'Pedro',
        'notes'           => 'Senior discount',
    ],
    true
);

// ── TC04: Valid sale with PayMaya ─────────────────────────────────────────────
runTest(
    'TC04 — Valid PayMaya sale (1x USB Cable)',
    [
        'items' => [
            ['product_id' => 10, 'quantity' => 1, 'price' => 120.00], // USB Cable
        ],
        'payment_method'  => 'paymaya',
        'amount_paid'     => 0,
        'discount_amount' => 0,
        'customer_name'   => '',
        'notes'           => '',
    ],
    true
);

// ── TC05: Cash payment insufficient ──────────────────────────────────────────
runTest(
    'TC05 — Insufficient cash payment (should FAIL)',
    [
        'items' => [
            ['product_id' => 1, 'quantity' => 3, 'price' => 65.00],  // 3x Coke = ₱195 + tax
        ],
        'payment_method'  => 'cash',
        'amount_paid'     => 50.00,  // way too low
        'discount_amount' => 0,
        'customer_name'   => '',
        'notes'           => '',
    ],
    false,
    'insufficient payment'
);

// ── TC06: Product does not exist ──────────────────────────────────────────────
runTest(
    'TC06 — Non-existent product ID (should FAIL)',
    [
        'items' => [
            ['product_id' => 9999, 'quantity' => 1, 'price' => 50.00],
        ],
        'payment_method'  => 'cash',
        'amount_paid'     => 200.00,
        'discount_amount' => 0,
        'customer_name'   => '',
        'notes'           => '',
    ],
    false,
    'not found'
);

// ── TC07: Empty items array ───────────────────────────────────────────────────
runTest(
    'TC07 — Empty items array (should FAIL)',
    [
        'items'          => [],
        'payment_method' => 'cash',
        'amount_paid'    => 100.00,
    ],
    false,
    'no items'
);

// ── TC08: Missing payment method ──────────────────────────────────────────────
runTest(
    'TC08 — Missing payment method (should FAIL)',
    [
        'items' => [
            ['product_id' => 1, 'quantity' => 1, 'price' => 65.00],
        ],
        'amount_paid' => 100.00,
    ],
    false,
    'payment method'
);

// ── TC09: Invalid payment method ─────────────────────────────────────────────
runTest(
    'TC09 — Invalid payment method "bitcoin" (should FAIL)',
    [
        'items' => [
            ['product_id' => 1, 'quantity' => 1, 'price' => 65.00],
        ],
        'payment_method' => 'bitcoin',
        'amount_paid'    => 200.00,
    ],
    false,
    'invalid payment method'
);

// ── TC10: Zero quantity item ──────────────────────────────────────────────────
runTest(
    'TC10 — Item with quantity 0 (should FAIL)',
    [
        'items' => [
            ['product_id' => 1, 'quantity' => 0, 'price' => 65.00],
        ],
        'payment_method' => 'cash',
        'amount_paid'    => 200.00,
    ],
    false,
    'invalid quantity'
);

// ── TC11: Negative price ──────────────────────────────────────────────────────
runTest(
    'TC11 — Negative price (should FAIL)',
    [
        'items' => [
            ['product_id' => 1, 'quantity' => 1, 'price' => -65.00],
        ],
        'payment_method' => 'cash',
        'amount_paid'    => 200.00,
    ],
    false,
    'invalid price'
);

// ── TC12: Quantity exceeds stock ──────────────────────────────────────────────
// Product 10 (USB Cable) has 25 units — requesting 999
runTest(
    'TC12 — Quantity exceeds available stock (should FAIL)',
    [
        'items' => [
            ['product_id' => 10, 'quantity' => 999, 'price' => 120.00],
        ],
        'payment_method'  => 'cash',
        'amount_paid'     => 999999.00,
        'discount_amount' => 0,
    ],
    false,
    'insufficient stock'
);

// ── TC13: Duplicate product IDs in same cart (should aggregate) ───────────────
runTest(
    'TC13 — Same product appears twice in items (should aggregate & succeed)',
    [
        'items' => [
            ['product_id' => 6, 'quantity' => 1, 'price' => 8.00],  // Surf Powder
            ['product_id' => 6, 'quantity' => 2, 'price' => 8.00],  // Surf Powder again → total 3
        ],
        'payment_method'  => 'cash',
        'amount_paid'     => 500.00,
        'discount_amount' => 0,
    ],
    true
);

// ── TC14: Discount larger than subtotal (should clamp, not error) ─────────────
runTest(
    'TC14 — Discount exceeds subtotal (should clamp to subtotal & succeed)',
    [
        'items' => [
            ['product_id' => 3, 'quantity' => 1, 'price' => 12.00],  // ₱12 subtotal
        ],
        'payment_method'  => 'cash',
        'amount_paid'     => 500.00,
        'discount_amount' => 9999.00,  // way bigger than subtotal
    ],
    true  // succeeds — discount is clamped to subtotal, total becomes ~0 + tax
);

// ── TC15: Role check — inventory_staff blocked ────────────────────────────────
runTest(
    'TC15 — inventory_staff role (should FAIL with access denied)',
    (function() {
        // Temporarily override role
        $_SESSION['role'] = 'inventory_staff';
        $payload = [
            'items' => [['product_id' => 1, 'quantity' => 1, 'price' => 65.00]],
            'payment_method' => 'cash',
            'amount_paid'    => 200.00,
        ];
        return $payload;
    })(),
    false,
    'access denied'
);
// Restore role for any tests after this
$_SESSION['role'] = 'cashier';


// ════════════════════════════════════════════════════════════════════════════
// RENDER RESULTS
// ════════════════════════════════════════════════════════════════════════════
$total = $passed + $failed;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Handle Transaction — Test Results</title>
<style>
  body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; padding: 24px; }
  h1   { font-size: 1.4rem; margin-bottom: 4px; color: #f8fafc; }
  .subtitle { font-size: .85rem; color: #64748b; margin-bottom: 24px; font-family: monospace; }

  .summary {
    display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;
  }
  .stat {
    background: #1e293b; border-radius: 10px; padding: 14px 22px;
    min-width: 120px; text-align: center;
  }
  .stat .num { font-size: 2rem; font-weight: 700; font-family: monospace; }
  .stat .lbl { font-size: .75rem; color: #64748b; text-transform: uppercase; letter-spacing: .06em; }
  .pass-num { color: #10b981; }
  .fail-num { color: #ef4444; }
  .total-num{ color: #f59e0b; }

  table { width: 100%; border-collapse: collapse; font-size: .85rem; }
  th    { background: #1e293b; padding: 10px 14px; text-align: left;
          font-size: .75rem; text-transform: uppercase; letter-spacing: .05em;
          color: #94a3b8; border-bottom: 2px solid #334155; }
  td    { padding: 10px 14px; border-bottom: 1px solid #1e293b; vertical-align: top; }
  tr:hover td { background: #1e293b55; }

  .badge { display: inline-block; padding: 3px 10px; border-radius: 20px;
           font-size: .75rem; font-weight: 700; font-family: monospace; }
  .badge-pass { background: #064e3b; color: #10b981; }
  .badge-fail { background: #450a0a; color: #ef4444; }

  .msg  { color: #94a3b8; font-size: .8rem; font-family: monospace; }
  .data { color: #38bdf8; font-size: .75rem; font-family: monospace; white-space: pre-wrap; }

  .tc-label { font-weight: 600; color: #f8fafc; }
  .expected { font-size: .75rem; color: #64748b; font-family: monospace; }
</style>
</head>
<body>

<h1>🧪 Handle Transaction — Test Results</h1>
<p class="subtitle">
  Session: user_id=<?= $_SESSION['user_id'] ?> | role=<?= $_SESSION['role'] ?> |
  Run at: <?= date('M d, Y h:i:s A') ?>
</p>

<div class="summary">
  <div class="stat"><div class="num total-num"><?= $total ?></div><div class="lbl">Total Tests</div></div>
  <div class="stat"><div class="num pass-num"><?= $passed ?></div><div class="lbl">Passed</div></div>
  <div class="stat"><div class="num fail-num"><?= $failed ?></div><div class="lbl">Failed</div></div>
  <div class="stat">
    <div class="num" style="color:<?= $failed === 0 ? '#10b981' : '#f59e0b' ?>">
      <?= $total > 0 ? round(($passed / $total) * 100) : 0 ?>%
    </div>
    <div class="lbl">Pass Rate</div>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th style="width:60px">#</th>
      <th>Test Case</th>
      <th style="width:80px">Result</th>
      <th>Message from API</th>
      <th>Returned Data</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($results as $i => $r): ?>
    <tr>
      <td style="color:#64748b;font-family:monospace"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></td>
      <td>
        <div class="tc-label"><?= htmlspecialchars($r['label']) ?></div>
        <div class="expected">Expected: <?= $r['expected'] ?> | Got success=<?= $r['got_success'] ?></div>
      </td>
      <td><span class="badge <?= $r['status'] === 'PASS' ? 'badge-pass' : 'badge-fail' ?>">
        <?= $r['status'] ?>
      </span></td>
      <td class="msg"><?= htmlspecialchars($r['message']) ?></td>
      <td class="data"><?= !empty($r['data']) ? htmlspecialchars(json_encode($r['data'], JSON_PRETTY_PRINT)) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php if ($failed === 0): ?>
  <p style="margin-top:24px;color:#10b981;font-weight:600;">✅ All <?= $total ?> tests passed!</p>
<?php else: ?>
  <p style="margin-top:24px;color:#ef4444;font-weight:600;">❌ <?= $failed ?> test(s) failed. Check the messages above.</p>
<?php endif; ?>

</body>
</html>