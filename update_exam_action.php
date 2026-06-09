<?php
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['exam_id'];
    $title = $_POST['exam_title'];
    $time = $_POST['duration'];

    try {
        // Update exam details in the database
        $stmt = $pdo->prepare("UPDATE exams SET exam_title = ?, duration = ? WHERE id = ?");
        $stmt->execute([$title, $time, $id]);

        header("Location: manage_exams.php");
        exit();
    } catch (PDOException $e) {
        die("Update failed: " . $e->getMessage());
    }
}
?>