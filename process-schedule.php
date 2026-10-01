<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handler for schedule updates/filtering
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id'] ?? 0);
    $event_status_id = intval($_POST['event_status_id'] ?? 0);

    if ($request_id > 0 && $event_status_id > 0) {
        $db = getDBConnection();
        $stmt = $db->prepare("UPDATE service_request SET Event_Status_ID = ? WHERE Request_ID = ?");
        $stmt->execute([$event_status_id, $request_id]);
    }
}

header('Location: photographer-schedule.php');
exit;
?>