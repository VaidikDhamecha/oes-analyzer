<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
    // Dynamic database configurations checks
    $user_columns_check = $pdo->query("SHOW COLUMNS FROM users");
    $user_columns = $user_columns_check->fetchAll(PDO::FETCH_COLUMN);
    
    $user_name_col = 'name';
    if (in_array('username', $user_columns)) { $user_name_col = 'username'; }
    elseif (in_array('full_name', $user_columns)) { $user_name_col = 'full_name'; }

    $db_pic_field = 'profile_pic';
    if (in_array('image', $user_columns)) { $db_pic_field = 'image'; }
    elseif (in_array('profile', $user_columns)) { $db_pic_field = 'profile'; }

    $admin_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $admin_stmt->execute([$user_id]);
    $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch exams matching your table structure 
    $exams_stmt = $pdo->query("SELECT * FROM exams ORDER BY id DESC");
    $exams = $exams_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$admin_name = htmlspecialchars($admin[$user_name_col] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OES Analyzer - Manage Examinations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f4f6f9; color: #333; min-height: 100vh; }
        
        /* --- SIDEBAR STYLE WITH SMOOTH TRANSITIONS --- */
        .sidebar { width: 260px; background-color: #0f172a; color: #fff; display: flex; flex-direction: column; justify-content: space-between; min-height: 100vh; position: fixed; transition: all 0.3s ease; }
        .sidebar-brand { padding: 24px; font-size: 20px; font-weight: 800; letter-spacing: 0.5px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #1e293b; }
        .sidebar-menu { list-style: none; padding: 20px 0; flex-grow: 1; }
        .sidebar-item { margin: 4px 16px; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #94a3b8; text-decoration: none; font-weight: 500; border-radius: 8px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        
        .sidebar-link:hover { background-color: #1e293b; color: #fff; transform: translateX(8px); }
        .sidebar-link.active { background-color: #2563eb; color: #fff; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25); transform: none; }
        .sidebar-link i { width: 20px; font-size: 16px; text-align: center; }
        .sidebar-footer { padding: 20px 16px; border-top: 1px solid #1e293b; }
        .logout-btn { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #ef4444; text-decoration: none; font-weight: 600; border-radius: 8px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .logout-btn:hover { background-color: rgba(239, 68, 68, 0.1); transform: translateX(8px); }

        /* Main Workspace Frame */
        .main-content { margin-left: 260px; flex-grow: 1; display: flex; flex-direction: column; width: calc(100% - 260px); }
        .top-navbar { height: 70px; background-color: #fff; display: flex; align-items: center; justify-content: flex-end; padding: 0 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .admin-profile-badge { display: flex; align-items: center; gap: 12px; }
        .admin-name { font-weight: 600; color: #1e293b; }
        .admin-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }

        .content-body { padding: 40px; }
        .header-action-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-action-container h1 { font-size: 28px; color: #0f172a; }
        
        .btn-primary { background-color: #2563eb; color: #fff; border: none; padding: 12px 22px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; text-decoration: none; }
        .btn-primary:hover { background-color: #1d4ed8; box-shadow: 0 8px 18px rgba(37, 99, 235, 0.2); transform: translateY(-2px); }

        .data-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.01); padding: 24px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .data-card:hover { box-shadow: 0 10px 32px rgba(15, 23, 42, 0.04); }
        
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { padding: 12px 16px; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .data-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 15px; }
        
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 13px; font-weight: 600; background-color: #d1fae5; color: #065f46; }
        .status-badge.inactive { background-color: #fee2e2; color: #991b1b; }
        
        .actions-cell { display: flex; align-items: center; gap: 12px; }
        .btn-action-add { background-color: #0f172a; color: #fff; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 14px; transition: all 0.2s; text-decoration: none; }
        .btn-action-add:hover { background-color: #1e293b; transform: translateY(-1px); }
        
        .btn-gear-edit { background: none; border: none; color: #64748b; cursor: pointer; font-size: 16px; transition: all 0.2s; padding: 4px; }
        .btn-gear-edit:hover { color: #2563eb; transform: rotate(45deg); }
        .btn-action-delete { background-color: transparent; color: #ef4444; border: none; cursor: pointer; font-size: 16px; padding: 8px; transition: all 0.2s; border-radius: 50%; }
        .btn-action-delete:hover { color: #dc2626; background-color: rgba(239, 68, 68, 0.05); }
        .no-data { text-align: center; color: #94a3b8; padding: 40px 0 !important; }

        /* --- EDIT OVERLAY MODAL GLASSMORPHISM ACCENT --- */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; }
        .modal-card { background: #fff; width: 100%; max-width: 480px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); padding: 32px; animation: modalFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        @keyframes modalFadeIn { from { transform: translateY(15px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .modal-header { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; color: #334155; background: #f8fafc; transition: all 0.2s; }
        .form-control:focus { border-color: #2563eb; background: #fff; outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px; }
        .btn-secondary { background: #f1f5f9; color: #475569; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-secondary:hover { background: #e2e8f0; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div>
            <div class="sidebar-brand"><i class="fa-solid fa-microchip"></i> <span>OES ANALYZER</span></div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="admin_dashboard.php" class="sidebar-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li class="sidebar-item"><a href="manage_exams.php" class="sidebar-link active"><i class="fa-solid fa-file-signature"></i> Manage Exams</a></li>
                <li class="sidebar-item"><a href="analytics.php" class="sidebar-link"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
                <li class="sidebar-item"><a href="edit_profile.php" class="sidebar-link"><i class="fa-solid fa-user-gear"></i> Edit Profile</a></li>
            </ul>
        </div>
        <div class="sidebar-footer"><a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
    </nav>

    <div class="main-content">
        <div class="top-navbar">
            <div class="admin-profile-badge">
                <span class="admin-name"><?= $admin_name ?></span>
                <?php if (!empty($admin[$db_pic_field]) && file_exists('uploads/' . $admin[$db_pic_field])): ?>
                    <img src="uploads/<?= htmlspecialchars($admin[$db_pic_field]) ?>" alt="Admin Avatar" class="admin-avatar">
                <?php else: ?>
                    <img src="https://i.imgur.com/w3duR07.png" alt="Admin Avatar" class="admin-avatar">
                <?php endif; ?>
            </div>
        </div>

        <div class="content-body">
            <div class="header-action-container">
                <h1>Manage Examinations</h1>
                <a href="create_exam.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Create New Exam</a>
            </div>

            <div class="data-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title & Settings</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr><td class="no-data" colspan="4">No exams scheduled yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($exams as $exam): 
                                $ex_id = $exam['id'];
                                $ex_title = htmlspecialchars($exam['exam_title'] ?? $exam['exam_name'] ?? '');
                                $ex_dur = htmlspecialchars($exam['duration'] ?? '0');
                                $ex_stat = htmlspecialchars($exam['status'] ?? 'Active');
                            ?>
                            <tr>
                                <td style="font-weight: 600;">
                                    <button class="btn-gear-edit" onclick="openInlineEdit(<?= $ex_id ?>, '<?= addslashes($ex_title) ?>', <?= $ex_dur ?>, '<?= addslashes($ex_stat) ?>')">
                                        <i class="fa-solid fa-gear"></i>
                                    </button> 
                                    <?= $ex_title ?>
                                </td>
                                <td><?= $ex_dur ?> Mins</td>
                                <td><span class="status-badge <?= strtolower($ex_stat) ?>"><?= $ex_stat ?></span></td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="add_questions.php?id=<?= $ex_id ?>" class="btn-action-add"><i class="fa-solid fa-list-check"></i> Add</a>
                                        <a href="delete_exam.php?id=<?= $ex_id ?>" class="btn-action-delete" onclick="return confirm('Are you sure you want to delete this exam?')"><i class="fa-solid fa-trash-can"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="editModal">
        <div class="modal-card">
            <div class="modal-header">Update Exam Rules</div>
            <form action="manage_exams.php" method="POST">
                <input type="hidden" name="action" value="update_exam">
                <input type="hidden" name="exam_id" id="modal_exam_id">
                
                <div class="form-group">
                    <label class="form-label">Exam Title</label>
                    <input type="text" name="exam_title" id="modal_exam_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Duration (Minutes)</label>
                    <input type="number" name="duration" id="modal_duration" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status Visibility</label>
                    <select name="status" id="modal_status" class="form-control">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeInlineEdit()">Dismiss</button>
                    <button type="submit" class="btn-primary" style="padding: 10px 20px;">Apply Rules</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openInlineEdit(id, title, duration, status) {
            document.getElementById('modal_exam_id').value = id;
            document.getElementById('modal_exam_title').value = title;
            document.getElementById('modal_duration').value = duration;
            document.getElementById('modal_status').value = status;
            document.getElementById('editModal').style.display = 'flex';
        }
         paternal
        function closeInlineEdit() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>

<?php
// Handle inline POST updates inside the same file to keep your directory clean!
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_exam') {
    $e_id = intval($_POST['exam_id']);
    $e_title = trim($_POST['exam_title']);
    $e_dur = intval($_POST['duration']);
    $e_stat = trim($_POST['status']);

    try {
        // Detects whether your schema uses exam_title or exam_name
        $exam_cols_check = $pdo->query("SHOW COLUMNS FROM exams");
        $exam_cols = $exam_cols_check->fetchAll(PDO::FETCH_COLUMN);
        $title_field = in_array('exam_title', $exam_cols) ? 'exam_title' : 'exam_name';

        $update_sql = "UPDATE exams SET {$title_field} = ?, duration = ?, status = ? WHERE id = ?";
        $pdo->prepare($update_sql)->execute([$e_title, $e_dur, $e_stat, $e_id]);
        
        echo "<script>window.location.href='manage_exams.php';</script>";
        exit();
    } catch (PDOException $e) {
        die("Update Error: " . $e->getMessage());
    }
}
?>