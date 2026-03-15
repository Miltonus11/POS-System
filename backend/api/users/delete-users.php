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

  // cant delete urself
  if ($id === intval($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode([
      'success' => false,
      'message' => 'You cannot delete your own account'
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

  // cannot delete the last admin
  if ($existingUser['role'] === 'admin') {
    $adminCountStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'admin' AND status = 'active'");
    $adminCountStmt->execute();
    $adminCount = $adminCountStmt->fetch(PDO::FETCH_ASSOC);
    if ((int) $adminCount['total'] <= 1) {
      http_response_code(403);
      exit(json_encode([
        'success' => false,
        'message' => 'Cannot delete the last admin, create another admin first'
      ]));
    }
  }

  // delete the user
  try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id =?");
    $stmt->execute([$id]);

    echo json_encode([
      'success' => true,
      'message' => 'User "' . $existingUser['full_name'] . '" deleted successfully'
    ]);

  } catch (PDOException $e) {
    // if the user have a sales records, it will be deactived instead
    if ($e->getCode() === '23000') {
      try{
        $deactivate = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
        $deactivate->execute([$id]);

        echo json_encode([
          'success' => true,
          'message' => '"' . $existingUser['full_name'] . '" has sales records and cannot be deleted. Account deactivated instead'
        ]);
      } catch (PDOException $e2) {
        http_response_code(500);
        echo json_encode([
          'success' => false,
          'message' => 'Database error: ' . $e2->getMessage()
        ]);
      }
    } else {
      http_response_code(500);
      echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
      ]);
    }
  }

?>
