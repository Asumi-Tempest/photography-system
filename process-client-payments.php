<?php
// Enable full error reporting to prevent white screens / infinite buffer
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();
    $request_id        = intval($_POST['request_id'] ?? 0);
    $amount_paid       = floatval($_POST['amount'] ?? 0);
    $payment_method_id = intval($_POST['payment_method_id'] ?? 0);

    // Fallback: If no payment method selected, fetch the first available valid ID from database
    if ($payment_method_id === 0) {
        $firstMethod = $db->query("SELECT Payment_Method_ID FROM payment_method LIMIT 1")->fetch();
        $payment_method_id = $firstMethod ? intval($firstMethod['Payment_Method_ID']) : 1;
    }

    if ($request_id > 0 && $amount_paid > 0) {
        // Determine column name dynamically
        $paymentColumn = 'Amount';
        try {
            $checkCol = $db->query("SHOW COLUMNS FROM payment LIKE 'Amount_Paid'")->fetch();
            if ($checkCol) {
                $paymentColumn = 'Amount_Paid';
            }
        } catch (Exception $e) {
            $paymentColumn = 'Amount';
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO payment (Request_ID, {$paymentColumn}, Payment_Method_ID, Payment_Date) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$request_id, $amount_paid, $payment_method_id]);

            header("Location: client-payments.php?success=" . urlencode("Payment of ₱" . number_format($amount_paid, 2) . " logged successfully!"));
            exit;
        } catch (PDOException $e) {
            header("Location: client-payments.php?error=" . urlencode("Database Error: " . $e->getMessage()));
            exit;
        }
    } else {
        header("Location: client-payments.php?error=" . urlencode("Invalid amount or request ID."));
        exit;
    }
}

header('Location: client-payments.php');
exit;
?>