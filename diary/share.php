<?php
// diary/share.php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/ImageGenerator.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('diary/index.php');
}

$entry_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch entry
$stmt = $conn->prepare("SELECT * FROM diary_entries WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $entry_id, $user_id);
$stmt->execute();
$entry = $stmt->get_result()->fetch_assoc();

if (!$entry) {
    setFlashMessage('error', 'Entry not found.');
    redirect('diary/index.php');
}

// Check if locked
if ($entry['unlock_date'] && strtotime($entry['unlock_date']) > time()) {
    setFlashMessage('error', 'This memory is still locked.');
    redirect('diary/view.php?id=' . $entry_id);
}

$card_path = '';
$theme = isset($_POST['theme']) ? sanitize($_POST['theme']) : 'modern';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ig = new ImageGenerator($conn);
    $card_path = $ig->generateCard($entry, $theme);
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="glass-card text-center fade-in">
            <h2 class="mb-4"><i class="fa-solid fa-image text-primary"></i> Create Memory Card</h2>
            <p class="text-muted">Turn your diary entry into a beautiful shareable card.</p>

            <div class="row mt-5">
                <div class="col-md-6">
                    <h5>Choose a Theme</h5>
                    <form action="" method="POST" class="mt-3">
                        <div class="d-grid gap-3 mb-4 text-start">
                            <div class="form-check card-select p-3 border rounded <?php echo $theme == 'modern' ? 'border-primary bg-light' : ''; ?>">
                                <input class="form-check-input" type="radio" name="theme" id="themeModern" value="modern" <?php echo $theme == 'modern' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <label class="form-check-label w-100" for="themeModern">
                                   <strong>Modern</strong><br><small class="text-muted">Clean white with blue accents</small>
                                </label>
                            </div>
                            <div class="form-check card-select p-3 border rounded <?php echo $theme == 'vintage' ? 'border-primary bg-light' : ''; ?>">
                                <input class="form-check-input" type="radio" name="theme" id="themeVintage" value="vintage" <?php echo $theme == 'vintage' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <label class="form-check-label w-100" for="themeVintage">
                                    <strong>Vintage</strong><br><small class="text-muted">Parchment paper style</small>
                                </label>
                            </div>
                            <div class="form-check card-select p-3 border rounded <?php echo $theme == 'dark' ? 'border-primary bg-light' : ''; ?>">
                                <input class="form-check-input" type="radio" name="theme" id="themeDark" value="dark" <?php echo $theme == 'dark' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <label class="form-check-label w-100" for="themeDark">
                                    <strong>Midnight</strong><br><small class="text-muted">Sleek dark mode card</small>
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                             <i class="fa-solid fa-arrows-rotate me-2"></i> Regenerate Preview
                        </button>
                    </form>
                </div>
                
                <div class="col-md-6">
                    <h5>Preview</h5>
                    <div class="card-preview-container mt-3 border rounded bg-light p-2" style="min-height: 400px;">
                        <?php if ($card_path): ?>
                            <img src="<?php echo BASE_URL . $card_path; ?>" class="img-fluid rounded shadow-sm" alt="Memory Card">
                            <div class="mt-3">
                                <a href="<?php echo BASE_URL . $card_path; ?>" download="MyDiary_Card.png" class="btn btn-success">
                                    <i class="fa-solid fa-download"></i> Download Image
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column justify-content-center h-100 py-5 text-muted">
                                <i class="fa-solid fa-wand-magic-sparkles fa-3x mb-3"></i>
                                <p>Select a theme to generate preview</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mt-5 text-start">
               <a href="view.php?id=<?php echo $entry_id; ?>" class="text-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Memory</a>
            </div>
        </div>
    </div>
</div>

<style>
.card-select { cursor: pointer; transition: all 0.2s; }
.card-select:hover { background: #f0f7ff; }
.card-preview-container { display: flex; align-items: center; justify-content: center; overflow: hidden; }
</style>

<?php require_once '../includes/footer.php'; ?>
