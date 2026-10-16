<?php
require_once 'config.php';
requireStaff(); // staff-only page

// Verify Admin/Photographer session
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

// Fetch requests
$requests = [];
try {
    $requests = $db->query("
        SELECT 
            sr.Request_ID,
            sr.Preferred_Date,
            sr.Preferred_Time,
            {$locationCol} AS Location,
            et.Name AS event_type,
            pkg.Name AS package_name,
            pkg.Price AS package_price,
            COALESCE(rs.Status_Name, 'Pending') AS status_name,
            sr.Request_Status_ID,
            u.Email AS client_email
        FROM service_request sr
        LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
        LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
        LEFT JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID
        LEFT JOIN user u ON sr.Client_ID = u.User_ID
        ORDER BY sr.Request_ID DESC
    ")->fetchAll();
} catch (Exception $e) {}

// Fetch statuses
$statuses = [];
try {
    $statuses = $db->query("SELECT * FROM request_status ORDER BY Request_Status_ID ASC")->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Requests - Photographer Portal</title>
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

        /* Portal Container */
        .portal-container { max-width: 1100px; margin: 32px auto; padding: 0 20px; }
        .page-title { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 24px; text-align: center; }

        /* Data Card & Table Formatting */
        .data-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .data-table th { background-color: #f8fafc; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }

        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-confirmed, .badge-approved { background: #dcfce7; color: #166534; }
        .badge-declined, .badge-cancelled, .badge-rejected { background: #fee2e2; color: #991b1b; }

        .action-form { display: flex; gap: 8px; align-items: center; }
        .select-status { padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; }
        .btn-update { background: #2563eb; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-update:hover { background: #1d4ed8; }
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
        <h1 class="page-title">Client Booking Requests</h1>

        <div class="data-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>REQ #</th>
                        <th>CLIENT</th>
                        <th>EVENT DETAILS</th>
                        <th>DATE & TIME</th>
                        <th>LOCATION</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td><strong>#REQ-<?= str_pad($r['Request_ID'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><?= htmlspecialchars($r['client_email'] ?? 'N/A') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r['event_type'] ?? 'General') ?></strong><br>
                                    <small style="color: #64748b;"><?= htmlspecialchars($r['package_name'] ?? '') ?> (₱<?= number_format($r['package_price'] ?? 0, 2) ?>)</small>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($r['Preferred_Date'])) ?><br>
                                    <small style="color: #64748b;"><?= date('h:i A', strtotime($r['Preferred_Time'])) ?></small>
                                </td>
                                <td><?= htmlspecialchars($r['Location'] ?? 'N/A') ?></td>
                                <td>
                                    <?php 
                                        $st = strtolower($r['status_name']);
                                        $badgeClass = 'badge-pending';
                                        if (in_array($st, ['confirmed', 'approved'])) {
                                            $badgeClass = 'badge-confirmed';
                                        } elseif (in_array($st, ['declined', 'cancelled', 'rejected'])) {
                                            $badgeClass = 'badge-declined';
                                        }
                                    ?>
                                    <span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($r['status_name']) ?></span>
                                </td>
                                <td>
                                    <form action="process-request.php" method="POST" class="action-form">
                                        <input type="hidden" name="request_id" value="<?= $r['Request_ID'] ?>">
                                        <select name="status_id" class="select-status">
                                            <?php foreach ($statuses as $st_opt): ?>
                                                <option value="<?= $st_opt['Request_Status_ID'] ?>" <?= ($st_opt['Request_Status_ID'] == $r['Request_Status_ID']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($st_opt['Status_Name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn-update">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: #64748b;">No booking requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>