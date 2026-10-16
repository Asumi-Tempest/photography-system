<?php
require_once 'config.php';
requireStaff(); // staff-only page

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Default values to guarantee page renders
$totalRevenue = 0.00;
$pendingCount = 0;
$upcomingCount = 0;
$recentRequests = [];

try {
    // 1. Total Revenue Calculation
    $paymentQuery = $db->query("SELECT COALESCE(SUM(Amount), 0) AS total FROM payment WHERE Payment_Status_ID = 2");
    if ($paymentQuery) {
        $totalRevenue = $paymentQuery->fetch()['total'];
    }

    // 2. Pending Requests Count
    $pendingQuery = $db->query("
        SELECT COUNT(*) AS total 
        FROM service_request sr 
        LEFT JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID 
        WHERE rs.Status_Name = 'Pending' OR sr.Request_Status_ID = 1
    ");
    if ($pendingQuery) {
        $pendingCount = $pendingQuery->fetch()['total'];
    }

    // 3. Upcoming Shoots Count
    $upcomingQuery = $db->query("
        SELECT COUNT(*) AS total 
        FROM service_request sr
        LEFT JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID
        WHERE sr.Preferred_Date >= CURDATE() 
          AND (rs.Status_Name IN ('Confirmed', 'Approved') OR sr.Request_Status_ID IN (2, 3))
    ");
    if ($upcomingQuery) {
        $upcomingCount = $upcomingQuery->fetch()['total'];
    }

    // 4. Fetch recent booking requests
    $recentQuery = $db->query("
        SELECT 
            sr.Request_ID,
            sr.Preferred_Date,
            sr.Preferred_Time,
            et.Name AS event_type,
            pkg.Name AS package_name,
            COALESCE(rs.Status_Name, 'Pending') AS status_name,
            u.Email AS client_email
        FROM service_request sr
        LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
        LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
        LEFT JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID
        LEFT JOIN user u ON sr.Client_ID = u.User_ID
        ORDER BY sr.Request_ID DESC
        LIMIT 5
    ");
    if ($recentQuery) {
        $recentRequests = $recentQuery->fetchAll();
    }
} catch (Exception $e) {
    // Graceful fallback on query failure
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Overview - Photographer Portal</title>
    <link rel="stylesheet" href="auth.css">
    <style>
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
        
        .top-nav { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 12px 32px; display: flex; align-items: center; justify-content: space-between; }
        .nav-left { display: flex; align-items: center; gap: 12px; }
        .nav-logo { background: #000; color: #fff; font-weight: 800; font-size: 14px; padding: 6px 12px; border-radius: 4px; letter-spacing: 1px; }
        .portal-title { font-weight: 700; font-size: 13px; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .nav-links { display: flex; gap: 24px; align-items: center; }
        .nav-links a { font-size: 12px; font-weight: 700; color: #475569; text-decoration: none; letter-spacing: 0.5px; }
        .nav-links a:hover, .nav-links a.active { color: #2563eb; }

        .nav-right { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 32px; height: 32px; background: #2563eb; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .btn-logout { font-size: 12px; font-weight: 700; color: #dc2626; text-decoration: none; letter-spacing: 0.5px; }

        .portal-container { max-width: 1000px; margin: 32px auto; padding: 0 20px; }
        .dashboard-title { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 24px; text-align: center; }
        
        .stats-grid { display: flex; gap: 20px; margin-bottom: 32px; justify-content: center; }
        .stat-card { flex: 1; background: #fff; padding: 20px 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; min-width: 220px; }
        .stat-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 32px; font-weight: 800; margin-top: 8px; }
        .value-green { color: #16a34a; }
        .value-orange { color: #f97316; }
        .value-blue { color: #2563eb; }

        .data-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .card-header h3 { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .data-table th { background-color: #f8fafc; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }

        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-confirmed, .badge-approved { background: #dcfce7; color: #166534; }
        .badge-declined, .badge-cancelled { background: #fee2e2; color: #991b1b; }
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
    <div class="nav-right" style="display: flex; align-items: center; gap: 14px;">
        <div class="user-avatar" style="width: 32px; height: 32px; background: #2563eb; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">A</div>
        <a href="logout.php" class="btn-logout" style="font-size: 13px; font-weight: 700; color: #f87171; text-decoration: none;">Logout</a>
    </div>
</header>

    <main class="portal-container">
        <h1 class="dashboard-title">Dashboard Overview</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">VERIFIED REVENUE</div>
                <div class="stat-value value-green">₱<?= number_format($totalRevenue, 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">PENDING REQUESTS</div>
                <div class="stat-value value-orange"><?= $pendingCount ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">UPCOMING SHOOTS</div>
                <div class="stat-value value-blue"><?= $upcomingCount ?></div>
            </div>
        </div>

        <div class="data-card">
            <div class="card-header">
                <h3>Recent Booking Requests</h3>
                <a href="photographer-request.php" style="color: #2563eb; font-size: 13px; font-weight: 600; text-decoration: none;">View All →</a>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>REQ #</th>
                        <th>CLIENT EMAIL</th>
                        <th>EVENT & PACKAGE</th>
                        <th>PREFERRED DATE</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentRequests)): ?>
                        <?php foreach ($recentRequests as $r): ?>
                            <tr>
                                <td><strong>#REQ-<?= str_pad($r['Request_ID'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><?= htmlspecialchars($r['client_email'] ?? 'N/A') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r['event_type'] ?? 'General') ?></strong><br>
                                    <small style="color: #64748b;"><?= htmlspecialchars($r['package_name'] ?? '') ?></small>
                                </td>
                                <td><?= date('M d, Y', strtotime($r['Preferred_Date'])) ?></td>
                                <td>
                                    <?php 
                                        $st = strtolower($r['status_name']);
                                        $badgeClass = 'badge-pending';
                                        if (in_array($st, ['confirmed', 'approved'])) {
                                            $badgeClass = 'badge-confirmed';
                                        } elseif (in_array($st, ['declined', 'cancelled'])) {
                                            $badgeClass = 'badge-declined';
                                        }
                                    ?>
                                    <span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($r['status_name']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #64748b;">No recent requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>