<?php
require_once 'config.php';

// Auth Guard - Client Access Only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$client_id = $_SESSION['user_id'];

// 1. Fetch client's approved service requests for the dropdown
$requests_query = "
    SELECT 
        sr.Request_ID,
        et.Name AS event_type,
        pkg.Price AS total_amount
    FROM Service_Request sr
    JOIN Event_Type et ON sr.Event_Type_ID = et.Event_Type_ID
    JOIN Package pkg ON sr.Package_ID = pkg.Package_ID
    WHERE sr.Client_ID = ? AND sr.Request_Status_ID = 2 -- Confirmed
    ORDER BY sr.Request_ID DESC
";
$stmt = $db->prepare($requests_query);
$stmt->execute([$client_id]);
$approved_requests = $stmt->fetchAll();

// 2. Fetch payment methods lookup list
$payment_methods = $db->query("SELECT Payment_Method_ID, Method_Name FROM Payment_Method ORDER BY Payment_Method_ID ASC")->fetchAll();

// 3. Fetch past payment uploads submitted by this client
$history_query = "
    SELECT 
        p.Payment_ID,
        sr.Request_ID,
        p.Amount,
        pm.Method_Name AS method,
        p.Receipt_Path AS proof_file,
        ps.Status_Name AS status,
        p.Verification_Date
    FROM Payment p
    JOIN Service_Request sr ON p.Request_ID = sr.Request_ID
    JOIN Payment_Method pm ON p.Payment_Method_ID = pm.Payment_Method_ID
    JOIN Payment_Status ps ON p.Payment_Status_ID = ps.Payment_Status_ID
    WHERE sr.Client_ID = ?
    ORDER BY p.Payment_ID DESC
";
$stmt = $db->prepare($history_query);
$stmt->execute([$client_id]);
$payment_history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payments & Upload Receipt</title>
    <link rel="stylesheet" href="client-payments.css">
</head>
<body>

    <!-- Client Header -->
    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Client Portal</span>
        </div>

        <nav class="navigation">
            <a href="client-request.php">BOOK SERVICE</a>
            <a href="client-payments.php" class="active">MY PAYMENTS</a>
            <a href="client-messages.php">MESSAGES</a>
        </nav>

        <div class="nav-user-actions">
            <div class="user-avatar">C</div>
        </div>
    </header>

    <main class="page-container">

        <div class="payment-grid">
            
            <!-- Upload Proof of Payment Form -->
            <section class="card">
                <div class="card-header">
                    <h2>Upload Proof of Payment</h2>
                </div>
                
                <form action="process-client-payment.php" method="POST" enctype="multipart/form-data" class="payment-form">
                    
                    <div class="form-group">
                        <label for="request_id">SERVICE REQUEST</label>
                        <select name="request_id" id="request_id" required>
                            <option value="" disabled selected>-- Select Confirmed Booking --</option>
                            <?php foreach ($approved_requests as $req): ?>
                                <option value="<?= $req['Request_ID'] ?>">
                                    #REQ-<?= str_pad($req['Request_ID'], 3, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($req['event_type']) ?> (₱<?= number_format($req['total_amount'], 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="payment_method_id">PAYMENT METHOD</label>
                        <select name="payment_method_id" id="payment_method_id" required>
                            <option value="" disabled selected>-- Select Payment Method --</option>
                            <?php foreach ($payment_methods as $pm): ?>
                                <option value="<?= $pm['Payment_Method_ID'] ?>"><?= htmlspecialchars($pm['Method_Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="amount">AMOUNT PAID (₱)</label>
                        <input type="number" name="amount" id="amount" step="0.01" min="1" placeholder="e.g. 5000" required>
                    </div>

                    <div class="form-group">
                        <label for="receipt_file">PROOF OF PAYMENT RECEIPT (JPG, PNG, PDF)</label>
                        <input type="file" name="receipt_file" id="receipt_file" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>

                    <button type="submit" class="btn-submit">SUBMIT PAYMENT PROOF</button>
                </form>
            </section>

            <!-- Payment Submission History -->
            <section class="card">
                <div class="card-header">
                    <h2>Submitted Payment Records</h2>
                </div>
                <div class="table-container">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Receipt</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($payment_history)): ?>
                                <?php foreach ($payment_history as $pay): ?>
                                    <tr>
                                        <td class="font-bold">#REQ-<?= str_pad($pay['Request_ID'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td>₱<?= number_format($pay['Amount'], 0) ?></td>
                                        <td><?= htmlspecialchars($pay['method']) ?></td>
                                        <td>
                                            <a href="uploads/<?= htmlspecialchars($pay['proof_file']) ?>" target="_blank" class="file-link">
                                                View Receipt
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge-status status-<?= strtolower($pay['status']) ?>">
                                                <?= htmlspecialchars($pay['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="empty-cell">No payment records submitted yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

    </main>

</body>
</html>