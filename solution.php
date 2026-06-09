<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['result_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$result_id = intval($_GET['result_id']);

// Fetch result and exam info
$stmt = $pdo->prepare("SELECT r.*, e.exam_title, e.id as exam_id FROM results r JOIN exams e ON r.exam_id = e.id WHERE r.id = ? AND r.user_id = ?");
$stmt->execute([$result_id, $user_id]);
$result_data = $stmt->fetch();

// Fetch all questions
$questions = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
$questions->execute([$result_data['exam_id']]);
$all_questions = $questions->fetchAll(PDO::FETCH_ASSOC);

// Fetch answers and map them (Ensuring '1' maps to 'A', '2' to 'B', etc.)
$student_answers = [];
$ans_stmt = $pdo->prepare("SELECT question_id, selected_option FROM student_answers WHERE result_id = ?");
$ans_stmt->execute([$result_id]);
foreach ($ans_stmt->fetchAll() as $row) {
    $map = ['1'=>'A', '2'=>'B', '3'=>'C', '4'=>'D'];
    $val = trim($row['selected_option']);
    $student_answers[$row['question_id']] = isset($map[$val]) ? $map[$val] : strtoupper($val);
}

// Calculate score
$score = 0;
foreach ($all_questions as $q) {
    if (isset($student_answers[$q['id']]) && $student_answers[$q['id']] === strtoupper(trim($q['correct_option']))) {
        $score++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-bg: #111827; --primary: #2563eb; --bg: #f8fafc; }
        body { margin: 0; font-family: 'Inter', sans-serif; display: flex; background: var(--bg); height: 100vh; overflow: hidden; }

        /* Left Navigation Bar - YOUR ORIGINAL DESIGN */
        .sidebar { width: 280px; background: var(--sidebar-bg); height: 100vh; padding: 30px 20px; color: white; flex-shrink: 0; box-sizing: border-box; }
        .nav-tab { 
            display: flex; align-items: center; padding: 14px 20px; border-radius: 12px; 
            color: #94a3b8; text-decoration: none; margin-bottom: 10px; cursor: pointer;
            transition: all 0.3s ease; 
        }
        .nav-tab:hover { background: rgba(255,255,255,0.05); color: white; transform: translateX(8px); }
        .nav-tab.active { background: var(--primary); color: white; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4); }

        /* Main Scrollable Area */
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .review-banner { background: white; padding: 35px; border-radius: 24px; border: 1px solid #e2e8f0; margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        
        .score-badge-box { background: #f1f5f9; padding: 15px 25px; border-radius: 16px; text-align: center; border: 1px solid #e2e8f0; }
        .score-badge-box h3 { margin: 0; font-size: 1.8rem; color: var(--primary); font-weight: 800; }
        
        .question-block { background: white; padding: 35px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .option-row { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-radius: 12px; margin-bottom: 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-weight: 600; color: #334155; }
        
        .correct-green { background: #dcfce7 !important; border-color: #bbf7d0 !important; color: #166534 !important; }
        .wrong-red { background: #fee2e2 !important; border-color: #fca5a5 !important; color: #991b1b !important; }
        .badge-status { font-size: 0.75rem; font-weight: 700; padding: 6px 14px; border-radius: 20px; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="sidebar">

    <h1 style="margin-bottom: 40px; padding-left: 15px;">OES</h1>
    <a href="index.php" class="nav-tab"><i class="fas fa-home"></i>&nbsp;&nbsp; Dashboard</a>
    <a href="index.php" class="nav-tab active"><i class="fas fa-file-alt"></i>&nbsp;&nbsp; My Records</a>
    <a href="index.php" class="nav-tab"><i class="fas fa-file-alt"></i>&nbsp;&nbsp; Setting</a>
    <a href="logout.php" class="nav-tab" style="color: #ef4444; margin-top: 50px;"><i class="fas fa-sign-out-alt"></i>&nbsp;&nbsp; Logout</a>
</div>
<div class="main-content">
<a href="index.php" class="nav-tab"><i class="fas fa-arrow-left"></i>&nbsp;&nbsp; Back to Dashboard</a>

    <div class="review-banner">
        <div>
            <h2><?= htmlspecialchars($result_data['exam_title']) ?></h2>
            <p>Performance Review</p>
        </div>
        <div class="score-badge-box">
            <span>Score</span>
            <h3><?= $score ?> / <?= count($all_questions) ?></h3>
        </div>
    </div>

    <?php foreach($all_questions as $q): 
        $user_ans = $student_answers[$q['id']] ?? '';
        $correct = strtoupper(trim($q['correct_option']));
    ?>
    <div class="question-block">
        <h3 style="margin-top:0;"><?= htmlspecialchars($q['question_text']) ?></h3>
        <?php foreach(['A'=>'option_a', 'B'=>'option_b', 'C'=>'option_c', 'D'=>'option_d'] as $key => $col): 
            $is_correct = ($key === $correct);
            $is_user = ($key === $user_ans);
            $class = $is_correct ? 'correct-green' : ($is_user ? 'wrong-red' : '');
        ?>
        <div class="option-row <?= $class ?>">
            <span><?= $key ?>. <?= htmlspecialchars($q[$col]) ?></span>
            <?php if($is_correct): ?><span class="badge-status" style="background:#22c55e; color:white;">Correct</span><?php endif; ?>
            <?php if($is_user && !$is_correct): ?><span class="badge-status" style="background:#ef4444; color:white;">Your Choice</span><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>