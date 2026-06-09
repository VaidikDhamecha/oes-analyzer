<?php
include 'db_config.php';
session_start();

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$id]);
$exam = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['exam_title'];
    $duration = $_POST['duration_mins'];

    $update = $pdo->prepare("UPDATE exams SET exam_title = ?, duration_mins = ? WHERE id = ?");
    $update->execute([$title, $duration, $id]);

    header("Location: manage_exams.php?msg=Exam updated");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Exam</title>
    <style>
        body { font-family: sans-serif; background: #f8fafc; padding: 50px; }
        .form-card { background: white; padding: 30px; border-radius: 15px; max-width: 400px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="form-card">
        <h2>Edit Exam</h2>
        <form method="POST">
            <label>Exam Title</label>
            <input type="text" name="exam_title" value="<?php echo htmlspecialchars($exam['exam_title']); ?>" required>
            
            <label>Duration (Mins)</label>
            <input type="number" name="duration_mins" value="<?php echo $exam['duration_mins']; ?>" required>
            
            <button type="submit">Update Exam</button>
            <a href="manage_exams.php" style="display:block; text-align:center; margin-top:15px; color:#64748b; text-decoration:none;">Cancel</a>
        </form>
    </div>
</body>
</html>