<?php
// summary/view.php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'weekly'; // 'weekly' or 'monthly'
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0; // 0 = current week/month, 1 = last week/month

// Calculate date range
if ($type == 'monthly') {
    $target_date = date('Y-m-01', strtotime("-$offset month"));
    $start_date = date('Y-m-01', strtotime($target_date));
    $end_date = date('Y-m-t', strtotime($target_date));
    $title = date('F Y', strtotime($start_date));
} else {
    // Weekly (Mon-Sun)
    $target_date = date('Y-m-d', strtotime("-$offset week"));
    $start_date = date('Y-m-d', strtotime('monday this week', strtotime($target_date)));
    $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($target_date)));
    $title = "Week of " . date('d M', strtotime($start_date)) . " - " . date('d M Y', strtotime($end_date));
}

// Fetch stats
$entries_sql = "SELECT * FROM diary_entries WHERE user_id = ? AND date_gregorian BETWEEN ? AND ? ORDER BY date_gregorian ASC";
$stmt = $conn->prepare($entries_sql);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$entries = $stmt->get_result();
$entries_data = [];
$mood_counts = [];
$avg_energy = 0;
while($row = $entries->fetch_assoc()) {
    $entries_data[] = $row;
    $mood_counts[$row['mood']] = ($mood_counts[$row['mood']] ?? 0) + 1;
    $avg_energy += $row['energy_level'];
}
$entry_count = count($entries_data);
if ($entry_count > 0) $avg_energy /= $entry_count;

// Find dominant mood
arsort($mood_counts);
$top_mood = !empty($mood_counts) ? key($mood_counts) : 'None';

require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="reflection-container fade-in">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fa-solid fa-sparkles text-warning"></i> Life Reflection</h2>
                <div class="btn-group">
                    <a href="?type=weekly&offset=0" class="btn btn-outline-primary <?php echo $type == 'weekly' ? 'active' : ''; ?>">Weekly</a>
                    <a href="?type=monthly&offset=0" class="btn btn-outline-primary <?php echo $type == 'monthly' ? 'active' : ''; ?>">Monthly</a>
                </div>
            </div>

            <div class="glass-card mb-4 p-5 text-center reflection-header">
                <h1 class="display-5 fw-bold"><?php echo $title; ?></h1>
                <p class="lead text-muted">A summary of your journey during this period.</p>
                
                <div class="row mt-5">
                    <div class="col-md-4">
                        <div class="stat-box">
                            <span class="stat-value"><?php echo $entry_count; ?></span>
                            <span class="stat-label">Entries Written</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box border-start border-end">
                            <span class="stat-value"><?php echo $top_mood; ?></span>
                            <span class="stat-label">Dominant Mood</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <span class="stat-value"><?php echo round($avg_energy, 1); ?>/10</span>
                            <span class="stat-label">Avg Energy</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-8">
                    <h4 class="mb-3">Chronicle of Moments</h4>
                    <div class="timeline">
                        <?php if (empty($entries_data)): ?>
                            <div class="alert alert-light text-center py-5">
                                <p class="mb-0 text-muted">You didn't write any entries during this period.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($entries_data as $e): ?>
                                <div class="timeline-item p-3 border-start border-primary border-4 mb-3 bg-white shadow-sm rounded">
                                    <small class="text-muted"><?php echo date('D, d M', strtotime($e['date_gregorian'])); ?></small>
                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($e['title']); ?></h6>
                                    <p class="small text-truncate mb-0"><?php echo strip_tags($e['content']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="glass-card h-100">
                        <h5 class="mb-4">Quick Navigation</h5>
                        <div class="list-group list-group-flush">
                            <a href="?type=<?php echo $type; ?>&offset=<?php echo $offset + 1; ?>" class="list-group-item list-group-item-action bg-transparent">
                                <i class="fa-solid fa-chevron-left me-2"></i> Previous Period
                            </a>
                            <?php if ($offset > 0): ?>
                            <a href="?type=<?php echo $type; ?>&offset=<?php echo $offset - 1; ?>" class="list-group-item list-group-item-action bg-transparent">
                                Next Period <i class="fa-solid fa-chevron-right ms-2"></i>
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="mt-5 p-4 bg-primary text-white rounded shadow-sm text-center">
                            <h6>Keep it up!</h6>
                            <p class="small mb-0">Consistent writing helps you track your growth and patterns.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5 no-print">
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-print"></i> Print This Reflection
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.reflection-header { background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(240,248,255,0.9)); }
.stat-box { padding: 10px; }
.stat-value { display: block; font-size: 2rem; font-weight: 700; color: #0d6efd; }
.stat-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #666; }
.timeline-item { border-left-width: 5px !important; }
@media print {
    .no-print, .btn-group, .navbar { display: none !important; }
    .glass-card { border: 1px solid #ddd !important; box-shadow: none !important; }
}
</style>

<?php require_once '../includes/footer.php'; ?>
