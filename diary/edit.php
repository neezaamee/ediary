<?php
// diary/edit.php
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

// Fetch existing entry
$stmt = $conn->prepare("SELECT * FROM diary_entries WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $entry_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Entry not found or access denied.');
    redirect('diary/index.php');
}

$entry = $result->fetch_assoc();
$is_locked = ($entry['unlock_date'] && strtotime($entry['unlock_date']) > time());
if ($is_locked) {
    setFlashMessage('error', 'You cannot edit a locked Time Capsule.');
    redirect('diary/view.php?id=' . $entry_id);
}

// Fetch Moods for dropdown
$moods_res = $conn->query("SELECT * FROM moods ORDER BY name ASC");
$moods_list = [];
while($row = $moods_res->fetch_assoc()) $moods_list[] = $row;

// Fetch Chapters
$chapters_res = $conn->query("SELECT * FROM chapters WHERE user_id = $user_id ORDER BY name ASC");
$chapters_list = [];
while($row = $chapters_res->fetch_assoc()) $chapters_list[] = $row;

// Fetch Collections
$collections_res = $conn->query("SELECT * FROM collections WHERE user_id = $user_id ORDER BY name ASC");
$collections_list = [];
while($row = $collections_res->fetch_assoc()) $collections_list[] = $row;

$stmt->close();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "CSRF Token Validation Failed";
    } else {
        $title = sanitize($_POST['title']);
        $content = $_POST['content']; 
        $mood = sanitize($_POST['mood']);
        $date_gregorian = sanitize($_POST['date']);
        $date_hijri = sanitize($_POST['date_hijri']);
        $is_private = isset($_POST['is_private']) ? 1 : 0;
        $unlock_date = !empty($_POST['unlock_date']) ? sanitize($_POST['unlock_date']) : null;
        $energy_level = isset($_POST['energy_level']) ? (int)$_POST['energy_level'] : 5;
        $chapter_id = !empty($_POST['chapter_id']) ? (int)$_POST['chapter_id'] : null;
        $collection_id = !empty($_POST['collection_id']) ? (int)$_POST['collection_id'] : null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
             $allowed = ['jpg', 'jpeg', 'png', 'gif'];
             $filename = $_FILES['image']['name'];
             $filetype = $_FILES['image']['type'];
             $filesize = $_FILES['image']['size'];
             $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

             if (!in_array($ext, $allowed)) {
                 $error = "Invalid file type. Only JPG, PNG, GIF allowed.";
             } elseif ($filesize > 102400) { // 100KB
                 $error = "File size must be less than 100KB.";
             } else {
                 $new_filename = uniqid() . "." . $ext;
                 $upload_path = '../assets/uploads/' . $new_filename;
                 if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                     $image_url_update = 'assets/uploads/' . $new_filename;
                 } else {
                     $error = "Failed to upload image.";
                 }
             }
         }

        if (empty($title) || empty($content) || empty($date_gregorian)) {
            $error = "Please fill in all required fields.";
        } else {
            // If new image, update it. If not, keep old.
            $sql = "UPDATE diary_entries SET title=?, content=?, mood=?, date_gregorian=?, date_hijri=?, is_private=?, unlock_date=?, energy_level=?, chapter_id=?, collection_id=?";
            
            // Build params
            $types = "ssssssisii";
            $params = [$title, $content, $mood, $date_gregorian, $date_hijri, $is_private, $unlock_date, $energy_level, $chapter_id, $collection_id];

            if (isset($image_url_update)) {
                $sql .= ", image_url=?";
                $types .= "s";
                $params[] = $image_url_update;
            }

            $sql .= " WHERE id=? AND user_id=?";
            $types .= "ii";
            $params[] = $entry_id;
            $params[] = $user_id;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                setFlashMessage('success', 'Diary entry updated successfully!');
                redirect('diary/view.php?id=' . $entry_id);
            } else {
                $error = "Error updating entry: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

require_once '../includes/header.php';
?>

<!-- Summernote css/js -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="glass-card fade-in">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-pen-to-square text-warning"></i> Edit Memory</h2>
                <a href="view.php?id=<?php echo $entry_id; ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row mb-3">
                    <?php if (!empty($entry['image_url'])): ?>
                        <div class="col-12 mb-3">
                            <label class="form-label">Current Image</label>
                            <div>
                                <img src="../<?php echo $entry['image_url']; ?>" alt="Current Image" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="col-12 mb-3">
                         <label for="image" class="form-label">Update Image (Max 100KB)</label>
                         <input type="file" class="form-control" id="image" name="image" accept="image/*">
                         <div class="form-text">Upload a new image to replace the current one.</div>
                    </div>
                    <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($entry['title']); ?>">
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" required value="<?php echo $entry['date_gregorian']; ?>">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="date_hijri" class="form-label">Islamic Date</label>
                        <input type="text" class="form-control" id="date_hijri" name="date_hijri" value="<?php echo htmlspecialchars($entry['date_hijri'] ?? ''); ?>" placeholder="e.g. 1st Ramadan 1450">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Dear Diary...</label>
                    <textarea id="summernote" name="content"><?php echo $entry['content']; ?></textarea>
                </div>

                <div class="row mb-4 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label">Mood</label>
                        <select class="form-select" name="mood">
                            <?php foreach ($moods_list as $m): ?>
                                <option value="<?php echo htmlspecialchars($m['name']); ?>" <?php echo ($entry['mood'] == $m['name']) ? 'selected' : ''; ?>>
                                    <?php echo $m['icon'] . ' ' . htmlspecialchars($m['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="chapter_id" class="form-label">Chapter (Phase)</label>
                        <select class="form-select" name="chapter_id">
                            <option value="">None</option>
                            <?php foreach($chapters_list as $ch): ?>
                                <option value="<?php echo $ch['id']; ?>" <?php echo ($entry['chapter_id'] == $ch['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ch['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                         <label for="collection_id" class="form-label">Collection (Topic)</label>
                         <select class="form-select" name="collection_id">
                            <option value="">None</option>
                            <?php foreach($collections_list as $col): ?>
                                <option value="<?php echo $col['id']; ?>" <?php echo ($entry['collection_id'] == $col['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($col['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <div class="col-md-4">
                        <label for="unlock_date" class="form-label text-warning"><i class="fa-solid fa-clock"></i> Future Unlock Date</label>
                        <input type="date" class="form-control border-warning" id="unlock_date" name="unlock_date" value="<?php echo $entry['unlock_date']; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="is_private" name="is_private" <?php echo $entry['is_private'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_private"><i class="fa-solid fa-lock"></i> Keep Private</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="energy_level" class="form-label">Energy Level (1-10)</label>
                        <input type="range" class="form-range" id="energy_level" name="energy_level" min="1" max="10" step="1" value="<?php echo $entry['energy_level'] ?: 5; ?>">
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Low</span>
                            <span>High</span>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Update Memory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    $('#summernote').summernote({
        placeholder: 'Write your heart out...',
        tabsize: 2,
        height: 300,
        toolbar: [
          ['style', ['style']],
          ['font', ['bold', 'underline', 'clear']],
          ['color', ['color']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['insert', ['link', 'picture']],
          ['view', ['fullscreen', 'help']]
        ]
    });
</script>

<?php require_once '../includes/footer.php'; ?>
