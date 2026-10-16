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
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $requestId = intval($_POST['request_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if ($requestId <= 0 || empty($comment)) {
        $errorMessage = "Please complete all fields to submit a review.";
    } elseif (!clientOwnsRequest($db, $requestId)) {
        $errorMessage = "Invalid request selected.";
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO review (Request_ID, Client_ID, Rating, Comment, Review_Date)
                VALUES (:req_id, :client_id, :rating, :comment, NOW())
            ");
            $stmt->execute([
                'req_id'    => $requestId,
                'client_id' => $currentUserId,
                'rating'    => $rating,
                'comment'   => $comment
            ]);
            $successMessage = "Thank you! Your review has been submitted successfully.";
        } catch (PDOException $e) {
            $errorMessage = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch bookings eligible for review
$completedRequests = [];
try {
    $stmt = $db->prepare("
        SELECT sr.Request_ID, COALESCE(et.Name, 'General Event') AS event_type, sr.Preferred_Date 
        FROM service_request sr
        LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
        WHERE sr.Client_ID = :uid 
          AND sr.Request_ID NOT IN (SELECT Request_ID FROM review WHERE Client_ID = :uid)
        ORDER BY sr.Request_ID DESC
    ");
    $stmt->execute(['uid' => $currentUserId]);
    $completedRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $completedRequests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - Client Portal</title>
    <link rel="stylesheet" href="auth.css">
    <style>
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
        
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

        .portal-container { max-width: 750px; margin: 36px auto; padding: 0 20px; }
        .page-title { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0 0 6px; text-align: center; }
        .page-subtitle { text-align: center; color: #64748b; font-size: 14px; margin: 0 0 30px; }
        
        .data-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
        
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-weight: 700; font-size: 13px; color: #0f172a; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        .btn-submit { background: #2563eb; color: #fff; border: none; padding: 12px; border-radius: 6px; font-weight: 700; width: 100%; cursor: pointer; font-size: 14px; }
        .btn-submit:hover { background: #1d4ed8; }

        .alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
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
            <a href="client-deliverables.php">GALLERY</a>
            <a href="client-reviews.php" class="active">REVIEWS</a>
            <a href="client-messages.php">MESSAGES</a>
        </nav>
        <div class="nav-right">
            <div class="user-avatar">C</div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="portal-container">
        <h1 class="page-title">Submit Review & Rating</h1>
        <p class="page-subtitle">Share your feedback for your photography sessions.</p>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert-success">✓ <?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <div class="data-card">
            <?php if (empty($completedRequests)): ?>
                <p style="text-align: center; color: #64748b; margin: 0; padding: 20px 0;">You have no bookings available for review at this time.</p>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="submit_review">
                    
                    <div class="form-group">
                        <label class="form-label">Select Booking / Event</label>
                        <select name="request_id" class="form-control" required>
                            <option value="">-- Choose Event --</option>
                            <?php foreach ($completedRequests as $req): ?>
                                <option value="<?= $req['Request_ID'] ?>">#REQ-<?= str_pad($req['Request_ID'], 3, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($req['event_type']) ?> (<?= $req['Preferred_Date'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rating (1 to 5 Stars)</label>
                        <select name="rating" class="form-control" required>
                            <option value="5">★★★★★ (5 - Excellent)</option>
                            <option value="4">★★★★☆ (4 - Very Good)</option>
                            <option value="3">★★★☆☆ (3 - Good)</option>
                            <option value="2">★★☆☆☆ (2 - Fair)</option>
                            <option value="1">★☆☆☆☆ (1 - Poor)</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label class="form-label">Your Feedback / Comments</label>
                        <textarea name="comment" class="form-control" rows="4" placeholder="Write your feedback about the service..." required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Submit Review</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>