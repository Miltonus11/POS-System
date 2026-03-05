<?php
session_start();

if(!isset($_SESSION['logged_in'])){
    header("Location: ../../../index.php");
    exit();
}
// only manager may access
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager'){
    header("Location: ../../../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/manager-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>QuickSale</h2>
            <nav>
                <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
                <a href="handleTransactions.php"><i class="fas fa-cash-register"></i> Handle Transactions</a>
                <a href="monitorTransactions.php"><i class="fas fa-eye"></i> Monitor Transactions</a>
            </nav>
            <a href="#" id="logoutLink" class="logout-link"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
        </aside>
        <main class="main-content">
            <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
        </main>
    </div>
    <script src="../../assets/js/logout.js"></script>
</body>
</html>