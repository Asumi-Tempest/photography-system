<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'Photographer') {
        header('Location: admin-dashboard.php');
    } else {
        header('Location: client-request.php');
    }
    exit;
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Client Portal</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>

    <!-- Persistent Top Navigation Bar -->
    <header class="top-nav">
        <div class="nav-left">
            <div class="nav-logo">LOGO</div>
            <span class="portal-title">Client Portal</span>
        </div>
        <nav class="nav-links">
            <a href="#">HOME</a>
            <a href="#">SERVICES</a>
            <a href="#">ABOUT</a>
            <a href="#">CONTACT</a>
        </nav>
    </header>

    <!-- Main Content Container -->
    <main class="login-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome Back!</h1>
                <p>Sign in to continue to photography portal</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="process-login.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" placeholder="yourname@email.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-submit">LOGIN</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </main>

</body>
</html>