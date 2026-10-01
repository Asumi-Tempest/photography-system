<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Handle Status Updates via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $request_id = intval($_POST['request_id'] ?? 0);
    
    if ($_POST['action_type'] === 'update_status' && $request_id > 0) {
        $req_status_id = intval($_POST['request_status_id']);
        $evt_status_id = intval($_POST['event_status_id']);

        try {
            $stmt = $db->prepare("
                UPDATE service_request 
                SET Request_Status_ID = ?, Event_Status_ID = ? 
                WHERE Request_ID = ?
            ");
            $stmt->execute([$req_status_id, $evt_status_id, $request_id]);
            header("Location: admin-request.php?success=Status updated successfully!");
            exit;
        } catch (PDOException $e) {
            header("Location: admin-request.php?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

// Fetch Status Dropdown Options
$req_statuses = $db->query("SELECT Request_Status_ID, Status_Name FROM request_status ORDER BY Request_Status_ID ASC")->fetchAll();
$evt_statuses = $db->query("SELECT Event_Status_ID, Status_Name FROM event_status ORDER BY Event_Status_ID ASC")->fetchAll();

// Fetch All Service Requests
$requests = $db->query("
    SELECT 
        sr.Request_ID,
        sr.Preferred_Date,
        sr.Preferred_Time,
        sr.Event_Location,
        sr.Special_Instructions,
        et.Name AS event_type,
        pkg.Name AS package_name,
        pkg.Price AS package_price,
        rs.Request_Status_ID,
        rs.Status_Name AS req_status,
        es.Event_Status_ID,
        es.Status_Name AS evt_status,
        u.Email AS client_email
    FROM service_request sr
    LEFT JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    LEFT JOIN package pkg ON sr.Package_ID = pkg.Package_ID
    LEFT JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID
    LEFT JOIN event_status es ON sr.Event_Status_ID = es.Event_Status_ID
    LEFT JOIN user u ON sr.Client_ID = u.User_ID
    ORDER BY sr.Request_ID DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Booking Requests</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; color: #333; }
        
        /* Unified Client/Admin Header Style */
        .navbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .logo-box { background: #111827; color: white; padding: 6px 12px; border-radius: 6px; font-weight: 800; font-size: 14px; letter-spacing: 1px; }
        .portal-title { font-weight: 700; color: #111827; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .navigation { display: flex; gap: 24px; }
        .navigation a { color: #6b7280; text-decoration: none; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; transition: color 0.2s; }
        .navigation a:hover, .navigation a.active { color: #2563eb; }

        .nav-user-actions { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 34px; height: 34px; background: #2563eb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }
        .logout-link { color: #ef4444; font-weight: 700; text-decoration: none; font-size: 13px; }

        /* Page Layout & Table Styles */
        .page-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        h2 { font-size: 22px; font-weight: 700; margin-bottom: 20px; color: #111827; }

        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .card { background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        th { font-weight: 700; color: #6b7280; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; background: #f9fafb; }
        
        select { padding: 6px 10px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 13px; background-color: #fff; outline: none; }
        select:focus { border-color: #2563eb; }

        .btn-save { background: #2563eb; color: white; border: none; padding: 7px 14px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; transition: background 0.2s; }
        .btn-save:hover { background: #1d4ed8; }
        .btn-chat { background: #10b981; color: white; padding: 7px 14px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 700; display: inline-block; transition: background 0.2s; }
        .btn-chat:hover { background: #059669; }
    </style>
</head>
<body>

    <!-- Dynamic Unified Admin Header -->
    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Photographer Portal</span>
        </div>

        <nav class="navigation">
            <a href="admin-request.php" class="active">BOOKING REQUESTS</a>
            <a href="admin-messages.php">MESSAGES</a>
        </nav>

        <div class="nav-user-actions">
            <div class="user-avatar">A</div>
            <a href="logout.php" class="logout-link">LOGOUT</a>
        </div>
    </header>

    <main class="page-container">
        <h2>Manage Client Booking Requests</h2>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success"><span>✓</span> <span><?= htmlspecialchars($_GET['success']) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger"><span>⚠️</span> <span><?= htmlspecialchars($_GET['error']) ?></span></div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Req #</th>
                        <th>Client Email</th>
                        <th>Event & Package</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Request Status</th>
                        <th>Event Status</th>
                        <th>Actions</th>
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
                                    <small style="color: #2563eb; font-weight: 600;"><?= htmlspecialchars($r['package_name'] ?? '') ?> (₱<?= number_format($r['package_price'] ?? 0, 2) ?>)</small>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($r['Preferred_Date'])) ?><br>
                                    <small style="color: #6b7280;"><?= date('h:i A', strtotime($r['Preferred_Time'])) ?></small>
                                </td>
                                <td><?= htmlspecialchars($r['Event_Location']) ?></td>
                                
                                <form action="admin-request.php" method="POST">
                                    <input type="hidden" name="action_type" value="update_status">
                                    <input type="hidden" name="request_id" value="<?= $r['Request_ID'] ?>">
                                    
                                    <td>
                                        <select name="request_status_id">
                                            <?php foreach ($req_statuses as $rs): ?>
                                                <option value="<?= $rs['Request_Status_ID'] ?>" <?= ($rs['Request_Status_ID'] == $r['Request_Status_ID']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($rs['Status_Name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    
                                    <td>
                                        <select name="event_status_id">
                                            <?php foreach ($evt_statuses as $es): ?>
                                                <option value="<?= $es['Event_Status_ID'] ?>" <?= ($es['Event_Status_ID'] == $r['Event_Status_ID']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($es['Status_Name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>

                                    <td>
                                        <div style="display: flex; gap: 6px; align-items: center;">
                                            <button type="submit" class="btn-save">Save</button>
                                            <a href="admin-messages.php?request_id=<?= $r['Request_ID'] ?>" class="btn-chat">Chat</a>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #6b7280;">No service requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>