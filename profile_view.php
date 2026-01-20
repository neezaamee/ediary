<?php
// profile_view.php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Allow viewing without login? Requirements say "on User wall Public Memorys... available to view". 
// Usually better if public. But let's check login if needed.
// "If we find a user with username then we can visit a user...".
// Assuming allow public access, but navbar handles login state.

$username = isset($_GET['username']) ? sanitize($_GET['username']) : '';

if (empty($username)) {
    redirect('dashboard.php');
}

// Fetch User
$stmt = $conn->prepare("SELECT id, full_name, username, created_at FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("User not found.");
}

$profile_user = $res->fetch_assoc();
$profile_id = $profile_user['id'];

// Fetch Public Memories
$mem_sql = "SELECT * FROM diary_entries WHERE user_id = ? AND is_private = 0 ORDER BY date_gregorian DESC";
$mem_stmt = $conn->prepare($mem_sql);
$mem_stmt->bind_param("i", $profile_id);
$mem_stmt->execute();
$public_memories = $mem_stmt->get_result();

// Fetch Public Autographs (Assuming 'public' visibility means visible to everyone)
// Also check is_approved = 1
$auto_sql = "SELECT a.*, u.full_name as author_name FROM autographs a 
             JOIN users u ON a.author_id = u.id 
             WHERE a.owner_id = ? AND a.visibility = 'public' AND a.is_approved = 1 
             ORDER BY a.created_at DESC";
$auto_stmt = $conn->prepare($auto_sql);
$auto_stmt->bind_param("i", $profile_id);
$auto_stmt->execute();
$public_autographs = $auto_stmt->get_result();

require_once 'includes/header.php';
?>

<div class="row mb-5">
    <div class="col-md-12 text-center">
        <div class="glass-card">
            <div class="mb-3">
                <i class="fa-solid fa-circle-user fa-5x text-secondary"></i>
            </div>
            <h1 class="display-4"><?php echo htmlspecialchars($profile_user['full_name']); ?></h1>
            <p class="text-muted">@<?php echo htmlspecialchars($profile_user['username']); ?> &bull; Joined <?php echo date('M Y', strtotime($profile_user['created_at'])); ?></p>
            
            <?php if (isLoggedIn() && $_SESSION['user_id'] != $profile_id): ?>
                <a href="autograph/write.php?to=<?php echo $profile_id; ?>" class="btn btn-primary"><i class="fa-solid fa-pen-fancy"></i> Sign their Diary</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-tabs mb-4 px-3 border-0 justify-content-center" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill px-4" id="memories-tab" data-bs-toggle="tab" data-bs-target="#memories" type="button" role="tab"><i class="fa-solid fa-book-open"></i> Public Memories</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="autographs-tab" data-bs-toggle="tab" data-bs-target="#autographs" type="button" role="tab"><i class="fa-solid fa-signature"></i> Autograph Wall</button>
            </li>
        </ul>

        <div class="tab-content" id="profileTabsContent">
            <!-- Memories Tab -->
            <div class="tab-pane fade show active" id="memories" role="tabpanel">
                <div class="row justify-content-center">
                    <?php if ($public_memories->num_rows > 0): ?>
                        <?php while($row = $public_memories->fetch_assoc()): ?>
                            <div class="col-md-8 mb-4">
                                <div class="glass-card">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h4 class="text-primary mb-0"><?php echo htmlspecialchars($row['title']); ?></h4>
                                        <span class="text-muted small"><?php echo date('d M Y', strtotime($row['date_gregorian'])); ?></span>
                                    </div>
                                    <?php if ($row['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" class="img-fluid rounded mb-3" style="max-height: 400px; width: 100%; object-fit: cover;">
                                    <?php endif; ?>
                                    <div class="card-text">
                                        <?php echo $row['content']; // Assumed safe or we strip tags if strictly text ?>
                                    </div>
                                    <div class="mt-3">
                                        <?php if (!empty($row['mood'])): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($row['mood']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-md-6 text-center">
                            <h5 class="text-muted">No public memories yet.</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Autographs Tab -->
            <div class="tab-pane fade" id="autographs" role="tabpanel">
                <div class="row">
                    <?php if ($public_autographs->num_rows > 0): ?>
                        <?php while($row = $public_autographs->fetch_assoc()): ?>
                            <div class="col-md-4 mb-4">
                                <div class="autograph-note h-100 position-relative">
                                    <div class="fs-5 mb-3">
                                        <i class="fa-solid fa-quote-left text-muted opacity-25 fa-2x position-absolute top-0 start-0 m-2"></i>
                                        <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                    </div>
                                    <div class="text-end mt-4">
                                        <small class="fw-bold">- <?php echo htmlspecialchars($row['author_name']); ?></small><br>
                                        <small class="text-muted" style="font-size: 0.8rem;">
                                            <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                         <div class="col-md-12 text-center">
                            <h5 class="text-muted">Autograph wall is empty.</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
