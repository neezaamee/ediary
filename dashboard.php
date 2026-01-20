<?php
// dashboard.php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Refresh session data if missing
if (!isset($_SESSION['full_name'])) {
    $stmt = $conn->prepare("SELECT full_name, username, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        $_SESSION['full_name'] = $user_data['full_name'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['role'] = $user_data['role'];
    } else {
        // User not found in DB (e.g. deleted), force logout
        session_destroy();
        redirect('login.php');
    }
    $stmt->close();
}

// === Time Capsule Unlock "Lazy Cron" ===
require_once 'includes/NotificationManager.php';
$nm = new NotificationManager($conn);
$unlocked_stmt = $conn->prepare("SELECT id, title FROM diary_entries WHERE user_id = ? AND unlock_date <= CURRENT_DATE() AND is_unlocked_notified = 0");
$unlocked_stmt->bind_param("i", $user_id);
$unlocked_stmt->execute();
$unlocked_res = $unlocked_stmt->get_result();

while ($row = $unlocked_res->fetch_assoc()) {
    $entry_id = $row['id'];
    $entry_title = $row['title'];
    
    // Notify User
    $nm->create($user_id, 'system', "Your Time Capsule '$entry_title' is now unlocked!", "diary/view.php?id=$entry_id");
    
    // Mark as notified
    $upd = $conn->prepare("UPDATE diary_entries SET is_unlocked_notified = 1 WHERE id = ?");
    $upd->bind_param("i", $entry_id);
    $upd->execute();
}
$unlocked_stmt->close();

