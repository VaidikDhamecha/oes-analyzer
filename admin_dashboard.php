<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
    // Dynamic schema validation for user identifiers
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

    // Dynamic dynamic counts from Database
    $total_exams = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();
    $active_exams = $pdo->query("SELECT COUNT(*) FROM exams WHERE status = 'Active'")->fetchColumn();
    $inactive_exams = $pdo->query("SELECT COUNT(*) FROM exams WHERE status = 'Inactive'")->fetchColumn();
    
    // Fallback safe total test attempt submissions count
    try {
        $total_submissions = $pdo->query("SELECT COUNT(*) FROM user_exams")->fetchColumn();
    } catch (PDOException $e) {
        $total_submissions = $pdo->query("SELECT COUNT(*) FROM results")->fetchColumn();
    }

    // Grab up to 5 exams for preview row loop
    $exams_stmt = $pdo->query("SELECT * FROM exams ORDER BY id DESC LIMIT 5");
    $exams = $exams_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Data aggregation connection failure: " . $e->getMessage());
}

$admin_name = htmlspecialchars($admin[$user_name_col] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OES Analyzer - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f4f6f9; color: #333; min-height: 100vh; }
        
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

        .main-content { margin-left: 260px; flex-grow: 1; display: flex; flex-direction: column; width: calc(100% - 260px); }
        .top-navbar { height: 70px; background-color: #fff; display: flex; align-items: center; justify-content: flex-end; padding: 0 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .admin-profile-badge { display: flex; align-items: center; gap: 12px; }
        .admin-name { font-weight: 600; color: #1e293b; }
        .admin-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }

        .content-body { padding: 40px; }
        .welcome-heading { font-size: 28px; color: #0f172a; margin-bottom: 8px; }
        .welcome-subtext { color: #64748b; font-size: 15px; margin-bottom: 32px; }
        
        .metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 40px; }
        .metric-card { background-color: #fff; padding: 24px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.01); transition: all 0.3s ease; }
        .metric-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04); }
        .metric-value { font-size: 28px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .metric-title { font-size: 14px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .metric-icon-box { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        
        .bg-blue { background-color: #eff6ff; color: #2563eb; }
        .bg-green { background-color: #ecfdf5; color: #10b981; }
        .bg-amber { background-color: #fffbeb; color: #d97706; }
        .bg-purple { background-color: #faf5ff; color: #9333ea; }

        .data-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.01); padding: 24px; }
        .card-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 18px; font-weight: 700; color: #0f172a; }
        
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { padding: 12px 16px; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; font-size: 14px; text-transform: uppercase; }
        .data-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 15px; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 13px; font-weight: 600; background-color: #d1fae5; color: #065f46; }
        .status-badge.inactive { background-color: #fee2e2; color: #991b1b; }
        .no-data { text-align: center; color: #94a3b8; padding: 30px 0; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div>
            <div class="sidebar-brand"><i class="fa-solid fa-microchip"></i> <span>OES ANALYZER</span></div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="admin_dashboard.php" class="sidebar-link active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li class="sidebar-item"><a href="manage_exams.php" class="sidebar-link"><i class="fa-solid fa-file-signature"></i> Manage Exams</a></li>
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
                    <img src="uploads/<?= htmlspecialchars($admin[$db_pic_field]) ?>" alt="Admin" class="admin-avatar">
                <?php else: ?>
                    <img src="https://i.imgur.com/w3duR07.png" alt="Admin" class="admin-avatar">
                <?php endif; ?>
            </div>
        </div>

        <div class="content-body">
            <h1 class="welcome-heading">Welcome back, <?= $admin_name ?>!</h1>
            <p class="welcome-subtext">System performance metrics and execution monitors are active.</p>

            <div class="metrics-grid">
                <div class="metric-card">
                    <div>
                        <div class="metric-value"><?= $total_exams ?></div>
                        <div class="metric-title">Total Exams</div>
                    </div>
                    <div class="metric-icon-box bg-blue"><i class="fa-solid fa-file-invoice"></i></div>
                </div>
                <div class="metric-card">
                    <div>
                        <div class="metric-value"><?= $active_exams ?></div>
                        <div class="metric-title">Active</div>
                    </div>
                    <div class="metric-icon-box bg-green"><i class="fa-solid fa-circle-check"></i></div>
                </div>
                <div class="metric-card">
                    <div>
                        <div class="metric-value"><?= $inactive_exams ?></div>
                        <div class="metric-title">Inactive</div>
                    </div>
                    <div class="metric-icon-box bg-amber"><i class="fa-solid fa-circle-minus"></i></div>
                </div>
                <div class="metric-card">
                    <div>
                        <div class="metric-value"><?= $total_submissions ?></div>
                        <div class="metric-title">Submissions</div>
                    </div>
                    <div class="metric-icon-box bg-purple"><i class="fa-solid fa-graduation-cap"></i></div>
                </div>
            </div>

            <div class="data-card">
                <div class="card-header-row">
                    <div class="card-title">Recent Exam Manifests</div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Exam Title</th>
                            <th>Duration</th>
                            <th>Status Visibility</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr><td colspan="3" class="no-data">No exams found in database.</td></tr>
                        <?php else: foreach ($exams as $exam): 
                            $ex_title = htmlspecialchars($exam['exam_title'] ?? $exam['exam_name'] ?? '');
                            $ex_dur = htmlspecialchars($exam['duration'] ?? '0');
                            $ex_stat = htmlspecialchars($exam['status'] ?? 'Active');
                        ?>
                            <tr>
                                <td style="font-weight: 600; color: #1e293b;"><?= $ex_title ?></td>
                                <td><?= $ex_dur ?> Minutes</td>
                                <td><span class="status-badge <?= strtolower($ex_stat) ?>"><?= $ex_stat ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>