<?php
require_once 'config.php';

// Authentication Guard - Photographer / Admin Access Only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Fetch payments with ERD table relationships
$query = "
    SELECT 
        p.Payment_ID,
        c.Fullname AS client_name,
        p.Amount,
        pm.Method_Name AS method,
        p.Receipt_Path AS proof_file,
        ps.Status_Name AS status
    FROM Payment p
    JOIN Service_Request sr ON p.Request_ID = sr.Request_ID
    JOIN Client c ON sr.Client_ID = c.Client_ID
    JOIN Payment_Method pm ON p.Payment_Method_ID = pm.Payment_Method_ID
    JOIN Payment_Status ps ON p.Payment_Status_ID = ps.Payment_Status_ID
    ORDER BY p.Payment_ID ASC
";

$stmt = $db->query($query);
$payments = $stmt->fetchAll();

// Calculate total collected amount from verified payments (Payment_Status_ID = 2)
$sum_stmt = $db->query("SELECT SUM(Amount) AS total FROM Payment WHERE Payment_Status_ID = 2");
$total_collected = $sum_stmt->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Monitoring & Verification</title>
    <link rel="stylesheet" href="photographer-payments.css">
</head>
<body>

    <!-- Header / Navigation Bar -->
    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Photographer Portal</span>
            <span class="badge-pro">PRO</span>
        </div>

        <nav class="navigation">
            <a href="photographer-dashboard.php">DASHBOARD</a>
            <a href="photographer-requests.php">REQUESTS</a>
            <a href="photographer-schedule.php">SCHEDULE</a>
            <a href="photographer-payments.php" class="active">PAYMENTS</a>
            <a href="photographer-messages.php">MESSAGES</a>
        </nav>

        <div class="nav-user-actions">
            <button class="icon-btn" aria-label="Notifications">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
            </button>
            <div class="user-avatar">P</div>
        </div>
    </header>

    <!-- Page Body Container -->
    <main class="page-container">

        <!-- Title & Total Counter Section -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Payment Monitoring &amp; Verification</h1>
                <p class="page-subtitle">Review and verify proof of payment uploads submitted by clients.</p>
            </div>

            <div class="total-collected-badge">
                TOTAL COLLECTED: <span>₱<?= number_format($total_collected, 0) ?></span>
            </div>
        </div>

        <!-- Table Card Layout -->
        <section class="table-card">
            <div class="table-container">
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 20%;">Client</th>
                            <th style="width: 15%;">Amount</th>
                            <th style="width: 18%;">Method</th>
                            <th style="width: 18%;">Proof File</th>
                            <th style="width: 19%;">Status</th>
                            <th style="width: 10%; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payments)): ?>
                            <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td class="font-bold">#<?= str_pad($pay['Payment_ID'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td class="font-bold"><?= htmlspecialchars($pay['client_name']) ?></td>
                                    <td>₱<?= number_format($pay['Amount'], 0) ?></td>
                                    <td><?= htmlspecialchars($pay['method']) ?></td>
                                    <td>
                                        <a href="uploads/<?= htmlspecialchars($pay['proof_file']) ?>" target="_blank" class="file-link">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                            </svg>
                                            <?= htmlspecialchars($pay['proof_file']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="status-action-group">
                                            <span class="status-badge">
                                                <?= htmlspecialchars($pay['status']) ?>
                                            </span>
                                            <?php if ($pay['status'] === 'Verified'): ?>
                                                <a href="process-payment.php?id=<?= $pay['Payment_ID'] ?>&action=reverify" class="btn-sub-action">RE-VERIFY</a>
                                            <?php else: ?>
                                                <a href="process-payment.php?id=<?= $pay['Payment_ID'] ?>&action=verify" class="btn-sub-action btn-highlight">VERIFY</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="view-payment.php?id=<?= $pay['Payment_ID'] ?>" class="btn-action">VIEW</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="7">
                                    <div class="empty-state">
                                        <p>No payment uploads submitted yet.</p>
                                        <span class="empty-subtext">Client payment verifications will appear here automatically.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div class="pagination">
                <button class="page-btn active" disabled>1</button>
                <button class="page-btn" disabled>2</button>
                <button class="page-btn" disabled>&gt;</button>
            </div>
        </section>

    </main>

</body>
</html>