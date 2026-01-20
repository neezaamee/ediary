<?php
// Security Headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
// includes/functions.php

/**
 * Sanitize Input
 */
function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($input))));
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

/**
 * Flash Message (Set)
 */
function setFlashMessage($name, $message, $type = 'success') {
    $_SESSION[$name] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Flash Message (Get/Display)
 */
function displayFlashMessage($name) {
    if (isset($_SESSION[$name])) {
        $msg = $_SESSION[$name];
        echo '<div class="alert alert-' . $msg['type'] . ' alert-dismissible fade show" role="alert">
                ' . $msg['message'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION[$name]);
    }
}

/**
 * Check Session Timeout
 */
function checkSessionTimeout() {
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
            session_start();
            setFlashMessage('error', 'Session timed out. Please login again.');
            redirect('login.php');
        }
        $_SESSION['last_activity'] = time();
    }
}
// Call immediately if session is started
if (session_status() === PHP_SESSION_ACTIVE) {
    checkSessionTimeout();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect
 */
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

/**
 * Shorten Text
 */
function shortenText($text, $chars = 100) {
    if (strlen($text) > $chars) {
        return substr($text, 0, $chars) . "...";
    }
    return $text;
}
?>
