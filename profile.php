<?php
// profile.php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user data
$stmt = $conn->prepare("SELECT full_name, dob, username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "CSRF Token Validation Failed";
    } else {
        $full_name = sanitize($_POST['full_name']);
        $dob = sanitize($_POST['dob']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $current_password = $_POST['current_password'];

        if (empty($full_name) || empty($current_password)) {
            $error = "Name and Current Password are required.";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            
            if (password_verify($current_password, $res['password_hash'])) {
                // Update Name & DOB
                $upd_sql = "UPDATE users SET full_name = ?, dob = ? WHERE id = ?";
                $stmt = $conn->prepare($upd_sql);
                $stmt->bind_param("ssi", $full_name, $dob, $user_id);
                $stmt->execute();
                $_SESSION['full_name'] = $full_name; // Update session
                $success = "Profile updated successfully.";

                // Update Password if provided
                if (!empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        $error = "New passwords do not match.";
                    } elseif (strlen($new_password) < 6) {
                        $error = "Password must be at least 6 characters.";
                    } else {
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $pwd_upd = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $pwd_upd->bind_param("si", $new_hash, $user_id);
                        $pwd_upd->execute();
                        $success .= " Password changed.";
                    }
                }
            } else {
                $error = "Incorrect current password.";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="glass-card fade-in">
            <h2 class="mb-4"><i class="fa-solid fa-user-gear text-primary"></i> Edit Profile</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Username (Cannot change)</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Email (Cannot change)</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob']); ?>" required>
                    </div>
                </div>

                <hr>
                <h5 class="text-secondary mb-3">Change Password <small class="text-muted fw-light">(Leave empty to keep current)</small></h5>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                    </div>
                    <div class="col-md-6">
                         <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                </div>

                <hr>
                <div class="mb-4">
                    <label for="current_password" class="form-label text-danger">Current Password (Required to save changes)</label>
                    <input type="password" class="form-control border-danger" id="current_password" name="current_password" required>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
