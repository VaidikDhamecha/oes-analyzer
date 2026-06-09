<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Student';

try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $results_table = in_array('user_exams', $tables) ? 'user_exams' : 'results';

    $res_cols = $pdo->query("SHOW COLUMNS FROM {$results_table}")->fetchAll(PDO::FETCH_COLUMN);
    $exam_cols = $pdo->query("SHOW COLUMNS FROM exams")->fetchAll(PDO::FETCH_COLUMN);
    
    $exam_title_field = 'title';
    if (in_array('exam_name', $exam_cols)) { $exam_title_field = 'exam_name'; }
    elseif (in_array('exam_title', $exam_cols)) { $exam_title_field = 'exam_title'; }

    $score_field = 'score';
    if (in_array('marks_obtained', $res_cols)) { $score_field = 'marks_obtained'; }

    $total_field = 'total_questions';
    if (in_array('total_questions', $res_cols)) { $total_field = 'total_questions'; }
    elseif (in_array('total_marks', $res_cols) && $score_field !== 'total_marks') { $total_field = 'total_marks'; }

    $date_field = 'id'; 
    if (in_array('date_attempted', $res_cols)) { $date_field = 'r.date_attempted'; }
    elseif (in_array('created_at', $res_cols)) { $date_field = 'r.created_at'; }

    $query = "SELECT r.id AS record_id, r.*, e.{$exam_title_field} AS exam_display_name 
              FROM {$results_table} r 
              JOIN exams e ON r.exam_id = e.id 
              WHERE r.user_id = ? 
              ORDER BY r.id DESC";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $userStmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $user_info = $userStmt->fetch(PDO::FETCH_ASSOC);

    $display_name = $user_info ? htmlspecialchars($user_info['username']) : htmlspecialchars($username);
    
    if ($user_info && !empty($user_info['profile_pic'])) {
        $db_pic = $user_info['profile_pic'];
        if (file_exists('uploads/' . $db_pic)) { $avatar_path = 'uploads/' . $db_pic; }
        elseif (file_exists('../uploads/' . $db_pic)) { $avatar_path = '../uploads/' . $db_pic; }
        else { $avatar_path = $db_pic; }
    } else {
        $avatar_path = 'https://i.imgur.com/wvxPV9S.png';
    }

} catch (PDOException $e) {
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Records | OES SYSTEM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', 'Segoe UI', sans-serif; }
        body { background-color: #f4f7fe; display: flex; min-height: 100vh; color: #1b2559; }

        /* EXACT SIDEBAR DESIGN MATCHING DASHBOARD PIXEL FOR PIXEL */
        .sidebar { width: 290px; background: #111c44; padding: 50px 0; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; }
        .logo-section { padding: 0 32px; margin-bottom: 40px; text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 30px; }
        .logo-section h2 { font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: 1px; text-transform: uppercase; }
        .logo-section h2 span { font-weight: 400; opacity: 0.6; }
        
        .menu-list { list-style: none; display: flex; flex-direction: column; gap: 5px; }
        .menu-item { position: relative; display: flex; align-items: center; }
        .menu-item a { display: flex; align-items: center; gap: 14px; padding: 16px 36px; color: #a3b1cc; font-size: 14px; font-weight: 500; text-decoration: none; width: 100%; transition: all 0.2s ease; }
        .menu-item a i { font-size: 16px; color: #a3b1cc; width: 20px; text-align: center; }
        
        .menu-item.active a { color: #ffffff; font-weight: 700; }
        .menu-item.active a i { color: #4318ff; }
        .menu-item.active::after { content: ''; position: absolute; right: 0; top: 4px; height: 36px; width: 4px; background: #4318ff; border-radius: 4px 0px 0px 4px; }
        .menu-item a:hover:not(.active) { color: #ffffff; }

        /* CONTENT CONTAINER AREA */
        .main-content { margin-left: 290px; flex: 1; padding: 40px; background-color: #f4f7fe; min-height: 100vh; }
        
        /* HEADER WIDGET CONTAINER STYLING */
        .top-header-navbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .page-info-title span { font-size: 14px; color: #707eae; font-weight: 500; margin-bottom: 4px; display: block; }
        .page-info-title h1 { font-size: 34px; font-weight: 700; color: #1b2559; letter-spacing: -0.5px; }
        
        .navbar-right-box { display: flex; align-items: center; background: #ffffff; padding: 6px 15px; border-radius: 30px; box-shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.08); gap: 15px; border: 1px solid #e9edf7; }
        .search-wrapper { display: flex; align-items: center; background: #f4f7fe; padding: 8px 14px; border-radius: 20px; gap: 10px; }
        .search-wrapper i { color: #1b2559; font-size: 12px; }
        .search-wrapper input { background: transparent; border: none; outline: none; font-size: 13px; color: #1b2559; font-weight: 500; width: 140px; }
        .navbar-right-box > i { color: #a3b1cc; font-size: 16px; cursor: pointer; }
        .profile-avatar { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; cursor: pointer; }

        /* CONTAINER DISPLAY CARDS */
        .table-card-wrapper { background: #ffffff; border-radius: 20px; padding: 30px; box-shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.03); border: 1px solid #e9edf7; }
        .table-header-title { font-size: 20px; font-weight: 700; color: #1b2559; margin-bottom: 24px; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 14px 20px; color: #a3b1cc; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e9edf7; }
        td { padding: 18px 20px; border-bottom: 1px solid #e9edf7; color: #1b2559; font-size: 15px; font-weight: 700; }
        tr:last-child td { border-bottom: none; }

        .date-txt { color: #a3b1cc; font-weight: 500; }
        .percentage-badge { display: inline-block; padding: 6px 14px; border-radius: 10px; font-size: 13px; font-weight: 700; background-color: #fee2e2; color: #ef4444; }
        .percentage-badge.pass { background-color: #e6fcf5; color: #059669; }

        .view-link { color: #4318ff; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .view-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo-section">
            <h2>OES <span>SYSTEM</span></h2>
        </div>
        <ul class="menu-list">
            <li class="menu-item"><a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li class="menu-item active"><a href="my_results.php"><i class="fa-solid fa-chart-simple"></i> My Records</a></li>
            <li class="menu-item"><a href="edit_profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
            <li class="menu-item" style="margin-top: auto;"><a href="logout.php" style="color: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-header-navbar">
            <div class="page-info-title">
                <span>Pages / My Records</span>
                <h1>Performance History</h1>
            </div>
            <div class="navbar-right-box">
                <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search...">
                </div>
                <i class="fa-solid fa-bell"></i>
                <i class="fa-solid fa-moon"></i>
                <i class="fa-solid fa-circle-info"></i>
                <img src="<?= $avatar_path ?>" class="profile-avatar" alt="Profile" onclick="window.location.href='edit_profile.php'">
            </div>
        </div>

        <div class="table-card-wrapper">
            <h2 class="table-header-title">My Examination History</h2>
            
            <?php if (count($records) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Exam Name</th>
                        <th>Date Attempted</th>
                        <th>Obtained Marks</th>
                        <th>Percentage</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $row): 
                        $score = $row[$score_field] ?? 0;
                        $total = $row[$total_field] ?? 0;
                        
                        $clean_date_col = str_replace('r.', '', $date_field);
                        $display_date = "Just Now";
                        if (!empty($row[$clean_date_col])) {
                            $display_date = date("d M Y, h:i A", strtotime($row[$clean_date_col]));
                        }
                        
                        $pct = ($total > 0) ? round(($score / $total) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['exam_display_name']) ?></td>
                        <td class="date-txt"><?= $display_date ?></td>
                        <td><?= $score ?> / <?= $total ?></td>
                        <td>
                            <span class="percentage-badge <?= ($pct >= 35) ? 'pass' : '' ?>">
                                <?= $pct ?>%
                            </span>
                        </td>
                        <td>
                            <a href="solution.php?result_id=<?= $row['record_id'] ?>" class="view-link">
                                View Solution <i class="fa-solid fa-chevron-right" style="font-size: 11px;"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="padding: 30px; text-align: center; color: #a3b1cc; font-weight: 600;">No examination records logged yet.</div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>