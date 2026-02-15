<?php
require_once '../includes/db.php';

$barcode = $_GET['barcode'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM products WHERE barcode = ? AND status = 'active'");
$stmt->execute([$barcode]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {
    echo json_encode([
        'found' => true,
        'source' => 'local',
        'product' => $product
    ]);
} else {
    $url = "https://world.openfoodfacts.org/api/v0/product/{$barcode}.json";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data['status'] == 1) {
        $productInfo = [
            'found' => true,
            'source' => 'api',
            'barcode' => $barcode,
            'name' => $data['product']['product_name'] ?? 'Unknown Product',
            'brand' => $data['product']['brands'] ?? '',
            'image' => $data['product']['image_url'] ?? '',
            'category' => $data['product']['categories'] ?? ''
        ];
        echo json_encode($productInfo);
    } else {
        echo json_encode(['found' => false, 'barcode' => $barcode]);
    }
}
?>
