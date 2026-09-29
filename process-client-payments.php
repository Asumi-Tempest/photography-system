<?php
require_once 'config.php';

// Auth Guard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();

    $request_id = intval($_POST['request_id'] ?? 0);
    $payment_method_id = intval($_POST['payment_method_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);

    // Handle File Upload
    $uploaded_filename = '';
    if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION);
        $uploaded_filename = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
        $target_path = $upload_dir . $uploaded_filename;

        move_uploaded_file($_FILES['receipt_file']['tmp_name'], $target_path);
    }

    if ($request_id > 0 && $payment_method_id > 0 && $amount > 0 && !empty($uploaded_filename)) {
        // Payment_Status_ID: 1 = Unverified
        $stmt = $db->prepare("
            INSERT INTO Payment 
            (Request_ID, Payment_Method_ID, Amount, Receipt_Path, Payment_Status_ID) 
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $request_id,
            $payment_method_id,
            $amount,
            $uploaded_filename
        ]);
    }
}

header('Location: client-payments.php');
exit;
?>