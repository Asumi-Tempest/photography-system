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
$errorMessage = '';
$successMessage = '';

// Handle Deliverable Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_deliverable') {
    $requestId = intval($_POST['request_id'] ?? 0);
    $galleryLink = trim($_POST['gallery_link'] ?? '');
    $downloadLink = trim($_POST['download_link'] ?? '');

    if ($requestId <= 0 || empty($galleryLink) || empty($downloadLink)) {
        $errorMessage = "Please provide all required fields and links.";
    } else {
        try {
            // Insert or update deliverable
            $stmt = $db->prepare("
                INSERT INTO deliverable (Request_ID, Gallery_Link, Download_Link, Delivered_Date, Status)
                VALUES (:req_id, :gallery, :download, NOW(), 'Delivered')
                ON DUPLICATE KEY UPDATE 
                    Gallery_Link = :gallery_up, 
                    Download_Link = :download_up, 
                    Delivered_Date = NOW(), 
                    Status = 'Delivered'
            ");
            $stmt->execute([
                'req_id'      => $requestId,
                'gallery'     => $galleryLink,
                'download'    => $downloadLink,
                'gallery_up'  => $galleryLink,
                'download_up' => $downloadLink
            ]);

            // Automatically update service request to Completed status if available
            $upStatus = $db->prepare("
                UPDATE service_request 
                SET Request_Status_ID = (SELECT Request_Status_ID FROM request_status WHERE LOWER(Status_Name) = 'completed' LIMIT 1)
                WHERE Request_ID = :req_id
            ");
            $upStatus->execute(['req_id' => $requestId]);

            $successMessage = "Deliverables successfully uploaded and sent to client for #REQ-" . str_pad($requestId, 3, '0', STR_PAD_LEFT) . "!";
        } catch (PDOException $e) {
            $errorMessage = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch confirmed / active requests for the dropdown
$confirmedRequests = [];
try {
    $stmt = $db->query("
        SELECT sr.Request_ID, c.Fullname AS client_name, et.Name AS event_type, sr.Preferred_Date
        FROM service_request sr
        JOIN client c ON sr.Client_ID = c.Client_ID
        JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
        JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID
        WHERE LOWER(rs.Status_Name) IN ('confirmed', 'completed')
        ORDER BY sr.Request_ID DESC
    ");
    $confirmedRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $confirmedRequests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deliverables - Photographer Portal</title>
    <link rel="stylesheet" href="auth.css">
    <style>
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
        
        .top-nav { background: #1e1e2d; color: #fff; padding: 14px 40px; display: flex; align-items: center; justify-content: space-between; }
        .nav-left { display: flex; align-items: center; gap: 12px; }
        .portal-title { font-weight: 700; font-size: 15px; color: #ffffff; letter-spacing: 0.2px; }
        
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { font-size: 11px; font-weight: 700; color: #92929f; text-decoration: none; letter-spacing: 0.8px; text-transform: uppercase; transition: color 0.2s; position: relative; padding-bottom: 4px; }
        .nav-links a:hover { color: #ffffff; }
        .nav-links a.active { color: #ffffff; border-bottom: 2px solid #ffffff; }

        .nav-right { display: flex; align-items: center; gap: 14px; }
        .user-avatar { width: 32px; height: 32px; background: #2563eb; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .btn-logout { font-size: 13px; font-weight: 700; color: #f87171; text-decoration: none; }
        .btn-logout:hover { color: #ef4444; }

        .portal-container { max-width: 750px; margin: 36px auto; padding: 0 20px; }
        .page-title { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0 0 6px; text-align: center; }
        .page-subtitle { text-align: center; color: #64748b; font-size: 14px; margin: 0 0 30px; }
        
        .data-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
        
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-weight: 700; font-size: 13px; color: #0f172a; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        .btn-submit { background: #10b981; color: #fff; border: none; padding: 12px; border-radius: 6px; font-weight: 700; width: 100%; cursor: pointer; font-size: 14px; }
        .btn-submit:hover { background: #059669; }

        .alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
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
        <h1 class="page-title">Manage Photo Deliverables</h1>
        <p class="page-subtitle">Upload online gallery links and download archives for confirmed client projects.</p>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert-success">✓ <?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <div class="data-card">
            <?php if (empty($confirmedRequests)): ?>
                <p style="text-align: center; color: #64748b; margin: 0; padding: 20px 0;">No confirmed bookings available for photo delivery.</p>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="upload_deliverable">
                    
                    <div class="form-group">
                        <label class="form-label">Select Confirmed Booking</label>
                        <select name="request_id" class="form-control" required>
                            <option value="">-- Choose Booking --</option>
                            <?php foreach ($confirmedRequests as $req): ?>
                                <option value="<?= $req['Request_ID'] ?>">#REQ-<?= str_pad($req['Request_ID'], 3, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($req['client_name']) ?> (<?= htmlspecialchars($req['event_type']) ?> on <?= $req['Preferred_Date'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Online Gallery Link (e.g. Google Drive / Pixieset)</label>
                        <input type="url" name="gallery_link" class="form-control" placeholder="https://drive.google.com/..." required>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label class="form-label">Download Archive Link (.ZIP)</label>
                        <input type="url" name="download_link" class="form-control" placeholder="https://drive.google.com/.../download.zip" required>
                    </div>

                    <button type="submit" class="btn-submit">Upload & Send Deliverables</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>