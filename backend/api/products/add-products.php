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
?>