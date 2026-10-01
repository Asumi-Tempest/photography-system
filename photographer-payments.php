<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Detect payment amount column dynamically
$paymentColumn = 'Amount';
try {
    $checkCol = $db->query("SHOW COLUMNS FROM payment LIKE 'Amount_Paid'")->fetch();
    if ($checkCol) {
        $paymentColumn = 'Amount_Paid';
    }
} catch (Exception $e) {
    $paymentColumn = 'Amount';
}

// Fetch all payments with request and client information
$query = "
    SELECT 
        p.Payment_ID,
        p.Request_ID,
        p.{$paymentColumn} AS payment_amount,
        p.Payment_Date,
        pm.Method_Name,
        u.Email AS client_email,
        et.Name AS event_type,
        pkg.Price AS total_package_price
    FROM payment p
    LEFT JOIN service_request sr ON p.Request_ID = sr.Request_ID
    LEFT JOIN user u ON sr.Client_ID = u.User_ID
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
    LEFT JOIN payment_method pm ON p.Payment_Method_ID = pm.Payment_Method_ID
    ORDER BY p.Payment_Date DESC
";

$payments = $db->query($query)->fetchAll();

// Calculate total revenue collected
$totalRevenue = 0;
foreach ($payments as $pay) {
    $totalRevenue += floatval($pay['payment_amount']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Records - Photographer Portal</title>
    <link rel="stylesheet" href="photographer-schedule.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-title {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin-top: 8px;
        }
    </style>
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
        <h2>Payment Records & Revenue Summary</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Revenue Collected</div>
                <div class="stat-value" style="color: #16a34a;">₱<?= number_format($totalRevenue, 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Transactions</div>
                <div class="stat-value"><?= count($payments) ?></div>
            </div>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Payment #</th>
                        <th>Req #</th>
                        <th>Client</th>
                        <th>Event</th>
                        <th>Method</th>
                        <th>Amount Paid</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><strong>#PAY-<?= str_pad($p['Payment_ID'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>#REQ-<?= str_pad($p['Request_ID'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($p['client_email'] ?? 'Client') ?></td>
                                <td><?= htmlspecialchars($p['event_type'] ?? 'N/A') ?></td>
                                <td><span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;"><?= htmlspecialchars($p['Method_Name'] ?? 'Cash/Online') ?></span></td>
                                <td><strong style="color: #16a34a;">₱<?= number_format($p['payment_amount'], 2) ?></strong></td>
                                <td><?= date('M d, Y - h:i A', strtotime($p['Payment_Date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #6b7280;">No payment transactions recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>