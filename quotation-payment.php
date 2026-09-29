<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation & Payment</title>
    <link rel="stylesheet" href="quotation-payment.css">
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span>Payments</span>
        </div>
        <nav class="navigation">
            <a href="dashboard.php">DASHBOARD</a>
            <a href="services.php">SERVICES</a>
            <a href="new-request.php">NEW REQUEST</a>
            <a href="quotation-payment.php" class="active">PAYMENTS</a>
            <a href="logout.php">LOGOUT</a>
        </nav>
    </header>

    <main class="payments-container">
        <h1>Quotations & Invoices</h1>
        <p>Review active quotes and process downpayments or final balances.</p>
    </main>

</body>
</html>