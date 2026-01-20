<?php
// admin/moods.php
require_once '../includes/admin_auth.php';

// Mood Stats
$mood_stats_sql = "SELECT mood, COUNT(*) as cnt FROM diary_entries WHERE mood IS NOT NULL GROUP BY mood ORDER BY cnt DESC";
$mood_stats = $conn->query($mood_stats_sql);

// Recent Mood Entries
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT d.mood, d.date_gregorian, u.username, u.full_name 
        FROM diary_entries d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.mood IS NOT NULL 
        ORDER BY d.created_at DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Mood Tracker</h2>
</div>

<!-- Mood Stats Cards -->
<div class="row mb-4">
    <?php while($stat = $mood_stats->fetch_assoc()): ?>
        <div class="col-md-2 mb-3">
            <div class="card text-center h-100 card-stat">
                <div class="card-body">
                    <h3 class="mb-0"><?php echo $stat['cnt']; ?></h3>
                    <div class="text-muted"><?php echo $stat['mood']; ?></div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Recent Mood Log</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Mood</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <div class="small text-muted">@<?php echo htmlspecialchars($row['username']); ?></div>
                                </td>
                                <td><h4><span class="badge bg-light text-dark border"><?php echo $row['mood']; ?></span></h4></td>
                                <td><?php echo date('M d, Y', strtotime($row['date_gregorian'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center p-4">No mood entries found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
