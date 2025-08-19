<?php
require_once 'includes/session.php';
require_once 'includes/auth_check.php';
requireAuth();

require_once 'config/database.php';

// Get product statistics
try {
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalQuantity = $pdo->query("SELECT SUM(quantity) FROM products")->fetchColumn();
    $outOfStock = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity = 0")->fetchColumn();
    $totalValue = $pdo->query("SELECT SUM(price * quantity) FROM products")->fetchColumn();
    $totalValue = $totalValue ? number_format($totalValue, 2) : '0.00';
} catch(PDOException $e) {
    // Handle error
    $totalProducts = 0;
    $totalQuantity = 0;
    $outOfStock = 0;
    $totalValue = '0.00';
}
?>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <h1 class="form-title">Dashboard</h1>
    
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
    </div>
</div>

<?php include 'includes/footer.php'; ?>