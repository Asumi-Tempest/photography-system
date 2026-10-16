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

$event_types = $db->query("SELECT Event_Type_ID, Name FROM event_type ORDER BY Name ASC")->fetchAll();
$packages    = $db->query("SELECT Package_ID, Name, Price FROM package ORDER BY Name ASC")->fetchAll();

$today_date = date('Y-m-d');
$current_time = date('H:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - Client Portal</title>
    <link rel="stylesheet" href="client-request.css">
    <style>
        .top-nav { background: #1e1e2d; color: #fff; padding: 14px 40px; display: flex; align-items: center; justify-content: space-between; }
        .nav-links a { font-size: 11px; font-weight: 700; color: #92929f; text-decoration: none; letter-spacing: 0.8px; text-transform: uppercase; margin-right: 15px; }
        .nav-links a:hover { color: #ffffff; }
        .nav-links a.active { color: #ffffff; border-bottom: 2px solid #ffffff; padding-bottom: 4px; }
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
            <a href="client-request.php" class="active">BOOK SERVICE</a>
            <a href="client-payments.php">MY PAYMENTS</a>
            <a href="client-deliverables.php">GALLERY</a>
            <a href="client-reviews.php">REVIEWS</a>
            <a href="client-messages.php">MESSAGES</a>
        </nav>

        <div class="nav-user-actions" style="display: flex; align-items: center; gap: 12px;">
            <div class="user-avatar" style="width: 32px; height: 32px; background: #3b82f6; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">C</div>
            <a href="logout.php" style="color: #ff6b6b; font-weight: bold; text-decoration: none; font-size: 14px;">Logout</a>
        </div>
    </header>

    <main class="page-container" style="max-width: 800px; margin: 30px auto; padding: 0 20px;">

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success" style="padding: 14px 18px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 8px; margin-bottom: 24px; font-weight: 600;">
                ✓ <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger" style="padding: 14px 18px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 8px; margin-bottom: 24px; font-weight: 600;">
                ⚠️ <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div class="card" style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <h2 style="margin-top: 0; margin-bottom: 20px;">Submit Service Request</h2>
            
            <form action="process-client-request.php" method="POST" id="bookingForm">
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 6px;">Event Type</label>
                    <select name="event_type_id" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                        <option value="">-- Select Event Type --</option>
                        <?php foreach ($event_types as $type): ?>
                            <option value="<?= $type['Event_Type_ID'] ?>">
                                <?= htmlspecialchars($type['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 6px;">Photography Package</label>
                    <select name="package_id" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                        <option value="">-- Select Package --</option>
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?= $pkg['Package_ID'] ?>">
                                <?= htmlspecialchars($pkg['Name']) ?> (₱<?= number_format($pkg['Price'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px;">Preferred Date</label>
                        <input type="date" id="preferred_date" name="preferred_date" min="<?= $today_date ?>" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px;">Preferred Time</label>
                        <input type="time" id="preferred_time" name="preferred_time" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 6px;">Event Location</label>
                    <input type="text" name="event_location" placeholder="e.g. Grand Hotel Ballroom, City Center" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 6px;">Special Instructions / Notes</label>
                    <textarea name="notes" rows="4" placeholder="Any specific requirements or visual preferences..." style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;"></textarea>
                </div>

                <button type="submit" style="width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 16px;">
                    SUBMIT BOOKING REQUEST
                </button>

            </form>
        </div>
    </main>

</body>
</html>