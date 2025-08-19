<?php
require_once '../includes/session.php';
require_once '../includes/auth_check.php';
requireAuth();

require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: sales_history.php');
    exit();
}

$sale_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT s.*, p.name as product_name, p.price as original_price 
        FROM sales s 
        JOIN products p ON s.product_id = p.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        header('Location: sales_history.php');
        exit();
    }
} catch(PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>

<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="card">
        <h1 class="form-title"><i class="fas fa-receipt"></i> Sale Details</h1>
        
        <div class="sale-details">
            <div class="detail-row">
                <div class="detail-label">Sale ID:</div>
                <div class="detail-value">#<?php echo $sale['id']; ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Date & Time:</div>
                <div class="detail-value"><?php echo date('F j, Y, g:i a', strtotime($sale['sale_date'])); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Product:</div>
                <div class="detail-value"><?php echo htmlspecialchars($sale['product_name']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Quantity:</div>
                <div class="detail-value"><?php echo $sale['quantity']; ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Unit Price:</div>
                <div class="detail-value">$<?php echo number_format($sale['sale_price'], 2); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Total Amount:</div>
                <div class="detail-value">$<?php echo number_format($sale['total_amount'], 2); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Customer Name:</div>
                <div class="detail-value"><?php echo htmlspecialchars($sale['customer_name'] ?: 'N/A'); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Customer Contact:</div>
                <div class="detail-value"><?php echo htmlspecialchars($sale['customer_contact'] ?: 'N/A'); ?></div>
            </div>
            
            <?php if (!empty($sale['notes'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Notes:</div>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($sale['notes'])); ?></div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="action-buttons" style="margin-top: 2rem;">
            <a href="sales_history.php" class="btn">Back to History</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Receipt</button>
        </div>
    </div>
</div>

<style>
.sale-details {
    margin: 1.5rem 0;
}

.detail-row {
    display: flex;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
}

.detail-label {
    font-weight: bold;
    width: 150px;
    color: var(--primary);
}

.detail-value {
    flex: 1;
}

@media print {
    .navbar, .footer, .action-buttons {
        display: none !important;
    }
    
    .container {
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>