<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services & Packages</title>
    <link rel="stylesheet" href="services.css">
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span>Services</span>
        </div>
        <nav class="navigation">
            <a href="dashboard.php">DASHBOARD</a>
            <a href="services.php" class="active">SERVICES</a>
            <a href="new-request.php">NEW REQUEST</a>
            <a href="quotation-payment.php">PAYMENTS</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php">LOGOUT</a>
            <?php else: ?>
                <a href="login.php">LOGIN</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="services-container">
        <h1>Our Photography Packages</h1>
        <p>Explore our shooting packages and special rates.</p>
    </main>

</body>
</html>