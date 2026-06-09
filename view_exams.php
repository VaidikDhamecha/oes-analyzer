<?php
require_once 'db_config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($exam_id === 0) {
    echo "<script>alert('Invalid exam session selection.'); window.location.href='index.php';</script>";
    exit();
}

try {
    $exam_stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $exam_stmt->execute([$exam_id]);
    $exam = $exam_stmt->fetch(PDO::FETCH_ASSOC);

    $questions_stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
    $questions_stmt->execute([$exam_id]);
    $all_questions = $questions_stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_questions = count($all_questions);
    
    $exam_title = htmlspecialchars($exam['exam_title'] ?? $exam['exam_name'] ?? 'Examination');
    $db_duration = intval($exam['duration'] ?? $exam['exam_duration'] ?? 45);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SECURE EXAM | <?= $exam_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #0f172a; color: #f8fafc; min-height: 100vh; padding: 40px 20px; }
        .exam-container { max-width: 900px; margin: 0 auto; background: rgba(30, 41, 59, 0.7); padding: 40px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.05); }
        .exam-header { display: flex; justify-content: space-between; margin-bottom: 35px; padding-bottom: 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .timer-badge { background: #ef4444; color: #fff; padding: 12px 24px; border-radius: 12px; font-size: 24px; font-weight: 800; }
        .question-card { background: rgba(15, 23, 42, 0.6); padding: 30px; border-radius: 16px; margin-bottom: 24px; border: 1px solid rgba(255, 255, 255, 0.03); }
        .option-label { display: block; padding: 16px 24px; background: rgba(30, 41, 59, 0.5); border-radius: 12px; cursor: pointer; color: #cbd5e1; margin-top: 10px; }
        .option-item input[type="radio"]:checked ~ .option-label { background: #2563eb; color: #fff; }
        .btn-submit-exam { background: #2563eb; color: #fff; border: none; padding: 16px 36px; border-radius: 12px; cursor: pointer; font-weight: 700; width: 100%; }
    </style>
</head>
<body>
    <div class="exam-container">
        <form id="examForm" action="submit_results.php" method="POST">
            <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
            <input type="hidden" name="score" id="finalScoreInput" value="0">
            <input type="hidden" name="user_ans_json" id="userAnsJson" value="{}">
            
            <div class="exam-header">
                <div><h1><?= $exam_title ?></h1><p><i class="fa-solid fa-shield-halved"></i> Strict Proctoring Enabled</p></div>
                <div class="timer-badge" id="timerDisplay">--:--</div>
            </div>

            <?php foreach ($all_questions as $q): $qid = $q['id']; ?>
                <div class="question-card" data-qid="<?= $qid ?>" data-correct="<?= strtoupper(trim($q['correct_option'])) ?>">
                    <div class="question-text"><?= htmlspecialchars($q['question_text']) ?></div>
                    <ul class="options-list" style="list-style:none;">
                        <?php foreach(['A','B','C','D'] as $opt): ?>
                        <li class="option-item">
                            <input type="radio" name="answers[<?= $qid ?>]" id="opt<?= $opt ?>_<?= $qid ?>" value="<?= $opt ?>" style="opacity:0; position:absolute;">
                            <label for="opt<?= $opt ?>_<?= $qid ?>" class="option-label"><?= $opt ?>. <?= htmlspecialchars($q['option_'.strtolower($opt)] ?? '') ?></label>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn-submit-exam">Submit Examination</button>
        </form>
    </div>

    <script>
        let isSubmitting = false;
        let timeRemaining = <?= $db_duration ?> * 60;

        document.getElementById('examForm').addEventListener('submit', function(e) {
            isSubmitting = true;
            let correctCount = 0;
            let total = <?= $total_questions ?: 1 ?>;
            let answersObj = {};

            document.querySelectorAll('.question-card').forEach(card => {
                const qid = card.getAttribute('data-qid');
                const correct = card.getAttribute('data-correct');
                const selected = card.querySelector(`input[name="answers[${qid}]"]:checked`);
                
                if (selected) {
                    answersObj[qid] = selected.value;
                    if (selected.value === correct) correctCount++;
                }
            });

            document.getElementById('finalScoreInput').value = Math.round((correctCount / total) * 100);
            document.getElementById('userAnsJson').value = JSON.stringify(answersObj);
        });

        // Anti-Cheat & Timer
      // ANTI-CHEAT: Overlay with clear instructions
document.addEventListener('visibilitychange', function() {
    if (document.hidden && !isSubmitting && performance.now() > 3000) {
        // 1. Silent update to database in the background
        navigator.sendBeacon('mark_silent.php?exam_id=<?= $exam_id ?>');
        
        // 2. Replace the entire page content with a locked warning screen
        document.body.innerHTML = `
            <div style="height:100vh; display:flex; justify-content:center; align-items:center; background:#0f172a; color:white; padding:20px;">
                <div style="background:#1e293b; padding:40px; border-radius:20px; text-align:center; border:1px solid #ef4444; max-width:500px; width:100%;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 50px; color: #ef4444; margin-bottom: 20px;"></i>
                    <h2 style="margin-bottom: 15px;">Security Violation</h2>
                    <p style="margin-bottom: 25px; color: #cbd5e1;">Tab switching is strictly prohibited during the examination. Your session has been terminated for security reasons.</p>
                    <a href="index.php" style="display:inline-block; padding:15px 30px; background:#2563eb; color:white; text-decoration:none; border-radius:10px; font-weight:700;">Return to Dashboard</a>
                </div>
            </div>`;
    }
});

        setInterval(() => {
            if (timeRemaining > 0) {
                timeRemaining--;
                let m = Math.floor(timeRemaining / 60);
                let s = timeRemaining % 60;
                document.getElementById('timerDisplay').textContent = (m < 10 ? '0'+m : m) + ':' + (s < 10 ? '0'+s : s);
            } else if (!isSubmitting) {
                document.getElementById('examForm').submit();
            }
        }, 1000);
    </script>
</body>
</html>