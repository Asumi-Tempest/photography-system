<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$photographer_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'block') {
    $date = $_POST['available_date'] ?? '';
    $start = $_POST['start_time'] ?? '08:00';
    $end = $_POST['end_time'] ?? '18:00';
    $status = trim($_POST['availability_status'] ?? 'Unavailable');

    if (!empty($date)) {
        $stmt = $db->prepare("
            INSERT INTO Photographer_Availability 
            (Photography_ID, Available_Date, Start_Time, End_Time, Availability_Status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$photographer_id, $date, $start, $end, $status]);
    }
} elseif ($action === 'unblock') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $db->prepare("DELETE FROM Photographer_Availability WHERE Availability_ID = ? AND Photography_ID = ?");
        $stmt->execute([$id, $photographer_id]);
    }
}

header('Location: photographer-schedule.php');
exit;
?>