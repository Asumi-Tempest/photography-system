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

// Get client's latest active Request_ID
$activeRequestId = null;
try {
    $reqStmt = $db->prepare("SELECT Request_ID FROM service_request WHERE Client_ID = :uid ORDER BY Request_ID DESC LIMIT 1");
    $reqStmt->execute(['uid' => $currentUserId]);
    $reqRow = $reqStmt->fetch(PDO::FETCH_ASSOC);
    if ($reqRow) {
        $activeRequestId = $reqRow['Request_ID'];
    }
} catch (Exception $e) {
    $errorMessage = "Error fetching request context: " . $e->getMessage();
}

// Handle Message Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $messageText = trim($_POST['message'] ?? '');

    if (empty($messageText)) {
        $errorMessage = "Message content cannot be empty.";
    } elseif (!$activeRequestId) {
        $errorMessage = "You must submit a booking request before sending messages.";
    } else {
        try {
            $insertStmt = $db->prepare("
                INSERT INTO message (Request_ID, Sender, Message_Text, Date_Sent, Message_Status)
                VALUES (:req_id, 'Client', :msg, NOW(), 'Unread')
            ");
            $insertStmt->execute([
                'req_id' => $activeRequestId,
                'msg'    => $messageText
            ]);
            $successMessage = "Message sent successfully!";
        } catch (PDOException $e) {
            $errorMessage = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch conversation history for this client's booking requests
$messages = [];
try {
    $stmt = $db->prepare("
        SELECT m.* 
        FROM message m
        INNER JOIN service_request sr ON m.Request_ID = sr.Request_ID
        WHERE sr.Client_ID = :uid
        ORDER BY m.Message_ID ASC
    ");
    $stmt->execute(['uid' => $currentUserId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errorMessage = "Query Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Client Portal</title>
    <link rel="stylesheet" href="auth.css">
    <style>
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
        .top-nav { background: #1e1e2d; color: #fff; padding: 14px 40px; display: flex; align-items: center; justify-content: space-between; }
        .nav-left { display: flex; align-items: center; gap: 12px; }
        .portal-title { font-weight: 700; font-size: 15px; color: #ffffff; letter-spacing: 0.2px; }
        .nav-links { display: flex.gap: 20px; align-items: center; }
        .nav-links a { font-size: 11px; font-weight: 700; color: #92929f; text-decoration: none; letter-spacing: 0.8px; text-transform: uppercase; transition: color 0.2s; position: relative; padding-bottom: 4px; margin-right: 15px; }
        .nav-links a:hover { color: #ffffff; }
        .nav-links a.active { color: #ffffff; border-bottom: 2px solid #ffffff; }
        .nav-right { display: flex; align-items: center; gap: 14px; }
        .user-avatar { width: 32px; height: 32px; background: #3b82f6; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .btn-logout { font-size: 13px; font-weight: 700; color: #f87171; text-decoration: none; }
        .portal-container { max-width: 900px; margin: 36px auto; padding: 0 20px; }
        .page-title { font-size: 26px; font-weight: 800; color: #0f172a; margin-bottom: 24px; text-align: center; }
        .chat-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
        .chat-box { min-height: 250px; max-height: 400px; overflow-y: auto; padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fafafa; margin-bottom: 20px; display: flex; flex-direction: column; gap: 12px; }
        .message-bubble { max-width: 75%; padding: 10px 14px; border-radius: 10px; font-size: 14px; line-height: 1.4; }
        .msg-client { background: #2563eb; color: #fff; align-self: flex-end; border-bottom-right-radius: 2px; }
        .msg-admin { background: #e2e8f0; color: #0f172a; align-self: flex-start; border-bottom-left-radius: 2px; }
        .msg-date { font-size: 10px; opacity: 0.75; margin-top: 4px; text-align: right; display: block; }
        .chat-form { display: flex; gap: 10px; }
        .chat-input { flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; }
        .btn-send { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; }
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
            <a href="client-reviews.php">REVIEWS</a>
            <a href="client-messages.php" class="active">MESSAGES</a>
        </nav>
        <div class="nav-right">
            <div class="user-avatar">C</div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="portal-container">
        <h1 class="page-title">Client Support Messages</h1>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert-success">✓ <?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <div class="chat-card">
            <div class="chat-box">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php $isClient = strcasecmp($msg['Sender'], 'Client') === 0; ?>
                        <div class="message-bubble <?= $isClient ? 'msg-client' : 'msg-admin' ?>">
                            <?= htmlspecialchars($msg['Message_Text'] ?? $msg['Message'] ?? '') ?>
                            <span class="msg-date"><?= date('M d, g:i a', strtotime($msg['Date_Sent'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #64748b; font-size: 13px; margin: auto;">No message history found. Send a message to get in touch!</p>
                <?php endif; ?>
            </div>

            <form method="POST" class="chat-form">
                <input type="hidden" name="action" value="send_message">
                <input type="text" name="message" class="chat-input" placeholder="Type your message here..." required>
                <button type="submit" class="btn-send">Send</button>
            </form>
        </div>
    </main>

</body>
</html>