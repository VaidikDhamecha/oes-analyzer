<?php
require_once 'db_config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$exam_id = $_GET['id'] ?? $_GET['exam_id'] ?? null;
if (!$exam_id) die("Exam ID not specified.");

try {
    $exam_stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $exam_stmt->execute([$exam_id]);
    $exam = $exam_stmt->fetch();

    $q_stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
    $q_stmt->execute([$exam_id]);
    $questions = $q_stmt->fetchAll();
    $total_questions = count($questions);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam: <?= htmlspecialchars($exam['exam_title'] ?? 'Test') ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 20px; }
        .exam-container { max-width: 800px; margin: 40px auto; padding: 30px; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .timer-box { position: fixed; top: 20px; right: 20px; background: #0f172a; color: white; padding: 15px 25px; border-radius: 10px; font-weight: 800; }
        .question-card { margin-bottom: 40px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
        .option-label { display: block; margin: 12px 0; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; cursor: pointer; }
        .btn-submit { width: 100%; padding: 18px; background: #2563eb; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>

<div class="timer-box">Time Left: <span id="timer">--:--</span></div>

<div class="exam-container">
    <h1><?= htmlspecialchars($exam['exam_title']) ?></h1>
    <form action="submit_results.php" method="POST" id="examForm" onsubmit="isSubmitting = true;">
        <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
        <input type="hidden" name="score" id="finalScore" value="0">
        <input type="hidden" name="time_spent" id="timeSpentInput" value="0">
        <input type="hidden" name="user_ans_json" id="userAnswersJson" value="">

        <?php foreach ($questions as $index => $q): ?>
            <div class="question-card">
                <h3>Q<?= $index + 1 ?>: <?= htmlspecialchars($q['question_text']) ?></h3>
                <div class="options-group" data-qid="<?= $q['id'] ?>" data-correct="<?= strtolower($q['correct_option']) ?>">
                    <?php 
                    $options = ['a' => 'option_a', 'b' => 'option_b', 'c' => 'option_c', 'd' => 'option_d'];
                    foreach($options as $letter => $column): 
                    ?>
                    <label class="option-label">
                        <input type="radio" name="q<?= $q['id'] ?>" value="<?= $letter ?>" required>
                        <strong><?= strtoupper($letter) ?>:</strong> <?= htmlspecialchars($q[$column]) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-submit">Submit Exam</button>
    </form>
</div>

<script>
    let isSubmitting = false;
    let timeSpentSeconds = 0;
    const totalQuestions = <?= $total_questions ?: 1 ?>;

    function securityHandler() {
        // If the tab is hidden and we are NOT submitting, trigger alert
        if (document.hidden && !isSubmitting && performance.now() > 3000) {
            document.removeEventListener('visibilitychange', securityHandler);
            
            // Use an overlay that covers the entire page to stop any further interaction
            document.body.innerHTML = `
                <div style="position:fixed; top:0; left:0; width:100%; height:100%; background:#0f172a; color:white; display:flex; justify-content:center; align-items:center; z-index:99999;">
                    <div style="background:#1e293b; padding:40px; border-radius:20px; text-align:center; border:1px solid #ef4444; width:90%; max-width:500px;">
                        <h2 style="color:#ef4444;">Security Alert</h2>
                        <p>Tab switching detected. Exam terminated.</p>
                        <a href="student_dashboard.php" style="display:inline-block; margin-top:20px; padding:15px 30px; background:#2563eb; color:white; text-decoration:none; border-radius:8px;">Return to Dashboard</a>
                    </div>
                </div>
            `;
            // Background update
            navigator.sendBeacon('mark_silent.php?exam_id=<?= $exam_id ?>');
        }
    }

    document.addEventListener('visibilitychange', securityHandler);

    // Form submission processing
    document.getElementById('examForm').addEventListener('submit', function(e) {
        if (!confirm("Are you sure you want to submit?")) {
            e.preventDefault();
            return false;
        }

        isSubmitting = true; // Crucial: This prevents the securityHandler from firing
        
        let correctCount = 0;
        let answersObj = {};
        document.querySelectorAll('.options-group').forEach(group => {
            const qid = group.getAttribute('data-qid');
            const correct = group.getAttribute('data-correct').trim().toLowerCase();
            const selected = group.querySelector('input[type="radio"]:checked');
            answersObj[qid] = selected ? selected.value : null;
            if (selected && selected.value === correct) correctCount++;
        });

        document.getElementById('finalScore').value = Math.round((correctCount / totalQuestions) * 100);
        document.getElementById('timeSpentInput').value = Math.floor(timeSpentSeconds / 60);
        document.getElementById('userAnswersJson').value = JSON.stringify(answersObj);
    });

    // Timer Logic
    let totalSeconds = <?= ($exam['duration_minutes'] ?? 30) * 60 ?>;
    setInterval(() => {
        if(totalSeconds > 0) {
            totalSeconds--; timeSpentSeconds++;
            let m = Math.floor(totalSeconds/60), s = totalSeconds%60;
            document.getElementById('timer').innerHTML = `${m}:${s < 10 ? '0' : ''}${s}`;
        } else { 
            isSubmitting = true;
            document.getElementById('examForm').submit(); 
        }
    }, 1000);
</script>
</body>
</html>