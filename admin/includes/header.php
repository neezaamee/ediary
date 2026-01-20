<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo APP_NAME; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --sidebar-width: 250px;
        }
        body {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(180deg, #2c3e50 0%, #3498db 100%);
            color: white;
            z-index: 1000;
            transition: all 0.3s;
        }
        .sidebar-brand {
            padding: 20px;
            font-size: 1.5rem;
            font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-menu {
            padding: 20px 0;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left: 4px solid white;
        }
        .nav-link i {
            width: 25px;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: all 0.3s;
        }
        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card-stat:hover {
            transform: translateY(-5px);
        }
        .dark-mode body {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        .dark-mode .card {
            background-color: #2d2d2d;
            border-color: #404040;
            color: #e0e0e0;
        }
        .dark-mode .table {
            color: #e0e0e0;
        }
        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(var(--sidebar-width) * -1);
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fa-solid fa-cloud-moon"></i> AdminPanel
    </div>
    <ul class="nav flex-column sidebar-menu">
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="index.php">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>" href="users.php">
                <i class="fa-solid fa-users"></i> Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'memories.php') ? 'active' : ''; ?>" href="memories.php">
                <i class="fa-solid fa-book"></i> Memories
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_types.php') ? 'active' : ''; ?>" href="manage_types.php">
                <i class="fa-solid fa-tags"></i> Memory Types
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_moods.php') ? 'active' : ''; ?>" href="manage_moods.php">
                <i class="fa-solid fa-face-smile"></i> Moods
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'analytics.php') ? 'active' : ''; ?>" href="analytics.php">
                <i class="fa-solid fa-chart-line"></i> Analytics
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'logs.php') ? 'active' : ''; ?>" href="logs.php">
                <i class="fa-solid fa-list-check"></i> Audit Logs
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'database.php') ? 'active' : ''; ?>" href="database.php">
                <i class="fa-solid fa-database"></i> Database
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>" href="settings.php">
                <i class="fa-solid fa-gear"></i> Settings
            </a>
        </li>
        <li class="nav-item mt-4">
            <a class="nav-link" href="../dashboard.php">
                <i class="fa-solid fa-arrow-left"></i> Back to Site
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="../logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </li>
    </ul>
</div>

<div class="main-content">
    <nav class="navbar navbar-expand-lg navbar-light bg-light rounded shadow-sm mb-4 d-md-none">
        <div class="container-fluid">
            <button class="btn btn-primary" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
            <span class="navbar-brand ms-3">Admin Panel</span>
        </div>
    </nav>
    
    <?php 
    if (isset($_SESSION['success'])) displayFlashMessage('success');
    if (isset($_SESSION['error'])) displayFlashMessage('error');
    ?>
