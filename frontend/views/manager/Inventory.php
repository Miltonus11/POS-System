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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>QuickSale</h2>
            <nav>
                <a href="inventory.php" class="active"><i class="fas fa-box"></i> Inventory</a>
                <a href="handleTransactions.php"><i class="fas fa-cash-register"></i> Handle Transactions</a>
                <a href="monitorTransactions.php"><i class="fas fa-eye"></i> Monitor Transactions</a>
            </nav>
            <a href="#" id="logoutLink" class="logout-link"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
        </aside>
        <main class="main-content">
            <!--products view -->
            <div id="products-view">
                <div class="inventory-header">
                    <h1>Inventory Management</h1>
                    <div class="header-actions">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" placeholder="Search products..." class="search-input">
                        </div>
                        <button id="add-product-btn" class="add-product-btn"><i class="fas fa-plus"></i> Add Product</button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock Quantity</th>
                                <th>Min Stock Level</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <!--stock history view(initially hidden) -->
            <div id="stock-history-view" style="display: none;">
                <div class="inventory-header">
                    <h1>Stock History</h1>
                    <button id="back-to-products-btn" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Products</button>
                </div>
                <div class="stock-history-controls">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" placeholder="Search history..." class="search-input">
                    </div>
                    <div class="filters">
                        <button class="filter-btn active">Day</button>
                        <button class="filter-btn">Week</button>
                        <button class="filter-btn">Month</button>
                        <button class="filter-btn">Year</button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="stock-history-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product ID</th>
                                <th>Movement Type</th>
                                <th>Quantity</th>
                                <th>Reference Type</th>
                                <th>Reference ID</th>
                                <th>Notes</th>
                                <th>User ID</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="password-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Enter Password</h2>
            <p>This action requires password authentication.</p>
            <input type="password" id="password-input" placeholder="Password">
            <button id="password-submit-btn">Submit</button>
        </div>
    </div>
    
    <div id="product-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="product-modal-title">Add Product</h2>
            <form id="product-form">
                 <div class="form-grid">
                    <input type="hidden" id="product-id" name="id">
                    <input type="text" id="product-barcode" name="barcode" placeholder="Barcode" class="form-field">
                    <input type="text" id="product-name" name="name" placeholder="Product Name" required class="form-field">
                    <input type="text" id="product-category" name="category_id" placeholder="Category" class="form-field">
                    <input type="number" id="product-price" name="price" placeholder="Price" step="0.01" required class="form-field">
                    <input type="number" id="product-cost" name="cost" placeholder="Cost" step="0.01" class="form-field">
                    <input type="number" id="product-stock" name="stock_quantity" placeholder="Stock Quantity" required class="form-field">
                    <input type="number" id="product-min-stock" name="min_stock_level" placeholder="Min Stock Level" class="form-field">
                    <input type="text" id="product-unit" name="unit" placeholder="Unit (e.g., pcs, kg)" class="form-field">
                    <select id="product-status" name="status" class="form-field">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <textarea id="product-description" name="description" placeholder="Product Description" rows="3" class="form-field"></textarea>
                <button type="submit" class="save-btn">Save Product</button>
            </form>
        </div>
    </div>

    <div id="barcode-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Product Barcode</h2>
            <p id="barcode-value" class="barcode-display">123456789012</p>
        </div>
    </div>
    
    <script src="../../assets/js/logout.js"></script>
    <script src="../../assets/js/inventory.js"></script>
</body>
</html>
