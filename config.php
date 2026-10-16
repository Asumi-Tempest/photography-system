<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'photography_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// PDO Connection Helper
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        return null; // Fallback to session store if MySQL is not setup yet
    }
}

if (!isset($_SESSION['mock_blocked_dates'])) {
    $_SESSION['mock_blocked_dates'] = [];
}

// ---- Access-control helpers (added) ----
// Only Admin / Photographer accounts may use staff pages.
function requireStaff() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    $role = strtolower($_SESSION['user_role'] ?? '');
    if ($role !== 'admin' && $role !== 'photographer') {
        header('Location: client-request.php');
        exit;
    }
}

// True if the service request belongs to the logged-in client.
function clientOwnsRequest($db, $requestId) {
    $stmt = $db->prepare("SELECT 1 FROM service_request WHERE Request_ID = ? AND Client_ID = ?");
    $stmt->execute([$requestId, $_SESSION['user_id'] ?? 0]);
    return (bool)$stmt->fetchColumn();
}
?>