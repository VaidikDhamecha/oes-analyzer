<?php include 'admin_header.php'; ?>
<?php 
$q_id = $_GET['q_id'];
$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->execute([$q_id]);
$q = $stmt->fetch();
?>

<div style="max-width: 700px; margin: 0 auto;">
    <h2 style="font-weight: 800; margin-bottom: 20px;">Fix Question Mistake</h2>
    
    <div class="card">
        <form action="update_question_action.php" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
            <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
            <input type="hidden" name="exam_id" value="<?= $q['exam_id'] ?>">

            <textarea name="question_text" required style="height: 120px;"><?= htmlspecialchars($q['question_text']) ?></textarea>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="option_a" value="<?= htmlspecialchars($q['option_a']) ?>" required>
                <input type="text" name="option_b" value="<?= htmlspecialchars($q['option_b']) ?>" required>
                <input type="text" name="option_c" value="<?= htmlspecialchars($q['option_c']) ?>" required>
                <input type="text" name="option_d" value="<?= htmlspecialchars($q['option_d']) ?>" required>
            </div>

            <select name="correct_option" required>
                <option value="A" <?= $q['correct_option']=='A'?'selected':'' ?>>A</option>
                <option value="B" <?= $q['correct_option']=='B'?'selected':'' ?>>B</option>
                <option value="C" <?= $q['correct_option']=='C'?'selected':'' ?>>C</option>
                <option value="D" <?= $q['correct_option']=='D'?'selected':'' ?>>D</option>
            </select>

            <button type="submit" class="btn-primary" style="padding: 18px;">Update & Save Fix</button>
        </form>
    </div>
</div>

<?php include 'admin_footer.php'; ?>