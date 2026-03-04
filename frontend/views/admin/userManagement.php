
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
    <title>User Management</title>
    <link rel="stylesheet" href="../../assets/css/userManagement.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>QuickSale</h2>
            <nav>
                <a href="userManagement.php"><i class="fas fa-users"></i> User Management</a>
                <a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a>
                <a href="handleTransactions.php"><i class="fas fa-cash-register"></i> Handle Transactions</a>
                <a href="monitorTransactions.php"><i class="fas fa-eye"></i> Monitor Transactions</a>
                <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
            </nav>
            <a href="#" id="logoutLink" class="logout-link"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
        </aside>
        <main class="main-content">
            <div class="user-management-header">
                <h1>User Management</h1>
                <button class="add-user-btn"><i class="fas fa-plus"></i> Add User</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Creation Date</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td><!-- Sample Data Pa Modify nalang po for Database -->
                        <td>RPDgamer101</td>
                        <td class="password-cell">
                            <span>********</span>
                            <i class="fas fa-eye-slash reveal-pass-icon"></i>
                        </td>
                        <td>Leon kennedy</td>
                        <td>Admin</td>
                        <td>Active</td>
                        <td>2023-01-15</td>
                        <td>2024-07-28</td>
                        <td class="action-buttons">
                            <button class="edit-btn"><i class="fas fa-edit"></i></button>
                            <button class="archive-btn"><i class="fas fa-archive"></i></button>
                        </td>
                    </tr>
                     <tr>
                        <td>2</td>
                        <td>WhereiSlEon55</td>
                        <td class="password-cell">
                            <span>********</span>
                            <i class="fas fa-eye-slash reveal-pass-icon"></i>
                        </td>
                        <td>Chris Redfield</td>
                        <td>Manager</td>
                        <td>Active</td>
                        <td>2023-02-20</td>
                        <td>2024-07-27</td>
                        <td class="action-buttons">
                            <button class="edit-btn"><i class="fas fa-edit"></i></button>
                            <button class="archive-btn"><i class="fas fa-archive"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </main>
    </div>
    <script src="../../assets/js/logout.js"></script>
</body>
</html>
