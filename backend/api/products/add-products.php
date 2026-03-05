<?php
    require_once '../../includes/db.php';
    require_once '../../includes/functions.php';

    header('Content-type: application/json');

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        http_response_code(405);
        echo json_encode([
            "message" => "Method not allowed"
        ]);
        exit();
    }
    $barcode = generateBarcode();
    $product_name = $_POST['name'] ?? null;
    $product_desc = $_POST['description']  ?? null;
    $product_category = $_POST['category_id']  ?? null;
    $product_price = $_POST['price']  ?? null;
    $product_cost = $_POST['cost']  ?? null ;
    $stock_quantity = $_POST['stock_quantity'] ?? null;
    $min_stock_level = $_POST['min_stock_level']  ?? null;
    $created_at = getCurrentDate();
    $updated_at = getCurrentDate();

    //constants
    $unit = "pcs";
    $status = "active";
    
    // var_dump("name: ". $product_name) ;
    try{
        // validate product name, category, price , cost and stock level
        if(empty($product_name) || empty($product_category) || empty($product_price) ||
         empty($product_cost) || empty($min_stock_level)){
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "incomplete fields"
            ]);
            exit();
        }

        $sql = "INSERT INTO products (barcode, name, description, category_id, price, cost, stock_quantity, min_stock_level, unit, status, created_at, updated_at)
                VALUES (:barcode, :name, :description, :category_id, :price, :cost, :stock_quantity, :min_stock_level, :unit, :status, :created_at, :updated_at)";
        $stmt = $pdo -> prepare($sql);
        $stmt->bindParam(':barcode', $barcode);
        $stmt->bindParam(':name', $product_name);
        $stmt->bindParam(':description', $product_desc);
        $stmt->bindParam(':category_id', $product_category);
        $stmt->bindParam(':price', $product_price);
        $stmt->bindParam(':cost', $product_cost);
        $stmt->bindParam(':stock_quantity', $stock_quantity);
        $stmt->bindParam(':min_stock_level', $min_stock_level);
        $stmt->bindParam(':unit', $unit);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->bindParam(':updated_at', $updated_at);

        $stmt -> execute();
        // gets latest inserted ID
        $lastId = $pdo->lastInsertId();
        $sqlFetch = "SELECT * FROM products WHERE id = :id";
        $stmtFetch = $pdo->prepare($sqlFetch);
        $stmtFetch->bindParam(':id', $lastId);
        $stmtFetch->execute();
        $newProduct = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        http_response_code(201);
        jsonResponse(true, "Product Added Succesfully", $newProduct);
        exit();
    } catch(PDOException $e){
        http_response_code(500);
        echo json_encode([
            "message" => "Error" . $e->getMessage()
        ]);
        exit();
    }

?>