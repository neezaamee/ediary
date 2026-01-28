<?php
// diary/create.php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch Memory Types
$types_res = $conn->query("SELECT * FROM memory_types ORDER BY name ASC");
$types = [];
while($row = $types_res->fetch_assoc()) $types[] = $row;

// Fetch Moods
$moods_res = $conn->query("SELECT * FROM moods ORDER BY name ASC");
$moods = [];
while($row = $moods_res->fetch_assoc()) $moods[] = $row;

// Fetch Chapters
$chapters_res = $conn->query("SELECT * FROM chapters WHERE user_id = $user_id ORDER BY name ASC");
$chapters = [];
while($row = $chapters_res->fetch_assoc()) $chapters[] = $row;

// Fetch Collections
$collections_res = $conn->query("SELECT * FROM collections WHERE user_id = $user_id ORDER BY name ASC");
$collections = [];
while($row = $collections_res->fetch_assoc()) $collections[] = $row;

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
        $memory_type = sanitize($_POST['memory_type']);
        $is_private = isset($_POST['is_private']) ? 1 : 0;
        $unlock_date = !empty($_POST['unlock_date']) ? sanitize($_POST['unlock_date']) : null;
        $energy_level = isset($_POST['energy_level']) ? (int)$_POST['energy_level'] : 5;
        $chapter_id = !empty($_POST['chapter_id']) ? (int)$_POST['chapter_id'] : null;
        $collection_id = !empty($_POST['collection_id']) ? (int)$_POST['collection_id'] : null;
        
        $image_url = null;

        // Handle Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $filetype = $_FILES['image']['type'];
            $filesize = $_FILES['image']['size'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $error = "Invalid file type. Only JPG, PNG, GIF allowed.";
            } elseif ($filesize > 102400) { // 100KB in bytes
                $error = "File size must be less than 100KB.";
            } else {
                $new_filename = uniqid() . "." . $ext;
                $upload_path = '../assets/uploads/' . $new_filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_url = 'assets/uploads/' . $new_filename;
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

        if (empty($error)) {
            if (empty($title) || empty($content) || empty($date_gregorian)) {
                $error = "Please fill in all required fields.";
            } else {
                $stmt = $conn->prepare("INSERT INTO diary_entries (user_id, title, content, mood, date_gregorian, date_hijri, memory_type, image_url, is_private, unlock_date, energy_level, chapter_id, collection_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssssisiii", $user_id, $title, $content, $mood, $date_gregorian, $date_hijri, $memory_type, $image_url, $is_private, $unlock_date, $energy_level, $chapter_id, $collection_id);

                if ($stmt->execute()) {
                // Badge Check
                require_once '../includes/BadgeManager.php';
                $bm = new BadgeManager($conn);
                $bm->checkAndAward($user_id, 'entry_count');

                setFlashMessage('success', 'Diary entry created successfully!');
                redirect('diary/index.php');
            } else {
                    $error = "Error saving entry: " . $conn->error;
                }
                $stmt->close();
            }
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
                <h2><i class="fa-solid fa-feather-pointed text-primary"></i> Write Key Memory</h2>
                <a href="../dashboard.php" class="btn btn-outline-secondary">Cancel</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row mb-3">
                    <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="What's on your mind?" required value="<?php echo isset($_POST['title']) ? $_POST['title'] : ''; ?>">
                    </div>
                     <div class="col-lg-3 col-6">
                        <label for="memory_type" class="form-label">Type</label>
                        <select class="form-select" name="memory_type">
                            <?php foreach($types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['name']); ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-6">
                         <label class="form-label">Mood</label>
                        <select class="form-select" name="mood">
                             <?php foreach($moods as $m): ?>
                                <option value="<?php echo htmlspecialchars($m['name']); ?>">
                                    <?php echo $m['icon'] . ' ' . htmlspecialchars($m['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="date" class="form-label">Gregorian Date</label>
                        <input type="date" class="form-control" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="chapter_id" class="form-label">Chapter (Phase)</label>
                        <select class="form-select" name="chapter_id">
                            <option value="">None</option>
                            <?php foreach($chapters as $ch): ?>
                                <option value="<?php echo $ch['id']; ?>"><?php echo htmlspecialchars($ch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                         <label for="collection_id" class="form-label">Collection (Topic)</label>
                         <select class="form-select" name="collection_id">
                            <option value="">None</option>
                            <?php foreach($collections as $col): ?>
                                <option value="<?php echo $col['id']; ?>"><?php echo htmlspecialchars($col['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="date_hijri" class="form-label">Islamic Date (Optional)</label>
                        <input type="text" class="form-control" id="date_hijri" name="date_hijri" placeholder="e.g. 1st Ramadan 1450">
                    </div>
                    <div class="col-md-4">
                         <label for="image" class="form-label">Upload Image (Max 100KB)</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Dear Diary...</label>
                    <textarea id="summernote" name="content"><?php echo isset($_POST['content']) ? $_POST['content'] : ''; ?></textarea>
                </div>

                <div class="row mb-4 align-items-center">
                    <div class="col-md-4">
                        <label for="unlock_date" class="form-label text-warning"><i class="fa-solid fa-clock"></i> Time Capsule (Unlock Date)</label>
                        <input type="date" class="form-control border-warning" id="unlock_date" name="unlock_date" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        <div class="form-text">Leave blank for immediate access.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="energy_level" class="form-label">Energy Level (1-10)</label>
                        <input type="range" class="form-range" id="energy_level" name="energy_level" min="1" max="10" step="1" value="5">
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Low</span>
                            <span>High</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="is_private" name="is_private" checked>
                            <label class="form-check-label" for="is_private"><i class="fa-solid fa-lock"></i> Keep Private</label>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Save Memory</button>
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
