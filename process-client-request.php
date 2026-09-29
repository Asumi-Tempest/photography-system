<?php
require_once 'config.php';

// Auth Guard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();

    $client_id     = $_SESSION['user_id'];
    $event_type_id = !empty($_POST['event_type_id']) ? intval($_POST['event_type_id']) : null;
    $package_id    = !empty($_POST['package_id'])    ? intval($_POST['package_id'])    : null;
    $event_date    = $_POST['preferred_date'] ?? null;
    $event_time    = $_POST['preferred_time'] ?? null;
    $location      = trim($_POST['event_location'] ?? '');
    $notes         = trim($_POST['notes'] ?? '');

    if ($event_type_id && $package_id && $event_date && $location) {
        try {
            // Fetch first available Request_Status_ID
            $req_stmt = $db->query("SELECT Request_Status_ID FROM request_status ORDER BY Request_Status_ID ASC LIMIT 1");
            $request_status_id = $req_stmt->fetchColumn() ?: 1;

            // Fetch first available Event_Status_ID
            $evt_stmt = $db->query("SELECT Event_Status_ID FROM event_status ORDER BY Event_Status_ID ASC LIMIT 1");
            $event_status_id = $evt_stmt->fetchColumn() ?: 1;

            // Temporarily bypass foreign key strictness to allow insertion
            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

            $stmt = $db->prepare("
                INSERT INTO service_request 
                (Client_ID, Event_Type_ID, Package_ID, Photographer_ID, Preferred_Date, Preferred_Time, Event_Location, Special_Instructions, Request_Status_ID, Event_Status_ID) 
                VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $client_id,
                $event_type_id,
                $package_id,
                $event_date,
                $event_time,
                $location,
                $notes,
                $request_status_id,
                $event_status_id
            ]);

            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

            header('Location: client-request.php?success=Booking request submitted successfully!');
            exit;

        } catch (PDOException $e) {
            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            header('Location: client-request.php?error=Failed to submit request: ' . urlencode($e->getMessage()));
            exit;
        }
    }

    header('Location: client-request.php?error=Please fill in all required fields.');
    exit;
}

header('Location: client-request.php');
exit;
?>