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
?>