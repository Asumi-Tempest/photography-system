<?php
require_once 'config.php';

// Auth Guard - Client Access Only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$client_id = $_SESSION['user_id'];
$active_request_id = intval($_GET['request_id'] ?? 0);

// 1. Fetch active service requests submitted by this client
$threads_query = "
    SELECT 
        sr.Request_ID,
        et.Name AS event_type,
        (SELECT Message FROM message WHERE Request_ID = sr.Request_ID ORDER BY Message_ID DESC LIMIT 1) AS last_message,
        (SELECT Date_Sent FROM message WHERE Request_ID = sr.Request_ID ORDER BY Message_ID DESC LIMIT 1) AS last_sent_at
    FROM service_request sr
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    WHERE sr.Client_ID = ?
    ORDER BY last_sent_at DESC, sr.Request_ID DESC
";
$stmt = $db->prepare($threads_query);
$stmt->execute([$client_id]);
$threads = $stmt->fetchAll();

// Default to first conversation thread if none specified
if ($active_request_id === 0 && !empty($threads)) {
    $active_request_id = $threads[0]['Request_ID'];
}

// 2. Fetch messages for the active conversation thread
$messages = [];
$active_event_type = '';

if ($active_request_id > 0) {
    // Get service request details
    $req_stmt = $db->prepare("
        SELECT et.Name 
        FROM service_request sr 
        LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID 
        WHERE sr.Request_ID = ? AND sr.Client_ID = ?
    ");
    $req_stmt->execute([$active_request_id, $client_id]);
    $active_event_type = $req_stmt->fetchColumn() ?: 'Shoot';

    // Fetch message history
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
    <title>Messages - Client Portal</title>
    <link rel="stylesheet" href="client-messages.css">
</head>
<body>

    <!-- Client Navigation Header -->
    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Client Portal</span>
        </div>

        <nav class="navigation">
            <a href="client-request.php">BOOK SERVICE</a>
            <a href="client-payments.php">MY PAYMENTS</a>
            <a href="client-messages.php" class="active">MESSAGES</a>
        </nav>

        <div class="nav-user-actions" style="display: flex; align-items: center; gap: 12px;">
            <div class="user-avatar">C</div>
            <a href="logout.php" style="color: #ff6b6b; font-weight: bold; text-decoration: none; font-size: 14px;">Logout</a>
        </div>
    </header>

    <main class="page-container">

        <div class="chat-card">
            
            <!-- Left Panel: My Bookings / Threads -->
            <div class="threads-panel">
                <div class="panel-header">
                    <h2>My Bookings</h2>
                </div>
                <div class="threads-list">
                    <?php if (!empty($threads)): ?>
                        <?php foreach ($threads as $t): ?>
                            <a href="client-messages.php?request_id=<?= $t['Request_ID'] ?>" 
                               class="thread-item <?= ($t['Request_ID'] == $active_request_id) ? 'active' : '' ?>">
                                <div class="thread-title">
                                    <span class="font-bold"><?= htmlspecialchars($t['event_type'] ?? 'Service Request') ?></span>
                                    <span class="thread-tag">#REQ-<?= str_pad($t['Request_ID'], 3, '0', STR_PAD_LEFT) ?></span>
                                </div>
                                <div class="thread-preview">
                                    <?= htmlspecialchars($t['last_message'] ?? 'No messages yet.') ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">No active bookings found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Panel: Chat Room -->
            <div class="chat-panel">
                <?php if ($active_request_id > 0): ?>
                    
                    <div class="panel-header chat-header">
                        <h2>Photographer Support (<?= htmlspecialchars($active_event_type) ?>)</h2>
                        <span class="req-badge">Request #REQ-<?= str_pad($active_request_id, 3, '0', STR_PAD_LEFT) ?></span>
                    </div>

                    <!-- Sent Confirmation Alert Banner -->
                    <?php if (!empty($_GET['msg_sent'])): ?>
                        <div style="padding: 8px 14px; background-color: #d4edda; color: #155724; border-radius: 6px; font-size: 13px; margin: 10px 15px 0 15px; display: flex; align-items: center; gap: 6px; font-weight: 500;">
                            <span style="color: #28a745; font-weight: bold;">✓</span> Message sent successfully!
                        </div>
                    <?php endif; ?>

                    <!-- Message Feed -->
                    <div class="chat-messages" id="chatMessages">
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $m): ?>
                                <?php $is_client = (strtolower($m['Sender']) === 'client'); ?>
                                <div class="message-bubble-wrapper <?= $is_client ? 'outgoing' : 'incoming' ?>">
                                    <div class="message-bubble">
                                        <p><?= nl2br(htmlspecialchars($m['Message'])) ?></p>
                                        <span class="message-time">
                                            <?= date('h:i A, M d', strtotime($m['Date_Sent'])) ?>
                                            <?php if ($is_client): ?>
                                                <span style="color: #4cd137; font-weight: bold; margin-left: 4px;" title="Sent">✓</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">Send a message to contact your photographer regarding this booking.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Client Message Input Form -->
                    <form action="process-client-message.php" method="POST" class="chat-input-area">
                        <input type="hidden" name="request_id" value="<?= $active_request_id ?>">
                        <input type="text" name="message" placeholder="Type your message here..." required autocomplete="off">
                        <button type="submit" class="btn-send">SEND</button>
                    </form>

                <?php else: ?>
                    <div class="empty-state flex-center">
                        <p>Select a service request from the left to view messages.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </main>

    <!-- JS Auto-Scroll to Bottom on Load -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const chatBox = document.getElementById('chatMessages');
            if (chatBox) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        });
    </script>

</body>
</html>