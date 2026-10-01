<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Fetch overall summary stats
$total_revenue = $db->query("SELECT COALESCE(SUM(Amount_Paid), 0) AS revenue FROM payment")->fetch()['revenue'];
$total_transactions = $db->query("SELECT COUNT(*) AS total FROM payment")->fetch()['total'];

// Fetch detailed payment history
$payments = $db->query("
    SELECT 
        p.Payment_ID,
        p.Request_ID,
        p.Amount_Paid,
        p.Payment_Date,
        u.Email AS client_email,
        et.Name AS event_type,
        pkg.Name AS package_name,
        pkg.Price AS package_price
    FROM payment p
    INNER JOIN service_request sr ON p.Request_ID = sr.Request_ID
    LEFT JOIN user u ON sr.Client_ID = u.User_ID
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
    ORDER BY p.Payment_Date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Records - Admin Portal</title>
    <link rel="stylesheet" href="photographer-payments.css">
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Photographer Portal</span>
        </div>

        <nav class="navigation">
            <a href="photographer-request.php">BOOKING REQUESTS</a>
            <a href="photographer-messages.php">MESSAGES</a>
            <a href="photographer-payments.php" class="active">PAYMENTS</a>
            <a href="photographer-schedule.php">SCHEDULE</a>
        </nav>

        <div class="nav-user-actions">
            <div class="user-avatar">A</div>
            <a href="logout.php" class="logout-link">LOGOUT</a>
        </div>
    </header>

    <main class="page-container">
        <h2>Payment Records & Revenue</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">Total Revenue Collected</span>
                <span class="stat-value">₱<?= number_format($total_revenue, 2) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Total Transactions</span>
                <span class="stat-value"><?= number_format($total_transactions) ?></span>
            </div>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Transaction #</th>
                        <th>Req #</th>
                        <th>Client Email</th>
                        <th>Event & Package</th>
                        <th>Amount Paid</th>
                        <th>Payment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><strong>#PAY-<?= str_pad($p['Payment_ID'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>#REQ-<?= str_pad($p['Request_ID'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($p['client_email'] ?? 'Client') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($p['event_type'] ?? 'N/A') ?></strong><br>
                                    <small style="color: #6b7280;"><?= htmlspecialchars($p['package_name'] ?? '') ?> (₱<?= number_format($p['package_price'] ?? 0, 2) ?>)</small>
                                </td>
                                <td><strong style="color: #10b981;">₱<?= number_format($p['Amount_Paid'], 2) ?></strong></td>
                                <td>
                                    <?= date('M d, Y', strtotime($p['Payment_Date'])) ?><br>
                                    <small style="color: #6b7280;"><?= date('h:i A', strtotime($p['Payment_Date'])) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #6b7280;">No payment transactions recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>