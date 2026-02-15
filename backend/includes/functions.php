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


?>