<?php
require_once 'config.php';

// Auth Guard - Admin / Photographer Access Only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$active_request_id = intval($_GET['request_id'] ?? 0);

// Fetch all active booking requests for the thread list
$threads = $db->query("
    SELECT 
        sr.Request_ID,
        et.Name AS event_type,
        u.Name AS client_name,
        (SELECT Message FROM message WHERE Request_ID = sr.Request_ID ORDER BY Message_ID DESC LIMIT 1) AS last_message,
        (SELECT Date_Sent FROM message WHERE Request_ID = sr.Request_ID ORDER BY Message_ID DESC LIMIT 1) AS last_sent_at
    FROM service_request sr
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    LEFT JOIN user u ON sr.Client_ID = u.User_ID
    ORDER BY last_sent_at DESC, sr.Request_ID DESC
")->fetchAll();

if ($active_request_id === 0 && !empty($threads)) {
    $active_request_id = $threads[0]['Request_ID'];
}

$messages = [];
$client_name = 'Client';

if ($active_request_id > 0) {
    // Fetch details & conversation history
    $details = $db->prepare("
        SELECT u.Name FROM service_request sr 
        LEFT JOIN user u ON sr.Client_ID = u.User_ID 
        WHERE sr.Request_ID = ?
    ");
    $details->execute([$active_request_id]);
    $client_name = $details->fetchColumn() ?: 'Client';

    $msg_stmt = $db->prepare("
        SELECT Message_ID, Sender, Message, Date_Sent 
        FROM message 
        WHERE Request_ID = ? 
        ORDER BY Date_Sent ASC
    ");
    $msg_stmt->execute([$active_request_id]);
    $messages = $msg_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Client Messages</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .navbar { background: #1e293b; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: #cbd5e1; text-decoration: none; font-weight: 600; margin-left: 20px; }
        .navbar a.active { color: #38bdf8; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .chat-layout { display: flex; background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); height: 600px; overflow: hidden; }
        .threads-panel { width: 320px; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; }
        .panel-header { padding: 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: bold; }
        .thread-item { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: inherit; display: block; }
        .thread-item.active { background: #f0f9ff; border-left: 4px solid #0284c7; }
        .chat-panel { flex: 1; display: flex; flex-direction: column; }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; background: #fafafa; display: flex; flex-direction: column; gap: 12px; }
        .message-bubble-wrapper { display: flex; flex-direction: column; max-width: 65%; }
        .outgoing { align-self: flex-end; }
        .incoming { align-self: flex-start; }
        .message-bubble { padding: 10px 14px; border-radius: 12px; font-size: 14px; line-height: 1.4; }
        .outgoing .message-bubble { background: #0284c7; color: white; border-bottom-right-radius: 2px; }
        .incoming .message-bubble { background: #e2e8f0; color: #1e293b; border-bottom-left-radius: 2px; }
        .message-time { font-size: 11px; margin-top: 4px; opacity: 0.8; text-align: right; }
        .chat-input { padding: 16px; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; background: white; }
        .chat-input input { flex: 1; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; outline: none; }
        .btn-send { background: #0284c7; color: white; border: none; padding: 0 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

    <header class="navbar">
        <div><strong>PHOTOGRAPHER ADMIN PORTAL</strong></div>
        <nav>
            <a href="admin-requests.php">BOOKING REQUESTS</a>
            <a href="admin-messages.php" class="active">MESSAGES</a>
            <a href="logout.php" style="color: #ef4444;">LOGOUT</a>
        </nav>
    </header>

    <main class="container">
        <div class="chat-layout">
            <!-- Left Thread List -->
            <div class="threads-panel">
                <div class="panel-header">Client Inquiries</div>
                <div style="overflow-y: auto; flex: 1;">
                    <?php foreach ($threads as $t): ?>
                        <a href="admin-messages.php?request_id=<?= $t['Request_ID'] ?>" class="thread-item <?= ($t['Request_ID'] == $active_request_id) ? 'active' : '' ?>">
                            <div><strong><?= htmlspecialchars($t['client_name'] ?? 'Client') ?></strong></div>
                            <div style="font-size: 12px; color: #0284c7; font-weight: 600;">#REQ-<?= str_pad($t['Request_ID'], 3, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($t['event_type'] ?? 'Shoot') ?></div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 4px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                <?= htmlspecialchars($t['last_message'] ?? 'No messages yet.') ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Chat Box -->
            <div class="chat-panel">
                <?php if ($active_request_id > 0): ?>
                    <div class="panel-header">
                        Chatting with: <strong><?= htmlspecialchars($client_name) ?></strong> (Request #REQ-<?= str_pad($active_request_id, 3, '0', STR_PAD_LEFT) ?>)
                    </div>

                    <div class="chat-messages" id="chatMessages">
                        <?php foreach ($messages as $m): ?>
                            <?php $is_admin = (strtolower($m['Sender']) === 'photographer' || strtolower($m['Sender']) === 'admin'); ?>
                            <div class="message-bubble-wrapper <?= $is_admin ? 'outgoing' : 'incoming' ?>">
                                <div class="message-bubble">
                                    <p style="margin: 0;"><?= nl2br(htmlspecialchars($m['Message'])) ?></p>
                                    <div class="message-time">
                                        <?= date('h:i A, M d', strtotime($m['Date_Sent'])) ?>
                                        <?php if ($is_admin): ?>
                                            <span style="color: #4cd137; font-weight: bold; margin-left: 2px;">✓</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form action="process-admin-message.php" method="POST" class="chat-input">
                        <input type="hidden" name="request_id" value="<?= $active_request_id ?>">
                        <input type="text" name="message" placeholder="Type your reply to client..." required autocomplete="off">
                        <button type="submit" class="btn-send">SEND</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const chatBox = document.getElementById('chatMessages');
            if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
        });
    </script>
</body>
</html>