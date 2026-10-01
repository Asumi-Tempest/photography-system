<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$client_id = $_SESSION['user_id'];

// Fetch client requests for message thread selection
$requests = $db->query("
    SELECT sr.Request_ID, et.Name AS event_type 
    FROM service_request sr 
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID 
    WHERE sr.Client_ID = {$client_id}
    ORDER BY sr.Request_ID DESC
")->fetchAll();

$selected_request_id = intval($_GET['request_id'] ?? ($requests[0]['Request_ID'] ?? 0));

// Fetch message thread for selected request
$messages = [];
if ($selected_request_id > 0) {
    $stmt = $db->prepare("
        SELECT m.*, u.Email AS sender_email, u.Role AS sender_role
        FROM message m
        LEFT JOIN user u ON m.Sender_ID = u.User_ID
        WHERE m.Request_ID = ?
        ORDER BY m.Sent_At ASC
    ");
    $stmt->execute([$selected_request_id]);
    $messages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Client Portal</title>
    <link rel="stylesheet" href="client-messages.css">
    <style>
        .chat-container { display: flex; gap: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); min-height: 500px; padding: 20px; }
        .threads-sidebar { width: 30%; border-right: 1px solid #edf2f7; padding-right: 15px; }
        .thread-item { display: block; padding: 12px; border-radius: 8px; text-decoration: none; color: #333; font-weight: 600; margin-bottom: 8px; background: #f9fafb; }
        .thread-item.active, .thread-item:hover { background: #2563eb; color: white; }
        .chat-box { width: 70%; display: flex; flex-direction: column; justify-content: space-between; }
        .message-list { flex-grow: 1; overflow-y: auto; padding: 10px; display: flex; flex-direction: column; gap: 10px; max-height: 400px; }
        .msg { max-width: 70%; padding: 10px 14px; border-radius: 10px; font-size: 14px; }
        .msg-client { align-self: flex-end; background: #2563eb; color: white; border-bottom-right-radius: 2px; }
        .msg-admin { align-self: flex-start; background: #e5e7eb; color: #111827; border-bottom-left-radius: 2px; }
        .chat-input { display: flex; gap: 10px; margin-top: 15px; }
        .chat-input input { flex-grow: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; }
        .chat-input button { background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Client Portal</span>
        </div>

        <nav class="navigation">
            <a href="client-request.php">BOOK EVENT</a>
            <a href="client-messages.php" class="active">MESSAGES</a>
            <a href="client-payments.php">MY PAYMENTS</a>
        </nav>

        <div class="nav-user-actions">
            <div class="user-avatar">C</div>
            <a href="logout.php" class="logout-link">LOGOUT</a>
        </div>
    </header>

    <main class="page-container" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
        <h2>Booking Conversations</h2>

        <div class="chat-container">
            <div class="threads-sidebar">
                <h4 style="margin-top: 0; color: #6b7280; font-size: 12px; text-transform: uppercase;">Your Requests</h4>
                <?php if (!empty($requests)): ?>
                    <?php foreach ($requests as $req): ?>
                        <a href="client-messages.php?request_id=<?= $req['Request_ID'] ?>" class="thread-item <?= $req['Request_ID'] == $selected_request_id ? 'active' : '' ?>">
                            #REQ-<?= str_pad($req['Request_ID'], 3, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($req['event_type'] ?? 'Booking') ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 13px; color: #6b7280;">No active requests.</p>
                <?php endif; ?>
            </div>

            <div class="chat-box">
                <?php if ($selected_request_id > 0): ?>
                    <div class="message-list">
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $msg): ?>
                                <?php $is_client = ($msg['Sender_ID'] == $client_id); ?>
                                <div class="msg <?= $is_client ? 'msg-client' : 'msg-admin' ?>">
                                    <small style="display: block; font-size: 10px; opacity: 0.8; margin-bottom: 2px;">
                                        <?= $is_client ? 'You' : 'Photographer' ?> • <?= date('M d, h:i A', strtotime($msg['Sent_At'])) ?>
                                    </small>
                                    <?= htmlspecialchars($msg['Message_Text'] ?? $msg['Content'] ?? '') ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #6b7280; margin-auto: 0;">No messages in this conversation yet. Send a inquiry below!</p>
                        <?php endif; ?>
                    </div>

                    <form action="process-client-messages.php" method="POST" class="chat-input">
                        <input type="hidden" name="request_id" value="<?= $selected_request_id ?>">
                        <input type="text" name="message" placeholder="Type your message..." required autocomplete="off">
                        <button type="submit">Send</button>
                    </form>
                <?php else: ?>
                    <p style="text-align: center; color: #6b7280; margin: auto;">Select a booking request from the sidebar to view messages.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>
</html>