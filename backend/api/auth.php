<?php
    session_start();
    header('Content-Type: application/json');
    require_once '../includes/db.php' ;


    // Get action from query string or POST
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    switch($action){
        case 'login':
            handleLogin();
            break;
        // case 'logout':
        //     handleLogout();
        //     break;
        default:
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid Action"
            ]);
            break;
    }

    //handle logIn
    function handleLogin() {
        $method = $_SERVER['REQUEST_METHOD'];
        if($method !== 'POST'){
            http_response_code(405);
            echo json_encode([
                "success" => false,
                "message" => "Method not allowed"
            ]);
            exit();
        }

        $uname = $_POST['username'] ?? '';
        $pw = $_POST['password'] ?? '';
            
        //validate input
        if(empty($uname) || empty($pw)){
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Username and password are required"
            ]);
            exit();
        }

        global $pdo;
        $stmt = $pdo->prepare("SELECT username,password FROM users WHERE username=?");
        $stmt ->execute([$uname]);
        $account = $stmt -> fetch(PDO::FETCH_ASSOC);
        if(!empty($account)){
            if (password_verify($pw, $account['password'])){
                $_SESSION['username'] = $account['username'];
                $_SESSION['logged_in'] = true;
                http_response_code(200);
                echo json_encode([
                    "succss" => true,
                    "message" => "Welcome " . $account['username']
                ]);
                exit();
            }
            else{
                http_response_code(200);
                echo json_encode([
                    "succss" => False,
                    "message" => "Invalid Password"
                ]);
                exit();
            }
        } else {
            echo json_encode([
                "success" => false,
                "message" => "User Not Found"
            ]);
            exit();
        }
    } 
?>