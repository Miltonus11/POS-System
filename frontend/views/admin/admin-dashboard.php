<?php
session_start();

if(!isset($_SESSION['logged_in'])){
    header("Location: ../../../index.php");
    exit();
}
// only admin may access
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../../../index.php");
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
    
    <ul>
        <li><a href="userManagement.php">User Management</a></li>
        <li><a href="analytics.php">Analytics</a></li>
        <li><a href="handleTransactions.php">Handle Transactions</a></li>
        <li><a href="monitorTransactions.php">Monitor Transactions</a></li>
        <li><a href="inventory.php">Inventory</a></li>
    </ul>

<script src="../../assets/js/logout.js"></script>
</body>
</html>