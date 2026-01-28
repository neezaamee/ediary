<?php
// diary/index.php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch Chapters and Collections for filters
$chapters = $conn->query("SELECT * FROM chapters WHERE user_id = $user_id ORDER BY name ASC");
$collections = $conn->query("SELECT * FROM collections WHERE user_id = $user_id ORDER BY name ASC");

// Filtering
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? sanitize($_GET['date_filter']) : '';
$chapter_filter = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;
$collection_filter = isset($_GET['collection_id']) ? (int)$_GET['collection_id'] : 0;

// Pagination Setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Build Base Query conditions
$where_clauses = ["d.user_id = ?"];
$params = [$user_id];
$types = "i";

if ($search) {
    $where_clauses[] = "(d.title LIKE ? OR d.content LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}
if ($date_filter) {
    $where_clauses[] = "d.date_gregorian = ?";
    $params[] = $date_filter;
    $types .= "s";
}
if ($chapter_filter > 0) {
    $where_clauses[] = "d.chapter_id = ?";
    $params[] = $chapter_filter;
    $types .= "i";
}
if ($collection_filter > 0) {
    $where_clauses[] = "d.collection_id = ?";
    $params[] = $collection_filter;
    $types .= "i";
}

$where_sql = implode(" AND ", $where_clauses);

// Count Query
$count_sql = "SELECT COUNT(*) as total FROM diary_entries d WHERE $where_sql";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_rows = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
$stmt->close();

// Fetch Data Query
$sql = "SELECT d.*, c.name as chapter_name, col.name as collection_name 
        FROM diary_entries d
        LEFT JOIN chapters c ON d.chapter_id = c.id
        LEFT JOIN collections col ON d.collection_id = col.id
        WHERE $where_sql
        ORDER BY d.date_gregorian DESC
        LIMIT ? OFFSET ?";

// Append Limit params
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fa-solid fa-book"></i> My Diary</h2>
    <a href="create.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Entry</a>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="glass-card p-3">
            <form action="" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="small text-muted mb-1">Search Keywords</label>
                    <input class="form-control" type="search" name="search" placeholder="Search memories..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <!-- Date Filter -->
                 <div class="col-md-3 col-sm-6">
                    <label class="small text-muted mb-1">Filter by Date</label>
                    <input class="form-control" type="date" name="date_filter" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="small text-muted mb-1">Filter by Chapter</label>
                    <select name="chapter_id" class="form-select">
                        <option value="">All Chapters</option>
                        <?php while($ch = $chapters->fetch_assoc()): ?>
                            <option value="<?php echo $ch['id']; ?>" <?php echo $chapter_filter == $ch['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ch['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="small text-muted mb-1">Filter by Collection</label>
                    <select name="collection_id" class="form-select">
                        <option value="">All Collections</option>
                        <?php while($col = $collections->fetch_assoc()): ?>
                            <option value="<?php echo $col['id']; ?>" <?php echo $collection_filter == $col['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($col['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 d-grid">
                    <button class="btn btn-primary" type="submit">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <?php 
                $is_locked = ($row['unlock_date'] && strtotime($row['unlock_date']) > time());
                $first_image = !empty($row['image_url']) ? '../' . $row['image_url'] : getFirstImage(htmlspecialchars_decode($row['content']));
            ?>
            <div class="col-md-4 mb-4">
                <div class="card glass-card h-100 diary-entry-card <?php echo $is_locked ? 'locked-capsule' : ''; ?>" 
                     onclick="<?php echo $is_locked ? 'alert(\'This time capsule is locked until ' . date('d M Y', strtotime($row['unlock_date'])) . '\')' : 'window.location.href=\'view.php?id=' . $row['id'] . '\''; ?>">
                    
                    <?php if (!$is_locked && $first_image): ?>
                        <div style="height: 200px; overflow: hidden; background-position: center; background-size: cover; background-image: url('<?php echo htmlspecialchars($first_image); ?>'); border-radius: 15px 15px 0 0;">
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <h5 class="card-title text-primary">
                            <?php if ($is_locked): ?>
                                <i class="fa-solid fa-lock text-warning me-2"></i> Locked Time Capsule
                            <?php else: ?>
                                <?php echo shortenText(htmlspecialchars($row['title']), 50); ?>
                            <?php endif; ?>
                        </h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <?php echo date('d M Y', strtotime($row['date_gregorian'])); ?>
                        </h6>
                        <p class="card-text text-muted">
                            <?php if ($is_locked): ?>
                                <em>This content will be available on <?php echo date('d M Y', strtotime($row['unlock_date'])); ?>.</em>
                            <?php else: ?>
                                <?php echo shortenText(strip_tags(htmlspecialchars_decode($row['content'])), 100); ?>
                            <?php endif; ?>
                        </p>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                             <div class="d-flex flex-wrap gap-1">
                                <?php if (!$is_locked && !empty($row['mood'])): ?>
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['mood']); ?></span>
                                <?php endif; ?>
                                <?php if (!$is_locked && !empty($row['chapter_name'])): ?>
                                    <span class="badge bg-success-subtle text-success border border-success"><?php echo htmlspecialchars($row['chapter_name']); ?></span>
                                <?php endif; ?>
                                <?php if (!$is_locked && !empty($row['collection_name'])): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning"><?php echo htmlspecialchars($row['collection_name']); ?></span>
                                <?php endif; ?>
                             </div>
                             <?php if (isset($row['energy_level']) && $row['energy_level'] > 0): ?>
                                <small class="text-muted"><i class="fa-solid fa-bolt text-warning"></i> <?php echo $row['energy_level']; ?>/10</small>
                             <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 d-flex justify-content-end">
                       <small class="text-muted">
                           <?php if ($is_locked): ?>
                               <i class="fa-solid fa-clock"></i> Future Memory
                           <?php else: ?>
                               <i class="fa-solid fa-chevron-right"></i> Read More
                           <?php endif; ?>
                       </small>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12 text-center mt-5">
            <div class="text-muted">
                <i class="fa-regular fa-folder-open fa-3x mb-3"></i>
                <p>No entries found. Start writing your first memory!</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <!-- Previous -->
        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
        </li>
        
        <!-- Numbers -->
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <!-- Next -->
        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
