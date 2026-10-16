<?php
require_once 'config.php';
requireStaff(); // staff-only page

// Only accept form submissions (not links typed in the address bar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // Payment_Status_ID: 1 = Unverified, 2 = Verified, 3 = Rejected
    $statusByAction = ['verify' => 2, 'reject' => 3, 'reverify' => 1];

    if ($id > 0 && isset($statusByAction[$action])) {
        $db = getDBConnection();
        $stmt = $db->prepare("UPDATE payment SET Payment_Status_ID = ? WHERE Payment_ID = ?");
        $stmt->execute([$statusByAction[$action], $id]);
    }
}

// Back to the payment monitoring page
header('Location: photographer-payments.php');
exit;
?>
