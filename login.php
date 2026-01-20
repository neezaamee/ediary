<?php
// login.php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "CSRF Token Validation Failed";
    } else {
        $login_id = sanitize($_POST['login_id']); // Username or Email
        $password = $_POST['password'];

        if (empty($login_id) || empty($password) || empty($_POST['captcha'])) {
            $error = "Please enter username/email, password, and captcha.";
        } elseif (!verifyMathCaptcha($_POST['captcha'])) {
            $error = "Incorrect captcha answer.";
        } else {
            // Check user by username OR email
            $stmt = $conn->prepare("SELECT id, username, full_name, password_hash, role, is_banned, failed_login_attempts, lockout_time FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $login_id, $login_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Check Lockout
                if ($user['lockout_time'] && strtotime($user['lockout_time']) > time()) {
                    $minutes_left = ceil((strtotime($user['lockout_time']) - time()) / 60);
                    $error = "Account locked due to too many failed attempts. Try again in $minutes_left minutes.";
                } elseif ($user['is_banned'] == 1) {
                    $error = "Your account has been suspended.";
                } elseif (password_verify($password, $user['password_hash'])) {
                    // Login Success
                    // Reset failed attempts
                    $conn->query("UPDATE users SET failed_login_attempts = 0, lockout_time = NULL WHERE id = " . $user['id']);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_activity'] = time(); // Init session timer
                    
                    setFlashMessage('success', 'Welcome back, ' . $user['full_name'] . '!');
                    redirect('dashboard.php');
                } else {
                    // Failed Login
                    $attempts = $user['failed_login_attempts'] + 1;
                    $lockout_sql = "";
                    if ($attempts >= 5) {
                        $lockout_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $lockout_sql = ", lockout_time = '$lockout_time'";
                        $error = "Too many failed attempts. Account locked for 15 minutes.";
                    } else {
                        $remaining = 5 - $attempts;
                        $error = "Invalid Password. $remaining attempts remaining before lockout.";
                    }
                    $conn->query("UPDATE users SET failed_login_attempts = $attempts $lockout_sql WHERE id = " . $user['id']);
                }
            } else {
                $error = "User not found.";
            }
            $stmt->close();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="glass-card fade-in">
            <h2 class="text-center mb-4"><i class="fa-solid fa-right-to-bracket"></i> Login</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label for="login_id" class="form-label">Username or Email</label>
                    <input type="text" class="form-control" id="login_id" name="login_id" required value="<?php echo isset($_POST['login_id']) ? $_POST['login_id'] : ''; ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">
                        Remember me
                    </label>
                </div>

                <div class="mb-3">
                    <label class="form-label">Are you human? (Captcha)</label>
                    <div class="input-group">
                        <span class="input-group-text"><?php echo generateMathCaptcha(); ?> = </span>
                        <input type="number" class="form-control" name="captcha" placeholder="Result" required>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            <div class="text-center mt-3">
                <p>New here? <a href="register.php">Create an account</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
