<?php
if(session_status() === PHP_SESSION_NONE) session_start();
require_once 'db_config.php';
if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$profile_img = !empty($admin['profile_pic']) ? "uploads/" . $admin['profile_pic'] : "uploads/default.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar: #0f172a; --primary: #2563eb; --bg: #f1f5f9; --white: #ffffff; }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; font-size: 16px; }
        body { display: flex; background: var(--bg); height: 100vh; overflow: hidden; }

        /* --- STATIC SIDEBAR (No Entry Slide) --- */
        .sidebar { 
            width: 260px; 
            background: var(--sidebar); 
            display: flex; 
            flex-direction: column; 
            height: 100vh; 
            position: fixed; 
            z-index: 10;
        }

        .sidebar-header { padding: 30px 20px; color: white; font-weight: 700; font-size: 1.3rem; }
        
        /* --- NORMAL TAB ANIMATION --- */
        .menu-item { 
            display: flex; align-items: center; gap: 12px; padding: 14px 18px; 
            color: #94a3b8; text-decoration: none; border-radius: 12px; 
            margin: 5px 15px; 
            transition: all 0.3s ease; /* Smooth normal transition */
            font-weight: 500; 
            position: relative;
        }

        /* Hover Effect: Gentle background and slight shift */
        .menu-item:hover { 
            background: rgba(255, 255, 255, 0.08); 
            color: white; 
            padding-left: 25px; /* Smoothly pushes the text slightly */
        }

        /* Active Tab: Bright and highlighted */
        .menu-item.active { 
            background: var(--primary); 
            color: white; 
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3); 
        }

        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; }
        
        .top-nav { 
            height: 75px; background: var(--white); display: flex; 
            align-items: center; justify-content: flex-end; padding: 0 40px; 
            border-bottom: 1px solid #e2e8f0; 
        }

        /* --- FADE-UP ANIMATION FOR CONTENT ONLY --- */
        .animate-content { 
            opacity: 0; 
            animation: fadeUp 0.5s ease-out forwards; 
        }
        @keyframes fadeUp { 
            from { opacity: 0; transform: translateY(15px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        .card { 
            background: var(--white); border-radius: 18px; padding: 30px; 
            border: 1px solid #e2e8f0; transition: 0.3s ease; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 15px 20px -5px rgba(0,0,0,0.08); }

        .btn-primary { 
            background: var(--primary); color: white !important; border: none; 
            padding: 14px 28px; border-radius: 12px; font-weight: 700; 
            cursor: pointer; transition: 0.2s ease; text-decoration: none; display: inline-block; 
        }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
        
        .main-content { padding: 40px; overflow-y: auto; flex: 1; }
        input, select, textarea { width: 100%; padding: 15px; border-radius: 12px; border: 2px solid #f1f5f9; outline: none; transition: 0.2s; background: #f8fafc; }
        input:focus { border-color: var(--primary); background: #fff; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><i class="fa-solid fa-microchip"></i> OES ANALYZER</div>
        <div class="menu">
            <a href="admin_dashboard.php" class="menu-item <?= strpos($_SERVER['PHP_SELF'], 'dashboard')?'active':'' ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
            <a href="manage_exams.php" class="menu-item <?= strpos($_SERVER['PHP_SELF'], 'manage')?'active':'' ?>"><i class="fa-solid fa-file-pen"></i> Manage Exams</a>
            <a href="analytics.php" class="menu-item <?= strpos($_SERVER['PHP_SELF'], 'analytics')?'active':'' ?>"><i class="fa-solid fa-chart-line"></i> Performance</a>
            <a href="edit_profile.php" class="menu-item <?= strpos($_SERVER['PHP_SELF'], 'profile')?'active':'' ?>"><i class="fa-solid fa-user-gear"></i> Edit Profile</a>
        </div>
        <div style="padding: 25px; margin-top: auto;"><a href="logout.php" style="color:#f87171; text-decoration:none; font-weight:700;"><i class="fa-solid fa-power-off"></i> Logout</a></div>
    </div>
    <div class="main-wrapper">
        <header class="top-nav">
            <div style="display:flex; align-items:center; gap:15px; cursor:pointer;" onclick="location.href='edit_profile.php'">
                <span style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($admin['username']) ?></span>
                <img src="<?= $profile_img ?>" style="width:42px; height:42px; border-radius:50%; border:2px solid var(--primary); object-fit:cover;">
            </div>
        </header>
        <main class="main-content animate-content">