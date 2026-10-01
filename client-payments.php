<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$client_id = $_SESSION['user_id'];

// Fetch available payment methods
$payment_methods = $db->query("SELECT * FROM payment_method ORDER BY Payment_Method_ID ASC")->fetchAll();

// Detect payment column name dynamically to avoid Unknown Column errors
$paymentColumn = 'Amount';
try {
    $checkCol = $db->query("SHOW COLUMNS FROM payment LIKE 'Amount_Paid'")->fetch();
    if ($checkCol) {
        $paymentColumn = 'Amount_Paid';
    }
} catch (Exception $e) {
    $paymentColumn = 'Amount';
}

// Fetch client service requests and total payments made
$stmt = $db->prepare("
    SELECT 
        sr.Request_ID,
        sr.Preferred_Date,
        sr.Preferred_Time,
        et.Name AS event_type,
        pkg.Name AS package_name,
        pkg.Price AS package_price,
        rs.Status_Name AS request_status,
        COALESCE(SUM(p.{$paymentColumn}), 0) AS total_paid
    FROM service_request sr
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
    LEFT JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID
    LEFT JOIN payment p ON sr.Request_ID = p.Request_ID
    WHERE sr.Client_ID = ?
    GROUP BY sr.Request_ID
    ORDER BY sr.Request_ID DESC
");
$stmt->execute([$client_id]);
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payments - Client Portal</title>
    <link rel="stylesheet" href="client-payments.css">
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Client Portal</span>
        </div>

        <nav class="navigation">
            <a href="client-request.php">BOOK EVENT</a>
            <a href="client-messages.php">MESSAGES</a>
            <a href="client-payments.php" class="active">MY PAYMENTS</a>
        </nav>

        <div class="nav-user-actions">
            <div class="user-avatar">C</div>
            <a href="logout.php" class="logout-link">LOGOUT</a>
        </div>
    </header>

    <main class="page-container">
        <h2>Invoices & Payment Records</h2>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Req #</th>
                        <th>Event & Package</th>
                        <th>Total Cost</th>
                        <th>Amount Paid</th>
                        <th>Balance Due</th>
                        <th>Payment Status</th>
                        <th>Submit Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $p): ?>
                            <?php 
                                $total = floatval($p['package_price'] ?? 0);
                                $paid = floatval($p['total_paid'] ?? 0);
                                $balance = max(0, $total - $paid);
                                
                                $status_text = 'Unpaid';
                                $badge_class = 'badge-unpaid';
                                if ($paid >= $total && $total > 0) {
                                    $status_text = 'Paid In Full';
                                    $badge_class = 'badge-paid';
                                } elseif ($paid > 0) {
                                    $status_text = 'Partial Deposit';
                                    $badge_class = 'badge-partial';
                                }
                            ?>
                            <tr>
                                <td><strong>#REQ-<?= str_pad($p['Request_ID'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($p['event_type'] ?? 'N/A') ?></strong><br>
                                    <small style="color: #6b7280;"><?= htmlspecialchars($p['package_name'] ?? '') ?></small>
                                </td>
                                <td>₱<?= number_format($total, 2) ?></td>
                                <td>₱<?= number_format($paid, 2) ?></td>
                                <td><strong style="color: <?= $balance > 0 ? '#dc2626' : '#16a34a' ?>;">₱<?= number_format($balance, 2) ?></strong></td>
                                <td><span class="status-badge <?= $badge_class ?>"><?= $status_text ?></span></td>
                                <td>
                                    <?php if ($balance > 0): ?>
                                        <form action="process-client-payments.php" method="POST" class="payment-form">
                                            <input type="hidden" name="request_id" value="<?= $p['Request_ID'] ?>">
                                            <input type="number" name="amount" min="1" max="<?= $balance ?>" step="0.01" placeholder="Amount" required>
                                            <select name="payment_method_id" required style="padding: 6px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 13px;">
                                                <option value="" disabled selected>Method</option>
                                                <?php foreach ($payment_methods as $pm): ?>
                                                    <option value="<?= $pm['Payment_Method_ID'] ?>"><?= htmlspecialchars($pm['Method_Name'] ?? $pm['Name'] ?? 'Method ' . $pm['Payment_Method_ID']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-pay">Pay</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #10b981; font-weight: 700; font-size: 13px;">✓ Settled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #6b7280;">No booking invoices found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>