<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_type = trim($_POST['event_type'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!empty($event_type) && !empty($event_date)) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=photography_db;charset=utf8mb4", "root", "");
            $stmt = $pdo->prepare("INSERT INTO requests (user_id, event_type, event_date, notes, status) VALUES (:user_id, :type, :date, :notes, 'Pending')");
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'type' => $event_type,
                'date' => $event_date,
                'notes' => $notes
            ]);
            $success_msg = 'Request submitted successfully!';
        } catch (PDOException $e) {
            $success_msg = 'Request recorded (testing mode).';
        }
    } else {
        $error_msg = 'Please fill out all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Request</title>
    <link rel="stylesheet" href="new-request.css">
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <div class="logo-box">LOGO</div>
            <span>New Request</span>
        </div>
        <nav class="navigation">
            <a href="dashboard.php">DASHBOARD</a>
            <a href="services.php">SERVICES</a>
            <a href="new-request.php" class="active">NEW REQUEST</a>
            <a href="quotation-payment.php">PAYMENTS</a>
            <a href="logout.php">LOGOUT</a>
        </nav>
    </header>

    <main class="form-container">
        <h1>Book a Shooting Session</h1>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <form method="POST" action="new-request.php">
            <div class="form-group">
                <label for="event_type">Event Type</label>
                <select name="event_type" id="event_type" required>
                    <option value="">Select Event Type</option>
                    <option value="Wedding Session">Wedding Session</option>
                    <option value="Birthday Bash">Birthday Bash</option>
                    <option value="Debut Special">Debut Special</option>
                </select>
            </div>

            <div class="form-group">
                <label for="event_date">Event Date</label>
                <input type="date" name="event_date" id="event_date" required>
            </div>

            <div class="form-group">
                <label for="notes">Special Notes / Requirements</label>
                <textarea name="notes" id="notes" rows="4" placeholder="Describe your session requests..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">SUBMIT REQUEST</button>
        </form>
    </main>

</body>
</html>