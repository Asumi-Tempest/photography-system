<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();
    $request_id  = intval($_POST['request_id'] ?? 0);
    $amount_paid = floatval($_POST['amount'] ?? 0);

    if ($request_id > 0 && $amount_paid > 0) {
        try {
            $stmt = $db->prepare("
                INSERT INTO payment (Request_ID, Amount_Paid, Payment_Date) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$request_id, $amount_paid]);

            header("Location: client-payments.php?success=" . urlencode("Payment of ₱" . number_format($amount_paid, 2) . " logged successfully!"));
            exit;
        } catch (PDOException $e) {
            header("Location: client-payments.php?error=" . urlencode("Failed to log payment: " . $e->getMessage()));
            exit;
        }
    }
}

header('Location: client-payments.php');
exit;
?>