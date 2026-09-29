<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $db->prepare("
            SELECT User_ID, Full_Name, Email, Password_Hash, Role 
            FROM User 
            WHERE Email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password_Hash'])) {
            $_SESSION['user_id']   = $user['User_ID'];
            $_SESSION['user_name'] = $user['Full_Name'];
            $_SESSION['user_email']= $user['Email'];
            $_SESSION['user_role'] = $user['Role'];

            // Route based on role using your exact file tree names
            if ($user['Role'] === 'Admin' || $user['Role'] === 'Photographer') {
                header('Location: photographer-dashboard.php');
            } else {
                header('Location: client-request.php');
            }
            exit;
        }
    }

    header('Location: login.php?error=Invalid email or password.');
    exit;
}

header('Location: login.php');
exit;
?>