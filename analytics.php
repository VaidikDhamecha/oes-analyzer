<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// --- HANDLE SECURE DATA DELETION REQUESTS ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        // Auto-detect results table name dynamically
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $results_table = in_array('user_exams', $tables) ? 'user_exams' : 'results';

        $delete_stmt = $pdo->prepare("DELETE FROM {$results_table} WHERE id = ?");
        $delete_stmt->execute([$delete_id]);

        // Refresh page cleanly to verify it won't load again
        header("Location: analytics.php");
        exit();
    } catch (PDOException $e) {
        die("Error processing record removal: " . $e->getMessage());
    }
}

try {
    // Check tables to ensure safe queries on user structures
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

    // Schema Check for exam configuration table title fields
    $exam_cols_check = $pdo->query("SHOW COLUMNS FROM exams");
    $exam_cols = $exam_cols_check->fetchAll(PDO::FETCH_COLUMN);
    $exam_title_field = in_array('exam_title', $exam_cols) ? 'exam_title' : 'exam_name';

    // Set results table context
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $results_table = in_array('user_exams', $tables) ? 'user_exams' : 'results';

    $results_cols_check = $pdo->query("SHOW COLUMNS FROM {$results_table}");
    $results_cols = $results_cols_check->fetchAll(PDO::FETCH_COLUMN);
    
    $score_field = 'score';
    if (in_array('total_marks', $results_cols)) { $score_field = 'total_marks'; }
    elseif (in_array('marks_obtained', $results_cols)) { $score_field = 'marks_obtained'; }
    elseif (in_array('percentage', $results_cols)) { $score_field = 'percentage'; }

    // Fetch live performance tracking rows linked directly to real students
    $query_string = "
        SELECT r.id AS performance_id, r.*, u.{$user_name_col} AS student_name, e.{$exam_title_field} AS test_title 
        FROM {$results_table} r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN exams e ON r.exam_id = e.id
        ORDER BY r.id DESC";
        
    $performance_stmt = $pdo->query($query_string);
    $records = $performance_stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- AGGREGATE REAL LIVE DATABASE METRICS FOR THE CHART DIAGRAM ---
    $chart_labels = [];
    $chart_scores = [];
    
    // Take the latest 7 test attempts reversed to read left-to-right on timeline chart
    $chart_records = array_slice($records, 0, 7);
    $chart_records = array_reverse($chart_records);
    
    foreach ($chart_records as $rec) {
        $chart_labels[] = ($rec['student_name'] ?? 'Student') . ' (' . ($rec['test_title'] ?? 'Exam') . ')';
        $chart_scores[] = floatval($rec[$score_field] ?? 0);
    }

} catch (PDOException $e) {
    die("Performance Analytics System Exception: " . $e->getMessage());
}

$admin_name = htmlspecialchars($admin[$user_name_col] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OES Analyzer - Performance Metrics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f4f6f9; color: #333; min-height: 100vh; }
        
        /* Sidebar layout matching design rules */
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
        .header-container { margin-bottom: 30px; }
        .header-container h1 { font-size: 28px; color: #0f172a; }

        /* Dynamic Diagram Card Slot styling */
        .chart-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.01); padding: 24px; margin-bottom: 32px; width: 100%; height: 320px; }

        .data-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.01); padding: 24px; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { padding: 12px 16px; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; font-size: 14px; text-transform: uppercase; }
        .data-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 15px; }
        
        .score-indicator { font-weight: 700; color: #10b981; }
        .score-indicator.low-score { color: #ef4444; }
        
        /* Sleek actions config */
        .btn-action-delete { background-color: transparent; color: #ef4444; border: none; cursor: pointer; font-size: 15px; padding: 8px; transition: all 0.2s; border-radius: 50%; }
        .btn-action-delete:hover { color: #dc2626; background-color: rgba(239, 68, 68, 0.05); }
        .no-data { text-align: center; color: #94a3b8; padding: 40px 0; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div>
            <div class="sidebar-brand"><i class="fa-solid fa-microchip"></i> <span>OES ANALYZER</span></div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="admin_dashboard.php" class="sidebar-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li class="sidebar-item"><a href="manage_exams.php" class="sidebar-link"><i class="fa-solid fa-file-signature"></i> Manage Exams</a></li>
                <li class="sidebar-item"><a href="analytics.php" class="sidebar-link active"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
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
            <div class="header-container">
                <h1>Performance Dashboard</h1>
            </div>

            <div class="chart-card">
                <canvas id="livePerformanceChart"></canvas>
            </div>

            <div class="data-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Exam Name</th>
                            <th>Score Evaluation</th>
                            <th style="width: 80px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr><td colspan="4" class="no-data">No candidate submissions recorded yet.</td></tr>
                        <?php else: foreach ($records as $row): 
                            $perf_id = $row['performance_id'];
                            $stud_name = htmlspecialchars($row['student_name'] ?? 'Unknown Student');
                            $exam_name = htmlspecialchars($row['test_title'] ?? 'Deleted Exam Context');
                            $raw_score = floatval($row[$score_field] ?? 0);
                        ?>
                            <tr>
                                <td style="font-weight: 600; color: #1e293b;"><?= $stud_name ?></td>
                                <td><?= $exam_name ?></td>
                                <td>
                                    <span class="score-indicator <?= ($raw_score < 40) ? 'low-score' : '' ?>">
                                        <?= $raw_score ?>%
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="analytics.php?delete_id=<?= $perf_id ?>" 
                                       class="btn-action-delete" 
                                       title="Delete Record Permanently"
                                       onclick="return confirm('Are you sure you want to permanently delete this student record? This cannot be undone and won\'t load back.')">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('livePerformanceChart').getContext('2d');
        
        const labelsData = <?php echo json_encode($chart_labels); ?>;
        const scoresData = <?php echo json_encode($chart_scores); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labelsData.length ? labelsData : ['No Data'],
                datasets: [{
                    label: 'Latest Evaluation Scores (%)',
                    data: scoresData.length ? scoresData : [0],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.06)',
                    borderWidth: 3,
                    pointBackgroundColor: '#2563eb',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        min: 0,
                        max: 100,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            callback: function(value) { return value + '%'; },
                            color: '#64748b',
                            font: { size: 12 }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>