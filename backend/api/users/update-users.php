<?php

  if (session_status() === PHP_SESSION_NONE){
    session_start();
  }

  header('Content-Type: application/json');
  require_once '../../includes/db.php';

  // must be logged in
  if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode([
      'success' => false,
      'message' => 'Please login first'
    ]));
  }

  $timeout = 1800;
  if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    http_response_code(401);
    exit(json_encode([
      'success' => false,
      'message' => 'Session expired. Please login again'
    ]));
  }
  $_SESSION['last_activity'] = time();

  if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode([
      'success' => false,
      'message' => 'Access denied. Only admins can manage users.'
    ]));
  }

  // POST method only
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode([
      'success' => false,
      'message' => 'Method not allowed'
    ]));
  }

  $data = json_decode(file_get_contents('php://input'), true);

  $id = intval($data['id'] ?? 0);
  if ($id <= 0) {
    http_response_code(400);
    exit(json_encode([
      'success' => false,
      'message' => 'Invalid or missing user ID'
    ]));
  }

  // check if the user exists
  $checkStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $checkStmt->execute([$id]);
  $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

  if (!$existingUser) {
    http_response_code(404);
    exit(json_encode([
      'success' => false,
      'message' => 'User not found'
    ]));
  }

  // toggling if the user is active or inactive
  if (!empty($data['toggle_only'])) {
    $newStatus = $data['status'] ?? '';

    if (!in_array($newStatus, ['active', 'inactive'])) {
      http_response_code(400);
      exit(json_encode([
        'success' => false,
        'message' => 'Invalid status value'
      ]));
    }

    try {
      $stmt = $pdo->prepare("UPDATE users SET status =? WHERE id = ?");
      $stmt->execute([$newStatus, $id]);
      exit(json_encode([
        'success' => true,
        'message' => 'User status updated to "' . $newStatus . '"successfully'
      ]));
    } catch (PDOException $e) {
      http_response_code(500);
      exit(json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
      ]));
    }
  }

  // update the user
  $full_name = trim($data['full_name'] ?? '');
  $username = trim($data['username'] ?? '');
  $role = trim($data['role'] ?? '');
  $status = trim($data['status'] ?? '');
  $password = $data['password'] ?? '';

  // validation of the required fields
  if (empty($full_name) || empty($username) || empty($role) || empty($status)) {
    http_response_code(400);
    exit(json_encode([
      'success' => false,
      'message' => 'full_name, username, role, and status are required'
    ]));
  }

  // validation of role
  $allowedRoles = ['admin', 'manager', 'cashier'];
  if (!in_array($role, $allowedRoles)) {
    http_response_code(400);
    exit(json_encode([
      'success' => false,
      'message' => 'Invalid role'
    ]));
  }

  // validation of status
  if (!in_array($status, ['active', 'inactive'])) {
    http_response_code(400);
    exit(json_encode([
      'success' => false,
      'message' => 'Invalid status'
    ]));
  }

  // validation of username format
  if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
      http_response_code(400);
      exit(json_encode([
        'success' => false, 
        'message' => 'Username can only contain letters, numbers, and underscores.'
      ]));
  }

  // check duplicate username
  $dupCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
  $dupCheck->execute([$username, $id]);
  if ($dupCheck->fetch()) {
    http_response_code(409);
    exit(json_encode([
      'success' => false,
      'message' => 'Username already exists'
    ]));
  }

  // validate new password if provided
  if (!empty($password) && strlen($password) < 6 ){
    http_response_code(400);
    exit(json_encode([
      'success' => false,
      'message' => "Password must be at least 6 characters long"
    ]));
  }

  // update the user
  try {
    if (!empty($password)) {
      // hash the new password
      $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
      $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, role = ?, status = ?, password = ? WHERE id = ?");

      $stmt->execute([$full_name, $username, $role, $status, $hashedPassword, $id]);
    } else {
      // update without changing the password
      $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, role = ?, status = ? WHERE id = ?");

      $stmt->execute([$full_name, $username, $role, $status, $id]);
    }

    echo json_encode([
      'success' => true,
      'message' => 'User "' . $full_name . '" updated successfully.'
    ]);

  } catch (PDOException $e) {
    http_response_code(500);
    exit(json_encode([
      'success' => false,
      'message' => 'Database error: ' . $e->getMessage()
    ]));
  }



?>