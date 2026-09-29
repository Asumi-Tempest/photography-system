<?php
require_once 'config.php';

// Auth Guard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();
    
    $request_id = intval($_POST['request_id'] ?? 0);
    $content = trim($_POST['message_content'] ?? '');

    if ($request_id > 0 && !empty($content)) {
        $stmt = $db->prepare("
            INSERT INTO Message (Request_ID, Sender_Type, Message_Content, Sent_At) 
            VALUES (?, 'Photographer', ?, NOW())
        ");
        $stmt->execute([$request_id, $content]);
    }

    header('Location: photographer-messages.php?request_id=' . $request_id);
    exit;
}

header('Location: photographer-messages.php');
exit;
?>