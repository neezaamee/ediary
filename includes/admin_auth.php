<?php
// includes/admin_auth.php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('error', 'Please login to access admin panel.');
    redirect('login.php');
}

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    setFlashMessage('error', 'Access Denied. Admin privileges required.');
    redirect('dashboard.php');
}

// Function to log admin actions
function logAdminAction($conn, $admin_id, $action, $target_details = null) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, target_details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $admin_id, $action, $target_details, $ip);
    $stmt->execute();
}
?>
