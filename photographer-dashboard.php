<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Detect payment amount column
$paymentColumn = 'Amount';
try {
    $checkCol = $db->query("SHOW COLUMNS FROM payment LIKE 'Amount_Paid'")->fetch();
    if ($checkCol) {
        $paymentColumn = 'Amount_Paid';
    }
} catch (Exception $e) {
    $paymentColumn = 'Amount';
}

// 1. Total Revenue
$totalRevenue = $db->query("SELECT COALESCE(SUM({$paymentColumn}), 0) AS total FROM payment")->fetch()['total'];

// 2. Pending Requests Count
$pendingCount = $db->query("
    SELECT COUNT(*) AS total 
    FROM service_request sr 
    LEFT JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID 
    WHERE rs.Status_Name = 'Pending' OR sr.Request_Status_ID = 1
")->fetch()['total'];

// 3. Upcoming Shoots Count
$upcomingCount = $db->query("
    SELECT COUNT(*) AS total 
    FROM service_request 
    WHERE Preferred_Date >= CURDATE()
")->fetch()['total'];

// 4. Fetch recent booking requests (Latest 5)
$recentRequests = $db->query("
    SELECT 
        sr.Request_ID,
        sr.Preferred_Date,
        sr.Preferred_Time,
        et.Name AS event_type,
        pkg.Name AS package_name,
        rs.Status_Name AS status_name,
        u.Email AS client_email
    FROM service_request sr
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
    LEFT JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID
    LEFT JOIN user u ON sr.Client_ID = u.User_ID
    ORDER BY sr.Request_ID DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Photographer Portal</title>
    <link rel="stylesheet" href="photographer-dashboard.css">
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Photographer Portal</span>
        </div>

        <nav class="navigation">
            <a href="photographer-dashboard.php" class="active">DASHBOARD</a>
            <a href="photographer-request.php">BOOKING REQUESTS</a>
            <a href="photographer-messages.php">MESSAGES</a>
            <a href="photographer-payments.php">PAYMENTS</a>
            <a href="photographer-schedule.php">SCHEDULE</a>
        </nav>

        <div class="nav-user-actions">
            <div class="user-avatar">A</div>
            <a href="logout.php" class="logout-link">LOGOUT</a>
        </div>
    </header>

    <main class="page-container">
        <h2>Dashboard Overview</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Revenue</div>
                <div class="stat-value" style="color: #16a34a;">₱<?= number_format($totalRevenue, 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Pending Requests</div>
                <div class="stat-value" style="color: #d97706;"><?= $pendingCount ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Upcoming Shoots</div>
                <div class="stat-value" style="color: #2563eb;"><?= $upcomingCount ?></div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 16px; color: #111827;">Recent Booking Requests</h3>
                <a href="photographer-request.php" style="color: #2563eb; text-decoration: none; font-size: 13px; font-weight: 600;">View All →</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Req #</th>
                        <th>Client Email</th>
                        <th>Event & Package</th>
                        <th>Preferred Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentRequests)): ?>
                        <?php foreach ($recentRequests as $r): ?>
                            <tr>
                                <td><strong>#REQ-<?= str_pad($r['Request_ID'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><?= htmlspecialchars($r['client_email'] ?? 'Client') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r['event_type'] ?? 'N/A') ?></strong><br>
                                    <small style="color: #6b7280;"><?= htmlspecialchars($r['package_name'] ?? '') ?></small>
                                </td>
                                <td><?= date('M d, Y', strtotime($r['Preferred_Date'])) ?></td>
                                <td><span class="status-tag"><?= htmlspecialchars($r['status_name'] ?? 'Pending') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #6b7280;">No recent requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>