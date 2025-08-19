<?php
require_once '../includes/session.php';
require_once '../includes/auth_check.php';
requireAuth();

require_once '../config/database.php';

$error = '';
$success = '';
$products = [];

// Fetch all products
try {
    $stmt = $pdo->query("SELECT id, name, price, quantity FROM products WHERE quantity > 0 ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Error loading products: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $customer_name = trim($_POST['customer_name']);
    $customer_contact = trim($_POST['customer_contact']);
    $notes = trim($_POST['notes']);
    
    // Validate inputs
    if (empty($product_id)) {
        $error = 'Please select a product.';
    } elseif (!is_numeric($quantity) || $quantity <= 0) {
        $error = 'Please enter a valid quantity.';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Get product details
            $stmt = $pdo->prepare("SELECT name, price, quantity FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception("Product not found.");
            }
            
            // Check if enough stock
            if ($product['quantity'] < $quantity) {
                throw new Exception("Insufficient stock. Only {$product['quantity']} available.");
            }
            
            $sale_price = $product['price'];
            $total_amount = $sale_price * $quantity;
            
            // Insert sale record
            $stmt = $pdo->prepare("INSERT INTO sales (product_id, quantity, sale_price, total_amount, customer_name, customer_contact, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$product_id, $quantity, $sale_price, $total_amount, $customer_name, $customer_contact, $notes]);
            
            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ?, sold_quantity = sold_quantity + ?, last_sale_date = NOW() WHERE id = ?");
            $stmt->execute([$quantity, $quantity, $product_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $success = "Sale recorded successfully! Total amount: $" . number_format($total_amount, 2);
            
            // Clear form
            $_POST = [];
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = 'Error processing sale: ' . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="card">
        <h1 class="form-title"><i class="fas fa-cash-register"></i> Make a Sale</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="product_id" class="form-label">Product *</label>
                <select class="form-control" id="product_id" name="product_id" required>
                    <option value="">Select a product</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" 
                            <?php echo (isset($_POST['product_id']) && $_POST['product_id'] == $product['id']) ? 'selected' : ''; ?>
                            data-price="<?php echo $product['price']; ?>"
                            data-stock="<?php echo $product['quantity']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?> - 
                            $<?php echo number_format($product['price'], 2); ?> 
                            (Stock: <?php echo $product['quantity']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantity" class="form-label">Quantity *</label>
                <input type="number" class="form-control" id="quantity" name="quantity" 
                    value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '1'; ?>" 
                    min="1" required>
                <small id="stockHelp" class="form-text">Available stock: <span id="stock-amount">0</span></small>
            </div>
            
           <div class="form-group">
    <label for="sale_price" class="form-label">Unit Price ($)</label>
    <input type="number" step="0.01" class="form-control" id="sale_price" name="sale_price"
        value="<?php echo isset($_POST['sale_price']) ? htmlspecialchars($_POST['sale_price']) : ''; ?>" required>
</div>
            
          <div class="form-group">
    <label for="total_amount" class="form-label">Total Amount ($)</label>
    <input type="text" class="form-control" id="total_amount" readonly>
</div>
            
            <div class="form-group">
                <label for="customer_name" class="form-label">Customer Name</label>
                <input type="text" class="form-control" id="customer_name" name="customer_name" 
                    value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="customer_contact" class="form-label">Customer Contact</label>
                <input type="text" class="form-control" id="customer_contact" name="customer_contact" 
                    value="<?php echo isset($_POST['customer_contact']) ? htmlspecialchars($_POST['customer_contact']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Complete Sale</button>
            <a href="sales_history.php" class="btn">View Sales History</a>
        </form>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const salePriceInput = document.getElementById('sale_price');
    const totalAmountInput = document.getElementById('total_amount');
    const stockAmountSpan = document.getElementById('stock-amount');

    function updateCalculations() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const stock = selectedOption ? parseInt(selectedOption.getAttribute('data-stock')) : 0;
        const quantity = parseInt(quantityInput.value) || 0;
        const price = parseFloat(salePriceInput.value) || 0;

        totalAmountInput.value = (price * quantity).toFixed(2);
        stockAmountSpan.textContent = stock;

        // Validate stock
        if (quantity > stock) {
            quantityInput.setCustomValidity(`Quantity cannot exceed available stock (${stock})`);
        } else {
            quantityInput.setCustomValidity('');
        }
    }

    // Set default price when product changes
    productSelect.addEventListener('change', function() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const price = selectedOption ? parseFloat(selectedOption.getAttribute('data-price')) : 0;
        salePriceInput.value = price.toFixed(2);
        updateCalculations();
    });

    // Recalculate on user input
    quantityInput.addEventListener('input', updateCalculations);
    salePriceInput.addEventListener('input', updateCalculations);

    // Initial calc
    updateCalculations();
});

</script>

<?php include '../includes/footer.php'; ?>