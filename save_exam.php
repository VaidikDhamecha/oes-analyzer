<?php
require_once 'db_config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = $_POST['exam_id'];
    $q_text = $_POST['question_text'];
    $oa = $_POST['option_a'];
    $ob = $_POST['option_b'];
    $oc = $_POST['option_c'];
    $od = $_POST['option_d'];
    $correct = $_POST['correct_option'];

    $sql = "INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if($stmt->execute([$exam_id, $q_text, $oa, $ob, $oc, $od, $correct])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database insertion failed.']);
    }
}