<?php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Handle Image upload
    if (!empty($_FILES['p_img']['name'])) {
        $imgName = time() . '_' . $_FILES['p_img']['name'];
        if(move_uploaded_file($_FILES['p_img']['tmp_name'], "uploads/" . $imgName)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $stmt->execute([$imgName, $uid]);
        }
    }

    // Update username
    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->execute([$user, $uid]);

    // Update password if provided
    if (!empty($pass)) {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$pass, $uid]);
    }

    header("Location: edit_profile.php?success=1");
    exit();
}