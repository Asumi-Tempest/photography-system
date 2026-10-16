<?php
require_once 'config.php';
requireStaff(); // staff-only page

// Verify session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Detect location column name dynamically
$locationCol = "'N/A'";
try {
    $cols = $db->query("SHOW COLUMNS FROM service_request")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($cols as $col) {
        if (in_array(strtolower($col), ['location', 'event_location', 'venue'])) {
            $locationCol = "sr.{$col}";
            break;
        }
    }
} catch (Exception $e) {
    $locationCol = "'N/A'";
}

// Fetch ONLY requests whose status name is strictly 'Confirmed' or 'Approved'
$schedule = [];
try {
    $schedule = $db->query("
        SELECT 
            sr.Request_ID,
            sr.Preferred_Date,
            sr.Preferred_Time,
            {$locationCol} AS Location,
            et.Name AS event_type,
            pkg.Name AS package_name,
            rs.Status_Name AS status_name,
            u.Email AS client_email
        FROM service_request sr
        INNER JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID
        LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
        LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
        LEFT JOIN user u ON sr.Client_ID = u.User_ID
        WHERE LOWER(rs.Status_Name) IN ('confirmed', 'approved')
        ORDER BY sr.Preferred_Date ASC, sr.Preferred_Time ASC
    ")->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - Photographer Portal</title>
    <link rel="stylesheet" href="auth.css">
    <style>
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
        
        /* Unified Header Navigation */
        .top-nav { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 12px 32px; display: flex; align-items: center; justify-content: space-between; }
        .nav-left { display: flex; align-items: center; gap: 12px; }
        .nav-logo { background: #000; color: #fff; font-weight: 800; font-size: 14px; padding: 6px 12px; border-radius: 4px; letter-spacing: 1px; }
        .portal-title { font-weight: 700; font-size: 13px; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .nav-links { display: flex; gap: 24px; align-items: center; }
        .nav-links a { font-size: 12px; font-weight: 700; color: #475569; text-decoration: none; letter-spacing: 0.5px; transition: color 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: #2563eb; }

        .nav-right { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 32px; height: 32px; background: #2563eb; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .btn-logout { font-size: 12px; font-weight: 700; color: #dc2626; text-decoration: none; letter-spacing: 0.5px; }

        .portal-container { max-width: 1100px; margin: 32px auto; padding: 0 20px; }
        .page-title { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 24px; text-align: center; }

        .data-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .data-table th { background-color: #f8fafc; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }

        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; background: #dcfce7; color: #166534; }
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
        <h1 class="page-title">Upcoming Event Schedule</h1>

        <div class="data-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>DATE & TIME</th>
                        <th>REQ #</th>
                        <th>EVENT TYPE</th>
                        <th>PACKAGE</th>
                        <th>LOCATION</th>
                        <th>CLIENT EMAIL</th>
                        <th>EVENT STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($schedule)): ?>
                        <?php foreach ($schedule as $s): ?>
                            <tr>
                                <td>
                                    <strong><?= date('M d, Y', strtotime($s['Preferred_Date'])) ?></strong><br>
                                    <small style="color: #64748b;"><?= date('h:i A', strtotime($s['Preferred_Time'])) ?></small>
                                </td>
                                <td><strong>#REQ-<?= str_pad($s['Request_ID'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><strong><?= htmlspecialchars($s['event_type'] ?? 'General') ?></strong></td>
                                <td><?= htmlspecialchars($s['package_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($s['Location'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($s['client_email'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="status-badge"><?= htmlspecialchars($s['status_name']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 24px; color: #64748b;">No confirmed upcoming shoots found in your schedule.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>