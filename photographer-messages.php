<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Fetch all service requests with client emails for thread navigation
$requests = $db->query("
    SELECT sr.Request_ID, et.Name AS event_type, u.Email AS client_email
    FROM service_request sr
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    LEFT JOIN user u ON sr.Client_ID = u.User_ID
    ORDER BY sr.Request_ID DESC
")->fetchAll();

$selected_request_id = intval($_GET['request_id'] ?? ($requests[0]['Request_ID'] ?? 0));

// Fetch messages for selected request thread
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
    <title>Client Messages - Photographer Portal</title>
    <link rel="stylesheet" href="photographer-schedule.css">
    <style>
        .chat-container { display: flex; gap: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); min-height: 520px; padding: 20px; }
        .threads-sidebar { width: 32%; border-right: 1px solid #edf2f7; padding-right: 15px; overflow-y: auto; max-height: 500px; }
        .thread-item { display: block; padding: 12px; border-radius: 8px; text-decoration: none; color: #333; font-size: 13px; margin-bottom: 8px; background: #f9fafb; border: 1px solid #e5e7eb; }
        .thread-item.active, .thread-item:hover { background: #2563eb; color: white; border-color: #2563eb; }
        .thread-item.active small, .thread-item:hover small { color: #e0e7ff !important; }
        .chat-box { width: 68%; display: flex; flex-direction: column; justify-content: space-between; }
        .message-list { flex-grow: 1; overflow-y: auto; padding: 10px; display: flex; flex-direction: column; gap: 10px; max-height: 400px; }
        .msg { max-width: 70%; padding: 10px 14px; border-radius: 10px; font-size: 14px; }
        .msg-admin { align-self: flex-end; background: #2563eb; color: white; border-bottom-right-radius: 2px; }
        .msg-client { align-self: flex-start; background: #f3f4f6; color: #111827; border-bottom-left-radius: 2px; }
        .chat-input { display: flex; gap: 10px; margin-top: 15px; }
        .chat-input input { flex-grow: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; }
        .chat-input button { background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Photographer Portal</span>
        </div>

        <nav class="navigation">
            <a href="photographer-request.php">BOOKING REQUESTS</a>
            <a href="photographer-messages.php" class="active">MESSAGES</a>
            <a href="photographer-payments.php">PAYMENTS</a>
            <a href="photographer-schedule.php">SCHEDULE</a>
        </nav>

        <div class="nav-user-actions">
            <div class="user-avatar">A</div>
            <a href="logout.php" class="logout-link">LOGOUT</a>
        </div>
    </header>

    <main class="page-container">
        <h2>Client Inquiries & Threads</h2>

        <div class="chat-container">
            <div class="threads-sidebar">
                <h4 style="margin-top: 0; color: #6b7280; font-size: 12px; text-transform: uppercase;">All Booking Conversations</h4>
                <?php if (!empty($requests)): ?>
                    <?php foreach ($requests as $req): ?>
                        <a href="photographer-messages.php?request_id=<?= $req['Request_ID'] ?>" class="thread-item <?= $req['Request_ID'] == $selected_request_id ? 'active' : '' ?>">
                            <strong>#REQ-<?= str_pad($req['Request_ID'], 3, '0', STR_PAD_LEFT) ?></strong> - <?= htmlspecialchars($req['event_type'] ?? 'Booking') ?><br>
                            <small style="color: #6b7280;"><?= htmlspecialchars($req['client_email'] ?? 'Client') ?></small>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 13px; color: #6b7280;">No booking threads found.</p>
                <?php endif; ?>
            </div>

            <div class="chat-box">
                <?php if ($selected_request_id > 0): ?>
                    <div class="message-list">
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $msg): ?>
                                <?php $is_admin = ($msg['Sender_ID'] == $_SESSION['user_id']); ?>
                                <div class="msg <?= $is_admin ? 'msg-admin' : 'msg-client' ?>">
                                    <small style="display: block; font-size: 10px; opacity: 0.8; margin-bottom: 2px;">
                                        <?= $is_admin ? 'You (Photographer)' : htmlspecialchars($msg['sender_email'] ?? 'Client') ?> • <?= date('M d, h:i A', strtotime($msg['Sent_At'])) ?>
                                    </small>
                                    <?= htmlspecialchars($msg['Message_Text'] ?? $msg['Content'] ?? '') ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #6b7280; margin: auto;">No messages in this thread yet. Send a reply below!</p>
                        <?php endif; ?>
                    </div>

                    <form action="process-admin-messages.php" method="POST" class="chat-input">
                        <input type="hidden" name="request_id" value="<?= $selected_request_id ?>">
                        <input type="text" name="message" placeholder="Reply to client..." required autocomplete="off">
                        <button type="submit">Send Reply</button>
                    </form>
                <?php else: ?>
                    <p style="text-align: center; color: #6b7280; margin: auto;">Select a request thread from the left sidebar to view messages.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>
</html>