<?php
require_once 'config.php';
requireStaff(); // staff-only page

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
        pkg.Price AS total_package_price,
        p.Payment_Status_ID,
        ps.Status_Name
    FROM payment p
    LEFT JOIN service_request sr ON p.Request_ID = sr.Request_ID
    LEFT JOIN user u ON sr.Client_ID = u.User_ID
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
    LEFT JOIN payment_method pm ON p.Payment_Method_ID = pm.Payment_Method_ID
    LEFT JOIN payment_status ps ON p.Payment_Status_ID = ps.Payment_Status_ID
    ORDER BY p.Payment_Date DESC
";

$payments = $db->query($query)->fetchAll();

// Revenue counts verified payments only (status 2); status 1 = waiting for verification
$totalRevenue = 0;
$pendingCount = 0;
foreach ($payments as $pay) {
    if ((int)$pay['Payment_Status_ID'] === 2) {
        $totalRevenue += floatval($pay['payment_amount']);
    } elseif ((int)$pay['Payment_Status_ID'] === 1) {
        $pendingCount++;
    }
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
        /* Modern Single-Row Flexbox Header Design */
        .top-nav { background: #1e1e2d; color: #fff; padding: 14px 40px; display: flex; align-items: center; justify-content: space-between; flex-wrap: nowrap; width: 100%; box-sizing: border-box; }
        .nav-left { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        .portal-title { font-weight: 700; font-size: 15px; color: #ffffff; letter-spacing: 0.2px; }
        
        .nav-links { display: flex; gap: 20px; align-items: center; flex-wrap: nowrap; }
        .nav-links a { font-size: 11px; font-weight: 700; color: #92929f; text-decoration: none; letter-spacing: 0.8px; text-transform: uppercase; transition: color 0.2s; position: relative; padding-bottom: 4px; white-space: nowrap; }
        .nav-links a:hover { color: #ffffff; }
        .nav-links a.active { color: #ffffff; border-bottom: 2px solid #ffffff; }

        .nav-right { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }
        .user-avatar { width: 32px; height: 32px; background: #2563eb; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .btn-logout { font-size: 13px; font-weight: 700; color: #f87171; text-decoration: none; }
        .btn-logout:hover { color: #ef4444; }

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
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
        .status-1 { background: #fef3c7; color: #92400e; }
        .status-2 { background: #dcfce7; color: #166534; }
        .status-3 { background: #fee2e2; color: #991b1b; }
        .action-form { display: inline; }
        .btn-act { border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; color: #fff; }
        .btn-verify { background: #10b981; }
        .btn-verify:hover { background: #059669; }
        .btn-reject { background: #ef4444; }
        .btn-reject:hover { background: #dc2626; }
        .btn-undo { background: #6b7280; }
        .btn-undo:hover { background: #4b5563; }
    </style>
</head>
<body>

<header class="top-nav">
    <div class="nav-left">
        <img src="logo.svg" alt="Logo" width="34" height="34" style="display:block;width:34px;height:34px;">
        <span class="portal-title">Photographer Portal</span>
    </div>
<nav class="nav-links">
    <a href="photographer-dashboard.php">DASHBOARD</a>
    <a href="photographer-request.php">BOOKING REQUESTS</a>
    <a href="photographer-deliverables.php">DELIVERABLES</a>
    <a href="photographer-payments.php">PAYMENTS</a>
    <a href="photographer-schedule.php">SCHEDULE</a>
    <a href="photographer-reviews.php">REVIEWS</a>
</nav>
    <div class="nav-right">
        <div class="user-avatar">A</div>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</header>

    <main class="page-container">
        <h2>Payment Records & Revenue Summary</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Verified Revenue</div>
                <div class="stat-value" style="color: #16a34a;">₱<?= number_format($totalRevenue, 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Waiting for Verification</div>
                <div class="stat-value" style="color: #d97706;"><?= $pendingCount ?></div>
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
                        <th>Status</th>
                        <th>Action</th>
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
                                <?php $st = (int)$p['Payment_Status_ID']; ?>
                                <td><span class="status-badge status-<?= $st ?>"><?= htmlspecialchars($p['Status_Name'] ?? 'Unverified') ?></span></td>
                                <td>
                                    <?php if ($st === 2 || $st === 3): ?>
                                        <form method="POST" action="process-payment.php" class="action-form">
                                            <input type="hidden" name="id" value="<?= (int)$p['Payment_ID'] ?>">
                                            <input type="hidden" name="action" value="reverify">
                                            <button type="submit" class="btn-act btn-undo">Undo</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="process-payment.php" class="action-form">
                                            <input type="hidden" name="id" value="<?= (int)$p['Payment_ID'] ?>">
                                            <input type="hidden" name="action" value="verify">
                                            <button type="submit" class="btn-act btn-verify">Verify</button>
                                        </form>
                                        <form method="POST" action="process-payment.php" class="action-form" onsubmit="return confirm('Reject this payment?');">
                                            <input type="hidden" name="id" value="<?= (int)$p['Payment_ID'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn-act btn-reject">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #6b7280;">No payment transactions recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>