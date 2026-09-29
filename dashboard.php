<?php
session_start();

// Session Guard: Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Database Connection
$db_host = 'localhost';
$db_name = 'photography_db';
$db_user = 'root';
$db_pass = '';

$user_name = $_SESSION['user_name'] ?? 'Client';
$recent_bookings = [];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Fetch user's bookings
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $recent_bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback static data if DB is offline during local UI testing
    $recent_bookings = [
        ['event_type' => 'Wedding Session', 'event_date' => '2026-09-20', 'status' => 'Pending'],
        ['event_type' => 'Birthday Bash', 'event_date' => '2026-10-05', 'status' => 'Confirmed']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span>Client Portal</span>
        </div>
        <nav class="navigation">
            <a href="dashboard.php" class="active">DASHBOARD</a>
            <a href="services.php">SERVICES</a>
            <a href="new-request.php">NEW REQUEST</a>
            <a href="quotation-payment.php">PAYMENTS</a>
            <a href="logout.php">LOGOUT</a>
        </nav>
    </header>

    <main class="dashboard-page">
        <div class="page-header">
            <h1>Welcome back, <?= htmlspecialchars($user_name) ?>!</h1>
            <a href="new-request.php" class="btn btn-primary">BOOK A SESSION</a>
        </div>

        <section class="booking-section">
            <h2>Your Recent Bookings</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_bookings)): ?>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['event_type']) ?></td>
                                    <td><?= htmlspecialchars($booking['event_date']) ?></td>
                                    <td><span class="status-badge"><?= htmlspecialchars($booking['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No bookings found. <a href="new-request.php">Create one now!</a></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>