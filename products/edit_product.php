<?php
include '../includes/navbar.php';
require_once '../config/database.php';

// Check if ID parameter is provided
if (!isset($_GET['id'])) {
    header('Location: view_products.php');
    exit();
}

$id = $_GET['id'];
$error = '';
$success = '';

// Fetch product data
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: view_products.php');
        exit();
    }
} catch(PDOException $e) {
    $error = 'Error: ' . $e->getMessage();
}

// Initialize variables with product data
$name = $product['name'];
$description = $product['description'];
$price = $product['price'];
$quantity = $product['quantity'];
$category = $product['category'];
$sku = $product['sku'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $quantity = trim($_POST['quantity']);
    $category = trim($_POST['category']);
    $sku = trim($_POST['sku']);
    
    // Validate inputs
    if (empty($name) || empty($price) || empty($quantity) || empty($sku)) {
        $error = 'Please fill in all required fields.';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = 'Please enter a valid price.';
    } elseif (!is_numeric($quantity) || $quantity < 0) {
        $error = 'Please enter a valid quantity.';
    } else {
        try {
            // Check if SKU already exists for another product
            $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
            $stmt->execute([$sku, $id]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'SKU already exists. Please use a unique SKU.';
            } else {
                // Update product
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, category = ?, sku = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $quantity, $category, $sku, $id]);
                
                $success = 'Product updated successfully!';
                header('Location: view_products.php?message=updated');
                exit();
            }
        } catch(PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <div class="card">
        <h1 class="form-title">Edit Product</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="name" class="form-label">Product Name *</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="price" class="form-label">Price *</label>
                <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="quantity" class="form-label">Quantity *</label>
                <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category" class="form-label">Category</label>
                <input type="text" class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($category); ?>">
            </div>
            
            <div class="form-group">
                <label for="sku" class="form-label">SKU (Stock Keeping Unit) *</label>
                <input type="text" class="form-control" id="sku" name="sku" value="<?php echo htmlspecialchars($sku); ?>" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Product</button>
            <a href="view_products.php" class="btn">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>