<?php
require_once '../vendor/autoload.php';
require_once '../includes/db.php';

$saleId = $_GET['sale_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT s.*, u.full_name as cashier_name,
           GROUP_CONCAT(
               CONCAT(si.quantity, 'x ', p.name, ' @ ₱', si.unit_price)
               SEPARATOR '|'
           ) as items
    FROM sales s
    JOIN users u ON s.cashier_id = u.id
    JOIN sale_items si ON s.id = si.sale_id
    JOIN products p ON si.product_id = p.id
    WHERE s.id = ?
    GROUP BY s.id
");
$stmt->execute([$saleId]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

// PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8');
$pdf->SetCreator('QuickSale POS');
$pdf->SetTitle('Receipt #' . $sale['receipt_no']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

$html = "
<h1 style='text-align:center;'>QuickSale Store</h1>
<p style='text-align:center; font-size:10px;'>
    123 Main Street, Manila<br>
    Tel: +63 912 345 6789
</p>
<hr>
<p><strong>Receipt #:</strong> {$sale['receipt_no']}<br>
<strong>Date:</strong> {$sale['sale_date']}<br>
<strong>Cashier:</strong> {$sale['cashier_name']}</p>
<hr>
<table border='1' cellpadding='4'>
    <tr style='background-color:#f0f0f0;'>
        <th>Item</th>
        <th>Qty</th>
        <th>Price</th>
        <th>Total</th>
    </tr>";

$items = explode('|', $sale['items']);
foreach ($items as $item) {
    $html .= "<tr><td colspan='4'>{$item}</td></tr>";
}

$html .= "
</table>
<hr>
<p style='text-align:right;'>
    <strong>TOTAL:</strong> ₱" . number_format($sale['total_amount'], 2) . "<br>
    <strong>PAID:</strong> ₱" . number_format($sale['amount_paid'], 2) . "<br>
    <strong>CHANGE:</strong> ₱" . number_format($sale['change_amount'], 2) . "
</p>
<p style='text-align:center; font-size:9px;'>Thank you for shopping with us!</p>
";

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output(__DIR__ . '/../receipts/receipt_' . $sale['receipt_no'] . '.pdf', 'F');

echo json_encode([
    'success' => true,
    'filename' => 'receipt_' . $sale['receipt_no'] . '.pdf'
]);
?>
