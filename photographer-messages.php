<?php
require_once 'config.php';
requireStaff(); // staff-only page

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

// Selected conversation (Request ID)
$selectedRequestId = intval($_GET['request_id'] ?? 0);

// Handle Admin Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_reply') {
    $replyText = trim($_POST['message'] ?? '');
    $reqId = intval($_POST['request_id'] ?? 0);

    if (!empty($replyText) && $reqId > 0) {
        try {
            $stmt = $db->prepare("
                INSERT INTO message (Request_ID, Sender, Message, Date_Sent, Message_Status)
                VALUES (:req_id, 'Admin', :msg, NOW(), 'Read')
            ");
            $stmt->execute([
                'req_id' => $reqId,
                'msg'    => $replyText
            ]);
            $successMessage = "Reply sent successfully!";
            $selectedRequestId = $reqId;
        } catch (PDOException $e) {
            $errorMessage = "Error sending reply: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Please enter a message.";
    }
}

// Fetch all conversation threads (grouped by Request_ID)
$conversations = [];
try {
    $convStmt = $db->query("
        SELECT 
            sr.Request_ID,
            COALESCE(c.Name, c.First_Name, 'Client') AS client_name,
            COALESCE(et.Name, 'General Request') AS event_type,
            (SELECT Message FROM message WHERE Request_ID = sr.Request_ID ORDER BY Message_ID DESC LIMIT 1) AS last_message,
            (SELECT Date_Sent FROM message WHERE Request_ID = sr.Request_ID ORDER BY Message_ID DESC LIMIT 1) AS last_date
        FROM service_request sr
        LEFT JOIN client c ON sr.Client_ID = c.Client_ID
        LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
        WHERE EXISTS (SELECT 1 FROM message m WHERE m.Request_ID = sr.Request_ID)
        ORDER BY last_date DESC
    ");
    $conversations = $convStmt->fetchAll(PDO::FETCH_ASSOC);

    // If no specific request selected, auto-select the first conversation
    if ($selectedRequestId === 0 && !empty($conversations)) {
        $selectedRequestId = $conversations[0]['Request_ID'];
    }
} catch (Exception $e) {
    // Fallback query if client/event_type table structures differ
    try {
        $convStmt = $db->query("
            SELECT DISTINCT 
                Request_ID,
                CONCAT('Request #', Request_ID) AS client_name,
                'Service Inquiry' AS event_type,
                (SELECT Message FROM message WHERE Request_ID = m.Request_ID ORDER BY Message_ID DESC LIMIT 1) AS last_message,
                (SELECT Date_Sent FROM message WHERE Request_ID = m.Request_ID ORDER BY Message_ID DESC LIMIT 1) AS last_date
            FROM message m
            ORDER BY Message_ID DESC
        ");
        $conversations = $convStmt->fetchAll(PDO::FETCH_ASSOC);
        if ($selectedRequestId === 0 && !empty($conversations)) {
            $selectedRequestId = $conversations[0]['Request_ID'];
        }
    } catch (Exception $ex) {
        $errorMessage = "Query Error: " . $ex->getMessage();
    }
}

// Fetch active chat messages for selected conversation
$activeMessages = [];
if ($selectedRequestId > 0) {
    try {
        // Mark client messages as read
        $db->prepare("UPDATE message SET Message_Status = 'Read' WHERE Request_ID = :req_id AND Sender = 'Client'")
           ->execute(['req_id' => $selectedRequestId]);

        $msgStmt = $db->prepare("
            SELECT * FROM message 
            WHERE Request_ID = :req_id 
            ORDER BY Message_ID ASC
        ");
        $msgStmt->execute(['req_id' => $selectedRequestId]);
        $activeMessages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $errorMessage = "Error loading chat: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Messages - Photographer Portal</title>
    <link rel="stylesheet" href="auth.css">
    <style>
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
        
        .top-nav { background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 14px 40px; display: flex; align-items: center; justify-content: space-between; }
        .nav-left { display: flex; align-items: center; gap: 12px; }
        .nav-logo { background: #000; color: #fff; font-weight: 800; font-size: 11px; padding: 4px 8px; border-radius: 4px; }
        .portal-title { font-weight: 700; font-size: 14px; color: #0f172a; letter-spacing: 0.5px; }
        
        .nav-links { display: flex; gap: 24px; align-items: center; }
        .nav-links a { font-size: 12px; font-weight: 700; color: #64748b; text-decoration: none; text-transform: uppercase; transition: color 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: #2563eb; }

        .nav-right { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 32px; height: 32px; background: #2563eb; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .btn-logout { font-size: 12px; font-weight: 700; color: #ef4444; text-decoration: none; }

        .portal-container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .page-title { font-size: 26px; font-weight: 800; color: #0f172a; margin-bottom: 24px; text-align: center; }

        .chat-layout { display: grid; grid-template-columns: 320px 1fr; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; min-height: 520px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        
        .sidebar { border-right: 1px solid #e2e8f0; background: #fafafa; }
        .sidebar-header { padding: 16px; font-weight: 700; font-size: 14px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        .conv-list { list-style: none; margin: 0; padding: 0; }
        .conv-item { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.2s; text-decoration: none; display: block; color: inherit; }
        .conv-item:hover { background: #f1f5f9; }
        .conv-item.active { background: #eff6ff; border-left: 4px solid #2563eb; }
        .conv-name { font-weight: 700; font-size: 14px; color: #0f172a; margin-bottom: 2px; }
        .conv-preview { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .chat-main { display: flex; flex-direction: column; justify-content: space-between; }
        .chat-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #0f172a; background: #fff; }
        .chat-box { flex: 1; padding: 20px; overflow-y: auto; max-height: 380px; display: flex; flex-direction: column; gap: 12px; background: #f8fafc; }
        
        .message-bubble { max-width: 70%; padding: 10px 14px; border-radius: 10px; font-size: 14px; line-height: 1.4; }
        .msg-admin { background: #2563eb; color: #fff; align-self: flex-end; border-bottom-right-radius: 2px; }
        .msg-client { background: #e2e8f0; color: #0f172a; align-self: flex-start; border-bottom-left-radius: 2px; }
        .msg-date { font-size: 10px; opacity: 0.75; margin-top: 4px; text-align: right; display: block; }

        .chat-footer { padding: 16px 20px; border-top: 1px solid #e2e8f0; background: #fff; }
        .chat-form { display: flex; gap: 10px; }
        .chat-input { flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; }
        .btn-send { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; }

        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-left">
            <img src="logo.svg" alt="Logo" width="34" height="34" style="display:block;width:34px;height:34px;">
            <span class="portal-title">PHOTOGRAPHER PORTAL</span>
        </div>
        <nav class="nav-links">
            <a href="photographer-dashboard.php">DASHBOARD</a>
            <a href="photographer-request.php">BOOKING REQUESTS</a>
            <a href="photographer-messages.php" class="active">MESSAGES</a>
            <a href="photographer-payments.php">PAYMENTS</a>
            <a href="photographer-schedule.php">SCHEDULE</a>
        </nav>
        <div class="nav-right">
            <div class="user-avatar">A</div>
            <a href="logout.php" class="btn-logout">LOGOUT</a>
        </div>
    </header>

    <main class="portal-container">
        <h1 class="page-title">Client Messages</h1>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert-success">✓ <?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <div class="chat-layout">
            <aside class="sidebar">
                <div class="sidebar-header">Conversations</div>
                <div class="conv-list">
                    <?php if (!empty($conversations)): ?>
                        <?php foreach ($conversations as $conv): ?>
                            <a href="photographer-messages.php?request_id=<?= $conv['Request_ID'] ?>" 
                               class="conv-item <?= $conv['Request_ID'] == $selectedRequestId ? 'active' : '' ?>">
                                <div class="conv-name"><?= htmlspecialchars($conv['client_name']) ?> (#REQ-<?= str_pad($conv['Request_ID'], 3, '0', STR_PAD_LEFT) ?>)</div>
                                <div class="conv-preview"><?= htmlspecialchars($conv['last_message'] ?? 'No messages yet') ?></div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 24px 16px; text-align: center; color: #64748b; font-size: 13px;">No active conversations found.</div>
                    <?php endif; ?>
                </div>
            </aside>

            <section class="chat-main">
                <?php if ($selectedRequestId > 0): ?>
                    <div class="chat-header">
                        Conversation for Request #REQ-<?= str_pad($selectedRequestId, 3, '0', STR_PAD_LEFT) ?>
                    </div>

                    <div class="chat-box">
                        <?php if (!empty($activeMessages)): ?>
                            <?php foreach ($activeMessages as $msg): ?>
                                <?php $isAdmin = strcasecmp($msg['Sender'], 'Admin') === 0 || strcasecmp($msg['Sender'], 'Photographer') === 0; ?>
                                <div class="message-bubble <?= $isAdmin ? 'msg-admin' : 'msg-client' ?>">
                                    <?= htmlspecialchars($msg['Message']) ?>
                                    <span class="msg-date"><?= date('M d, g:i a', strtotime($msg['Date_Sent'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #64748b; font-size: 13px; margin: auto;">No messages in this conversation yet.</p>
                        <?php endif; ?>
                    </div>

                    <div class="chat-footer">
                        <form method="POST" class="chat-form">
                            <input type="hidden" name="action" value="send_reply">
                            <input type="hidden" name="request_id" value="<?= $selectedRequestId ?>">
                            <input type="text" name="message" class="chat-input" placeholder="Type your reply here..." required>
                            <button type="submit" class="btn-send">Send Reply</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="margin: auto; color: #64748b; font-size: 14px;">Select a conversation from the left to view messages.</div>
                <?php endif; ?>
            </section>
        </div>
    </main>

</body>
</html>