<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Dynamic database configurations checks for admin user
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

    // Fetch Target Exam Details safely
    $exam_cols_check = $pdo->query("SHOW COLUMNS FROM exams");
    $exam_cols = $exam_cols_check->fetchAll(PDO::FETCH_COLUMN);
    $title_field = in_array('exam_title', $exam_cols) ? 'exam_title' : 'exam_name';

    $exam_stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $exam_stmt->execute([$exam_id]);
    $current_exam = $exam_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_exam) {
        header("Location: manage_exams.php");
        exit();
    }

    // --- FORM PROCESSOR: HANDLES BOTH ADD AND UPDATE ACTIONS ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $q_text = trim($_POST['question_text']);
        $op_a = trim($_POST['option_a']);
        $op_b = trim($_POST['option_b']);
        $op_c = trim($_POST['option_c']);
        $op_d = trim($_POST['option_d']);
        $correct = trim($_POST['correct_option']);

        if ($_POST['action'] === 'add_q') {
            // Insert regular new question entry
            $ins_sql = "INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($ins_sql)->execute([$exam_id, $q_text, $op_a, $op_b, $op_c, $op_d, $correct]);
        } elseif ($_POST['action'] === 'update_q') {
            // Update existing question configuration record
            $q_id = intval($_POST['question_id']);
            $up_sql = "UPDATE questions SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ? WHERE id = ? AND exam_id = ?";
            $pdo->prepare($up_sql)->execute([$q_text, $op_a, $op_b, $op_c, $op_d, $correct, $q_id, $exam_id]);
        }
        
        // Refresh cleanly to reset state
        header("Location: add_questions.php?id=" . $exam_id);
        exit();
    }

    // Gather existing questions inventory lists
    $q_list_stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
    $q_list_stmt->execute([$exam_id]);
    $all_questions = $q_list_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Connection Map Error: " . $e->getMessage());
}

