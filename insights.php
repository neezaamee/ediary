<?php
// insights.php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// 1. Writing Frequency (Grouped by Date - Last 30 Days)
$freq_sql = "SELECT date_gregorian as date, COUNT(*) as total FROM diary_entries WHERE user_id = ? AND date_gregorian >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) GROUP BY date_gregorian ORDER BY date_gregorian ASC";
$stmt = $conn->prepare($freq_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$freq_res = $stmt->get_result();
$freq_labels = []; $freq_data = [];
while($row = $freq_res->fetch_assoc()) {
    $freq_labels[] = date('d M', strtotime($row['date']));
    $freq_data[] = $row['total'];
}

// 2. Mood Distribution
$mood_sql = "SELECT mood, COUNT(*) as count FROM diary_entries WHERE user_id = ? AND mood IS NOT NULL AND mood != '' GROUP BY mood";
$stmt = $conn->prepare($mood_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$mood_res = $stmt->get_result();
$mood_labels = []; $mood_data = [];
while($row = $mood_res->fetch_assoc()) {
    $mood_labels[] = $row['mood'];
    $mood_data[] = $row['count'];
}

// 3. Energy Level Trends (Timeline)
$energy_sql = "SELECT date_gregorian as date, AVG(energy_level) as avg_energy FROM diary_entries WHERE user_id = ? AND date_gregorian >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) GROUP BY date_gregorian ORDER BY date_gregorian ASC";
$stmt = $conn->prepare($energy_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$energy_res = $stmt->get_result();
$energy_labels = []; $energy_data = [];
while($row = $energy_res->fetch_assoc()) {
    $energy_labels[] = date('d M', strtotime($row['date']));
    $energy_data[] = round($row['avg_energy'], 1);
}

// 4. Keyword Frequency (Simple PHP Analysis)
// Get last 50 entries
$content_sql = "SELECT content FROM diary_entries WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
$stmt = $conn->prepare($content_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$content_res = $stmt->get_result();
$all_text = "";
while($row = $content_res->fetch_assoc()) {
    $all_text .= " " . strip_tags(htmlspecialchars_decode($row['content']));
}

$words = str_word_count(strtolower($all_text), 1);
$stop_words = ['the', 'and', 'a', 'to', 'of', 'in', 'i', 'is', 'it', 'was', 'for', 'on', 'with', 'my', 'that', 'at', 'this', 'but', 'by', 'an', 'be', 'as', 'he', 'she', 'they', 'we', 'today', 'very', 'really', 'just', 'me', 'had', 'have', 'from', 'am', 'so'];
$filtered_words = array_diff($words, $stop_words);
$word_counts = array_count_values($filtered_words);
arsort($word_counts);
$top_words = array_slice($word_counts, 0, 15);

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h2><i class="fa-solid fa-lightbulb text-warning"></i> Personal Insights</h2>
        <p class="text-muted">Explore your writing patterns and emotional trends over the last 30 days.</p>
    </div>
</div>

<div class="row">
    <!-- Writing Frequency -->
    <div class="col-md-8 mb-4">
        <div class="glass-card">
            <h5><i class="fa-solid fa-chart-line"></i> Writing Frequency</h5>
            <canvas id="freqChart" height="150"></canvas>
        </div>
    </div>
    
    <!-- Top Keywords -->
    <div class="col-md-4 mb-4">
        <div class="glass-card h-100">
            <h5><i class="fa-solid fa-tags"></i> Top Keywords</h5>
            <div class="mt-3">
                <?php if (!empty($top_words)): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach($top_words as $word => $count): ?>
                            <span class="badge bg-light text-primary border p-2" style="font-size: <?php echo max(0.8, min(1.5, 0.8 + ($count/5))); ?>rem;">
                                <?php echo htmlspecialchars($word); ?> (<?php echo $count; ?>)
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small">Write more to see your common themes here.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Mood Distribution -->
    <div class="col-md-4 mb-4">
        <div class="glass-card">
            <h5><i class="fa-solid fa-face-smile"></i> Mood Split</h5>
            <canvas id="moodChart" height="250"></canvas>
        </div>
    </div>

    <!-- Energy Level Trend -->
    <div class="col-md-8 mb-4">
        <div class="glass-card">
            <h5><i class="fa-solid fa-bolt"></i> Energy Level Timeline</h5>
            <canvas id="energyChart" height="120"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Global Options
    Chart.defaults.color = "<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark') ? '#ddd' : '#666'; ?>";

    // 1. Frequency Chart
    new Chart(document.getElementById('freqChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($freq_labels); ?>,
            datasets: [{
                label: 'Entries per Day',
                data: <?php echo json_encode($freq_data); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // 2. Mood Chart
    new Chart(document.getElementById('moodChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($mood_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($mood_data); ?>,
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0', '#6610f2', '#fd7e14']
            }]
        }
    });

    // 3. Energy Chart
    new Chart(document.getElementById('energyChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($energy_labels); ?>,
            datasets: [{
                label: 'Avg Energy Level',
                data: <?php echo json_encode($energy_data); ?>,
                backgroundColor: '#ffc107',
                borderRadius: 5
            }]
        },
        options: {
            scales: { y: { min: 1, max: 10 } }
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
