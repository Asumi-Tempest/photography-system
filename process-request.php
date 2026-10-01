<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();
    
    $request_id = intval($_POST['request_id'] ?? 0);
    $status_id  = intval($_POST['status_id'] ?? 0);

    if ($request_id > 0 && $status_id > 0) {
        try {
            $stmt = $db->prepare("
                UPDATE service_request 
                SET Request_Status_ID = ? 
                WHERE Request_ID = ?
            ");
            $stmt->execute([$status_id, $request_id]);

            header("Location: photographer-request.php?success=" . urlencode("Booking status updated successfully!"));
            exit;
        } catch (PDOException $e) {
            header("Location: photographer-request.php?error=" . urlencode("Failed to update status: " . $e->getMessage()));
            exit;
        }
    }
}

header('Location: photographer-request.php');
exit;
?>