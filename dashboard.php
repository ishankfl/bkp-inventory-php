<?php
require_once 'config/database.php';

// Get inventory statistics
try {
    // Product statistics
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalQuantity = $pdo->query("SELECT SUM(quantity) FROM products")->fetchColumn();
    $outOfStock = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity = 0")->fetchColumn();
    $totalValue = $pdo->query("SELECT SUM(price * quantity) FROM products")->fetchColumn();
    $totalValue = $totalValue ? number_format($totalValue, 2) : '0.00';
    
    // Sales statistics
    $totalSales = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
    $totalItemsSold = $pdo->query("SELECT SUM(quantity) FROM sales")->fetchColumn();
    $totalRevenue = $pdo->query("SELECT SUM(total_amount) FROM sales")->fetchColumn();
    $totalRevenue = $totalRevenue ? number_format($totalRevenue, 2) : '0.00';
    
    // Get recent sales
    $recentSales = $pdo->query("
        SELECT s.*, p.name as product_name 
        FROM sales s 
        JOIN products p ON s.product_id = p.id 
        ORDER BY s.sale_date DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get low stock products
    $lowStockProducts = $pdo->query("
        SELECT * FROM products 
        WHERE quantity > 0 AND quantity <= 10 
        ORDER BY quantity ASC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top selling products
    $topSellingProducts = $pdo->query("
        SELECT p.*, SUM(s.quantity) as total_sold 
        FROM products p 
        LEFT JOIN sales s ON p.id = s.product_id 
        GROUP BY p.id 
        ORDER BY total_sold DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Handle error
    $totalProducts = 0;
    $totalQuantity = 0;
    $outOfStock = 0;
    $totalValue = '0.00';
    $totalSales = 0;
    $totalItemsSold = 0;
    $totalRevenue = '0.00';
    $recentSales = [];
    $lowStockProducts = [];
    $topSellingProducts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard - Public View</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Add to existing CSS */

/* Section styling */
.section {
    margin: 3rem 0;
}

.section-title {
    color: var(--primary);
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--light-gray);
}

/* Cards grid */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin: 1.5rem 0;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: bold;
}

.badge-warning {
    background-color: var(--warning);
    color: white;
}

.badge-success {
    background-color: var(--success);
    color: white;
}

.badge-danger {
    background-color: var(--danger);
    color: white;
}

/* Text utilities */
.text-center {
    text-align: center;
}

/* Small text for subtitles */
small {
    font-size: 0.7em;
    color: var(--gray);
    font-weight: normal;
}

/* Public view specific styles */
.public-view .action-buttons {
    display: none;
}

/* Responsive adjustments for public view */
@media screen and (max-width: 768px) {
    .cards-grid {
        grid-template-columns: 1fr;
    }
}
    </style>
</head>
<body>
    <!-- <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="dashboard.php"><i class="fas fa-boxes"></i> Inventory Dashboard</a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="#inventory" class="nav-link">Inventory</a>
                </li>
                <li class="nav-item">
                    <a href="#sales" class="nav-link">Sales</a>
                </li>
                <li class="nav-item">
                    <a href="auth/login.php" class="nav-link">Admin Login</a>
                </li>
            </ul>
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav> -->
    <?php
require_once 'includes/navbar.php';
// require_once 'auth_check.php';
?>

    <div class="container">
        <h1 class="form-title">Inventory Dashboard <small>Public View</small></h1>
        
        <!-- Summary Cards -->
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <i class="fas fa-box"></i>
                <h3>Total Products</h3>
                <p><?php echo $totalProducts; ?></p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-cubes"></i>
                <h3>Total Quantity</h3>
                <p><?php echo $totalQuantity; ?></p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Out of Stock</h3>
                <p><?php echo $outOfStock; ?></p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-dollar-sign"></i>
                <h3>Total Value</h3>
                <p>$<?php echo $totalValue; ?></p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-receipt"></i>
                <h3>Total Sales</h3>
                <p><?php echo $totalSales; ?></p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-shopping-cart"></i>
                <h3>Items Sold</h3>
                <p><?php echo $totalItemsSold; ?></p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-money-bill-wave"></i>
                <h3>Total Revenue</h3>
                <p>$<?php echo $totalRevenue; ?></p>
            </div>
        </div>
        
        <!-- Inventory Section -->
        <section id="inventory" class="section">
            <h2 class="section-title"><i class="fas fa-warehouse"></i> Inventory Overview</h2>
            
            <div class="cards-grid">
                <!-- Low Stock Products -->
                <div class="card">
                    <h3><i class="fas fa-exclamation-circle"></i> Low Stock Alert</h3>
                    <div class="table-container">
                        <?php if (count($lowStockProducts) > 0): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Current Stock</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockProducts as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><span class="badge badge-warning"><?php echo $product['quantity']; ?></span></td>
                                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center">No low stock products</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Top Selling Products -->
                <div class="card">
                    <h3><i class="fas fa-star"></i> Top Selling Products</h3>
                    <div class="table-container">
                        <?php if (count($topSellingProducts) > 0): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Sold</th>
                                        <th>In Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topSellingProducts as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo $product['total_sold'] ?? 0; ?></td>
                                            <td><?php echo $product['quantity']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center">No sales data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Sales Section -->
        <section id="sales" class="section">
            <h2 class="section-title"><i class="fas fa-chart-line"></i> Recent Sales</h2>
            
            <div class="card">
                <div class="table-container">
                    <?php if (count($recentSales) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                    <th>Customer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSales as $sale): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($sale['sale_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                        <td><?php echo $sale['quantity']; ?></td>
                                        <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($sale['customer_name'] ?: 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center">No sales records found</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <!-- Full Inventory Link -->
        <div class="text-center" style="margin: 2rem 0;">
            <a href="products/view_products.php?public=1" class="btn btn-primary">
                <i class="fas fa-list"></i> View Full Inventory
            </a>
            <a href="sales/sales_history.php?public=1" class="btn btn-primary">
                <i class="fas fa-history"></i> View Sales History
            </a>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Inventory Management System. Public Dashboard.</p>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>