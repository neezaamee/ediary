<?php
// admin/logs.php
require_once '../includes/admin_auth.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';

$where = "1=1";
if (!empty($filter)) {
    $where .= " AND action LIKE '%$filter%'";
}

$count_res = $conn->query("SELECT COUNT(*) as cnt FROM admin_logs WHERE $where");
$total_rows = $count_res->fetch_assoc()['cnt'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT l.*, u.username, u.full_name 
        FROM admin_logs l 
        JOIN users u ON l.admin_id = u.id 
        WHERE $where 
        ORDER BY l.created_at DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Activity Logs</h2>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-6">
                <input type="text" name="filter" class="form-control" placeholder="Filter by action type..." value="<?php echo htmlspecialchars($filter); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <?php if (!empty($filter)): ?>
                <div class="col-md-2">
                     <a href="logs.php" class="btn btn-secondary w-100">Reset</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Target/Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <div class="small text-muted">@<?php echo htmlspecialchars($row['username']); ?></div>
                                </td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['action']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['target_details']); ?></td>
                                <td class="text-muted small"><?php echo htmlspecialchars($row['ip_address']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center p-4">No logs found.</td></tr>
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
                        <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
