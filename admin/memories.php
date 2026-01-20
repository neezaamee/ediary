<?php
// admin/memories.php
require_once '../includes/admin_auth.php';

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $id = (int)$_POST['memory_id'];
        $conn->query("DELETE FROM diary_entries WHERE id = $id");
        logAdminAction($conn, $_SESSION['user_id'], 'Delete Memory', "Memory ID: $id");
        setFlashMessage('success', 'Memory deleted.');
        redirect('admin/memories.php');
    }
}

// Filters
$type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "1=1";
if (!empty($type)) {
    $where .= " AND memory_type = '$type'";
}
if (!empty($search)) {
    $where .= " AND (title LIKE '%$search%' OR content LIKE '%$search%')";
}

// Pagination Count
$count_res = $conn->query("SELECT COUNT(*) as cnt FROM diary_entries WHERE $where");
$total_rows = $count_res->fetch_assoc()['cnt'];
$total_pages = ceil($total_rows / $limit);

// Fetch Memories
$sql = "SELECT d.*, u.username, u.full_name 
        FROM diary_entries d 
        JOIN users u ON d.user_id = u.id 
        WHERE $where 
        ORDER BY d.created_at DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Memory Management</h2>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="General" <?php if ($type == 'General') echo 'selected'; ?>>General</option>
                    <option value="Birthday" <?php if ($type == 'Birthday') echo 'selected'; ?>>Birthday</option>
                    <option value="Anniversary" <?php if ($type == 'Anniversary') echo 'selected'; ?>>Anniversary</option>
                    <option value="Death" <?php if ($type == 'Death') echo 'selected'; ?>>Death</option>
                    <option value="Achievement" <?php if ($type == 'Achievement') echo 'selected'; ?>>Achievement</option>
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search content..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                 <a href="memories.php" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- List -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                 <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Visibility</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="max-width: 300px;">
                                    <div class="fw-bold text-truncate"><?php echo htmlspecialchars($row['title']); ?></div>
                                    <div class="small text-muted text-truncate"><?php echo strip_tags($row['content']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <div class="small text-muted">@<?php echo htmlspecialchars($row['username']); ?></div>
                                </td>
                                <td><span class="badge bg-info text-dark"><?php echo $row['memory_type']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($row['date_gregorian'])); ?></td>
                                <td>
                                    <?php if ($row['is_private']): ?>
                                        <i class="fa-solid fa-lock text-muted" title="Private"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-globe text-primary" title="Public"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                     <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $row['id']; ?>">
                                        <i class="fa-solid fa-eye"></i>
                                     </button>
                                     <form action="" method="POST" class="d-inline" onsubmit="return confirm('Delete this memory?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="memory_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                     </form>

                                     <!-- View Modal -->
                                     <div class="modal fade" id="viewModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php if ($row['image_url']): ?>
                                                        <img src="../<?php echo $row['image_url']; ?>" class="img-fluid rounded mb-3">
                                                    <?php endif; ?>
                                                    <div><?php echo $row['content']; ?></div>
                                                    <hr>
                                                    <p class="text-muted"><small>By <?php echo htmlspecialchars($row['full_name']); ?> on <?php echo date('F d, Y', strtotime($row['date_gregorian'])); ?></small></p>
                                                </div>
                                            </div>
                                        </div>
                                     </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center p-4">No memories found.</td></tr>
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
                        <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $type; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
