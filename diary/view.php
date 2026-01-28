<?php
// diary/view.php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('diary/index.php');
}

$entry_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT d.*, c.name as chapter_name, col.name as collection_name 
                        FROM diary_entries d
                        LEFT JOIN chapters c ON d.chapter_id = c.id
                        LEFT JOIN collections col ON d.collection_id = col.id
                        WHERE d.id = ? AND d.user_id = ?");
$stmt->bind_param("ii", $entry_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Entry not found or access denied.');
    redirect('diary/index.php');
}

$entry = $result->fetch_assoc();
$stmt->close();

require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="glass-card fade-in">
            <?php 
                $is_locked = ($entry['unlock_date'] && strtotime($entry['unlock_date']) > time());
            ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <div>
                    <?php if (!$is_locked): ?>
                        <a href="share.php?id=<?php echo $entry['id']; ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-share-nodes"></i> Share as Card</a>
                        <a href="edit.php?id=<?php echo $entry['id']; ?>" class="btn btn-warning btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
                        <a href="delete.php?id=<?php echo $entry['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this memory? This action cannot be undone.');"><i class="fa-solid fa-trash"></i> Delete</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($is_locked): ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-clock-rotate-left fa-5x text-warning mb-4"></i>
                    <h1 class="display-6">This Time Capsule is Sealed</h1>
                    <p class="lead text-muted">It will unlock on <strong><?php echo date('d F Y', strtotime($entry['unlock_date'])); ?></strong>.</p>
                    <div class="mt-4">
                        <span class="badge bg-light text-dark border p-2">
                             Created on <?php echo date('d M Y', strtotime($entry['date_gregorian'])); ?>
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <h1 class="display-6 text-primary mb-2"><?php echo htmlspecialchars($entry['title']); ?></h1>
                <div class="text-muted mb-4 d-flex align-items-center flex-wrap">
                    <span class="me-3"><i class="fa-regular fa-calendar"></i> <?php echo date('d F Y', strtotime($entry['date_gregorian'])); ?></span>
                    <?php if ($entry['date_hijri']): ?>
                        <span class="me-3"><i class="fa-solid fa-moon"></i> <?php echo htmlspecialchars($entry['date_hijri']); ?></span>
                    <?php endif; ?>
                    <?php if ($entry['mood']): ?>
                        <span class="badge bg-light text-dark border me-2"><?php echo htmlspecialchars($entry['mood']); ?></span>
                    <?php endif; ?>
                    <?php if ($entry['energy_level'] > 0): ?>
                        <span class="badge bg-warning text-dark me-2"><i class="fa-solid fa-bolt"></i> <?php echo $entry['energy_level']; ?>/10 Energy</span>
                    <?php endif; ?>
                    <?php if ($entry['chapter_name']): ?>
                        <span class="badge bg-success-subtle text-success border border-success me-2"><i class="fa-solid fa-book-bookmark"></i> <?php echo htmlspecialchars($entry['chapter_name']); ?></span>
                    <?php endif; ?>
                    <?php if ($entry['collection_name']): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning me-2"><i class="fa-solid fa-tags"></i> <?php echo htmlspecialchars($entry['collection_name']); ?></span>
                    <?php endif; ?>
                    <?php if ($entry['is_private'] == 1): ?>
                        <span class="badge bg-dark"><i class="fa-solid fa-lock"></i> Private</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($entry['image_url'])): ?>
                    <div class="mb-4 text-center">
                        <img src="../<?php echo $entry['image_url']; ?>" class="img-fluid rounded shadow-sm" style="max-height: 400px; width: auto;" alt="Memory Image">
                    </div>
                <?php endif; ?>

                <hr>

                <div class="diary-content mt-4" style="line-height: 1.8; font-size: 1.1rem;">
                    <?php echo $entry['content']; // Outputting HTML from Summernote ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
