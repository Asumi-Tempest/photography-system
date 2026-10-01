<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();
    $request_id = intval($_POST['request_id'] ?? 0);
    $message    = trim($_POST['message'] ?? '');

    if ($request_id > 0 && !empty($message)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO message (Request_ID, Sender, Message, Date_Sent) 
                VALUES (?, 'Photographer', ?, NOW())
            ");
            $stmt->execute([$request_id, $message]);

            header("Location: admin-messages.php?request_id={$request_id}&msg_sent=1");
            exit;
        } catch (PDOException $e) {
            header("Location: admin-messages.php?request_id={$request_id}&error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

header('Location: admin-messages.php');
exit;
?>