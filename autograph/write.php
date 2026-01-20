<?php
// autograph/write.php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$to_user_id = isset($_GET['to']) ? (int)$_GET['to'] : 0;
$req_id = isset($_GET['req_id']) ? (int)$_GET['req_id'] : 0;
$error = '';
$target_user = null;

// Fetch Target User Info
if ($to_user_id > 0) {
    $stmt = $conn->prepare("SELECT id, full_name, username FROM users WHERE id = ?");
    $stmt->bind_param("i", $to_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $target_user = $res->fetch_assoc();
    } else {
        $error = "User not found.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "CSRF Token Validation Failed";
    } else {
        $target_id = (int)$_POST['target_id'];
        $request_id_to_fulfill = (int)$_POST['request_id'];
        $message = sanitize($_POST['message']); 
        $visibility = sanitize($_POST['visibility']); // default public

        if (empty($message)) {
            $error = "Message cannot be empty.";
        } else {
            // Permission Check: Can sign anyone's wall? Yes, per requirements.
            // But if signing for someone else, it needs approval.
            $is_approved = 0;
            // Exception: If signing OWN wall (rare but possible), auto-approve?
            // Let's stick to rule: "display with the user permission on their wall".
            // So if author != owner, is_approved = 0.
            if ($user_id == $target_id) {
                $is_approved = 1; 
            }

            $stmt = $conn->prepare("INSERT INTO autographs (owner_id, author_id, message, visibility, is_approved) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iissi", $target_id, $user_id, $message, $visibility, $is_approved);
            
            if ($stmt->execute()) {
                // Badge Check (for Wall Owner)
                require_once '../includes/BadgeManager.php';
                $bm = new BadgeManager($conn);
                $bm->checkAndAward($target_id, 'autograph_received');

                // Notification (for Wall Owner)
                require_once '../includes/NotificationManager.php';
                $nm = new NotificationManager($conn);
                $nm->create($target_id, 'autograph', "New autograph from " . $_SESSION['full_name'], 'autograph/wall.php');

                // If this was fulfilling a request, mark request as accepted (completed)
                if ($request_id_to_fulfill > 0) {
                     // Check if this request ID matches the context
                     $upd = $conn->prepare("UPDATE autograph_requests SET status = 'accepted' WHERE id = ? AND target_user_id = ? AND requester_id = ?");
                     $upd->bind_param("iii", $request_id_to_fulfill, $user_id, $target_id); 
                     // Wait! Structure is: REQUESTER asks TARGET to sign.
                     // So if I am SIGNING (user_id), I am the TARGET of the request.
                     // The OWNER of the wall (target_id) is the REQUESTER.
                     // So: requester_id (wall owner) asks target_user_id (signer/me).
                     $upd->execute();
                }

                setFlashMessage('success', 'Autograph signed! It will appear on their wall after approval.');
                redirect('autograph/wall.php?user_id=' . $target_id); 
            } else {
                $error = "Error signing autograph.";
            }
        }
    }
}


require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="glass-card fade-in">
            <h2 class="mb-4 text-center"><i class="fa-solid fa-signature"></i> Sign an Autograph</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($target_user): ?>
                <div class="alert alert-info text-center">
                    You are signing the guestbook of <strong><?php echo htmlspecialchars($target_user['full_name']); ?></strong>
                </div>

                <form action="" method="POST">
                     <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                     <input type="hidden" name="target_id" value="<?php echo $target_user['id']; ?>">
                     <input type="hidden" name="request_id" value="<?php echo $req_id; ?>">
                     
                     <div class="mb-3">
                        <label class="form-label">Your Autograph Message</label>
                        <textarea name="message" class="form-control" rows="6" placeholder="Write something memorable..." required></textarea>
                     </div>

                     <div class="mb-3">
                        <label class="form-label">Visibility Preference</label>
                        <select name="visibility" class="form-select">
                            <option value="public">Public (Visible to everyone)</option>
                            <option value="friends">Friends Only (Visible to their friends)</option>
                            <option value="private">Private (Only they can see)</option>
                        </select>
                        <div class="form-text">The wall owner will need to approve this before it appears.</div>
                     </div>

                     <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Sign & Send for Approval</button>
                        <a href="search.php" class="btn btn-secondary">Cancel</a>
                     </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    No user selected. <a href="search.php">Search for a user first.</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
