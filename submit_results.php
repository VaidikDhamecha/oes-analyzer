<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['exam_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$exam_id = intval($_POST['exam_id']);
$score = isset($_POST['score']) ? intval($_POST['score']) : 0; 
$answers = json_decode($_POST['user_ans_json'] ?? '{}', true); 

// DEBUG: Uncomment the next line to see if the score reaches this file
// die("DEBUG: Received Score is " . $score); 

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO results (user_id, exam_id, status, score) VALUES (?, ?, 'completed', ?)");
    $stmt->execute([$user_id, $exam_id, $score]);
    $result_id = $pdo->lastInsertId();

    if (!empty($answers)) {
        $ans_stmt = $pdo->prepare("INSERT INTO student_answers (user_id, exam_id, question_id, result_id, selected_option) VALUES (?, ?, ?, ?, ?)");
        foreach ($answers as $q_id => $selected_opt) {
            $ans_stmt->execute([$user_id, $exam_id, intval($q_id), $result_id, $selected_opt]);
        }
    }

    $pdo->commit();
    header("Location: solution.php?result_id=" . $result_id);
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error submitting exam: " . $e->getMessage());
}
?>