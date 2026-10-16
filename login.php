<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'Photographer')) {
        header('Location: photographer-dashboard.php');
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

    <!-- Top Navigation Bar (Unused links removed) -->
    <header class="top-nav">
        <div class="nav-left">
            <img src="logo.svg" alt="Logo" width="34" height="34" style="display:block;width:34px;height:34px;">
            <span class="portal-title">Client Portal</span>
        </div>
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