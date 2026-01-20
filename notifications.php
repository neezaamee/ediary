<?php
// notifications.php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/NotificationManager.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$nm = new NotificationManager($conn);

// Handle Actions (Mark Read / Mark All Read)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'mark_read') {
                $id = (int)$_POST['id'];
                $nm->markAsRead($id, $user_id);
                // JSON response if AJAX, else Redirect
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo json_encode(['status' => 'success']);
                    exit;
                }
            } elseif ($_POST['action'] == 'mark_all_read') {
                $nm->markAllAsRead($user_id);
            }
        }
    }
    redirect('notifications.php');
}

// Fetch Notifications
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$notifications = $nm->getAll($user_id, $limit, $offset);

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fa-solid fa-bell text-primary"></i> Notifications</h2>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-check-double"></i> Mark all read</button>
            </form>
        </div>

        <div class="glass-card">
            <?php if ($notifications->num_rows > 0): ?>
                <div class="list-group list-group-flush bg-transparent">
                    <?php while($row = $notifications->fetch_assoc()): ?>
                        <div class="list-group-item bg-transparent <?php echo ($row['is_read'] == 0) ? 'fw-bold bg-light-info' : ''; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <?php 
                                        $icon = 'fa-info-circle';
                                        if ($row['type'] == 'badge') $icon = 'fa-trophy text-warning';
                                        elseif ($row['type'] == 'autograph') $icon = 'fa-pen-nib text-primary';
                                        elseif ($row['type'] == 'summary') $icon = 'fa-chart-line text-success';
                                    ?>
                                    <i class="fa-solid <?php echo $icon; ?> me-2"></i>
                                    <?php echo htmlspecialchars($row['message']); ?>
                                </div>
                                <small class="text-muted text-nowrap ms-2"><?php echo date('d M H:i', strtotime($row['created_at'])); ?></small>
                            </div>
                            <?php if (!empty($row['link'])): ?>
                                <div class="mt-2 ms-4">
                                     <a href="<?php echo htmlspecialchars($row['link']); ?>" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="markRead(<?php echo $row['id']; ?>, this)">View Details <i class="fa-solid fa-chevron-right"></i></a>
                                </div>
                            <?php endif; ?>
                            <?php if ($row['is_read'] == 0): ?>
                                <span class="position-absolute top-0 start-0 translate-middle p-2 bg-danger border border-light rounded-circle ms-3 mt-3">
                                    <span class="visually-hidden">New alerts</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-bell-slash fa-3x mb-3"></i>
                    <p>No notifications yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function markRead(id, el) {
    // Optional: Send AJAX to mark read without reload, or just let the link click handle navigation 
    // We mainly rely on the server handling it or user seeing it.
    // Ideally, clicking 'View' should mark it read.
    // For now, we will perform a fetch
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('id', id);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>'); // Careful exposing CSRF via JS var if not secure, but ok here

    fetch('notifications.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
