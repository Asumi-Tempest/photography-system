<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();
    
    $request_id = intval($_POST['request_id'] ?? 0);
    $sender_id  = $_SESSION['user_id'];
    $message    = trim($_POST['message'] ?? '');

    if ($request_id > 0 && !empty($message)) {
        // Detect column name dynamically for message body (Message_Text vs Content)
        $msgColumn = 'Message_Text';
        try {
            $checkCol = $db->query("SHOW COLUMNS FROM message LIKE 'Content'")->fetch();
            if ($checkCol) {
                $msgColumn = 'Content';
            }
        } catch (Exception $e) {
            $msgColumn = 'Message_Text';
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO message (Request_ID, Sender_ID, {$msgColumn}, Sent_At) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$request_id, $sender_id, $message]);

            header("Location: client-messages.php?request_id={$request_id}");
            exit;
        } catch (PDOException $e) {
            header("Location: client-messages.php?request_id={$request_id}&error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

header('Location: client-messages.php');
exit;
?>