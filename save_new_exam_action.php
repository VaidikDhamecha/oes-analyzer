<?php
session_start();
require_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['exam_title'];
    $duration = $_POST['duration'];

    try {
        // We insert the title and the duration into the database
        $stmt = $pdo->prepare("INSERT INTO exams (exam_title, duration) VALUES (?, ?)");
        
        if ($stmt->execute([$title, $duration])) {
            // Get the ID of the exam we just created
            $new_exam_id = $pdo->lastInsertId();
            
            // Redirect to add_questions.php so you can start adding questions immediately
            header("Location: add_questions.php?exam_id=" . $new_exam_id);
            exit();
        }
    } catch (PDOException $e) {
        // If there is still an error, it will show here
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: create_exam.php");
    exit();
}
?>