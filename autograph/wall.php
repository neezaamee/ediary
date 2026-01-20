<?php
// autograph/wall.php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$target_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $user_id;

$is_owner = ($user_id == $target_user_id);
$is_friend = false; // logic removed for now, or always true if public

// Fetch Target User
$user_res = $conn->query("SELECT * FROM users WHERE id = $target_user_id");
if ($user_res->num_rows == 0) {
    die("User not found via wall.");
}
$target_user = $user_res->fetch_assoc();

// Handle Actions (Approve/Delete Autograph)
if ($is_owner && $_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
        if (isset($_POST['action'])) {
            $autograph_id = (int)$_POST['autograph_id'];
            if ($_POST['action'] == 'approve') {
                $upd = $conn->prepare("UPDATE autographs SET is_approved = 1 WHERE id = ? AND owner_id = ?");
                $upd->bind_param("ii", $autograph_id, $user_id);
                $upd->execute();
                setFlashMessage('success', 'Autograph approved!');
            } elseif ($_POST['action'] == 'delete') {
                $del = $conn->prepare("DELETE FROM autographs WHERE id = ? AND owner_id = ?");
                $del->bind_param("ii", $autograph_id, $user_id);
                $del->execute();
                setFlashMessage('success', 'Autograph deleted.');
            }
             redirect('autograph/wall.php'); // Refresh to View Tab
        }
    }
}

// Fetch Autographs (Approved)
$sql = "SELECT a.*, u.full_name, u.username FROM autographs a JOIN users u ON a.author_id = u.id WHERE a.owner_id = ? AND a.is_approved = 1 ORDER BY a.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$approved_autographs = $stmt->get_result();

// Fetch Pending Autographs (Only if Owner)
$pending_autographs = null;
if ($is_owner) {
    $sql_pending = "SELECT a.*, u.full_name, u.username FROM autographs a JOIN users u ON a.author_id = u.id WHERE a.owner_id = ? AND a.is_approved = 0 ORDER BY a.created_at DESC";
    $stmt_p = $conn->prepare($sql_pending);
    $stmt_p->bind_param("i", $user_id);
    $stmt_p->execute();
    $pending_autographs = $stmt_p->get_result();
}

// Fetch Incoming Requests (For Inbox Tab)
$incoming_requests = null;
if ($is_owner) {
    $incoming_sql = "SELECT r.id, u.username, u.full_name, r.created_at, u.id as requester_user_id FROM autograph_requests r JOIN users u ON r.requester_id = u.id WHERE r.target_user_id = ? AND r.status = 'pending'";
    $stmt_inc = $conn->prepare($incoming_sql);
    $stmt_inc->bind_param("i", $user_id);
    $stmt_inc->execute();
    $incoming_requests = $stmt_inc->get_result();
}

// Fetch Sent Requests (For Sent Tab)
$outgoing_requests = null;
if ($is_owner) {
    $outgoing_sql = "SELECT r.id, u.username, u.full_name, r.created_at FROM autograph_requests r JOIN users u ON r.target_user_id = u.id WHERE r.requester_id = ? AND r.status = 'pending'";
    $stmt_out = $conn->prepare($outgoing_sql);
    $stmt_out->bind_param("i", $user_id);
    $stmt_out->execute();
    $outgoing_requests = $stmt_out->get_result();
}

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fa-solid fa-book-bookmark text-warning"></i> <?php echo ($is_owner) ? "My Autograph Wall" : htmlspecialchars($target_user['full_name']) . "'s Wall"; ?></h2>
            <?php if (!$is_owner): ?>
                <a href="write.php?to=<?php echo $target_user_id; ?>" class="btn btn-primary"><i class="fa-solid fa-pen-nib"></i> Sign This Wall</a>
            <?php endif; ?>
        </div>

        <?php if ($is_owner): ?>
            <!-- Tabs for Owner -->
            <ul class="nav nav-tabs mb-4" id="wallTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="wall-tab" data-bs-toggle="tab" data-bs-target="#wall-content" type="button" role="tab"><i class="fa-solid fa-note-sticky"></i> My Wall</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox-content" type="button" role="tab">
                        <i class="fa-solid fa-inbox"></i> Inbox 
                        <?php if ($incoming_requests && $incoming_requests->num_rows > 0): ?>
                            <span class="badge bg-danger"><?php echo $incoming_requests->num_rows; ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent-content" type="button" role="tab"><i class="fa-solid fa-paper-plane"></i> Sent Requests</button>
                </li>
            </ul>
        <?php endif; ?>

        <div class="tab-content" id="myTabContent">
            <!-- Wall Tab (Default) -->
            <div class="tab-pane fade show active" id="wall-content" role="tabpanel">
                
                <?php if ($is_owner && $pending_autographs && $pending_autographs->num_rows > 0): ?>
                    <div class="alert alert-warning">
                        <h5><i class="fa-solid fa-clock"></i> Pending Approvals</h5>
                        <div class="row">
                            <?php while($p = $pending_autographs->fetch_assoc()): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted">From: <?php echo htmlspecialchars($p['full_name']); ?></h6>
                                            <p class="card-text fst-italic">"<?php echo htmlspecialchars($p['message']); ?>"</p>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="autograph_id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <?php if ($approved_autographs->num_rows > 0): ?>
                        <?php while($row = $approved_autographs->fetch_assoc()): ?>
                            <div class="col-md-4 mb-4">
                                <div class="autograph-note h-100">
                                    <p><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                                    <div class="text-end mt-3">
                                        <small class="fw-bold">- <?php echo htmlspecialchars($row['full_name']); ?></small><br>
                                        <small class="text-muted" style="font-size: 0.8em;"><?php echo date('d M Y', strtotime($row['created_at'])); ?></small>
                                    </div>
                                    <?php if ($is_owner): ?>
                                        <div class="position-absolute top-0 end-0 p-2">
                                            <form action="" method="POST" onsubmit="return confirm('Delete this autograph?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="autograph_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="btn btn-sm text-danger p-0 border-0" style="background:none;"><i class="fa-solid fa-times"></i></button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center text-muted py-5">
                            <i class="fa-solid fa-pen-fancy fa-3x mb-3"></i>
                            <p>No autographs strictly on this wall yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($is_owner): ?>
                <!-- Inbox Tab -->
                <div class="tab-pane fade" id="inbox-content" role="tabpanel">
                    <div class="glass-card">
                        <h5>Requests FOR You</h5>
                        <p class="text-muted small">Confirm these requests by signing their wall.</p>
                        <?php if ($incoming_requests->num_rows > 0): ?>
                             <ul class="list-group list-group-flush bg-transparent">
                                <?php while($row = $incoming_requests->fetch_assoc()): ?>
                                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo date('d M Y', strtotime($row['created_at'])); ?></small>
                                        </div>
                                        <a href="write.php?to=<?php echo $row['requester_user_id']; ?>&req_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-pen-nib"></i> Sign Now
                                        </a>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">No pending requests.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sent Tab -->
                <div class="tab-pane fade" id="sent-content" role="tabpanel">
                    <div class="glass-card">
                         <h5>Sent Requests</h5>
                         <p class="text-muted small">People you have asked to sign your wall.</p>
                         <?php if ($outgoing_requests->num_rows > 0): ?>
                            <ul class="list-group list-group-flush bg-transparent">
                                <?php while($row = $outgoing_requests->fetch_assoc()): ?>
                                    <li class="list-group-item bg-transparent">
                                        <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                        <br><small class="text-muted">Requested on <?php echo date('d M Y', strtotime($row['created_at'])); ?></small>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">No pending sent requests.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php require_once '../includes/footer.php'; ?>
