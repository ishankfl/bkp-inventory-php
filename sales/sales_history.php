<?php
require_once '../includes/session.php';
require_once '../includes/auth_check.php';
requireAuth();

require_once '../config/database.php';

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(p.name LIKE ? OR customer_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($date_from)) {
    $whereConditions[] = "s.sale_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $whereConditions[] = "s.sale_date <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM sales s JOIN products p ON s.product_id = p.id $whereClause");
$countStmt->execute($params);
$totalSales = $countStmt->fetchColumn();
$totalPages = ceil($totalSales / $perPage);

// Get sales data
$salesStmt = $pdo->prepare("
    SELECT s.*, p.name as product_name, p.price as original_price 
    FROM sales s 
    JOIN products p ON s.product_id = p.id 
    $whereClause 
    ORDER BY s.sale_date DESC 
    LIMIT $perPage OFFSET $offset
");
$salesStmt->execute($params);
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get sales summary
$summaryStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_sales,
        SUM(s.quantity) as total_items,
        SUM(s.total_amount) as total_revenue
    FROM sales s 
    JOIN products p ON s.product_id = p.id 
    $whereClause
");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
?>

<?php include '../includes/navbar.php'; ?>

<div class="container">
    <h1 class="form-title"><i class="fas fa-history"></i> Sales History</h1>
    
    <!-- Summary Cards -->
    <div class="dashboard-cards">
        <div class="dashboard-card">
            <i class="fas fa-receipt"></i>
            <h3>Total Sales</h3>
            <p><?php echo $summary['total_sales'] ?? 0; ?></p>
        </div>
        
        <div class="dashboard-card">
            <i class="fas fa-cubes"></i>
            <h3>Items Sold</h3>
            <p><?php echo $summary['total_items'] ?? 0; ?></p>
        </div>
        
        <div class="dashboard-card">
            <i class="fas fa-dollar-sign"></i>
            <h3>Total Revenue</h3>
            <p>$<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></p>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card">
        <h3>Filters</h3>
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                    placeholder="Product or customer name">
            </div>
            
            <div class="filter-group">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="filter-group">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="filter-group filter-btn">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="sales_history.php" class="btn">Clear</a>
            </div>
        </form>
    </div>
    
    <!-- Sales Table -->
    <div class="card">
        <div class="table-container">
            <?php if (count($sales) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Customer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><?php echo date('M j, Y h:i A', strtotime($sale['sale_date'])); ?></td>
                                <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                <td><?php echo $sale['quantity']; ?></td>
                                <td>$<?php echo number_format($sale['sale_price'], 2); ?></td>
                                <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($sale['customer_name'] ?: 'N/A'); ?></td>
                                <td>
                                    <a href="view_sale.php?id=<?php echo $sale['id']; ?>" class="btn btn-success">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn">Previous</a>
                        <?php endif; ?>
                        
                        <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p>No sales records found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>