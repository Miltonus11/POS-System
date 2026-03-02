<?php
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }
  header('Content-Type: application/json');
  require_once '../../includes/db.php';

  // check if there's user logged in
  if (!isset($_SESSION['user_id'])) {
      http_response_code(401);
      exit(json_encode([
        'success' => false, 
        'message' => 'Not logged in. Please login first.'
      ]));
  }

  // 30 mins inactivity
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

  // only the admin can add user
  if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    exit(json_encode([
      'success' => false,
      'error' => 'Unauthorized Access. Admin access required'
    ]));
  } 

  // post method only
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode([
      'success' => false,
      'error' => 'Method not allowed.'
    ]));
  }

  // reader of the json
  $data = json_decode(file_get_contents('php://input'), true);

  // validation of the required fields
  $full_name = trim($data['full_name'] ?? '');
  $username = trim($data['username'] ?? '');
  $role = trim($data['role'] ?? '');
  $password = $data['password'] ?? '';

  if (empty($full_name) || empty($username) || empty($role) || empty($password)) {
    exit(json_encode([
      'success' => false,
      'error' => "All require fields are required"
    ]));
  }

  // validation of role
  $allowedRoles = ['admin', 'manager', 'cashier'];
  if (!in_array($role, $allowedRoles)) {
    http_response_code(400);
    exit(json_encode([
      'success' => false,
      'message' => 'Invalid role. Only admin, manager, and cashier are allowed'
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
  $checkStmt = $pdo->prepare('SELECT id from users WHERE username = ?');
  $checkStmt->execute([$username]);
  if ($checkStmt->fetch()) {
    http_response_code(409);
    exit(json_encode([
    'success' => false,
    'message' => "Username already exists. Please choose a different one"
    ]));
  }

  // hash password
  $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

  // inser the new user
  try {
    $stmt = $pdo->prepare("
      INSERT INTO users (username, password, full_name, role, status, created_at)
      VALUES (?, ?, ?, ?, 'active', NOW())
    ");
    $stmt->execute([$username, $hashedPassword, $full_name, $role]);

    echo json_encode([
      'success' => true,
      'message' => 'User "' . $full_name . '" added successfully.',
      'user_id' => (int) $pdo->lastInsertId()
    ]);

  } catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
      'success' => false,
      'message' => 'Database error: ' . $e->getMessage()
    ]);
  }

?>