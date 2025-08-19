<?php
include '../includes/navbar.php';
require_once '../config/database.php';

// Handle delete action
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        header('Location: view_products.php?message=deleted');
        exit();
    } catch(PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Fetch all products
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $products = [];
    $error = 'Error: ' . $e->getMessage();
}

// Check for success message
$message = '';
if (isset($_GET['message']) && $_GET['message'] == 'deleted') {
    $message = 'Product deleted successfully.';
} elseif (isset($_GET['message']) && $_GET['message'] == 'updated') {
    $message = 'Product updated successfully.';
}
?>

<div class="container">
    <h1 class="form-title">Product Inventory</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="table-container">
            <?php if (count($products) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Category</th>
                            <th>SKU</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td class="action-buttons">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-success">Edit</a>
                                    <a href="view_products.php?delete_id=<?php echo $product['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No products found. <a href="add_product.php">Add your first product</a>.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>