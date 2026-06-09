<?php
require_once 'db_config.php';

$msg = "";
$status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if (!empty($user) && !empty($pass) && !empty($confirm_pass)) {
        if ($pass !== $confirm_pass) {
            $msg = "Passwords do not match!";
            $status = "error";
        } else {
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
            try {
                // Hardcoded 'student' role for security
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
                if ($stmt->execute([$user, $hashed_password])) {
                    $msg = "Account created! <a href='login.php' style='color:#166534; font-weight:bold;'>Login now</a>";
                    $status = "success";
                }
            } catch (PDOException $e) {
                $msg = "Username already taken.";
                $status = "error";
            }
        }
    } else {
        $msg = "All fields are required.";
        $status = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OES Analyzer - Create Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same style block as login.php */
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: #0f172a; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: white; padding: 40px; border-radius: 24px; width: 400px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); text-align: center; }
        .logo-icon { color: #2563eb; font-size: 2.5rem; margin-bottom: 10px; }
        h2 { margin: 0; color: #1e293b; font-size: 1.5rem; }
        p.subtitle { color: #64748b; font-size: 0.9rem; margin-top: 5px; margin-bottom: 25px; }
        .alert { padding: 12px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid; }
        .alert.error { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .alert.success { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .input-group { text-align: left; margin-bottom: 15px; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        input { width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 0.95rem; }
        .btn-signin { width: 100%; padding: 14px; background: #3b82f6; color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 20px; transition: 0.2s; }
        .footer-text { margin-top: 25px; font-size: 0.85rem; color: #64748b; }
        .footer-text a { color: #2563eb; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-card">
        <i class="fas fa-user-plus logo-icon"></i>
        <h2>Create Account</h2>
        <p class="subtitle">Join OES Analyzer today</p>

        <?php if($msg): ?>
        <div class="alert <?= $status ?>">
            <i class="fas <?= $status == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i> <?= $msg ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Choose a username" required>
                </div>
            </div>
            <div class="input-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Create password" required>
                </div>
            </div>
            <div class="input-group">
                <label>Confirm Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-shield-halved"></i>
                    <input type="password" name="confirm_password" placeholder="Repeat password" required>
                </div>
            </div>
            <button type="submit" class="btn-signin">Register Account <i class="fas fa-arrow-right"></i></button>
        </form>
        <div class="footer-text">Already have an account? <a href="login.php">Sign In</a></div>
    </div>
</body>
</html>