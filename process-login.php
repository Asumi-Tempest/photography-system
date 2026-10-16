<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();
    
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        // Query user table
        $stmt = $db->prepare("SELECT * FROM user WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Support Password_Hash, Password, or password column variants
            $dbPassword = $user['Password_Hash'] ?? $user['Password'] ?? $user['password'] ?? '';

            // Verify hashed password or plain-text match
            if (password_verify($password, $dbPassword) || $password === $dbPassword) {
                $_SESSION['user_id']   = $user['User_ID'];
                $_SESSION['email']     = $user['Email'];
                $_SESSION['user_role'] = $user['Role'];

                $role = strtolower($user['Role']);
                if ($role === 'admin' || $role === 'photographer') {
                    header('Location: photographer-dashboard.php');
                } else {
                    header('Location: client-request.php');
                }
                exit;
            }
        }
    }

    header('Location: login.php?error=' . urlencode('Invalid email or password.'));
    exit;
}

header('Location: login.php');
exit;