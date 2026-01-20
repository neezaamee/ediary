<?php
// logout.php
require_once 'config/config.php';
require_once 'includes/functions.php';

session_start();
session_unset();
session_destroy();

// Redirect to login with a trick to restart session for flash message
session_start();
setFlashMessage('success', 'You have been logged out.');
redirect('login.php');
?>
