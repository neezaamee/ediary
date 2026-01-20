<?php
// diary/organize.php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Chapter Create/Delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "CSRF Token Validation Failed";
    } else {
        if ($_POST['action'] == 'add_chapter') {
            $name = sanitize($_POST['name']);
            $start = sanitize($_POST['start_date']);
            $end = sanitize($_POST['end_date']);
            if (empty($name)) {
                $error = "Chapter name is required.";
            } else {
                $stmt = $conn->prepare("INSERT INTO chapters (user_id, name, start_date, end_date) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $user_id, $name, $start, $end);
                if ($stmt->execute()) $success = "Chapter added!";
                else $error = "Error adding chapter.";
            }
        } elseif ($_POST['action'] == 'delete_chapter') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM chapters WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            $success = "Chapter deleted.";
        } elseif ($_POST['action'] == 'add_collection') {
            $name = sanitize($_POST['name']);
            $desc = sanitize($_POST['description']);
            if (empty($name)) {
                $error = "Collection name is required.";
            } else {
                $stmt = $conn->prepare("INSERT INTO collections (user_id, name, description) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $name, $desc);
                if ($stmt->execute()) $success = "Collection added!";
                else $error = "Error adding collection.";
            }
        } elseif ($_POST['action'] == 'delete_collection') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM collections WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            $success = "Collection deleted.";
        }
    }
}

// Fetch Chapters
$chapters = [];
$res = $conn->query("SELECT * FROM chapters WHERE user_id = $user_id ORDER BY start_date ASC");
while($row = $res->fetch_assoc()) $chapters[] = $row;

// Fetch Collections
$collections = [];
$res = $conn->query("SELECT * FROM collections WHERE user_id = $user_id ORDER BY name ASC");
while($row = $res->fetch_assoc()) $collections[] = $row;

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h2><i class="fa-solid fa-folder-tree text-primary"></i> Organize Your Memories</h2>
        <p class="text-muted">Create Chapters for life phases and Collections for specific topics.</p>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

<div class="row">
    <!-- Chapters Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card h-100">
            <h5 class="mb-4"><i class="fa-solid fa-book-bookmark text-success"></i> Chapters (Life Phases)</h5>
            
            <form action="" method="POST" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="add_chapter">
                <div class="row g-2">
                    <div class="col-12">
                        <input type="text" name="name" class="form-control" placeholder="Chapter Name (e.g. University Days)" required>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 pt-2">
                        <button type="submit" class="btn btn-sm btn-success w-100">Add Chapter</button>
                    </div>
                </div>
            </form>

            <ul class="list-group list-group-flush bg-transparent">
                <?php foreach($chapters as $ch): ?>
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($ch['name']); ?></strong>
                            <?php if ($ch['start_date']): ?>
                                <div class="small text-muted">
                                    <?php echo date('M Y', strtotime($ch['start_date'])); ?> 
                                    - <?php echo $ch['end_date'] ? date('M Y', strtotime($ch['end_date'])) : 'Present'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <form action="" method="POST" onsubmit="return confirm('Delete this chapter?')">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="delete_chapter">
                            <input type="hidden" name="id" value="<?php echo $ch['id']; ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm border-0"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($chapters)): ?>
                    <li class="list-group-item bg-transparent text-muted small text-center">No chapters defined yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Collections Management -->
    <div class="col-md-6 mb-4">
        <div class="glass-card h-100">
            <h5 class="mb-4"><i class="fa-solid fa-tags text-warning"></i> Collections (Thematic)</h5>
            
            <form action="" method="POST" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="add_collection">
                <div class="row g-2">
                    <div class="col-12">
                        <input type="text" name="name" class="form-control" placeholder="Collection Name (e.g. Travel, Family)" required>
                    </div>
                    <div class="col-12">
                        <textarea name="description" class="form-control" placeholder="Description (Optional)" rows="2"></textarea>
                    </div>
                    <div class="col-12 pt-2">
                        <button type="submit" class="btn btn-sm btn-warning w-100">Add Collection</button>
                    </div>
                </div>
            </form>

            <ul class="list-group list-group-flush bg-transparent">
                <?php foreach($collections as $col): ?>
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($col['name']); ?></strong>
                            <div class="small text-muted"><?php echo htmlspecialchars($col['description']); ?></div>
                        </div>
                        <form action="" method="POST" onsubmit="return confirm('Delete this collection?')">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="delete_collection">
                            <input type="hidden" name="id" value="<?php echo $col['id']; ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm border-0"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($collections)): ?>
                    <li class="list-group-item bg-transparent text-muted small text-center">No collections defined yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
