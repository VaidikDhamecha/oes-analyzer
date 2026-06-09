<?php
session_start();
require_once 'db_config.php';

$exam_id = intval($_GET['exam_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? null;

// Only mark as failed if the exam wasn't already completed
if ($user_id && $exam_id > 0) {
    $stmt = $pdo->prepare("UPDATE results SET status = 'failed' WHERE user_id = ? AND exam_id = ? AND status != 'completed'");
    $stmt->execute([$user_id, $exam_id]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Exam Terminated</title>
    <style>
        body { background: #0f172a; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; }
        .box { background: #1e293b; padding: 40px; border-radius: 20px; text-align: center; border: 1px solid #ef4444; }
        .btn { display: inline-block; margin-top: 20px; padding: 15px 30px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="box">
        <h2 style="color: #ef4444;">Security Alert</h2>
        <p>You have switched tabs. Your exam has been terminated for suspicious activity.</p>
        <a href="index.php" class="btn">Return to Dashboard</a>
    </div>
</body>
</html>