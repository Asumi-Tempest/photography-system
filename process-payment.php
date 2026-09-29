<?php
require_once 'config.php';

// Auth Guard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id > 0 && in_array($action, ['verify', 'reverify', 'reject'])) {
    $db = getDBConnection();

    $verifier_name = $_SESSION['user_name'] ?? 'Photographer Admin';

    if ($action === 'verify') {
        // Payment_Status_ID: 2 = Verified
        $stmt = $db->prepare("
            UPDATE Payment 
            SET Payment_Status_ID = 2, 
                Verified_By = ?, 
                Verification_Date = NOW() 
            WHERE Payment_ID = ?
        ");
        $stmt->execute([$verifier_name, $id]);

    } elseif ($action === 'reject') {
        // Payment_Status_ID: 3 = Rejected
        $stmt = $db->prepare("
            UPDATE Payment 
            SET Payment_Status_ID = 3, 
                Verified_By = ?, 
                Verification_Date = NOW() 
            WHERE Payment_ID = ?
        ");
        $stmt->execute([$verifier_name, $id]);

    } elseif ($action === 'reverify') {
        // Reset to Payment_Status_ID: 1 = Unverified
        $stmt = $db->prepare("
            UPDATE Payment 
            SET Payment_Status_ID = 1, 
                Verified_By = NULL, 
                Verification_Date = NULL 
            WHERE Payment_ID = ?
        ");
        $stmt->execute([$id]);
    }
}

// Redirect back to payment monitoring page
header('Location: photographer-payments.php');
exit;
?>