<?php
require_once '../includes/session.php';
require_once '../includes/auth_check.php';
redirectIfLoggedIn();

require_once '../config/database.php';

$username = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            // ❗ no hashing (as you requested, but not secure for production)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];

                header('Location: ../index.php');
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } catch(PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="card">
        <h1 class="form-title">Login</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['message']) && $_GET['message'] == 'registered'): ?>
            <div class="alert alert-success">Registration successful! Please login.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?php echo htmlspecialchars($username); ?>" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
  <!-- Forgot password link -->
            <div class="form-group" style="margin-bottom: 0.5rem;">
                <a href="../auth/forgot_password_request.php">Forgot your password?</a>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
            <p style="margin-top: 1rem;">Don't have an account? <a href="register.php">Register here</a></p>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
