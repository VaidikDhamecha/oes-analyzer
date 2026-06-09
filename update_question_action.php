<?php
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $q_id = $_POST['question_id'];
    $exam_id = $_POST['exam_id'];
    $question_text = $_POST['question_text'];
    $option_a = $_POST['option_a'];
    $option_b = $_POST['option_b'];
    $option_c = $_POST['option_c'];
    $option_d = $_POST['option_d'];
    $correct = $_POST['correct_option'];

    try {
        $sql = "UPDATE questions SET 
                question_text = ?, 
                option_a = ?, 
                option_b = ?, 
                option_c = ?, 
                option_d = ?, 
                correct_option = ? 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$question_text, $option_a, $option_b, $option_c, $option_d, $correct, $q_id]);

        // Redirect back to the question management page for this exam
        header("Location: update_exam_questions.php?exam_id=" . $exam_id);
        exit();
    } catch (PDOException $e) {
        die("Error updating question: " . $e->getMessage());
    }
}
?>