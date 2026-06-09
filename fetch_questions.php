<?php
require_once 'db_config.php';
$exam_id = $_GET['exam_id'];

$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id DESC");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

if(!$questions) {
    echo '<p style="color: #94a3b8; font-style: italic;">No questions added yet.</p>';
}

foreach($questions as $index => $q) {
    echo "
    <div style='background:white; padding:15px; border-radius:10px; border:1px solid #e2e8f0; animation: slideUp 0.3s ease-out;'>
        <div style='font-weight:700; margin-bottom:5px; color:#1e293b;'>Q: " . htmlspecialchars($q['question_text']) . "</div>
        <div style='font-size:0.8rem; color:#64748b;'>Correct: <span style='color:#16a34a; font-weight:700;'>Option " . $q['correct_option'] . "</span></div>
    </div>";
}