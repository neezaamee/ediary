<?php
// admin/users.php
require_once '../includes/admin_auth.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'CSRF validation failed');
    } else {
        $target_id = (int)$_POST['user_id'];
        $action = $_POST['action'];
        
        // Prevent action on self
        if ($target_id == $_SESSION['user_id']) {
            setFlashMessage('error', 'You cannot modify your own rule/status.');
        } else {
            if ($action == 'ban') {
                $conn->query("UPDATE users SET is_banned = 1 WHERE id = $target_id");
                logAdminAction($conn, $_SESSION['user_id'], 'Ban User', "User ID: $target_id");
                setFlashMessage('success', 'User banned.');
            } elseif ($action == 'unban') {
                $conn->query("UPDATE users SET is_banned = 0 WHERE id = $target_id");
                logAdminAction($conn, $_SESSION['user_id'], 'Unban User', "User ID: $target_id");
                setFlashMessage('success', 'User unbanned.');
            } elseif ($action == 'promote') {
                $conn->query("UPDATE users SET role = 'admin' WHERE id = $target_id");
                logAdminAction($conn, $_SESSION['user_id'], 'Promote User', "User ID: $target_id to Admin");
                setFlashMessage('success', 'User promoted to Admin.');
            } elseif ($action == 'demote') {
                $conn->query("UPDATE users SET role = 'user' WHERE id = $target_id");
                logAdminAction($conn, $_SESSION['user_id'], 'Demote User', "User ID: $target_id to User");
                setFlashMessage('success', 'User demoted.');
            } elseif ($action == 'delete') {
                // Delete user (cascade will delete memories/autographs - ensure DB foreign keys are set UP)
                // In database.sql checks: CONSTRAINT ... ON DELETE CASCADE. Yes.
                $conn->query("DELETE FROM users WHERE id = $target_id");
                logAdminAction($conn, $_SESSION['user_id'], 'Delete User', "User ID: $target_id");
                setFlashMessage('success', 'User deleted.');
            }
        }
    }
    redirect('admin/users.php');
}

// Pagination & Search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$where = "1=1";
if (!empty($search)) {
    $where .= " AND (username LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%')";
}

// Count total for pagination
$count_sql = "SELECT COUNT(*) as total FROM users WHERE $where";
$total_res = $conn->query($count_sql);
$total_rows = $total_res->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch Users
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM diary_entries WHERE user_id = u.id) as memory_count,
        (SELECT MAX(created_at) FROM admin_logs WHERE admin_id = u.id) as last_admin_action 
        FROM users u 
        WHERE $where 
        ORDER BY u.created_at DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>User Management</h2>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
            <?php if (!empty($search)): ?>
                <div class="col-md-2">
                     <a href="users.php" class="btn btn-secondary w-100">Clear</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Memories</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                            <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                            <div class="text-muted small">@<?php echo htmlspecialchars($row['username']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($row['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($row['role'] == 'admin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['is_banned']): ?>
                                        <span class="badge bg-dark">Banned</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['memory_count']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Manage
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="../profile_view.php?username=<?php echo $row['username']; ?>" target="_blank">View Profile</a></li>
                                            
                                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                
                                                <?php if ($row['is_banned']): ?>
                                                    <li>
                                                        <form action="" method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                            <input type="hidden" name="action" value="unban">
                                                            <button type="submit" class="dropdown-item text-success"><i class="fa-solid fa-check"></i> Unban User</button>
                                                        </form>
                                                    </li>
                                                <?php else: ?>
                                                    <li>
                                                        <form action="" method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                            <input type="hidden" name="action" value="ban">
                                                            <button type="submit" class="dropdown-item text-warning"><i class="fa-solid fa-ban"></i> Ban User</button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <?php if ($row['role'] == 'user'): ?>
                                                    <li>
                                                        <form action="" method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                            <input type="hidden" name="action" value="promote">
                                                            <button type="submit" class="dropdown-item text-primary"><i class="fa-solid fa-user-shield"></i> Promote to Admin</button>
                                                        </form>
                                                    </li>
                                                <?php else: ?>
                                                    <li>
                                                        <form action="" method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                            <input type="hidden" name="action" value="demote">
                                                            <button type="submit" class="dropdown-item text-secondary"><i class="fa-solid fa-user-minus"></i> Demote to User</button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                     <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure? This will delete all user data.');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-trash"></i> Delete User</button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center p-4">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-center">
        <nav>
            <ul class="pagination mb-0">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
