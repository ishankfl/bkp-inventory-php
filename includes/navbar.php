<?php
require_once 'session.php';
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <link rel="stylesheet" href="/bkp-inventory/css/style.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <a href="/bkp-inventory/index.php"><i class="fas fa-boxes"></i> Inventory System</a>
        </div>
        <ul class="nav-menu">
            <?php if (isLoggedIn()): ?>
                <li class="nav-item"><a href="/bkp-inventory/index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="/bkp-inventory/dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="/bkp-inventory/products/add_product.php" class="nav-link">Add Product</a></li>
                <li class="nav-item"><a href="/bkp-inventory/products/view_products.php" class="nav-link">View Products</a></li>
                <li class="nav-item"><a href="/bkp-inventory/reports.php" class="nav-link">Reports</a></li>
                <li class="nav-item">
    <a href="/bkp-inventory/sales/make_sale.php" class="nav-link">Sales</a>
</li>
                <li class="nav-item">
                    <a href="/bkp-inventory/auth/logout.php" class="nav-link">
                        Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)
                    </a>
                </li>

            <?php else: ?>
                  <li class="nav-item"><a href="/bkp-inventory/dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="/bkp-inventory/auth/login.php" class="nav-link">Login</a></li>
                <li class="nav-item"><a href="/bkp-inventory/auth/register.php" class="nav-link">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<script>
// // Auto logout when user closes tab or browser
// window.addEventListener("beforeunload", function () {
//     // Send logout request silently
//     navigator.sendBeacon("/bkp-inventory/auth/logout.php");
// });
</script>
