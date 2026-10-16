<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$packages = [];
$db = getDBConnection();
if ($db) {
    try {
        $packages = $db->query("SELECT * FROM package ORDER BY Price ASC")->fetchAll();
    } catch (Exception $e) {
        $packages = [];
    }
}

function packageFeatures($text) {
    $text = trim((string)$text);
    if ($text === '') return [];
    $parts = preg_split('/\r\n|\r|\n|,/', $text);
    return array_values(array_filter(array_map('trim', $parts)));
}

function packageImage($id) {
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        $path = "images/package-{$id}.{$ext}";
        if (file_exists($path)) return $path;
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - Client Portal</title>
    <style>
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
        .top-nav { background: #1e1e2d; color: #fff; padding: 14px 40px; display: flex; align-items: center; justify-content: space-between; }
        .portal-title { font-weight: 700; font-size: 15px; color: #ffffff; letter-spacing: 0.2px; }
        .nav-links { display: flex; align-items: center; }
        .nav-links a { font-size: 11px; font-weight: 700; color: #92929f; text-decoration: none; letter-spacing: 0.8px; text-transform: uppercase; padding-bottom: 4px; margin-right: 15px; }
        .nav-links a:hover { color: #ffffff; }
        .nav-links a.active { color: #ffffff; border-bottom: 2px solid #ffffff; }
        .user-avatar { width: 32px; height: 32px; background: #3b82f6; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .btn-logout { font-size: 13px; font-weight: 700; color: #f87171; text-decoration: none; }
        .portal-container { max-width: 1250px; margin: 36px auto; padding: 0 20px; }
        .page-title { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0 0 6px; text-align: center; }
        .page-subtitle { text-align: center; color: #64748b; font-size: 14px; margin: 0 0 30px; }
        .packages-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .package-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .package-image { height: 160px; background: linear-gradient(135deg, #e2e8f0 0%, #f8fafc 100%); display: flex; align-items: center; justify-content: center; color: #94a3b8; }
        .package-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .package-body { padding: 20px; flex-grow: 1; }
        .package-name { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 4px; }
        .package-duration { display: block; font-size: 13px; color: #64748b; margin-bottom: 10px; }
        .package-price { font-size: 24px; font-weight: 800; color: #007bff; margin: 8px 0 16px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; }
        .package-features { list-style: none; margin: 0; padding: 0; }
        .package-features li { position: relative; padding-left: 24px; margin-bottom: 9px; font-size: 14px; color: #334155; line-height: 1.4; }
        .package-features li::before { content: "✓"; position: absolute; left: 0; font-weight: 800; color: #10b981; }
        .no-packages { text-align: center; color: #64748b; padding: 50px 0; font-size: 15px; }
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="nav-left">
            <img src="logo.svg" alt="Logo" width="34" height="34" style="display:block;width:34px;height:34px;">
            <span class="portal-title">Client Portal</span>
        </div>
        <nav class="nav-links">
            <a href="client-services.php" class="active">SERVICES</a>
            <a href="client-request.php">BOOK SERVICE</a>
            <a href="client-payments.php">MY PAYMENTS</a>
            <a href="client-deliverables.php">GALLERY</a>
            <a href="client-reviews.php">REVIEWS</a>
            <a href="client-messages.php">MESSAGES</a>
        </nav>
        <div class="nav-right" style="display: flex; align-items: center; gap: 14px;">
            <div class="user-avatar">C</div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="portal-container">
        <h1 class="page-title">Our Photography Packages</h1>
        <p class="page-subtitle">Browse our packages, then go to Book Service to make your request.</p>

        <?php if (empty($packages)): ?>
            <div class="no-packages">No packages are available right now. Please check back soon.</div>
        <?php else: ?>
            <div class="packages-grid">
                <?php foreach ($packages as $pkg): ?>
                    <?php
                        $features = packageFeatures($pkg['Description'] ?? '');
                        $image    = packageImage($pkg['Package_ID']);
                        $duration = trim((string)($pkg['Duration'] ?? ''));
                    ?>
                    <article class="package-card">
                        <div class="package-image">
                            <?php if ($image): ?>
                                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($pkg['Name']) ?>">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width: 56px; height: 56px;">
                                    <path d="M3 8a2 2 0 0 1 2-2h2l1.5-2h7L17 6h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8z"/>
                                    <circle cx="12" cy="13" r="3.5"/>
                                </svg>
                            <?php endif; ?>
                        </div>

                        <div class="package-body">
                            <h2 class="package-name"><?= htmlspecialchars($pkg['Name']) ?></h2>
                            <?php if ($duration !== ''): ?>
                                <span class="package-duration"><?= htmlspecialchars($duration) ?></span>
                            <?php endif; ?>
                            <div class="package-price">₱<?= number_format((float)$pkg['Price'], 2) ?></div>

                            <?php if ($features): ?>
                                <ul class="package-features">
                                    <?php foreach ($features as $item): ?>
                                        <li><?= htmlspecialchars($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>