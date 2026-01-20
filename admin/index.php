<?php
// admin/index.php
require_once '../includes/admin_auth.php';

// Fetch Statistics
// Total Users
$res = $conn->query("SELECT COUNT(*) as cnt FROM users");
$total_users = $res->fetch_assoc()['cnt'];

// Active Users (last 30 days login not tracked, but we can track 'activities' via logs or just total users for now. 
// Ideally we'd have last_login in users table. Let's stick to total users and recent growth).

// Total Memories
$res = $conn->query("SELECT COUNT(*) as cnt FROM diary_entries");
$total_memories = $res->fetch_assoc()['cnt'];

// Total Moods (entries with mood)
$res = $conn->query("SELECT COUNT(*) as cnt FROM diary_entries WHERE mood IS NOT NULL");
$total_moods = $res->fetch_assoc()['cnt'];

// Recent Activity (Admin Logs)
$logs = $conn->query("SELECT l.*, u.username FROM admin_logs l JOIN users u ON l.admin_id = u.id ORDER BY l.created_at DESC LIMIT 5");

// Memory Types Chart Data
$types_res = $conn->query("SELECT memory_type, COUNT(*) as cnt FROM diary_entries GROUP BY memory_type");
$types_labels = [];
$types_data = [];
while($row = $types_res->fetch_assoc()) {
    $types_labels[] = $row['memory_type'];
    $types_data[] = $row['cnt'];
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Dashboard</h2>
    <div class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card card-stat bg-primary text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">Total Users</h6>
                    <h2 class="mb-0"><?php echo $total_users; ?></h2>
                </div>
                <i class="fa-solid fa-users fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card card-stat bg-success text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">Total Memories</h6>
                    <h2 class="mb-0"><?php echo $total_memories; ?></h2>
                </div>
                <i class="fa-solid fa-book fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card card-stat bg-info text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">Mood Entries</h6>
                    <h2 class="mb-0"><?php echo $total_moods; ?></h2>
                </div>
                <i class="fa-solid fa-face-smile fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts & Tables -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">User Growth (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <canvas id="growthChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Memories by Type</h5>
            </div>
            <div class="card-body">
                <canvas id="typeChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Admin Activity</h5>
                <a href="logs.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs->num_rows > 0): ?>
                            <?php while($row = $logs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['action']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['target_details']); ?></td>
                                    <td><?php echo date('M d, H:i', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">No recent activity.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Consumer Growth Chart (Mock Data for now as we don't have historical "user count snapshots", 
    // unless we query users created_at by month)
    <?php 
        // Get last 6 months
        $months = [];
        $counts = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $month_label = date('M Y', strtotime("-$i months"));
            $sql = "SELECT COUNT(*) as cnt FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') <= '$month'";
            $res = $conn->query($sql);
            $months[] = $month_label;
            $counts[] = $res->fetch_assoc()['cnt'];
        }
    ?>

    const ctxGrowth = document.getElementById('growthChart').getContext('2d');
    new Chart(ctxGrowth, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Total Users',
                data: <?php echo json_encode($counts); ?>,
                borderColor: '#3498db',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(52, 152, 219, 0.1)'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // Type Chart
    const ctxType = document.getElementById('typeChart').getContext('2d');
    new Chart(ctxType, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($types_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($types_data); ?>,
                backgroundColor: ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#3498db', '#9b59b6']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
