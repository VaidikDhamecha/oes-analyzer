<?php include 'admin_header.php'; ?>
<?php 
$exam_id = $_GET['exam_id'] ?? null;
$stmt = $pdo->prepare("SELECT exam_title FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 style="font-size: 2.2rem; font-weight: 800; color: #0f172a;">Manage Questions</h1>
        <p style="color: #64748b; font-size: 1.1rem;">Exam: <span style="color: var(--primary); font-weight: 700;"><?= htmlspecialchars($exam['exam_title']) ?></span></p>
    </div>
    
    <!-- BUTTON 1: ADD NEW QUESTION -->
    <a href="add_questions.php?exam_id=<?= $exam_id ?>" class="btn-primary" style="padding: 12px 24px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-plus-circle"></i> Add New Question
    </a>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 20px 25px; color: #64748b; font-size: 0.85rem; font-weight: 700; width: 60px;">#</th>
                <th style="padding: 20px 25px; color: #64748b; font-size: 0.85rem; font-weight: 700;">QUESTION</th>
                <th style="padding: 20px 25px; color: #64748b; font-size: 0.85rem; font-weight: 700; width: 100px;">ANSWER</th>
                <th style="padding: 20px 25px; color: #64748b; font-size: 0.85rem; font-weight: 700; text-align: right;">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $q_stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
            $q_stmt->execute([$exam_id]);
            $count = 1;
            while ($q = $q_stmt->fetch()):
            ?>
            <tr style="border-bottom: 1px solid #f1f5f9; transition: 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                <td style="padding: 20px 25px; color: #94a3b8; font-weight: 600;"><?= $count++ ?></td>
                <td style="padding: 20px 25px; color: #1e293b; font-weight: 500;">
                    <?= htmlspecialchars(substr($q['question_text'], 0, 100)) ?><?= strlen($q['question_text']) > 100 ? '...' : '' ?>
                </td>
                <td style="padding: 20px 25px;">
                    <span style="background: #f0fdf4; color: #16a34a; padding: 4px 10px; border-radius: 6px; font-weight: 800; border: 1px solid #dcfce7;">
                        <?= $q['correct_option'] ?>
                    </span>
                </td>
                <td style="padding: 20px 25px; text-align: right;">
                    <!-- BUTTON 2: UPDATE OLD QUESTION (EDIT) -->
                    <a href="edit_specific_question.php?q_id=<?= $q['id'] ?>" style="background: #0f172a; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: 0.3s;">
                        <i class="fa-solid fa-pen-to-square" style="font-size: 0.8rem;"></i> Update
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div style="margin-top: 30px; display: flex; gap: 15px;">
    <a href="manage_exams.php" class="btn-primary" style="background: #64748b;">
        <i class="fa-solid fa-arrow-left"></i> Back to Exam List
    </a>
    <a href="admin_dashboard.php" class="btn-primary">
        <i class="fa-solid fa-house"></i> Dashboard
    </a>
</div>

<?php include 'admin_footer.php'; ?>