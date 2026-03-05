<?php
// hadn't added session yet - olen 
    require_once '../../includes/db.php';
    require_once '../../includes/functions.php';

    header('Content-type: application/json');

    if($_SERVER['REQUEST_METHOD'] !== 'GET'){
        http_response_code(405);
        echo json_encode([
            "message" => "Method not allowed"
        ]);
        exit();
    }

   $product_id = $_GET['prod_id'] ?? null;
    if($product_id){
        $sql = "SELECT * FROM products WHERE id = :id";
        $stmt = $pdo -> prepare($sql);
        $stmt -> bindParam(':id',  $product_id);
        $stmt -> execute();

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$product){
            echo json_encode([
                "success" => false,
                "message" => "product not found"
            ]);
            exit();
        } else {
            echo json_encode([
                "success" => true,
                "product" => $product
            ]);
            exit();
        }

    } else {
        $sql = "SELECT * FROM products";
        $stmt = $pdo->prepare($sql);
        $stmt -> execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "products" => $products
        ]);
        exit();

    }
?>