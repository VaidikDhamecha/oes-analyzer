<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

// Authorization Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

try {
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $has_role_column = in_array('role', $columns);

    if ($has_role_column) {
        $userStmt = $pdo->prepare("SELECT id, username, profile_pic, role FROM users WHERE id = ?");
    } else {
        $userStmt = $pdo->prepare("SELECT id, username, profile_pic FROM users WHERE id = ?");
    }
    
    $userStmt->execute([$user_id]);
    $user_info = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        header("Location: logout.php");
        exit();
    }
    
    $display_name = htmlspecialchars($user_info['username']);
    
    $user_role = 'student';
    if (isset($user_info['role'])) {
        $user_role = strtolower(trim($user_info['role']));
    } elseif (isset($_SESSION['role'])) {
        $user_role = strtolower(trim($_SESSION['role']));
    }

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $newFileName = null;
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_image']['tmp_name'];
            $fileName = $_FILES['profile_image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
                $targetDir = 'uploads/';
                if (!is_dir($targetDir)) { 
                    mkdir($targetDir, 0755, true); 
                }
                move_uploaded_file($fileTmpPath, $targetDir . $newFileName);
            }
        }

        $updated_name = ($user_role === 'admin') ? trim($_POST['admin_title'] ?? '') : trim($_POST['username'] ?? '');
        
        if (!empty($updated_name)) {
            if ($newFileName) {
                $sql = "UPDATE users SET username = ?, profile_pic = ? WHERE id = ?";
                $params = [$updated_name, $newFileName, $user_id];
            } else {
                $sql = "UPDATE users SET username = ? WHERE id = ?";
                $params = [$updated_name, $user_id];
            }
            
            $pdo->prepare($sql)->execute($params);
            $success_msg = "Profile updated successfully!";
            
            $userStmt->execute([$user_id]);
            $user_info = $userStmt->fetch(PDO::FETCH_ASSOC);
            $display_name = htmlspecialchars($user_info['username']);
        }
    } catch (PDOException $e) {
        $error_msg = "Database Error: " . $e->getMessage();
    }
}

