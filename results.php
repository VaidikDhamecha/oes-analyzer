<?php
session_start();
require_once 'db_config.php';

$result_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get the specific result and exam details
$stmt = $pdo->prepare("
    SELECT r.*, e.exam_title 
    FROM results r 
    JOIN exams e ON r.exam_id = e.id 
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$result_id, $user_id]);
$result = $stmt->fetch();

if (!$result) die("Result not found.");

// Fetch questions and student answers
$q_stmt = $pdo->prepare("
    SELECT q.*, ua.selected_option 
    FROM questions q 
    LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.result_id = ?
    WHERE q.exam_id = ?
");
$q_stmt->execute([$result_id, $result['exam_id']]);
$questions = $q_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Solution - <?= htmlspecialchars($result['exam_title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #0f172a; color: white; font-family: 'Inter', sans-serif; padding: 40px; }
        .solution-container { max-width: 800px; margin: auto; }
        .card { background: #1e293b; padding: 30px; border-radius: 20px; margin-bottom: 20px; position: relative; }
        .correct { border: 2px solid #10b981; }
        .wrong { border: 2px solid #ef4444; }
        .badge { position: absolute; right: 30px; top: 30px; font-size: 1.5rem; }
        .badge.fa-check-circle { color: #10b981; }
        .badge.fa-times-circle { color: #ef4444; }
        .option { padding: 12px; border-radius: 8px; margin: 8px 0; background: rgba(255,255,255,0.05); }
        .option.right-ans { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; }
    </style>
</head>
<body>
<div class="solution-container">
    <a href="index.php" style="color: #94a3b8; text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <h1 style="margin-top: 20px;">Exam Solution: <?= htmlspecialchars($result['exam_title']) ?></h1>
    <p>Your Score: <span style="color:#3b82f6; font-size: 1.5rem; font-weight: 800;"><?= $result['score'] ?>%</span></p>

    <?php foreach($questions as $index => $q): 
        $is_correct = ($q['selected_option'] == $q['correct_option']);
    ?>
    <div class="card <?= $is_correct ? 'correct' : 'wrong' ?>">
        <i class="badge fas <?= $is_correct ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
        <h3>Q<?= $index+1 ?>: <?= htmlspecialchars($q['question_text']) ?></h3>
        
        <div class="option <?= ($q['correct_option'] == 'option1') ? 'right-ans' : '' ?>">
            A. <?= htmlspecialchars($q['option1']) ?>
        </div>
        <div class="option <?= ($q['correct_option'] == 'option2') ? 'right-ans' : '' ?>">
            B. <?= htmlspecialchars($q['option2']) ?>
        </div>
        <div class="option <?= ($q['correct_option'] == 'option3') ? 'right-ans' : '' ?>">
            C. <?= htmlspecialchars($q['option3']) ?>
        </div>
        <div class="option <?= ($q['correct_option'] == 'option4') ? 'right-ans' : '' ?>">
            D. <?= htmlspecialchars($q['option4']) ?>
        </div>

        <p style="margin-top: 15px; font-size: 0.9rem; color: #94a3b8;">
            Your Answer: <span style="color: white;"><?= $q['selected_option'] ? strtoupper(str_replace('option', 'Option ', $q['selected_option'])) : 'Not Answered' ?></span>
        </p>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>