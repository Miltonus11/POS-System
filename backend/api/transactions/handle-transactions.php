<?php
  session_start();
  require_once '../includes/db.php';
  require_once '../includes/functions.php';

  // post method only
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed');
  }

  // auth guard
  if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Unauthorized Access');
  }

  $allowedRoles = ['admin', 'manager', 'cashier'];
  if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    http_response_code(403);
    jsonResponse(false, 'Access Denied, your role cannot process transactions');
  }

  // read json
  $data = json_decode(file_get_contents('php://input'), true);

  if (!$data) {
    jsonResponse(false, 'Invalid or empty request body');
  }

  // validation of the required fields
  if (empty($data['items']) || !is_array($data['items'])) {
    jsonResponse(false, 'No items provided');
  }
  
  if (empty($data['payment_method'])) {
    jsonResponse(false, 'Payment method is required');
  }

  $validPaymentMethods = ['cash', 'card', 'gcash', 'paymaya'];
  if (!in_array($data['payment_method'], $validPaymentMethods)) {
    jsonResponse(false, 'Invalid Payment method. Allowed: cash, card, gcash or paymaya');
  }

  // sanitize inputs
  $paymentMethod = $data['payment_method'];
  $amountPaid = (float) $data['amount_paid'];
  $customerName = sanitizeInput($data['customer_name'] ?? '');
  $discountAmount = (float) ($data['discount_amount'] ?? 0);
  $notes = sanitizeInput($data['notes'] ?? '');
  $cashierId = (int) $_SESSION['user_id'];

  // validation for each cart item
  foreach ($data['items'] as $index => $item) {
      if (empty($item['product_id']) || !is_numeric($item['product_id'])) {
        jsonResponse(false, "Item #{$index} is missing a valid product_id");
      }
      if (empty($item['quantity']) || !is_numeric($item['quantity']) || (int)$item['quantity'] < 1) {
        jsonResponse(false, "Item #{$index} has an invalid quantity");
      }
      if (!isset($item['price']) || !is_numeric($item['price']) || (float)$item['price'] < 0) {
        jsonResponse(false, "Item #{$index} has an invalid price");
      }
    }
  // begin db transaction
  try {
    $pdo->beginTransaction();

    // STEP 1: we lock and verify every produc's stock to avoid two users sell last unit at the same time
    $productIds = array_map(fn($i) => (int)$i['product_id'], $data['items']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    // build query string with the correct placeholders
    $stockCheck = $pdo->prepare("SELECT id, name, price, stock_quantity, status FROM products WHERE id IN ($placeholders) FOR UPDATE");

    $stockCheck->execute($productIds);
    $dbProducts = $stockCheck->fetchAll(PDO::FETCH_ASSOC);

    // index by id for easy lookup
    $dbProductMap = [];
    foreach ($dbProducts as $p) {
      $dbProductMap[$p['id']] = $p;
    }

    // aggregate (merge into one) quantities per produce in case same product appears twice
    $aggregatedItems = [];
    foreach ($data['items'] as $item) {
      $pid = (int)$item['product_id'];
      if (!isset($aggregatedItems[$pid])) {
        $aggregatedItems[$pid] = [
          'product_id' => $pid,
          'quantity' => 0,
          'price' => (float)$item['price'],
        ];
      }

      $aggregatedItems[$pid]['quantity'] += (int)$item['quantity'];
    }

    // check if each product exists, its status and stock
    foreach ($aggregatedItems as $pid => $item) {
      if (!isset($dbProductMap[$pid])) {
        $pdo->rollBack();
        jsonResponse(false, "Product ID {$pid} not found");
      }

      $product = $dbProductMap[$pid];

      if ($product['status'] !== 'active') {
        $pdo->rollBack();
        jsonResponse(false, "Product '{$product['name']}' is no longer available");
      }

      if ($product['stock_quantity'] < $item['quantity']) {
        $pdo->rollBack();
        jsonResponse(false, 
          "Insufficient stock for '{$product['name']}'. " .
          "Available: {$product['stock_quantity']}, Requested: {$item['quantity']}");
      }
    }

  
    // STEP 2: calculate totals
    $subtotal = 0;
    foreach ($aggregatedItems as $item) {
      // db price for source of truth
      $dbPrice = (float)$dbProductMap[$item['product_id']]['price'];
      $subtotal += $dbPrice * $item['quantity'];
    }

    // clamp discount so it never exceeds subtotal
    $discountAmount = min($discountAmount, $subtotal);
    if ($discountAmount < 0) $discountAmount = 0;

    $afterDiscount = $subtotal - $discountAmount;
    $taxAmount = $afterDiscount * 0.12; //12% VAT
    $totalAmount = $afterDiscount + $taxAmount;
    $changeAmount = $amountPaid - $totalAmount;

    // for non cash payments, change is always 0
    if ($paymentMethod !== 'cash') {
      $amountPaid = $totalAmount;
      $changeAmount = 0;
    }

    // validation of cash payment to covers the total
    if ($paymentMethod === 'cash' && $amountPaid < $totalAmount) {
      $pdo->rollBack();
      jsonResponse(false, 
        'Insufficient payment ' .
        'Total is ₱' . number_format($totalAmount, 2) .
        ', but received ₱' . number_format($amountPaid, 2)
      );
    }

    // STEP 3: generate receipt num by looping to avoid rare collision (get unique one)
    do {
      $receiptNo = generateReceiptNumber();
      $dupCheck = $pdo->prepare("SELECT id FROM sales WHERE receipt_no = ?");
      $dupCheck->execute([$receiptNo]);
    } while ($dupCheck->fetch());

    // STEP 4: we insert the sale record
    $insertSale = $pdo->prepare("
      INSERT INTO sales
        (receipt_no, cashier_id, total_amount, payment_method, amount_paid, change_amount, discount_amount, tax_amount, customer_name, notes, sale_date)
      VALUES
        (:receipt_no, :cashier_id, :total_amount, :payment_method, :amount_paid, :change_amount, :discount_amount, :tax_amount, :customer_name, :notes, NOW())
    ");

    $insertSale->execute([
      ':receipt_no' => $receiptNo,
      ':cashier_id' => $cashierId,
      ':total_amount' => round($totalAmount, 2),
      ':payment_method' => $paymentMethod,
      ':amount_paid' => round($amountPaid, 2),
      ':change_amount' => round(max(0, $changeAmount), 2),
      ':discount_amount' => round($discountAmount, 2),
      ':tax_amount' => round($taxAmount, 2),
      ':customer_name' => $customerName ?: null,
      ':notes' => $notes ?: null,
    ]);

    $saleId = (int)$pdo->lastInsertId();

    // STEP 5: insert sale items, update stock, log movemnts
    $insertItem = $pdo->prepare("
      INSERT INTO sale_items
        (sale_id, product_id, quantity, unit_price, subtotal, discount)
      VALUES
        (:sale_id, :product_id, :quantity, :unit_price, :subtotal, :discount)
    ");

    foreach ($aggregatedItems as $pid => $item) {
      $dbPrice = (float)$dbProductMap[$pid]['price'];
      $itemSubtotal = $dbPrice * $item['quantity'];

      // spread out among items the overall discount proportionally
      $itemDiscount = $subtotal > 0 ? round(($itemSubtotal / $subtotal) * $discountAmount, 2) : 0;

      // insert the sale item row
      $insertItem->execute([
        ':sale_id' => $saleId,
        ':product_id' => $pid,
        ':quantity' => $item['quantity'],
        ':unit_price' => $dbPrice,
        ':subtotal' => round($itemSubtotal, 2),
        ':discount' => $itemDiscount,
      ]);

      // deduck the stock 
      $stockUpdated = updateProductStock($pdo, $pid, $item['quantity']);
      if (!$stockUpdated) {
        $pdo->rollBack();
        jsonResponse(false, "Failed to update stock for product ID {$pid}");
      }

      // log the outgoing stock movement
      recordStockMovement(
        $pdo,
        $pid,
        'out',
        $item['quantity'],
        'sale',
        $saleId,
        'Sold via POS - Receipt ' . $receiptNo,
        $cashierId
      );
    }

    // STEP 6L commit everything
    $pdo->commit();

    // STEP 7: return the success response
    jsonResponse(true, 'Transaction completed successfully', [
      'sale_id' => $saleId,
      'receipt_no' => $receiptNo,
      'subtotal' => round($subtotal, 2),
      'discount_amount' => round($discountAmount, 2),
      'tax_amount' => round($taxAmount, 2),
      'total_amount' => round($totalAmount, 2),
      'amount_paid' => round($amountPaid, 2),
      'change_amount' => round(max(0, $changeAmount), 2),
      'payment_method' => $paymentMethod,
      'cashier_id' => $cashierId,
      'items_count' => count($aggregatedItems)
    ]);

  } catch (PDOException $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    http_response_code(500);
    jsonResponse(false, 'Database error: ' . $e->getMessage());
  }
  
?>