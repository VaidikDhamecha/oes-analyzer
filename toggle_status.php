<?php
require_once 'db_config.php';
$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT status FROM exams WHERE id = ?");
$stmt->execute([$id]);
$current = $stmt->fetchColumn();

$newStatus = ($current == 'Active') ? 'Inactive' : 'Active';
$update = $pdo->prepare("UPDATE exams SET status = ? WHERE id = ?");
$update->execute([$newStatus, $id]);

header("Location: manage_exams.php");
exit();