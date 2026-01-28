<?php
// config/config.php
header('Content-Type: text/html; charset=utf-8');

define('APP_NAME', 'MyDiary');
define('BASE_URL', 'http://localhost/MyDiary/'); // Adjust if needed
define('SESSION_TIMEOUT', 18000); // 300 minutes in seconds

// Timezone
date_default_timezone_set('Asia/Karachi'); // Adjust as per requirement or make it dynamic

// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
