<?php
require_once 'config.php';

// Auth Guard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id > 0 && in_array($action, ['approve', 'reject'])) {
    $db = getDBConnection();

    // Map actions to Request_Status lookup table:
    // Request_Status_ID: 1 = Pending, 2 = Confirmed, 3 = Rejected, 4 = Completed
    $new_status_id = ($action === 'approve') ? 2 : 3;

    $stmt = $db->prepare("
        UPDATE Service_Request 
        SET Request_Status_ID = ? 
        WHERE Request_ID = ?
    ");
    $stmt->execute([$new_status_id, $id]);
}

// Redirect back to request monitoring page
header('Location: photographer-requests.php');
exit;
?>