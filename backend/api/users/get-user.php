<?php
session_start();
require_once '../../includes/auth-check.php';

//set the content type to JSON for all responses
header('Content-Type: application/json');

//role check: Only admins can access this endpoint
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403); 
    echo json_encode(['success' => false, 'message' => 'Access denied. You do not have the required permissions.']);
    exit;
}

require_once '../../includes/db.php'; 

$id = $_GET['id'] ?? null;

try {
    if ($id) {
        //fetch a single user by ID
        $stmt = $pdo->prepare("SELECT id, username, full_name, role, status, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode(['success' => true, 'users' => [$user]]);
        } else {
            http_response_code(404); 
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } else {
        //fetch all users
        $stmt = $pdo->query("SELECT id, username, full_name, role, status, created_at, last_login FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'users' => $users]);
    }
} catch (PDOException $e) {
    //database error
    http_response_code(500); 
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
