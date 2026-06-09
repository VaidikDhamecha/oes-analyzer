<?php
session_start();
require_once 'db_config.php';

// Check login status
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$status = "";

// --- 1. PROFILE UPDATE HANDLER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['username']);
    
    // Fetch current pic to retain if no new upload
    $curr = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $curr->execute([$user_id]);
    $user_data = $curr->fetch();
    $profile_pic = $user_data['profile_pic'];

    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $profile_pic = $target_file;
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET username = ?, profile_pic = ? WHERE id = ?");
    if ($stmt->execute([$new_name, $profile_pic, $user_id])) {
        $_SESSION['username'] = $new_name;
        $message = "Profile updated successfully!";
        $status = "success";
    }
}

// --- 2. DATA RETRIEVAL ---
// FIXED: Added execute() so active exams are pulled successfully, filtering out inactive ones
$exam_query = $pdo->prepare("SELECT * FROM exams WHERE status = 'Active' ORDER BY id DESC");
$exam_query->execute();
$exams = $exam_query->fetchAll();

// Fetch My Records (Results)
$records_stmt = $pdo->prepare("
    SELECT r.*, e.exam_title 
    FROM results r 
    JOIN exams e ON r.exam_id = e.id 
    WHERE r.user_id = ? 
    ORDER BY r.date_taken DESC
");
$records_stmt->execute([$user_id]);
$records = $records_stmt->fetchAll();

// Calculate Stats for Cards
$total_attempts = count($records);
$avg_score = 0;
if ($total_attempts > 0) {
    $sum = array_sum(array_column($records, 'score'));
    $avg_score = round($sum / $total_attempts, 1);
}

// Fetch User Info
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OES Dashboard | <?= htmlspecialchars($user['username']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-bg: #111827; --primary: #2563eb; --bg: #f8fafc; }
        body { margin: 0; font-family: 'Inter', sans-serif; display: flex; background: var(--bg); height: 100vh; overflow: hidden; }

        /* Sidebar & Tab Animations */
        .sidebar { width: 280px; background: var(--sidebar-bg); height: 100vh; padding: 30px 20px; color: white; flex-shrink: 0; }
        .nav-tab { 
            display: flex; align-items: center; padding: 14px 20px; border-radius: 12px; 
            color: #94a3b8; text-decoration: none; margin-bottom: 10px; cursor: pointer;
            transition: all 0.3s ease; 
        }
        .nav-tab:hover { background: rgba(255,255,255,0.05); color: white; transform: translateX(8px); }
        .nav-tab.active { background: var(--primary); color: white; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4); }

        /* Content Area */
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .section { display: none; animation: fadeIn 0.4s ease-out; }
        .section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Header & Profile */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .profile-avatar { width: 55px; height: 55px; border-radius: 50%; border: 2px solid var(--primary); overflow: hidden; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--primary); }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }

        /* Dashboard Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: 0.3s; border: 1px solid transparent; }
        .stat-card:hover { transform: translateY(-5px); border-color: var(--primary); }

        /* Exam Cards */
        .exam-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .exam-card { background: white; padding: 25px; border-radius: 18px; border: 1px solid #e2e8f0; transition: 0.3s; }
        .exam-card:hover { box-shadow: 0 10px 15px rgba(0,0,0,0.1); transform: translateY(-3px); }
        .btn-start { display: block; width: 100%; padding: 12px; background: var(--primary); color: white; text-align: center; text-decoration: none; border-radius: 10px; font-weight: bold; margin-top: 15px; transition: 0.2s; }
        .btn-start:hover { opacity: 0.9; }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        th, td { padding: 18px; text-align: left; border-bottom: 1px solid #f1f5f9; }
        th { background: #f8fafc; color: #64748b; font-weight: 600; }

        /* Settings Form */
        .settings-card { background: white; padding: 40px; border-radius: 24px; max-width: 600px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; box-sizing: border-box; }
        .alert { background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:20px; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>

<div class="sidebar">
    <h1 style="margin-bottom: 40px; padding-left: 15px;">OES</h1>
    <div class="nav-tab active" onclick="showSection('dashboard', this)"><i class="fas fa-home"></i>&nbsp;&nbsp; Dashboard</div>
    <div class="nav-tab" onclick="showSection('records', this)"><i class="fas fa-file-alt"></i>&nbsp;&nbsp; My Records</div>
    <div class="nav-tab" onclick="showSection('settings', this)"><i class="fas fa-cog"></i>&nbsp;&nbsp; Settings</div>
    <a href="logout.php" class="nav-tab" style="color: #ef4444; margin-top: 50px; text-decoration: none;"><i class="fas fa-sign-out-alt"></i>&nbsp;&nbsp; Logout</a>
</div>

<div class="main-content">
    <div class="header">
        <h1 id="page-title" style="font-weight: 800;">Welcome Back, <?= htmlspecialchars($user['username']) ?>!</h1>
        <div class="profile-avatar">
            <?php if($user['profile_pic']): ?>
                <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile">
            <?php else: ?>
                <?= strtoupper(substr($user['username'], 0, 2)) ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="dashboard" class="section active">
        <div class="stats-grid">
            <div class="stat-card">
                <p style="color: #64748b; font-weight: 600;">Total Attempts</p>
                <h2 style="font-size: 3rem; margin: 5px 0;"><?= $total_attempts ?></h2>
            </div>
            <div class="stat-card">
                <p style="color: #64748b; font-weight: 600;">Avg Performance</p>
                <h2 style="color: var(--primary); font-size: 3rem; margin: 5px 0;"><?= $avg_score ?>%</h2>
            </div>
        </div>

        <h3 style="margin-bottom: 20px;">Active Examinations</h3>
        <div class="exam-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-top: 20px;">
    <?php if(empty($exams)): ?>
        <p style="color: #94a3b8;">No active exams available at the moment.</p>
    <?php else: ?>
        <?php foreach($exams as $exam): ?>
        <div class="exam-card" style="background: white; padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0; position: relative; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">
            
            <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: #2563eb;"></div>
            
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="background: #dcfce7; color: #166534; font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;">Active</span>
                    <i class="fas fa-file-signature" style="color: #cbd5e1;"></i>
                </div>
                <h4 style="margin: 0; font-size: 1.4rem; color: #1e293b; font-weight: 700; line-height: 1.2;">
                    <?= htmlspecialchars($exam['exam_title']) ?>
                </h4>
                <p style="color: #64748b; font-size: 0.95rem; margin-top: 12px; display: flex; align-items: center; gap: 8px;">
                    <i class="far fa-clock" style="color: #3b82f6;"></i> 
                    <strong><?= $exam['duration'] ?> Minutes</strong> limit
                </p>
            </div>

            <a href="view_exams.php?id=<?= $exam['id'] ?>" class="btn-start" style="display: block; width: 100%; padding: 14px; background: #2563eb; color: white; text-align: center; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 1rem; border: none; box-sizing: border-box; transition: background 0.2s;">
                Start Examination <i class="fas fa-chevron-right" style="font-size: 0.8rem; margin-left: 5px;"></i>
            </a>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
    </div>

    <div id="records" class="section">
    <h2 style="margin-bottom: 25px; color: #1e293b;">My Examination History</h2>
    <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px;">
        <thead>
            <tr style="text-align: left; color: #64748b; font-size: 0.9rem;">
                <th style="padding: 15px;">Exam Name</th>
                <th>Date Attempted</th>
                <th>Percentage</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Re-fetching records without needing to calculate marks
            $stmt = $pdo->prepare("SELECT r.*, e.exam_title 
                                   FROM results r 
                                   JOIN exams e ON r.exam_id = e.id 
                                   WHERE r.user_id = ? 
                                   ORDER BY r.date_taken DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $user_results = $stmt->fetchAll();

            if(empty($user_results)): ?>
                <tr><td colspan="4" style="text-align:center; padding:40px; background:white; border-radius:15px; color:#94a3b8;">You haven't attempted any exams yet.</td></tr>
            <?php else: 
                foreach($user_results as $res): ?>
                <tr style="background: white; transition: 0.2s;">
                    <td style="padding: 20px; border-radius: 12px 0 0 12px; font-weight: 600; color: #1e293b;">
                        <?= htmlspecialchars($res['exam_title']) ?>
                    </td>
                    <td style="color: #64748b;"><?= date('d M Y, h:i A', strtotime($res['date_taken'])) ?></td>
                    <td>
                        <span style="padding: 5px 12px; border-radius: 20px; font-weight: 700; background: <?= $res['score'] >= 50 ? '#dcfce7' : '#fee2e2' ?>; color: <?= $res['score'] >= 50 ? '#166534' : '#991b1b' ?>;">
                            <?= htmlspecialchars($res['score']) ?>%
                        </span>
                    </td>
                    <td style="border-radius: 0 12px 12px 0;">
                        <a href="solution.php?result_id=<?= $res['id'] ?>" style="color: #2563eb; text-decoration: none; font-weight: 600;">
                            View Solution <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
    <div id="settings" class="section">
        <div class="settings-card">
            <h2 style="margin-top: 0; margin-bottom: 25px;">Edit Profile</h2>
            <?php if($message): ?>
                <div class="alert"><i class="fas fa-check-circle"></i> <?= $message ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="image" accept="image/*">
                    <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 8px;">Upload a portrait photo for better results.</p>
                </div>
                <button type="submit" name="update_profile" class="btn-start" style="width: auto; padding: 12px 40px;">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<script>
    function showSection(sectionId, element) {
        // Hide all sections and remove active classes
        document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
        document.querySelectorAll('.nav-tab').forEach(tab => tab.classList.remove('active'));
        
        // Show selected section
        document.getElementById(sectionId).classList.add('active');
        element.classList.add('active');

        // Update main header title
        const title = document.getElementById('page-title');
        if(sectionId === 'settings') title.innerText = "Account Settings";
        else if(sectionId === 'records') title.innerText = "Your Performance Records";
        else title.innerText = "Welcome Back, <?= htmlspecialchars($user['username']) ?>!";
    }
</script>

</body>
</html>