<?php
include 'db_config.php';

$username = 'admin';
$password = 'admin123'; // This is what you will type in the login box
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $hashed_password]);
    echo "Admin Created! <br> ID: admin <br> Pass: admin123";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>