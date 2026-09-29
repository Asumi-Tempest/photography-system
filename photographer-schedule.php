<?php
require_once 'config.php';

// Authentication Guard - Photographer Access Only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$photographer_id = $_SESSION['user_id'];

// 1. Fetch upcoming confirmed shoots from Service_Request
$shoots_query = "
    SELECT 
        sr.Request_ID,
        c.Fullname AS client_name,
        et.Name AS event_type,
        sr.Preferred_Date,
        sr.Preferred_Time,
        sr.Event_Location
    FROM Service_Request sr
    JOIN Client c ON sr.Client_ID = c.Client_ID
    JOIN Event_Type et ON sr.Event_Type_ID = et.Event_Type_ID
    WHERE sr.Request_Status_ID = 2 -- Confirmed
    ORDER BY sr.Preferred_Date ASC
";
$upcoming_shoots = $db->query($shoots_query)->fetchAll();

// 2. Fetch blocked dates from Photographer_Availability
$blocked_query = "
    SELECT Availability_ID, Available_Date, Start_Time, End_Time, Availability_Status
    FROM Photographer_Availability
    WHERE Photography_ID = ?
    ORDER BY Available_Date ASC
";
$stmt = $db->prepare($blocked_query);
$stmt->execute([$photographer_id]);
$blocked_dates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule & Availability - Photographer Portal</title>
    <link rel="stylesheet" href="photographer-schedule.css">
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
            <a href="photographer-schedule.php" class="active">SCHEDULE</a>
            <a href="photographer-payments.php">PAYMENTS</a>
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

    <!-- Page Body -->
    <main class="page-container">

        <div class="page-header">
            <div>
                <h1 class="page-title">Schedule &amp; Availability</h1>
                <p class="page-subtitle">Track confirmed photoshoot events and block out personal or unavailable dates.</p>
            </div>
        </div>

        <div class="schedule-grid">
            
            <!-- Left Column: Upcoming Confirmed Shoots -->
            <section class="card">
                <div class="card-header">
                    <h2>Upcoming Confirmed Shoots</h2>
                </div>
                <div class="table-container">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>Client</th>
                                <th>Event Type</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($upcoming_shoots)): ?>
                                <?php foreach ($upcoming_shoots as $shoot): ?>
                                    <tr>
                                        <td>
                                            <div class="font-bold"><?= date('M d, Y', strtotime($shoot['Preferred_Date'])) ?></div>
                                            <div class="subtext"><?= date('h:i A', strtotime($shoot['Preferred_Time'])) ?></div>
                                        </td>
                                        <td class="font-bold"><?= htmlspecialchars($shoot['client_name']) ?></td>
                                        <td><span class="badge-event"><?= htmlspecialchars($shoot['event_type']) ?></span></td>
                                        <td><?= htmlspecialchars($shoot['Event_Location']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-cell">No confirmed shoots scheduled yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Right Column: Block Date Form & Blocked List -->
            <div class="side-column">
                
                <!-- Block Date Form Card -->
                <section class="card">
                    <div class="card-header">
                        <h2>Block Out Unavailable Date</h2>
                    </div>
                    <form action="process-schedule.php" method="POST" class="form-block-date">
                        <input type="hidden" name="action" value="block">
                        
                        <div class="form-group">
                            <label>DATE</label>
                            <input type="date" name="available_date" required min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>START TIME</label>
                                <input type="time" name="start_time" value="08:00" required>
                            </div>
                            <div class="form-group">
                                <label>END TIME</label>
                                <input type="time" name="end_time" value="18:00" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>REASON / STATUS</label>
                            <input type="text" name="availability_status" placeholder="Fully Booked / Personal Leave" required>
                        </div>

                        <button type="submit" class="btn-block">BLOCK DATE</button>
                    </form>
                </section>

                <!-- Blocked Dates List -->
                <section class="card">
                    <div class="card-header">
                        <h2>Blocked Dates</h2>
                    </div>
                    <ul class="blocked-list">
                        <?php if (!empty($blocked_dates)): ?>
                            <?php foreach ($blocked_dates as $b): ?>
                                <li class="blocked-item">
                                    <div>
                                        <div class="font-bold"><?= date('M d, Y', strtotime($b['Available_Date'])) ?></div>
                                        <div class="subtext">
                                            <?= date('h:i A', strtotime($b['Start_Time'])) ?> - <?= date('h:i A', strtotime($b['End_Time'])) ?>
                                            (<?= htmlspecialchars($b['Availability_Status']) ?>)
                                        </div>
                                    </div>
                                    <a href="process-schedule.php?action=unblock&id=<?= $b['Availability_ID'] ?>" class="btn-unblock">UNBLOCK</a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="empty-cell">No blocked dates set.</li>
                        <?php endif; ?>
                    </ul>
                </section>

            </div>

        </div>

    </main>

</body>
</html>