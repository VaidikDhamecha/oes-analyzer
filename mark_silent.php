<?php
session_start();
require_once 'db_config.php';
$exam_id = intval($_GET['exam_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id && $exam_id > 0) {
    // This SQL update happens in the background via sendBeacon
    $stmt = $pdo->prepare("UPDATE results SET status = 'failed' WHERE user_id = ? AND exam_id = ? AND status != 'completed'");
    $stmt->execute([$user_id, $exam_id]);
}
exit();
?>