<?php
session_start();

// Admin Guard: Ensure only logged in admins/photographers access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'photographer')) {
    header('Location: login.php');
    exit;
}

$db_host = 'localhost';
$db_name = 'photography_db';
$db_user = 'root';
$db_pass = '';

$requests = [];
$metrics = ['new_requests' => 5, 'upcoming' => 3, 'pending_payments' => 2, 'active_projects' => 2];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $stmt = $pdo->query("SELECT r.*, u.name as client_name FROM requests r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 5");
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    // Static fallback array for preview
    $requests = [
        ['client_name' => 'John Doe', 'event_type' => 'Wedding Session', 'event_date' => '09/20/2026', 'status' => 'Pending', 'id' => 1],
        ['client_name' => 'Jane Smith', 'event_type' => 'Birthday Bash', 'event_date' => '10/05/2026', 'status' => 'Pending', 'id' => 2],
        ['client_name' => 'Mark Lee', 'event_type' => 'Debut Special', 'event_date' => '10/12/2026', 'status' => 'Confirmed', 'id' => 3]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photographer Dashboard</title>
    <link rel="stylesheet" href="photographer-dashboard.css">
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span class="portal-title">Photographer Portal</span>
            <span class="badge-pro">PRO</span>
        </div>

        <nav class="navigation">
            <a href="photographer-dashboard.php" class="active">DASHBOARD</a>
            <a href="photographer-requests.php">REQUESTS</a>
            <a href="photographer-schedule.php">SCHEDULE</a>
            <a href="photographer-payments.php">PAYMENTS</a>
            <a href="photographer-messages.php">MESSAGES</a>
            <a href="photographer-deliveries.php">DELIVERIES</a>
            <a href="photographer-reviews.php">REVIEWS</a>
            <a href="logout.php">LOGOUT</a>
        </nav>
    </header>

    <main class="dashboard-page">
        <div class="page-header">
            <div>
                <span class="subheading">PHOTOGRAPHER DASHBOARD</span>
                <h1 class="welcome-title">Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Studio Photographer') ?>!</h1>
            </div>
            <div class="header-actions">
                <a href="photographer-schedule.php" class="btn btn-secondary">CALENDAR VIEW</a>
                <a href="manage-slots.php" class="btn btn-primary">MANAGE SLOTS</a>
            </div>
        </div>

        <!-- Metrics Grid -->
        <section class="metrics-grid">
            <div class="metric-card">
                <span class="metric-label">NEW REQUESTS</span>
                <div class="metric-value"><?= $metrics['new_requests'] ?></div>
                <span class="metric-subtext">Awaiting verification</span>
            </div>
            <div class="metric-card">
                <span class="metric-label">UPCOMING BOOKINGS</span>
                <div class="metric-value"><?= $metrics['upcoming'] ?></div>
                <span class="metric-subtext">Secured dates</span>
            </div>
            <div class="metric-card">
                <span class="metric-label">PENDING PAYMENTS</span>
                <div class="metric-value"><?= $metrics['pending_payments'] ?></div>
                <span class="metric-subtext">Verification queued</span>
            </div>
            <div class="metric-card">
                <span class="metric-label">ACTIVE PROJECTS</span>
                <div class="metric-value"><?= $metrics['active_projects'] ?></div>
                <span class="metric-subtext">In progress</span>
            </div>
        </section>

        <!-- Requests Monitoring -->
        <section class="monitoring-card">
            <h2 class="card-section-title">Recent Requests Monitoring</h2>
            <div class="table-container">
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Event Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td class="font-bold"><?= htmlspecialchars($req['client_name']) ?></td>
                            <td><?= htmlspecialchars($req['event_type']) ?></td>
                            <td><?= htmlspecialchars($req['event_date']) ?></td>
                            <td><span class="status-badge status-<?= strtolower($req['status']) ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                            <td><a href="request-detail.php?id=<?= $req['id'] ?>" class="btn-table-action">VIEW</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>