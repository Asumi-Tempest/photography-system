<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {$db = getDBConnection();

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  =$_POST['password'] ?? '';

    // Set to 1 matching Client in your role table
    $client_role_id = 1; 

    if (!empty($full_name) && !empty($email) && !empty($phone) && !empty($password)) {
        
        $check_stmt =$db->prepare("SELECT `User_ID` FROM `User` WHERE `Email` = ?");
        $check_stmt->execute([$email]);

        if ($check_stmt->fetch()) {
            header('Location: register.php?error=Email address is already registered.');
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            $db->beginTransaction();

            // 1. Insert into `User`
            $stmt =$db->prepare("
                INSERT INTO `User` (`Full_Name`, `Email`, `Phone`, `Password_Hash`, `Role`) 
                VALUES (?, ?, ?, ?, 'Client')
            ");
            $stmt->execute([$full_name,$email, $phone,$hashed_password]);
            $user_id =$db->lastInsertId();

            // 2. Insert into `client` table with Role_ID = 1
            $client_stmt =$db->prepare("
                INSERT INTO `client` (`Client_ID`, `Fullname`, `Email`, `Phonenumber`, `Password_Hash`, `Role_ID`) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $client_stmt->execute([$user_id,$full_name, $email,$phone, $hashed_password,$client_role_id]);

            $db->commit();

            $_SESSION['user_id']   =$user_id;
            $_SESSION['user_name'] =$full_name;
            $_SESSION['user_email']=$email;
            $_SESSION['user_role'] = 'Client';

            header('Location: client-request.php');
            exit;

        } catch (Exception $e) {$db->rollBack();
            header('Location: register.php?error=Registration failed. Please try again.');
            exit;
        }
    }

    header('Location: register.php?error=Please fill in all required fields.');
    exit;
}

header('Location: register.php');
exit;
?>