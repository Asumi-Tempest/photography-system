<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'Photographer') {
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
    <title>Register - Client Portal</title>
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

    <!-- Centered Wrapper -->
    <main class="login-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Register to book photography services</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="process-register.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="full_name">FULL NAME</label>
                    <input type="text" name="full_name" id="full_name" placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label for="email">EMAIL ADDRESS</label>
                    <input type="email" name="email" id="email" placeholder="name@example.com" required>
                </div>

                <div class="form-group">
                    <label for="phone">PHONE NUMBER</label>
                    <input type="tel" name="phone" id="phone" placeholder="09123456789" required>
                </div>

                <div class="form-group">
                    <label for="password">PASSWORD</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" required minlength="6">
                </div>

                <button type="submit" class="btn-submit">CREATE ACCOUNT</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Log In</a></p>
            </div>
        </div>
    </main>

</body>
</html>