// Fetch Stats
$entry_count_sql = "SELECT COUNT(*) as total FROM diary_entries WHERE user_id = ?";
$stmt = $conn->prepare($entry_count_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$entry_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$autograph_count_sql = "SELECT COUNT(*) as total FROM autographs WHERE owner_id = ?";
$stmt = $conn->prepare($autograph_count_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$autograph_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Fetch Recent Entries
$recent_entries_sql = "SELECT * FROM diary_entries WHERE user_id = ? ORDER BY date_gregorian DESC LIMIT 5";
$stmt = $conn->prepare($recent_entries_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_entries = $stmt->get_result();
$stmt->close();

// === Logic for Upcoming Reminders (Birthday, Anniversary, Death) ===
// Requirement: remind within 3 days after and before of same date in future.
// Complex SQL or PHP logic? PHP logic is easier for year agnostic comparison.
$reminders = [];
$memories_sql = "SELECT * FROM diary_entries WHERE user_id = ? AND memory_type IN ('Birthday', 'Anniversary', 'Death')";
$stmt = $conn->prepare($memories_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$memories_res = $stmt->get_result();

$current_year = date('Y');
$today_ts = strtotime(date('Y-m-d'));

while ($row = $memories_res->fetch_assoc()) {
    $event_date = $row['date_gregorian'];
    $event_ts = strtotime($event_date);
    $event_month = date('m', $event_ts);
    $event_day = date('d', $event_ts);
    $event_year = date('Y', $event_ts);

    // Calculate this year's occurrence
    $this_year_occurrence = "$current_year-$event_month-$event_day";
    $occurrence_ts = strtotime($this_year_occurrence);
    
    // Check next year if this year's has passed? 
    // Requirement says "remind me ... within 3 days after ... and before".
    // So if today is 2026-01-20. Event is 2021-01-22. This year event is 2026-01-22.
    // Difference between Now and Occurance.
    
    $diff_days = ($occurrence_ts - $today_ts) / (60 * 60 * 24); // Positive if future, negative if past
    
    // Range: -3 to +3
    if ($diff_days >= -3 && $diff_days <= 3) {
        $years_diff = $current_year - $event_year;
        if ($years_diff > 0) { // Only future anniversaries
             // Ordinal suffix logic
             $suffix = 'th';
             if (!in_array(($years_diff % 100), [11, 12, 13])) {
                 switch ($years_diff % 10) {
                     case 1: $suffix = 'st'; break;
                     case 2: $suffix = 'nd'; break;
                     case 3: $suffix = 'rd'; break;
                 }
             }
             
             $type_text = "";
             if ($row['memory_type'] == 'Birthday') $type_text = "Birthday";
             elseif ($row['memory_type'] == 'Anniversary') $type_text = "Anniversary";
             elseif ($row['memory_type'] == 'Death') $type_text = "Remembrance Day";

             $status_text = ($diff_days == 0) ? "Today!" : (($diff_days > 0) ? "in $diff_days days" : abs($diff_days) . " days ago");
             $alert_type = ($diff_days == 0) ? "success" : "info";

             $reminders[] = [
                 'title' => htmlspecialchars($row['title']),
                 'msg' => "{$years_diff}{$suffix} {$type_text} - $status_text ({$this_year_occurrence})",
                 'type' => $alert_type
             ];
        }
    }
}
$stmt->close();

// === Logic for "On This Day" ===
$on_this_day = [];
// Match Month and Day, but Year < Current Year
$otd_sql = "SELECT * FROM diary_entries WHERE user_id = ? AND MONTH(date_gregorian) = MONTH(CURRENT_DATE()) AND DAY(date_gregorian) = DAY(CURRENT_DATE()) AND YEAR(date_gregorian) < YEAR(CURRENT_DATE())";
$stmt = $conn->prepare($otd_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$otd_res = $stmt->get_result();
while ($row = $otd_res->fetch_assoc()) {
    $on_this_day[] = $row;
}
$stmt->close();


// Hijri Date
$date_gregorian = date('l, d F Y');
$date_hijri = ""; 
if (extension_loaded('intl')) {
    $formatter = new IntlDateFormatter('en_US@calendar=islamic', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Karachi', IntlDateFormatter::TRADITIONAL);
    $date_hijri = $formatter->format(time());
} else {
    $date_hijri = "Hijri Date unavailable";
}

require_once 'includes/header.php';
?>

<!-- Welcome Banner -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="glass-card text-center py-4">
            <h1 class="display-4">Hello, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
            <p class="lead text-muted">How was your day? Capture your memories today.</p>
            <div class="d-flex justify-content-center gap-4 mt-3">
                <div class="badge bg-light text-dark p-3 shadow-sm">
                    <i class="fa-regular fa-calendar me-2"></i> <?php echo $date_gregorian; ?>
                </div>
                <div class="badge bg-light text-success p-3 shadow-sm">
                    <i class="fa-solid fa-moon me-2"></i> <?php echo $date_hijri; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reminders & On This Day -->
<?php if (!empty($reminders) || !empty($on_this_day)): ?>
<div class="row mb-4">
    <?php if (!empty($reminders)): ?>
        <div class="col-md-<?php echo (!empty($on_this_day)) ? '6' : '12'; ?> mb-3">
             <div class="glass-card h-100 border-start border-5 border-info">
                 <h4 class="text-info"><i class="fa-solid fa-bell"></i> Upcoming Memories</h4>
                 <ul class="list-group list-group-flush bg-transparent">
                     <?php foreach ($reminders as $rem): ?>
                         <li class="list-group-item bg-transparent">
                             <strong><?php echo $rem['title']; ?></strong><br>
                             <span class="badge bg-<?php echo $rem['type']; ?>"><?php echo $rem['msg']; ?></span>
                         </li>
                     <?php endforeach; ?>
                 </ul>
             </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($on_this_day)): ?>
        <div class="col-md-<?php echo (!empty($reminders)) ? '6' : '12'; ?> mb-3">
             <div class="glass-card h-100 border-start border-5 border-warning">
                 <h4 class="text-warning"><i class="fa-solid fa-clock-rotate-left"></i> On This Day</h4>
                 <ul class="list-group list-group-flush bg-transparent">
                     <?php foreach ($on_this_day as $otd): ?>
                         <li class="list-group-item bg-transparent">
                             <strong><?php echo htmlspecialchars($otd['title']); ?></strong>
                             <?php $years_ago = date('Y') - date('Y', strtotime($otd['date_gregorian'])); ?>
                             <span class="badge bg-secondary float-end"><?php echo $years_ago; ?> year<?php echo $years_ago > 1 ? 's' : ''; ?> ago</span>
                             <br>
                             <a href="diary/view.php?id=<?php echo $otd['id']; ?>" class="small text-muted">Read entry</a>
                         </li>
                     <?php endforeach; ?>
                 </ul>
             </div>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>


<div class="row">
    <!-- Quick Stats -->
    <div class="col-md-4 mb-4">
        <div class="glass-card h-100">
            <h4 class="mb-3"><i class="fa-solid fa-chart-pie me-2"></i> Your Stats</h4>
            <div class="list-group list-group-flush bg-transparent">
                <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center">
                    Total Diary Entries
                    <span class="badge bg-primary rounded-pill"><?php echo $entry_count; ?></span>
                </div>
                <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center">
                    Autographs Received
                    <span class="badge bg-success rounded-pill"><?php echo $autograph_count; ?></span>
                </div>
            </div>
            <div class="mt-4 d-grid gap-2">
                <a href="diary/create.php" class="btn btn-primary"><i class="fa-solid fa-pen-nib"></i> Write New Entry</a>
                <a href="autograph/search.php" class="btn btn-outline-success"><i class="fa-solid fa-magnifying-glass"></i> Find Friends</a>
            </div>
        </div>
    </div>

    <!-- Recent Entries -->
    <div class="col-md-8 mb-4">
        <div class="glass-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fa-solid fa-book-open"></i> Recent Entries</h4>
                <a href="diary" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            
            <?php if ($recent_entries->num_rows > 0): ?>
                <div class="row">
                    <?php while($row = $recent_entries->fetch_assoc()): ?>
                        <div class="col-md-12 mb-3">
                            <div class="card shadow-sm border-0 diary-entry-card" onclick="window.location.href='diary/view.php?id=<?php echo $row['id']; ?>'">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="card-title text-primary"><?php echo htmlspecialchars($row['title']); ?></h5>
                                        <?php if (!empty($row['memory_type']) && $row['memory_type'] != 'General'): ?>
                                            <span class="badge bg-info text-dark align-self-start"><?php echo htmlspecialchars($row['memory_type']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <?php echo date('d M Y', strtotime($row['date_gregorian'])); ?> 
                                        <?php if (!empty($row['date_hijri'])): ?>
                                             | <small><?php echo htmlspecialchars($row['date_hijri']); ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($row['mood'])): ?>
                                            <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($row['mood']); ?></span>
                                        <?php endif; ?>
                                    </h6>
                                    <p class="card-text text-truncate"><?php echo strip_tags(htmlspecialchars_decode($row['content'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    You haven't written any entries yet. <a href="diary/create.php">Start writing now!</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
