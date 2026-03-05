<?php
    require_once '../../includes/db.php';
    require_once '../../includes/functions.php';

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        jsonResponse(false, 'Method not allowed');
    }

    $id = $_POST['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        http_response_code(400);
        jsonResponse(false, 'Valid product ID is required');
    }

    $data = [
        'name' => $_POST['name'] ?? null,
        'description' => $_POST['description'] ?? null,
        'category_id' => $_POST['category_id'] ?? null,
        'price' => $_POST['price'] ?? null,
        'cost' => $_POST['cost'] ?? null,
        'stock_quantity' => $_POST['stock_quantity'] ?? null,
        'min_stock_level' => $_POST['min_stock_level'] ?? null,
        'unit' => $_POST['unit'] ?? null,
        'status' => $_POST['status'] ?? null,
        'image' => $_POST['image'] ?? null,
    ];

    // Filter out null values
    $data = array_filter($data, function($value) {
        return $value !== null;
    });

    $errors = validateProductUpdateData($data);
    if (!empty($errors)) {
        http_response_code(400);
        jsonResponse(false, 'Validation errors: ' . implode(', ', $errors));
    }

    try {
        $product = getProductById($pdo, $id);
        if (!$product) {
            http_response_code(404);
            jsonResponse(false, 'Product not found');
        }

        $result = updateProduct($pdo, $id, $data);
        if ($result) {
            $updatedProduct = getProductById($pdo, $id);
            http_response_code(200);
            jsonResponse(true, 'Product updated successfully', $updatedProduct);
        } else {
            http_response_code(500);
            jsonResponse(false, 'Failed to update product');
        }
    } catch (PDOException $e) {
        http_response_code(500);
        jsonResponse(false, 'Database error: ' . $e->getMessage());
    }
?>