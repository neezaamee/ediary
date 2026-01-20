<?php
// autograph/search.php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$search_results = null;

// Handle Search (GET)
if (isset($_GET['query'])) {
    $query = sanitize($_GET['query']);
    $search_like = "%" . $query . "%";
    $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE (username LIKE ? OR full_name LIKE ?) AND id != ? LIMIT 20");
    $stmt->bind_param("ssi", $search_like, $search_like, $user_id);
    $stmt->execute();
    $search_results = $stmt->get_result();
}

// Handle Autograph Request (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'request_autograph') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "CSRF Token Validation Failed";
    } else {
        $target_id = (int)$_POST['target_id'];
        
        // Check if self
        if ($target_id == $user_id) {
            $error = "You cannot request an autograph from yourself.";
        } else {
            // Check if request already exists (pending or accepted)
            $check = $conn->prepare("SELECT id, status FROM autograph_requests WHERE requester_id = ? AND target_user_id = ?");
            $check->bind_param("ii", $user_id, $target_id);
            $check->execute();
            $res = $check->get_result();
            
            if ($res->num_rows > 0) {
                 $row = $res->fetch_assoc();
                 if ($row['status'] == 'pending') {
                    $error = "You check already sent a pending request to this user.";
                 } elseif ($row['status'] == 'accepted') {
                     $error = "This user has already accepted your request.";
                 } else {
                     // Rejected? Maybe allow re-request? For now block.
                     $error = "Unable to send request.";
                 }
            } else {
                $ins = $conn->prepare("INSERT INTO autograph_requests (requester_id, target_user_id) VALUES (?, ?)");
                $ins->bind_param("ii", $user_id, $target_id);
                if ($ins->execute()) {
                    setFlashMessage('success', 'Autograph request sent!');
                     // Stay on page
                } else {
                    $error = "Error sending request.";
                }
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <!-- Breadcrumb or Back Link -->
        <a href="wall.php" class="btn btn-outline-secondary mb-3"><i class="fa-solid fa-arrow-left"></i> Back to My Wall</a>

        <div class="glass-card">
            <h4><i class="fa-solid fa-magnifying-glass"></i> Search Results</h4>
            
            <?php if (isset($_GET['query'])): ?>
                <p class="text-muted">Showing results for: <strong><?php echo htmlspecialchars($_GET['query']); ?></strong></p>
                
                <?php if ($search_results && $search_results->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while($user = $search_results->fetch_assoc()): ?>
                            <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> 
                                    <small class="text-muted">(@<?php echo htmlspecialchars($user['username']); ?>)</small>
                                </div>
                                <div>
                                     <a href="../profile_view.php?username=<?php echo $user['username']; ?>" class="btn btn-sm btn-outline-info me-1">
                                        <i class="fa-solid fa-eye"></i> View Profile
                                    </a>
                                    
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="request_autograph">
                                        <input type="hidden" name="target_id" value="<?php echo $user['id']; ?>">
                                        
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="fa-solid fa-signature"></i> Request Autograph
                                        </button>
                                        <a href="write.php?to=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary ms-1">
                                            <i class="fa-solid fa-pen-fancy"></i> Sign Wall
                                        </a>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">No users found matching your query.</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">Use the search bar in the navigation to find users.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
