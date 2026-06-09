<?php
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $exam_id = $_POST['exam_id'];
    $q_text  = $_POST['question'];
    $a = $_POST['opt_a'];
    $b = $_POST['opt_b'];
    $c = $_POST['opt_c'];
    $d = $_POST['opt_d'];
    $correct = $_POST['correct'];

    try {
        $sql = "INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$exam_id, $q_text, $a, $b, $c, $d, $correct]);

        // THE SECRET: Redirect back to the same page with the exam_id
        // This causes the "Questions Preview" to refresh and show the new data.
        header("Location: add_questions.php?exam_id=" . $exam_id . "&msg=success");
        exit();

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>