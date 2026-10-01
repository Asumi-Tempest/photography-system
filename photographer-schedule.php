<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Fetch confirmed and pending schedules
$schedules = $db->query("
    SELECT 
        sr.Request_ID,
        sr.Preferred_Date,
        sr.Preferred_Time,
        sr.Event_Location,
        sr.Special_Instructions,
        et.Name AS event_type,
        pkg.Name AS package_name,
        es.Status_Name AS event_status,
        u.Email AS client_email
    FROM service_request sr
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
    LEFT JOIN event_status es ON sr.Event_Status_ID = es.Event_Status_ID
    LEFT JOIN user u ON sr.Client_ID = u.User_ID
    ORDER BY sr.Preferred_Date ASC, sr.Preferred_Time ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Schedule - Photographer Portal</title>
    <link rel="stylesheet" href="photographer-schedule.css">
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Photographer Portal</span>
        </div>

        <nav class="navigation">
            <a href="photographer-request.php">BOOKING REQUESTS</a>
            <a href="photographer-messages.php">MESSAGES</a>
            <a href="photographer-payments.php">PAYMENTS</a>
            <a href="photographer-schedule.php" class="active">SCHEDULE</a>
        </nav>

        <div class="nav-user-actions">
            <div class="user-avatar">A</div>
            <a href="logout.php" class="logout-link">LOGOUT</a>
        </div>
    </header>

    <main class="page-container">
        <h2>Upcoming Event Schedule</h2>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Req #</th>
                        <th>Event Type</th>
                        <th>Package</th>
                        <th>Location</th>
                        <th>Client Email</th>
                        <th>Event Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($schedules)): ?>
                        <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td>
                                    <strong><?= date('M d, Y', strtotime($s['Preferred_Date'])) ?></strong><br>
                                    <small style="color: #6b7280;"><?= date('h:i A', strtotime($s['Preferred_Time'])) ?></small>
                                </td>
                                <td>#REQ-<?= str_pad($s['Request_ID'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td><strong><?= htmlspecialchars($s['event_type'] ?? 'N/A') ?></strong></td>
                                <td><?= htmlspecialchars($s['package_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($s['Event_Location']) ?></td>
                                <td><?= htmlspecialchars($s['client_email'] ?? 'Client') ?></td>
                                <td>
                                    <span class="status-tag"><?= htmlspecialchars($s['event_status'] ?? 'Scheduled') ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #6b7280;">No scheduled events found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>