if (!empty($user_info['profile_pic'])) {
    $db_pic = $user_info['profile_pic'];
    $avatar_path = (file_exists('uploads/' . $db_pic)) ? 'uploads/' . $db_pic : $db_pic;
} else {
    $avatar_path = 'https://i.imgur.com/wvxPV9S.png'; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | OES ANALYZER</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: #f4f7fe; display: flex; min-height: 100vh; color: #1b2559; }

        /* EXACT PARENT SIDEBAR WRAPPER HOUSING PERSISTENT POSITION PROPERTIES */
        .sidebar { width: 260px; background: #0f172a; position: fixed; height: 100vh; z-index: 100; display: flex; flex-direction: column; }
        
        /* THE BRAND LOGO ROW COMPONENT WITH INTEGRATED ENGINE ICON */
        .logo-section { padding: 30px 24px; display: flex; align-items: center; gap: 12px; }
        .logo-section i { color: #ffffff; font-size: 22px; }
        .logo-section h2 { font-size: 20px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px; margin: 0; }
        
        /* INNER APP LIST CONTAINER FLOW */
        .menu-list { list-style: none; padding: 0 12px; flex: 1; position: relative; display: flex; flex-direction: column; }
        .menu-item { margin-bottom: 4px; }
        .menu-item a { display: flex; align-items: center; gap: 12px; padding: 14px 16px; color: #94a3b8; font-size: 15px; font-weight: 600; text-decoration: none; border-radius: 10px; transition: all 0.2s ease; }
        .menu-item a i { font-size: 16px; width: 20px; color: #94a3b8; }
        
        .menu-item.active a { color: #ffffff; background: #2563eb; }
        .menu-item.active a i { color: #ffffff; }
        .menu-item a:hover:not(.active) { color: #ffffff; background: rgba(255, 255, 255, 0.05); }
        
        /* EXACT LOGOUT PINNED VIEWPORT POSITION MATCHING MANAGE_EXAMS FROM YOUR SCREENSHOT */
        .logout-btn { position: absolute; bottom: 24px; left: 12px; right: 12px; margin-bottom: 0; }
        .logout-btn a { color: #f87171 !important; }
        .logout-btn a i { color: #f87171 !important; }

        /* CONTENT WORKSPACE WRAPPER PANELS */
        .main-content { margin-left: 260px; flex: 1; background: #f4f7fe; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* TOP APP WINDOW STATUS NAVIGATION BAR DISPLAY CONTAINER */
        .top-header-navbar { height: 80px; background: #ffffff; display: flex; justify-content: flex-end; align-items: center; padding: 0 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .header-user-block { display: flex; align-items: center; gap: 12px; }
        .header-username { font-size: 15px; font-weight: 700; color: #1e293b; }
        .header-avatar-circle { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }

        /* VIEWPORT CARD MODULES CONTAINER */
        .content-body { padding: 40px; flex: 1; }
        .page-title { font-size: 28px; font-weight: 700; color: #0f172a; margin-bottom: 30px; }
        
        .form-panel-card { background: #ffffff; border-radius: 16px; padding: 35px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); border: 1px solid #e2e8f0; max-width: 800px; }

        /* COMPONENT PANEL ELEMENT FIELDS SETUP */
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
        
        .text-input-field { width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 15px; font-weight: 500; color: #0f172a; outline: none; background: #ffffff; transition: border-color 0.2s; }
        .text-input-field:focus { border-color: #2563eb; }
        .text-input-field.disabled-style { background: #f8fafc; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0; }
        
        /* ACCENT PIC PREVIEW DRAG TARGET INTERFACE WRAPPER */
        .file-input-wrapper { display: flex; align-items: center; gap: 20px; background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px dashed #cbd5e1; }
        .file-input-wrapper img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; background: #fff; border: 2px solid #e2e8f0; }
        .file-upload-control { font-size: 14px; color: #64748b; }

        /* ALERTS BANNER SYSTEM FEEDBACK LOGIC ELEMENTS */
        .status-alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .status-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        /* EXACT DEEP BLUE FORM SAVE SUBMIT CONTROL STYLE */
        .btn-action-submit { background: #2563eb; color: #ffffff; font-size: 14px; font-weight: 600; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; transition: background-color 0.2s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-action-submit:hover { background: #1d4ed8; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo-section">
            <i class="fa-solid fa-microchip"></i>
            <h2>OES ANALYZER</h2>
        </div>
        
        <ul class="menu-list">
            <?php if ($user_role === 'admin'): ?>
                <li class="menu-item"><a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li class="menu-item"><a href="manage_exams.php"><i class="fa-solid fa-file-signature"></i> Manage Exams</a></li>
                <li class="menu-item"><a href="analytics.php"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
                <li class="menu-item active"><a href="edit_profile.php"><i class="fa-solid fa-user-gear"></i> Edit Profile</a></li>
            <?php else: ?>
                <li class="menu-item"><a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li class="menu-item"><a href="my_records.php"><i class="fa-solid fa-file-lines"></i> My Records</a></li>
                <li class="menu-item active"><a href="edit_profile.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
            <?php endif; ?>
            
            <li class="menu-item logout-btn"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        
        <div class="top-header-navbar">
            <div class="header-user-block">
                <span class="header-username"><?php echo $display_name; ?></span>
                <img src="<?php echo $avatar_path; ?>" class="header-avatar-circle" alt="Primary User Header Portrait Account">
            </div>
        </div>

        <div class="content-body">
            <h1 class="page-title">Manage Profile</h1>

            <div class="form-panel-card">
                
                <?php if(!empty($success_msg)): ?>
                    <div class="status-alert status-success">
                        <i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
                    
                    <div class="form-group">
                        <label class="form-label">Profile Image Brand Avatar</label>
                        <div class="file-input-wrapper">
                            <img src="<?php echo $avatar_path; ?>" alt="Profile Preview Panel Frame Display">
                            <input type="file" name="profile_image" class="file-upload-control" accept="image/*">
                        </div>
                    </div>

                    <?php if ($user_role === 'admin'): ?>
                        <div class="form-group">
                            <label class="form-label">Admin Username Handle</label>
                            <input type="text" name="admin_title" class="text-input-field" value="<?php echo $display_name; ?>" required>
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="username" class="text-input-field" value="<?php echo $display_name; ?>" required>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Database Status Connection Context</label>
                        <input type="text" class="text-input-field disabled-style" value="PDO System Driver Live Connection" readonly>
                    </div>

                    <button type="submit" class="btn-action-submit">
                        <i class="fa-solid fa-floppy-disk"></i> Save Profile Changes
                    </button>

                </form>
            </div>
        </div>
    </div>

</body>
</html>