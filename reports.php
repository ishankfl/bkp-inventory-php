<?php
require_once 'includes/session.php';
require_once 'includes/auth_check.php';
requireAuth();

require_once 'config/database.php';

// Get report data
try {
    // Total products by category
    $categoryStmt = $pdo->query("
        SELECT category, COUNT(*) as count 
        FROM products 
        WHERE category IS NOT NULL AND category != '' 
        GROUP BY category 
        ORDER BY count DESC
    ");
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Stock status
    $stockStmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(CASE WHEN quantity > 0 AND quantity <= 10 THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN quantity > 10 THEN 1 ELSE 0 END) as in_stock
        FROM products
    ");
    $stockData = $stockStmt->fetch(PDO::FETCH_ASSOC);
    
    // Top products by quantity
    $topProductsStmt = $pdo->query("
        SELECT name, quantity 
        FROM products 
        ORDER BY quantity DESC 
        LIMIT 5
    ");
    $topProducts = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly value trend (example data - in real app you'd use actual date-based data)
    $monthlyValue = [
        ['month' => 'Jan', 'value' => 12500],
        ['month' => 'Feb', 'value' => 13200],
        ['month' => 'Mar', 'value' => 14500],
        ['month' => 'Apr', 'value' => 13800],
        ['month' => 'May', 'value' => 15600],
        ['month' => 'Jun', 'value' => 16800],
    ];
    
    // Total inventory value
    $valueStmt = $pdo->query("SELECT SUM(price * quantity) as total_value FROM products");
    $totalValue = $valueStmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;
    
} catch(PDOException $e) {
    $error = 'Error generating reports: ' . $e->getMessage();
}
?>

<?php include 'includes/navbar.php'; ?>
<!-- <?php include '/bkp-inventory/css/style.css'; ?> -->

<div class="container">
    <h1 class="form-title">Inventory Reports & Analytics</h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Summary Cards -->
    <div class="dashboard-cards">
        <div class="dashboard-card">
            <i class="fas fa-chart-line"></i>
            <h3>Total Value</h3>
            <p>$<?php echo number_format($totalValue, 2); ?></p>
        </div>
        
        <div class="dashboard-card">
            <i class="fas fa-boxes"></i>
            <h3>Total Products</h3>
            <p><?php echo array_sum(array_column($categories, 'count')); ?></p>
        </div>
        
        <div class="dashboard-card">
            <i class="fas fa-check-circle"></i>
            <h3>In Stock</h3>
            <p><?php echo $stockData['in_stock'] ?? 0; ?></p>
        </div>
        
        <div class="dashboard-card">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Low Stock</h3>
            <p><?php echo $stockData['low_stock'] ?? 0; ?></p>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="charts-grid">
        <!-- Stock Status Chart -->
        <div class="chart-card">
            <h3><i class="fas fa-chart-pie"></i> Stock Status</h3>
            <div class="chart-container">
                <canvas id="stockChart" width="400" height="250"></canvas>
            </div>
        </div>
        
        <!-- Category Distribution -->
        <div class="chart-card">
            <h3><i class="fas fa-chart-bar"></i> Products by Category</h3>
            <div class="chart-container">
                <canvas id="categoryChart" width="400" height="250"></canvas>
            </div>
        </div>
        
        <!-- Top Products -->
        <div class="chart-card">
            <h3><i class="fas fa-star"></i> Top Products by Quantity</h3>
            <div class="chart-container">
                <canvas id="topProductsChart" width="400" height="250"></canvas>
            </div>
        </div>
        
        <!-- Monthly Value Trend -->
        <div class="chart-card full-width">
            <h3><i class="fas fa-chart-line"></i> Inventory Value Trend</h3>
            <div class="chart-container">
                <canvas id="valueTrendChart" width="600" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Data Tables -->
    <div class="data-tables">
        <div class="card">
            <h3>Category Summary</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Product Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalProducts = array_sum(array_column($categories, 'count'));
                        foreach ($categories as $category): 
                            $percentage = $totalProducts > 0 ? ($category['count'] / $totalProducts) * 100 : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['category'] ?: 'Uncategorized'); ?></td>
                            <td><?php echo $category['count']; ?></td>
                            <td><?php echo number_format($percentage, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <h3>Stock Status Details</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalItems = array_sum($stockData);
                        $statuses = [
                            ['label' => 'Out of Stock', 'count' => $stockData['out_of_stock'], 'color' => '#f72585'],
                            ['label' => 'Low Stock', 'count' => $stockData['low_stock'], 'color' => '#fca311'],
                            ['label' => 'In Stock', 'count' => $stockData['in_stock'], 'color' => '#4cc9f0']
                        ];
                        
                        foreach ($statuses as $status): 
                            $percentage = $totalItems > 0 ? ($status['count'] / $totalItems) * 100 : 0;
                        ?>
                        <tr>
                            <td><span class="status-indicator" style="background-color: <?php echo $status['color']; ?>"></span> <?php echo $status['label']; ?></td>
                            <td><?php echo $status['count']; ?></td>
                            <td><?php echo number_format($percentage, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Stock Status Chart (Doughnut)
    const stockCtx = document.getElementById('stockChart').getContext('2d');
    const stockChart = new Chart(stockCtx, {
        type: 'doughnut',
        data: {
            labels: ['Out of Stock', 'Low Stock', 'In Stock'],
            datasets: [{
                data: [
                    <?php echo $stockData['out_of_stock'] ?? 0; ?>,
                    <?php echo $stockData['low_stock'] ?? 0; ?>,
                    <?php echo $stockData['in_stock'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#f72585', // Red for out of stock
                    '#fca311', // Orange for low stock
                    '#4cc9f0'  // Blue for in stock
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
    
    // Category Distribution Chart (Bar)
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($cat) { 
                return "'" . addslashes($cat['category'] ?: 'Uncategorized') . "'"; 
            }, $categories)); ?>],
            datasets: [{
                label: 'Products',
                data: [<?php echo implode(',', array_column($categories, 'count')); ?>],
                backgroundColor: '#4361ee',
                borderColor: '#3a0ca3',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Top Products Chart (Horizontal Bar)
    const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
    const topProductsChart = new Chart(topProductsCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($product) { 
                return "'" . addslashes(substr($product['name'], 0, 15)) . "'"; 
            }, $topProducts)); ?>],
            datasets: [{
                label: 'Quantity',
                data: [<?php echo implode(',', array_column($topProducts, 'quantity')); ?>],
                backgroundColor: '#4cc9f0',
                borderColor: '#4895ef',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true
        }
    });
    
    // Value Trend Chart (Line)
    const valueTrendCtx = document.getElementById('valueTrendChart').getContext('2d');
    const valueTrendChart = new Chart(valueTrendCtx, {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', array_map(function($month) { 
                return "'" . $month['month'] . "'"; 
            }, $monthlyValue)); ?>],
            datasets: [{
                label: 'Inventory Value ($)',
                data: [<?php echo implode(',', array_column($monthlyValue, 'value')); ?>],
                backgroundColor: 'rgba(67, 97, 238, 0.2)',
                borderColor: '#4361ee',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>