<?php
// Is user logged in?
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Require login (for dashboard/products/etc.)
function requireAuth() {
    if (!isLoggedIn()) {

        header("Location: /bkp-inventory/auth/login.php");
        exit();
    }
}

// Redirect logged-in users (for login/register)
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: /bkp-inventory/index.php");
        exit();
    }
}
?>
