<?php 
require_once 'db_config.php';
include 'admin_header.php'; 

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$id]);
$exam = $stmt->fetch();

if (!$exam) { header("Location: manage_exams.php"); exit(); }
?>

<div style="max-width: 600px; margin: 40px auto;">
    <h1 style="font-weight: 800; margin-bottom: 10px;">Edit Exam Settings</h1>
    <p style="color: #64748b; margin-bottom: 30px;">Update title and duration for: <strong><?= htmlspecialchars($exam['exam_title']) ?></strong></p>

    <div class="card">
        <form action="update_exam_action.php" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
            <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">

            <div>
                <label style="display:block; margin-bottom:8px; font-weight:700;">Exam Title</label>
                <input type="text" name="exam_title" value="<?= htmlspecialchars($exam['exam_title']) ?>" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;">
            </div>

            <div>
                <label style="display:block; margin-bottom:8px; font-weight:700;">Duration (Minutes)</label>
                <input type="number" name="duration" value="<?= $exam['duration'] ?>" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;">
            </div>

            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <button type="submit" class="btn-primary" style="flex: 2; padding: 16px;">Save Changes</button>
                <a href="manage_exams.php" style="flex: 1; text-align: center; padding: 16px; border-radius: 12px; background: #f1f5f9; color: #64748b; text-decoration: none; font-weight: 700; border: 1px solid #e2e8f0;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'admin_footer.php'; ?>