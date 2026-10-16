<?php
require_once 'config.php';
requireStaff(); // staff-only check

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$reviews = [];

try {
    $stmt = $db->query("
        SELECT r.*, sr.Preferred_Date, 
               COALESCE(et.Name, 'General Event') AS event_type, 
               COALESCE(u.Email, 'Client') AS client_email
        FROM review r
        JOIN service_request sr ON r.Request_ID = sr.Request_ID
        LEFT JOIN user u ON r.Client_ID = u.User_ID
        LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
        ORDER BY r.Review_ID DESC
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reviews = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Reviews - Photographer Portal</title>
    <link rel="stylesheet" href="auth.css">
    <style>
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
        
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

        .portal-container { max-width: 1100px; margin: 36px auto; padding: 0 20px; }
        .page-title { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0 0 6px; text-align: center; }
        .page-subtitle { text-align: center; color: #64748b; font-size: 14px; margin: 0 0 30px; }
        
        .data-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .data-table th { background-color: #f8fafc; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }
        .star-rating { color: #f59e0b; font-weight: 700; font-size: 14px; letter-spacing: 2px; }
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
            <a href="photographer-reviews.php" class="active">REVIEWS</a>
        </nav>
        <div class="nav-right">
            <div class="user-avatar">A</div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="portal-container">
        <h1 class="page-title">Client Feedback & Reviews</h1>
        <p class="page-subtitle">View ratings and comments submitted by clients for completed photography sessions.</p>

        <div class="data-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>REQ #</th>
                        <th>CLIENT</th>
                        <th>EVENT & DATE</th>
                        <th>RATING</th>
                        <th>FEEDBACK COMMENT</th>
                        <th>DATE SUBMITTED</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $rev): ?>
                            <tr>
                                <td><strong>#REQ-<?= str_pad($rev['Request_ID'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><?= htmlspecialchars($rev['client_email']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($rev['event_type']) ?></strong><br>
                                    <small style="color: #64748b;"><?= $rev['Preferred_Date'] ?></small>
                                </td>
                                <td>
                                    <span class="star-rating">
                                        <?= str_repeat('★', (int)$rev['Rating']) ?><?= str_repeat('☆', 5 - (int)$rev['Rating']) ?>
                                    </span>
                                    <br><small style="color: #64748b; font-weight: 600;"><?= $rev['Rating'] ?> / 5</small>
                                </td>
                                <td style="max-width: 300px; color: #334155;"><?= htmlspecialchars($rev['Comment']) ?></td>
                                <td><?= date('M d, Y', strtotime($rev['Review_Date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: #64748b;">No reviews have been submitted by clients yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>