$admin_name = htmlspecialchars($admin[$user_name_col] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OES Analyzer - Question Builder</title>
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
        .back-nav { display: inline-flex; align-items: center; gap: 6px; color: #64748b; text-decoration: none; font-weight: 600; font-size: 14px; margin-bottom: 20px; transition: color 0.2s; }
        .back-nav:hover { color: #2563eb; }
        
        .header-section { margin-bottom: 30px; }
        .header-section h1 { font-size: 28px; color: #0f172a; }
        .header-section span { color: #2563eb; font-weight: 700; }

        /* Form Controls Setup Layout */
        .grid-layout { display: grid; grid-template-columns: 1.2fr 1fr; gap: 32px; align-items: start; }
        .input-panel-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.01); padding: 32px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: sticky; top: 20px; }
        .input-panel-card:hover { box-shadow: 0 10px 32px rgba(15, 23, 42, 0.04); }
        
        .form-group { margin-bottom: 18px; }
        .form-label { display: block; font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        .form-input-text { width: 100%; padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: #f8fafc; color: #334155; }
        .form-input-text:focus { border-color: #2563eb; background: #fff; outline: none; }
        
        .btn-submit { width: 100%; background: #2563eb; color: #fff; border: none; padding: 12px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-top: 10px; }
        .btn-submit:hover { background: #1d4ed8; box-shadow: 0 8px 18px rgba(37, 99, 235, 0.2); }
        .btn-cancel-edit { width: 100%; background: #f1f5f9; color: #475569; border: none; padding: 10px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-align: center; margin-top: 8px; display: none; }

        /* Question Inventory Queue Styling */
        .inventory-panel { max-height: calc(100vh - 200px); overflow-y: auto; padding-right: 8px; }
        .question-log-node { background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 16px; border-left: 4px solid #cbd5e1; box-shadow: 0 4px 20px rgba(0,0,0,0.01); position: relative; }
        .question-log-node.active-node { border-left-color: #10b981; }
        
        .node-actions { position: absolute; top: 18px; right: 20px; display: flex; gap: 8px; }
        .btn-node-edit { background: none; border: none; color: #64748b; cursor: pointer; font-size: 14px; transition: color 0.2s; }
        .btn-node-edit:hover { color: #2563eb; }

        .question-title { font-weight: 600; color: #1e293b; margin-bottom: 10px; font-size: 15px; padding-right: 50px; }
        .options-summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; font-size: 13px; color: #64748b; margin-bottom: 10px; }
        .target-key-tag { font-size: 12px; font-weight: 700; color: #10b981; text-transform: uppercase; background: #d1fae5; display: inline-block; padding: 2px 8px; border-radius: 4px; }
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
            <a href="manage_exams.php" class="back-nav"><i class="fa-solid fa-arrow-left"></i> Return to List</a>
            
            <div class="header-section">
                <h1 id="panel_main_title">Question Builder Matrix</h1>
                <p>Configuring Questions for: <span><?= htmlspecialchars($current_exam[$title_field]) ?></span></p>
            </div>

            <div class="grid-layout">
                <div class="input-panel-card">
                    <h3 id="form_mode_heading" style="font-size:16px; margin-bottom:16px; color:#0f172a;">Create New Question</h3>
                    
                    <form action="add_questions.php?id=<?= $exam_id ?>" method="POST" id="questionForm">
                        <input type="hidden" name="action" id="form_action" value="add_q">
                        <input type="hidden" name="question_id" id="form_question_id" value="">
                        
                        <div class="form-group">
                            <label class="form-label">Question Text Statement</label>
                            <textarea name="question_text" id="input_q_text" rows="3" class="form-input-text" style="resize:none; height:80px;" placeholder="Type problem statement text here..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option A</label>
                            <input type="text" name="option_a" id="input_op_a" class="form-input-text" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option B</label>
                            <input type="text" name="option_b" id="input_op_b" class="form-input-text" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option C</label>
                            <input type="text" name="option_c" id="input_op_c" class="form-input-text" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option D</label>
                            <input type="text" name="option_d" id="input_op_d" class="form-input-text" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Correct Target Key Answer</label>
                            <select name="correct_option" id="input_correct" class="form-input-text" style="cursor:pointer;" required>
                                <option value="A">Option A</option>
                                <option value="B">Option B</option>
                                <option value="C">Option C</option>
                                <option value="D">Option D</option>
                            </select>
                        </div>
                        
                        <button type="submit" id="btn_submit_form" class="btn-submit">Push to Question Pool</button>
                        <button type="button" id="btn_cancel_form" class="btn-cancel-edit" onclick="resetFormToDefault()">Cancel Modifications</button>
                    </form>
                </div>

                <div class="inventory-panel">
                    <h3 style="font-size:16px; margin-bottom:16px; color:#0f172a;">Active Question Pool Queue (<?= count($all_questions) ?>)</h3>
                    <?php if (empty($all_questions)): ?>
                        <p style="color:#94a3b8; font-size:14px;">No items built for this evaluation sheet context yet.</p>
                    <?php else: $index = 1; foreach ($all_questions as $q): ?>
                        <div class="question-log-node active-node" id="node_<?= $q['id'] ?>">
                            <div class="node-actions">
                                <button class="btn-node-edit" title="Modify Question" 
                                        onclick="enableInlineEdit(
                                            <?= $q['id'] ?>, 
                                            '<?= addslashes(htmlspecialchars($q['question_text'])) ?>', 
                                            '<?= addslashes(htmlspecialchars($q['option_a'])) ?>', 
                                            '<?= addslashes(htmlspecialchars($q['option_b'])) ?>', 
                                            '<?= addslashes(htmlspecialchars($q['option_c'] ?? '')) ?>', 
                                            '<?= addslashes(htmlspecialchars($q['option_d'] ?? '')) ?>', 
                                            '<?= $q['correct_option'] ?>'
                                        )">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                            </div>
                            <div class="question-title">#<?= $index++ ?>. <?= htmlspecialchars($q['question_text']) ?></div>
                            <div class="options-summary-grid">
                                <div><strong>A:</strong> <?= htmlspecialchars($q['option_a']) ?></div>
                                <div><strong>B:</strong> <?= htmlspecialchars($q['option_b']) ?></div>
                                <div><strong>C:</strong> <?= htmlspecialchars($q['option_c'] ?? '') ?></div>
                                <div><strong>D:</strong> <?= htmlspecialchars($q['option_d'] ?? '') ?></div>
                            </div>
                            <div class="target-key-tag">Key: Option <?= htmlspecialchars($q['correct_option']) ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function enableInlineEdit(id, text, a, b, c, d, correct) {
            // Change structural form labels & behaviors
            document.getElementById('form_mode_heading').innerText = "Modify Existing Question Record";
            document.getElementById('btn_submit_form').innerText = "Apply Question Updates";
            document.getElementById('btn_cancel_form').style.display = "block";
            
            // Adjust Hidden field state values
            document.getElementById('form_action').value = "update_q";
            document.getElementById('form_question_id').value = id;
            
            // Map inputs values cleanly
            document.getElementById('input_q_text').value = text;
            document.getElementById('input_op_a').value = a;
            document.getElementById('input_op_b').value = b;
            document.getElementById('input_op_c').value = c;
            document.getElementById('input_op_d').value = d;
            document.getElementById('input_correct').value = correct;

            // Smooth scroll view directly to input form layout card on mobile/smaller viewports
            document.getElementById('questionForm').scrollIntoView({ behavior: 'smooth' });
        }

        function resetFormToDefault() {
            // Reset structural labels & layouts back to basic insertion behaviors
            document.getElementById('form_mode_heading').innerText = "Create New Question";
            document.getElementById('btn_submit_form').innerText = "Push to Question Pool";
            document.getElementById('btn_cancel_form').style.display = "none";
            
            // Wipe data keys
            document.getElementById('form_action').value = "add_q";
            document.getElementById('form_question_id').value = "";
            
            // Reset fields
            document.getElementById('questionForm').reset();
        }
    </script>
</body>
</html>