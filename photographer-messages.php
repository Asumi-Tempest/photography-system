<?php
require_once 'config.php';

// Auth Guard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$photographer_id = $_SESSION['user_id'];
$active_request_id = intval($_GET['request_id'] ?? 0);

// 1. Fetch active conversation threads (grouped by Service Request)
$threads_query = "
    SELECT 
        sr.Request_ID,
        c.Fullname AS client_name,
        et.Name AS event_type,
        (SELECT Message_Content FROM Message WHERE Request_ID = sr.Request_ID ORDER BY Message_ID DESC LIMIT 1) AS last_message,
        (SELECT Sent_At FROM Message WHERE Request_ID = sr.Request_ID ORDER BY Message_ID DESC LIMIT 1) AS last_sent_at
    FROM Service_Request sr
    JOIN Client c ON sr.Client_ID = c.Client_ID
    JOIN Event_Type et ON sr.Event_Type_ID = et.Event_Type_ID
    ORDER BY last_sent_at DESC
";
$threads = $db->query($threads_query)->fetchAll();

// Default to first thread if no specific conversation is selected
if ($active_request_id === 0 && !empty($threads)) {
    $active_request_id = $threads[0]['Request_ID'];
}

// 2. Fetch active chat details & messages if a request is selected
$active_client_name = '';
$messages = [];

if ($active_request_id > 0) {
    // Get client details for header
    $client_stmt = $db->prepare("
        SELECT c.Fullname 
        FROM Service_Request sr 
        JOIN Client c ON sr.Client_ID = c.Client_ID 
        WHERE sr.Request_ID = ?
    ");
    $client_stmt->execute([$active_request_id]);
    $active_client_name = $client_stmt->fetchColumn() ?: 'Client';

    // Fetch messages for this request
    $msg_stmt = $db->prepare("
        SELECT Message_ID, Sender_Type, Message_Content, Sent_At 
        FROM Message 
        WHERE Request_ID = ? 
        ORDER BY Sent_At ASC
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
    <title>Messages - Photographer Portal</title>
    <link rel="stylesheet" href="photographer-messages.css">
</head>
<body>

    <!-- Header / Navigation Bar -->
    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Photographer Portal</span>
            <span class="badge-pro">PRO</span>
        </div>

        <nav class="navigation">
            <a href="photographer-dashboard.php">DASHBOARD</a>
            <a href="photographer-requests.php">REQUESTS</a>
            <a href="photographer-schedule.php">SCHEDULE</a>
            <a href="photographer-payments.php">PAYMENTS</a>
            <a href="photographer-messages.php" class="active">MESSAGES</a>
        </nav>

        <div class="nav-user-actions">
            <button class="icon-btn" aria-label="Notifications">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
            </button>
            <div class="user-avatar">P</div>
        </div>
    </header>

    <!-- Page Body -->
    <main class="page-container">

        <div class="chat-card">
            
            <!-- Left Panel: Conversation Threads -->
            <div class="threads-panel">
                <div class="panel-header">
                    <h2>Conversations</h2>
                </div>
                <div class="threads-list">
                    <?php if (!empty($threads)): ?>
                        <?php foreach ($threads as $t): ?>
                            <a href="photographer-messages.php?request_id=<?= $t['Request_ID'] ?>" 
                               class="thread-item <?= ($t['Request_ID'] == $active_request_id) ? 'active' : '' ?>">
                                <div class="thread-title">
                                    <span class="font-bold"><?= htmlspecialchars($t['client_name']) ?></span>
                                    <span class="thread-tag">#REQ-<?= str_pad($t['Request_ID'], 3, '0', STR_PAD_LEFT) ?></span>
                                </div>
                                <div class="thread-subtext"><?= htmlspecialchars($t['event_type']) ?></div>
                                <div class="thread-preview">
                                    <?= htmlspecialchars($t['last_message'] ?? 'No messages yet.') ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">No conversations found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Panel: Active Chat Window -->
            <div class="chat-panel">
                <?php if ($active_request_id > 0): ?>
                    
                    <div class="panel-header chat-header">
                        <h2>Chat with <?= htmlspecialchars($active_client_name) ?></h2>
                        <span class="req-badge">Request #REQ-<?= str_pad($active_request_id, 3, '0', STR_PAD_LEFT) ?></span>
                    </div>

                    <div class="chat-messages">
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $m): ?>
                                <?php $is_photographer = ($m['Sender_Type'] === 'Photographer'); ?>
                                <div class="message-bubble-wrapper <?= $is_photographer ? 'outgoing' : 'incoming' ?>">
                                    <div class="message-bubble">
                                        <p><?= nl2br(htmlspecialchars($m['Message_Content'])) ?></p>
                                        <span class="message-time"><?= date('h:i A, M d', strtotime($m['Sent_At'])) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">Start the conversation by sending a message below.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Reply Form -->
                    <form action="process-message.php" method="POST" class="chat-input-area">
                        <input type="hidden" name="request_id" value="<?= $active_request_id ?>">
                        <input type="text" name="message_content" placeholder="Type your message here..." required autocomplete="off">
                        <button type="submit" class="btn-send">SEND</button>
                    </form>

                <?php else: ?>
                    <div class="empty-state flex-center">
                        <p>Select a client conversation from the left to view messages.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </main>

</body>
</html>