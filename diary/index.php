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
$chapter_filter = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;
$collection_filter = isset($_GET['collection_id']) ? (int)$_GET['collection_id'] : 0;

$sql = "SELECT d.*, c.name as chapter_name, col.name as collection_name 
        FROM diary_entries d
        LEFT JOIN chapters c ON d.chapter_id = c.id
        LEFT JOIN collections col ON d.collection_id = col.id
        WHERE d.user_id = ?";

if ($search) {
    $sql .= " AND (d.title LIKE '%$search%' OR d.content LIKE '%$search%')";
}
if ($chapter_filter > 0) {
    $sql .= " AND d.chapter_id = $chapter_filter";
}
if ($collection_filter > 0) {
    $sql .= " AND d.collection_id = $collection_filter";
}

$sql .= " ORDER BY d.date_gregorian DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
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
                <div class="col-md-4">
                    <label class="small text-muted mb-1">Search Keywords</label>
                    <input class="form-control" type="search" name="search" placeholder="Search memories..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="small text-muted mb-1">Filter by Chapter</label>
                    <select name="chapter_id" class="form-select">
                        <option value="">All Chapters</option>
                        <?php while($ch = $chapters->fetch_assoc()): ?>
                            <option value="<?php echo $ch['id']; ?>" <?php echo $chapter_filter == $ch['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ch['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small text-muted mb-1">Filter by Collection</label>
                    <select name="collection_id" class="form-select">
                        <option value="">All Collections</option>
                        <?php while($col = $collections->fetch_assoc()): ?>
                            <option value="<?php echo $col['id']; ?>" <?php echo $collection_filter == $col['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($col['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
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
            ?>
            <div class="col-md-4 mb-4">
                <div class="card glass-card h-100 diary-entry-card <?php echo $is_locked ? 'locked-capsule' : ''; ?>" 
                     onclick="<?php echo $is_locked ? 'alert(\'This time capsule is locked until ' . date('d M Y', strtotime($row['unlock_date'])) . '\')' : 'window.location.href=\'view.php?id=' . $row['id'] . '\''; ?>">
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

<?php require_once '../includes/footer.php'; ?>
