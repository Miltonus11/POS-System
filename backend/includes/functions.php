<?php
  // Format the amount to Peso currency
  function formatCurrency($amount) {
    return '₱' . number_format((float)$amount, 2);
  }

  // Unique Receipt Number
  function generateReceiptNumber() {
    return 'RCP-' .date('YmdHis') . '-' . rand(100, 999);
  }

  // Sanitize user input
  function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
  }

  // JSON for APIs
  function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
      'success' => $success,
      'message' => $message,
      'data' => $data
    ]);
    exit;
  }

  // Format date
  function formatDate($date) {
    if (!$date) return '';
    return date('M d, Y h:i A;', strtotime($date));
  }

  // Update product stock after sale
  function updateProductStock($pdo, $productId, $quantity) {
    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

    return $stmt->execute([$quantity, $productId]);
  }

  // Record Stock Movement
  function recordStockMovement($pdo, $productId, $type, $quantity, $reference = null) {
    $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reference VALUES (?, ?, ?, ?)");

    return $stmt->execute([$productId, $type, $quantity, $reference]);
  }
  
  // Barcode Generator
  function generateBarcode($prefix = '8888') {
    $timestamp = date('ymd');               // 6 digits: YYMMDD
    $random = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); // 6 random digits
    $raw = $prefix . $timestamp . $random; // 16 digits total

    // EAN-13 style: trim to 12 digits, then calculate check digit
    $code = substr($prefix . $timestamp . $random, 0, 12);

    // Calculate check digit
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
      $sum += $code[$i] * ($i % 2 === 0 ? 1 : 3);
    }
    $checkDigit = (10 - ($sum % 10)) % 10;

    return $code . $checkDigit; // 13-digit barcode
  }
  // get current dates
  function getCurrentDate() {
    return date('Y-m-d H:i:s');
  }
?>

