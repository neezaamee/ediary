<?php
// admin/analytics.php
require_once '../includes/admin_auth.php';

// 1. User Growth (Last 12 Months)
$months = [];
$growth_data = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_label = date('M Y', strtotime("-$i months"));
    $sql = "SELECT COUNT(*) as cnt FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') <= '$month'";
    $res = $conn->query($sql);
    $months[] = $month_label;
    $growth_data[] = $res->fetch_assoc()['cnt'];
}

// 2. Memory Types
$types_res = $conn->query("SELECT memory_type, COUNT(*) as cnt FROM diary_entries GROUP BY memory_type");
$types_labels = [];
$types_data = [];
while($row = $types_res->fetch_assoc()) {
    $types_labels[] = $row['memory_type'];
    $types_data[] = $row['cnt'];
}

// 3. Activity (Last 30 Days - Entries created)
$days = [];
$activity_data = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('m/d', strtotime("-$i days"));
    $sql = "SELECT COUNT(*) as cnt FROM diary_entries WHERE DATE(created_at) = '$date'";
    $res = $conn->query($sql);
    $days[] = $label;
    $activity_data[] = $res->fetch_assoc()['cnt'];
}

// 4. Top 10 Active Users
$top_users = $conn->query("SELECT u.username, u.full_name, COUNT(d.id) as entry_count 
                           FROM users u 
                           JOIN diary_entries d ON u.id = d.user_id 
                           GROUP BY u.id 
                           ORDER BY entry_count DESC 
                           LIMIT 10");

// 5. User Age Distribution
$age_sql = "SELECT 
                CASE 
                    WHEN dob IS NULL THEN 'Unknown'
                    WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 18 THEN 'Under 18'
                    WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                    WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                    WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                    WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                    ELSE '55+'
                END as age_group,
                COUNT(*) as cnt 
            FROM users 
            GROUP BY age_group";
$age_res = $conn->query($age_sql);
$age_labels = [];
$age_data = [];
while($row = $age_res->fetch_assoc()) {
    $age_labels[] = $row['age_group'];
    $age_data[] = $row['cnt'];
}

require_once 'includes/header.php';
?>

<h2>Analytics Dashboard</h2>
<div class="row mt-4">
    <!-- Growth Chart -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">User Growth (12 Months)</h5>
            </div>
            <div class="card-body">
                <canvas id="growthChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <!-- Types Chart -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Memory Types</h5>
            </div>
            <div class="card-body">
                <canvas id="typeChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Daily Activity -->
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Daily Memory Creation (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="activityChart" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Top Users -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Top Active Users</h5>
            </div>
             <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Rank</th>
                        <th>User</th>
                        <th>Total Memories</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank=1; while($row = $top_users->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $rank++; ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($row['full_name']); ?></div>
                                <div class="small text-muted">@<?php echo htmlspecialchars($row['username']); ?></div>
                            </td>
                            <td><span class="badge bg-primary rounded-pill"><?php echo $row['entry_count']; ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Growth
    const ctxGrowth = document.getElementById('growthChart').getContext('2d');
    new Chart(ctxGrowth, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Total Users',
                data: <?php echo json_encode($growth_data); ?>,
                borderColor: '#2980b9',
                backgroundColor: 'rgba(41, 128, 185, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: { responsive: true }
    });

    // Types
    const ctxType = document.getElementById('typeChart').getContext('2d');
    new Chart(ctxType, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($types_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($types_data); ?>,
                backgroundColor: ['#e74c3c', '#f1c40f', '#2ecc71', '#9b59b6', '#34495e', '#e67e22']
            }]
        },
        options: { responsive: true }
    });

    // Activity
    const ctxActivity = document.getElementById('activityChart').getContext('2d');
    new Chart(ctxActivity, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($days); ?>,
            datasets: [{
                label: 'New Memories',
                data: <?php echo json_encode($activity_data); ?>,
                backgroundColor: '#27ae60'
            }]
        },
        options: { responsive: true }
    });

    // Age Distribution
    const ctxAge = document.getElementById('ageChart').getContext('2d');
    new Chart(ctxAge, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($age_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($age_data); ?>,
                backgroundColor: ['#1abc9c', '#3498db', '#9b59b6', '#f1c40f', '#e67e22', '#e74c3c', '#95a5a6']
            }]
        },
        options: { responsive: true }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
