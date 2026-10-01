<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Fetch all available statuses for dropdown
$statuses = $db->query("SELECT * FROM request_status ORDER BY Request_Status_ID ASC")->fetchAll();

// Fetch all service requests with details
$requests = $db->query("
    SELECT 
        sr.Request_ID,
        sr.Preferred_Date,
        sr.Preferred_Time,
        sr.Event_Location,
        sr.Special_Instructions,
        sr.Request_Status_ID,
        et.Name AS event_type,
        pkg.Name AS package_name,
        pkg.Price AS package_price,
        rs.Status_Name AS status_name,
        u.Email AS client_email
    FROM service_request sr
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
    LEFT JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID
    LEFT JOIN user u ON sr.Client_ID = u.User_ID
    ORDER BY sr.Request_ID DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Requests - Photographer Portal</title>
    <link rel="stylesheet" href="photographer-schedule.css">
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Photographer Portal</span>
        </div>

        <nav class="navigation">
            <a href="photographer-request.php" class="active">BOOKING REQUESTS</a>
            <a href="photographer-messages.php">MESSAGES</a>
            <a href="photographer-payments.php">PAYMENTS</a>
            <a href="photographer-schedule.php">SCHEDULE</a>
        </nav>

        <div class="nav-user-actions">
            <div class="user-avatar">A</div>
            <a href="logout.php" class="logout-link">LOGOUT</a>
        </div>
    </header>

    <main class="page-container">
        <h2>Client Booking Requests</h2>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 6px; margin-bottom: 20px;">✓ <?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger" style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px;">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Req #</th>
                        <th>Client</th>
                        <th>Event Details</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td><strong>#REQ-<?= str_pad($r['Request_ID'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><?= htmlspecialchars($r['client_email'] ?? 'Client') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r['event_type'] ?? 'N/A') ?></strong><br>
                                    <small style="color: #6b7280;"><?= htmlspecialchars($r['package_name'] ?? '') ?> (₱<?= number_format($r['package_price'] ?? 0, 2) ?>)</small>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($r['Preferred_Date'])) ?><br>
                                    <small style="color: #6b7280;"><?= date('h:i A', strtotime($r['Preferred_Time'])) ?></small>
                                </td>
                                <td><?= htmlspecialchars($r['Event_Location']) ?></td>
                                <td>
                                    <span class="status-tag"><?= htmlspecialchars($r['status_name'] ?? 'Pending') ?></span>
                                </td>
                                <td>
                                    <form action="process-request.php" method="POST" style="display: flex; gap: 6px; align-items: center;">
                                        <input type="hidden" name="request_id" value="<?= $r['Request_ID'] ?>">
                                        <select name="status_id" required style="padding: 6px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 13px;">
                                            <?php foreach ($statuses as $st): ?>
                                                <option value="<?= $st['Request_Status_ID'] ?>" <?= $st['Request_Status_ID'] == $r['Request_Status_ID'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($st['Status_Name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" style="background: #2563eb; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #6b7280;">No request records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>