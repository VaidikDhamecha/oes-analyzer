<?php
session_start();
require_once 'db_config.php';

if(isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $id = $_GET['id'];
    
    try {
        $pdo->beginTransaction();

        // 1. Delete Student Results (Fixes your Integrity constraint violation)
        $stmt1 = $pdo->prepare("DELETE FROM results WHERE exam_id = ?");
        $stmt1->execute([$id]);

        // 2. Delete Questions
        $stmt2 = $pdo->prepare("DELETE FROM questions WHERE exam_id = ?");
        $stmt2->execute([$id]);

        // 3. Finally Delete the Exam
        $stmt3 = $pdo->prepare("DELETE FROM exams WHERE id = ?");
        $stmt3->execute([$id]);

        $pdo->commit();
        header("Location: manage_exams.php?success=deleted");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}