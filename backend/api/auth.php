<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    header('Content-Type: application/json');
    require_once '../includes/db.php' ;


    // Get action from query string or POST
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch($action){
        case 'login':
            handleLogin();
            break;
        case 'logout':
            handleLogout();
            break;
        default:
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid Action"
            ]);
            break;
    }

    //handle logIn
    function handleLogin(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false, 
                'message' => 'Method not allowed'
                ]);
            exit;
        }

        $uname = trim($_POST['username'] ?? '');
        $pw = $_POST['password'] ?? '';
            
        //validate input
        if(empty($uname) || empty($pw)){
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Username and password are required"
            ]);
            exit;
        }

        global $pdo;

        $stmt = $pdo->prepare("SELECT id, username, password, full_name, role, status FROM users WHERE username=? LIMIT 1");
        $stmt ->execute([$uname]);
        $account = $stmt -> fetch(PDO::FETCH_ASSOC);
        
        // user not found
        if (!$account) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
            exit;
        }

        // account deactivated
        if ($account['status'] === 'inactive') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Account is inactive'
            ]);
            exit;
        }

        // wrong password
        if (!password_verify($pw, $account['password'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid Password'
            ]);
            exit;
        }

        $_SESSION['user_id'] = $account['id'];
        $_SESSION['username'] = $account['username'];
        $_SESSION['full_name'] = $account['full_name'];
        $_SESSION['role'] = $account['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'role' => $account['role']
        ]);
    } 

    function handleLogout(): void {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
        header('Location: ../../index.php?logged_out=1');
        exit;
    }
?>