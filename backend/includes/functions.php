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
  function recordStockMovement($pdo, $productId, $type, $quantity, $referenceType = null, $referenceId = null, $notes = null, $userId = 1) {
    $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, notes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

    return $stmt->execute([$productId, $type, $quantity, $referenceType, $referenceId, $notes, $userId]);
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

  // Validate product update data
  function validateProductUpdateData($data) {
    $errors = [];
    if (isset($data['name']) && empty($data['name'])) {
      $errors[] = 'Product name cannot be empty';
    }
    if (isset($data['category_id']) && empty($data['category_id'])) {
      $errors[] = 'Category cannot be empty';
    }
    if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
      $errors[] = 'Valid price is required';
    }
    if (isset($data['cost']) && (!is_numeric($data['cost']) || $data['cost'] < 0)) {
      $errors[] = 'Valid cost is required';
    }
    if (isset($data['min_stock_level']) && (!is_numeric($data['min_stock_level']) || $data['min_stock_level'] < 0)) {
      $errors[] = 'Valid minimum stock level is required';
    }
    if (isset($data['stock_quantity']) && (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0)) {
      $errors[] = 'Valid stock quantity is required';
    }
    return $errors;
  }

  // Get product by ID
  function getProductById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Update product
  function updateProduct($pdo, $id, $data) {
    $product = getProductById($pdo, $id);
    if (!$product) {
      return false;
    }

    $fields = [];
    $params = [];
    $stockChanged = false;
    $oldStock = $product['stock_quantity'];
    $newStock = isset($data['stock_quantity']) ? $data['stock_quantity'] : $oldStock;

    if (isset($data['name'])) {
      $fields[] = "name = ?";
      $params[] = sanitizeInput($data['name']);
    }
    if (isset($data['description'])) {
      $fields[] = "description = ?";
      $params[] = sanitizeInput($data['description']);
    }
    if (isset($data['category_id'])) {
      $fields[] = "category_id = ?";
      $params[] = $data['category_id'];
    }
    if (isset($data['price'])) {
      $fields[] = "price = ?";
      $params[] = $data['price'];
    }
    if (isset($data['cost'])) {
      $fields[] = "cost = ?";
      $params[] = $data['cost'];
    }
    if (isset($data['stock_quantity'])) {
      $fields[] = "stock_quantity = ?";
      $params[] = $data['stock_quantity'];
      $stockChanged = true;
    }
    if (isset($data['min_stock_level'])) {
      $fields[] = "min_stock_level = ?";
      $params[] = $data['min_stock_level'];
    }
    if (isset($data['unit'])) {
      $fields[] = "unit = ?";
      $params[] = $data['unit'];
    }
    if (isset($data['status'])) {
      $fields[] = "status = ?";
      $params[] = $data['status'];
    }
    if (isset($data['image'])) {
      $fields[] = "image = ?";
      $params[] = $data['image'];
    }

    $fields[] = "updated_at = ?";
    $params[] = getCurrentDate();
    $params[] = $id;

    $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    if ($result && $stockChanged && $newStock != $oldStock) {
      $movementType = 'adjustment';
      $quantity = abs($newStock - $oldStock);
      $notes = 'Product update: ' . ($newStock > $oldStock ? 'increased' : 'decreased') . ' by ' . $quantity;
      recordStockMovement($pdo, $id, $movementType, $quantity, 'product_update', $id, $notes);
    }

    return $result;
  }

