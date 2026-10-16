<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$currentUserId = $_SESSION['user_id'];
$deliverables = [];

try {
    $stmt = $db->prepare("
        SELECT d.*, sr.Preferred_Date, 
               COALESCE(et.Name, 'General Event') AS event_type, 
               COALESCE(p.Fullname, 'Assigned Photographer') AS photographer_name
        FROM deliverable d
        JOIN service_request sr ON d.Request_ID = sr.Request_ID
        LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
        LEFT JOIN photographer p ON sr.Photographer_ID = p.Photographer_ID
        WHERE sr.Client_ID = :uid
        ORDER BY d.Deliverable_ID DESC
    ");
    $stmt->execute(['uid' => $currentUserId]);
    $deliverables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $deliverables = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Gallery & Deliverables - Client Portal</title>
    <link rel="stylesheet" href="auth.css">
    <style>
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
        
        /* Dark Navigation Bar matching original design */
        .top-nav { background: #1e1e2d; color: #fff; padding: 14px 40px; display: flex; align-items: center; justify-content: space-between; }
        .nav-left { display: flex; align-items: center; gap: 12px; }
        .portal-title { font-weight: 700; font-size: 15px; color: #ffffff; letter-spacing: 0.2px; }
        
        .nav-links { display: flex; gap: 28px; align-items: center; }
        .nav-links a { font-size: 12px; font-weight: 700; color: #92929f; text-decoration: none; letter-spacing: 0.8px; text-transform: uppercase; transition: color 0.2s; position: relative; padding-bottom: 4px; }
        .nav-links a:hover { color: #ffffff; }
        .nav-links a.active { color: #ffffff; border-bottom: 2px solid #ffffff; }

        .nav-right { display: flex; align-items: center; gap: 14px; }
        .user-avatar { width: 32px; height: 32px; background: #3b82f6; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .btn-logout { font-size: 13px; font-weight: 700; color: #f87171; text-decoration: none; }
        .btn-logout:hover { color: #ef4444; }

        /* Container Layout */
        .portal-container { max-width: 1100px; margin: 36px auto; padding: 0 20px; }
        .page-title { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0 0 6px; text-align: center; }
        .page-subtitle { text-align: center; color: #64748b; font-size: 14px; margin: 0 0 30px; }
        
        .data-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .data-table th { background-color: #f8fafc; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }

        .btn-action { background: #2563eb; color: #fff; padding: 6px 14px; border-radius: 6px; font-weight: 700; font-size: 12px; text-decoration: none; display: inline-block; margin-right: 6px; }
        .btn-action:hover { background: #1d4ed8; }
        .btn-zip { background: #10b981; }
        .btn-zip:hover { background: #059669; }
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-left">
            <img src="logo.svg" alt="Logo" width="34" height="34" style="display:block;width:34px;height:34px;">
            <span class="portal-title">Client Portal</span>
        </div>
        <nav class="nav-links">
            <a href="client-services.php">SERVICES</a>
            <a href="client-request.php">BOOK SERVICE</a>
            <a href="client-payments.php">MY PAYMENTS</a>
            <a href="client-deliverables.php" class="active">GALLERY</a>
            <a href="client-reviews.php">REVIEWS</a>
            <a href="client-messages.php">MESSAGES</a>
        </nav>
        <div class="nav-right">
            <div class="user-avatar">C</div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="portal-container">
        <h1 class="page-title">Photo Deliverables & Gallery</h1>
        <p class="page-subtitle">Access your digital proofing galleries and high-resolution event archives.</p>

        <div class="data-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>REQ #</th>
                        <th>EVENT & DETAILS</th>
                        <th>DELIVERED DATE</th>
                        <th>ACTIONS & DOWNLOADS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($deliverables)): ?>
                        <?php foreach ($deliverables as $deliv): ?>
                            <tr>
                                <td><strong>#REQ-<?= str_pad($deliv['Request_ID'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($deliv['event_type']) ?></strong><br>
                                    <small style="color: #64748b;">Date: <?= $deliv['Preferred_Date'] ?> | Photographer: <?= htmlspecialchars($deliv['photographer_name']) ?></small>
                                </td>
                                <td><?= date('M d, Y', strtotime($deliv['Delivered_Date'])) ?></td>
                                <td>
                                    <?php if (!empty($deliv['Gallery_Link'])): ?>
                                        <a href="<?= htmlspecialchars($deliv['Gallery_Link']) ?>" target="_blank" class="btn-action">View Album</a>
                                    <?php endif; ?>
                                    <?php if (!empty($deliv['Download_Link'])): ?>
                                        <a href="<?= htmlspecialchars($deliv['Download_Link']) ?>" target="_blank" class="btn-action btn-zip">Download .ZIP</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 30px; color: #64748b;">No photo deliverables have been uploaded for your bookings yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>