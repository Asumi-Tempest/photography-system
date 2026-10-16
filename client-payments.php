<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$currentUserId = $_SESSION['user_id'];
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_payment') {
    $requestId = intval($_POST['request_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $methodName = trim($_POST['method'] ?? 'GCash');

    if ($requestId <= 0 || $amount <= 0) {
        $errorMessage = "Please enter a valid payment amount.";
    } elseif (!clientOwnsRequest($db, $requestId)) {
        $errorMessage = "Invalid booking selected.";
    } else {
        try {
            $methodStmt = $db->prepare("SELECT Payment_Method_ID FROM payment_method WHERE LOWER(Method_Name) LIKE :m LIMIT 1");
            $methodStmt->execute(['m' => '%' . strtolower($methodName) . '%']);
            $methodRow = $methodStmt->fetch();
            $methodId = $methodRow ? $methodRow['Payment_Method_ID'] : 1;

            $statusRow = $db->query("SELECT Payment_Status_ID FROM payment_status WHERE LOWER(Status_Name) IN ('pending', 'unverified') LIMIT 1")->fetch();
            $statusId = $statusRow ? $statusRow['Payment_Status_ID'] : 1;

            $insertStmt = $db->prepare("
                INSERT INTO payment (Request_ID, Amount, Payment_Method_ID, Payment_Status_ID, Payment_Date)
                VALUES (:req_id, :amount, :method_id, :status_id, NOW())
            ");
            $insertStmt->execute([
                'req_id'    => $requestId,
                'amount'    => $amount,
                'method_id' => $methodId,
                'status_id' => $statusId
            ]);

            $successMessage = "Payment of ₱" . number_format($amount, 2) . " submitted for verification!";
        } catch (PDOException $e) {
            $errorMessage = "Database Error: " . $e->getMessage();
        }
    }
}

$invoices = [];
try {
    $stmt = $db->prepare("
        SELECT 
            sr.Request_ID,
            COALESCE(et.Name, 'General Event') AS event_type,
            COALESCE(pkg.Name, 'Custom Package') AS package_name,
            COALESCE(pkg.Price, 0.00) AS total_cost,
            COALESCE(pay_totals.total_paid, 0.00) AS total_paid
        FROM service_request sr
        LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
        LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
        LEFT JOIN (
            SELECT Request_ID, SUM(Amount) AS total_paid
            FROM payment
            GROUP BY Request_ID
        ) pay_totals ON sr.Request_ID = pay_totals.Request_ID
        WHERE sr.Client_ID = :uid
        ORDER BY sr.Request_ID DESC
    ");
    $stmt->execute(['uid' => $currentUserId]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errorMessage = "Query Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices & Payments - Client Portal</title>
    <link rel="stylesheet" href="auth.css">
    <style>
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
        .top-nav { background: #1e1e2d; color: #fff; padding: 14px 40px; display: flex; align-items: center; justify-content: space-between; }
        .nav-left { display: flex; align-items: center; gap: 12px; }
        .portal-title { font-weight: 700; font-size: 15px; color: #ffffff; letter-spacing: 0.2px; }
        .nav-links { display: flex; align-items: center; }
        .nav-links a { font-size: 11px; font-weight: 700; color: #92929f; text-decoration: none; letter-spacing: 0.8px; text-transform: uppercase; transition: color 0.2s; position: relative; padding-bottom: 4px; margin-right: 15px; }
        .nav-links a:hover { color: #ffffff; }
        .nav-links a.active { color: #ffffff; border-bottom: 2px solid #ffffff; }
        .nav-right { display: flex; align-items: center; gap: 14px; }
        .user-avatar { width: 32px; height: 32px; background: #3b82f6; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .btn-logout { font-size: 13px; font-weight: 700; color: #f87171; text-decoration: none; }
        .portal-container { max-width: 1050px; margin: 36px auto; padding: 0 20px; }
        .page-title { font-size: 26px; font-weight: 800; color: #0f172a; margin-bottom: 24px; text-align: center; }
        .data-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .data-table th { background-color: #f8fafc; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .pay-form { display: flex; gap: 6px; align-items: center; }
        .input-amt { width: 90px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; }
        .select-method { padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #fff; }
        .btn-pay { background: #10b981; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; }
        .alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-left">
            <img src="logo.svg" alt="Logo" width="34" height="34" style="display:block;width:34px;height:34px;">
            <span class="portal-title">Client Portal</span>
        </div>
        <nav class="nav-links">
            <a href="client-services.php">SERVICES</a>
            <a href="client-request.php">BOOK SERVICE</a>
            <a href="client-payments.php" class="active">MY PAYMENTS</a>
            <a href="client-deliverables.php">GALLERY</a>
            <a href="client-reviews.php">REVIEWS</a>
            <a href="client-messages.php">MESSAGES</a>
        </nav>
        <div class="nav-right">
            <div class="user-avatar">C</div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="portal-container">
        <h1 class="page-title">Invoices & Payment Records</h1>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert-success">✓ <?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <div class="data-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>REQ #</th>
                        <th>EVENT & PACKAGE</th>
                        <th>TOTAL COST</th>
                        <th>AMOUNT PAID</th>
                        <th>BALANCE DUE</th>
                        <th>PAYMENT STATUS</th>
                        <th>SUBMIT PAYMENT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($invoices)): ?>
                        <?php foreach ($invoices as $inv): ?>
                            <?php 
                                $cost = floatval($inv['total_cost']);
                                $paid = floatval($inv['total_paid']);
                                $balance = max(0, $cost - $paid);
                                
                                $statusLabel = 'Unpaid';
                                $statusClass = 'status-unpaid';
                                if ($cost > 0 && $paid >= $cost) {
                                    $statusLabel = 'Paid';
                                    $statusClass = 'status-paid';
                                } elseif ($paid > 0) {
                                    $statusLabel = 'Partial';
                                    $statusClass = 'status-partial';
                                }
                            ?>
                            <tr>
                                <td><strong>#REQ-<?= str_pad($inv['Request_ID'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($inv['event_type']) ?></strong><br>
                                    <small style="color: #64748b;"><?= htmlspecialchars($inv['package_name']) ?></small>
                                </td>
                                <td>₱<?= number_format($cost, 2) ?></td>
                                <td>₱<?= number_format($paid, 2) ?></td>
                                <td style="color: <?= $balance > 0 ? '#dc2626' : '#166534' ?>; font-weight: 700;">₱<?= number_format($balance, 2) ?></td>
                                <td>
                                    <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                                </td>
                                <td>
                                    <?php if ($balance > 0): ?>
                                        <form method="POST" class="pay-form">
                                            <input type="hidden" name="action" value="submit_payment">
                                            <input type="hidden" name="request_id" value="<?= $inv['Request_ID'] ?>">
                                            <input type="number" step="0.01" name="amount" class="input-amt" placeholder="Amount" max="<?= $balance ?>" required>
                                            <select name="method" class="select-method">
                                                <option value="GCash">GCash</option>
                                                <option value="Bank Transfer">Bank Transfer</option>
                                                <option value="Cash">Cash</option>
                                            </select>
                                            <button type="submit" class="btn-pay">Pay</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #166534; font-size: 12px; font-weight: 700;">Fully Paid</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 24px; color: #64748b;">No billing records or invoices found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>