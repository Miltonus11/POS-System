<?php
session_start();

if(!isset($_SESSION['logged_in'])){
    header("Location: ../views/index.php");
    exit();
}
// only managers allowed
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager'){
    header("Location: ../views/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h1>Welcome <?php echo $_SESSION['username']; ?></h1>
    <a href="#" id="logoutLink">Logout</a>
    
<script src="../../assets/js/logout.js"></script>
</body>
</html>