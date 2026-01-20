<?php
// includes/header.php
// Ensure dependencies are loaded if not already
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/NotificationManager.php';

$notif_count = 0;
if (isset($_SESSION['user_id'])) {
    $nm = new NotificationManager($conn);
    $notif_count = $nm->getUnreadCount($_SESSION['user_id']);
}
?>
<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('APP_NAME') ? APP_NAME : 'MyDiary'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark') ? 'navbar-dark bg-dark' : 'navbar-light bg-light'; ?>">
  <div class="container">
    <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
        <i class="fa-solid fa-book-open-reader me-2"></i> <?php echo APP_NAME; ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (isLoggedIn()): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>dashboard.php">Dashboard</a></li>
            <!-- Navbar Search -->
            <li class="nav-item me-3">
                <form class="d-flex mt-1" action="<?php echo BASE_URL; ?>autograph/search.php" method="GET">
                    <div class="input-group input-group-sm">
                        <input class="form-control" type="search" name="query" placeholder="Find users..." aria-label="Search" required>
                        <button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                </form>
            </li>

            <!-- My Space Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="mySpaceDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                   My Space
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mySpaceDropdown">
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>diary/"><i class="fa-solid fa-book me-2"></i> My Diary</a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>autograph/wall.php"><i class="fa-solid fa-pen-nib me-2"></i> My Autographs</a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>insights.php"><i class="fa-solid fa-lightbulb me-2"></i> Personal Insights</a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>summary/view.php"><i class="fa-solid fa-sparkles me-2"></i> My Reflections</a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>diary/export.php"><i class="fa-solid fa-file-pdf me-2"></i> Life Archive (Export)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>diary/organize.php"><i class="fa-solid fa-folder-tree me-2"></i> Organize Memories</a></li>
                </ul>
            </li>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link fw-bold text-primary" href="<?php echo BASE_URL; ?>admin/">Admin Panel</a></li>
            <?php endif; ?>

            <li class="nav-item dropdown ms-2">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>profile.php"><i class="fa-solid fa-user-gear me-2"></i> Edit Profile</a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>profile_view.php?username=<?php echo $_SESSION['username']; ?>"><i class="fa-solid fa-eye me-2"></i> Public Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                </ul>
            </li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>login.php">Login</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>register.php">Register</a></li>
        <?php endif; ?>
        <li class="nav-item ms-2 align-self-center">
            <button id="theme-toggle" class="btn btn-sm btn-outline-secondary rounded-circle"><i class="fa-solid fa-moon"></i></button>
        </li>
        <?php if (isLoggedIn()): ?>
            <li class="nav-item ms-2 align-self-center">
                <a href="<?php echo BASE_URL; ?>notifications.php" class="btn btn-sm btn-outline-primary position-relative rounded-circle">
                    <i class="fa-solid fa-bell"></i>
                    <?php if ($notif_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $notif_count > 99 ? '99+' : $notif_count; ?>
                            <span class="visually-hidden">unread messages</span>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <!-- Flash Messages -->
    <?php
    if (isset($_SESSION['success'])) displayFlashMessage('success');
    if (isset($_SESSION['error'])) displayFlashMessage('error');
    ?>
