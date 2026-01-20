<?php
// admin/manage_moods.php
require_once '../includes/admin_auth.php';

// Handle Add / Edit / Delete
$edit_mode = false;
$edit_data = ['name' => '', 'icon' => ''];
$edit_id = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $name = sanitize($_POST['name']);
        $icon = sanitize($_POST['icon']);
        
        if ($_POST['action'] == 'add') {
            $stmt = $conn->prepare("INSERT INTO moods (name, icon) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $icon);
            $stmt->execute();
            logAdminAction($conn, $_SESSION['user_id'], 'Add Mood', "Name: $name");
            setFlashMessage('success', 'Mood added.');
        } elseif ($_POST['action'] == 'edit') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE moods SET name = ?, icon = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $icon, $id);
            $stmt->execute();
            logAdminAction($conn, $_SESSION['user_id'], 'Edit Mood', "ID: $id");
            setFlashMessage('success', 'Mood updated.');
        } elseif ($_POST['action'] == 'delete') {
            $id = (int)$_POST['id'];
            $conn->query("DELETE FROM moods WHERE id = $id");
            logAdminAction($conn, $_SESSION['user_id'], 'Delete Mood', "ID: $id");
            setFlashMessage('success', 'Mood deleted.');
        }
    }
    redirect('admin/manage_moods.php');
}

// Fetch for Edit
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM moods WHERE id = $edit_id");
    if ($res->num_rows > 0) {
        $edit_mode = true;
        $edit_data = $res->fetch_assoc();
    }
}

// List All
$moods = $conn->query("SELECT * FROM moods ORDER BY name ASC");

require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?php echo $edit_mode ? 'Edit Mood' : 'Add New Mood'; ?></h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_data['name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Emoji / Icon</label>
                        <input type="text" name="icon" class="form-control" placeholder="😊" required value="<?php echo htmlspecialchars($edit_data['icon']); ?>">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Update Mood' : 'Add Mood'; ?></button>
                        <?php if ($edit_mode): ?>
                            <a href="manage_moods.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Existing Moods</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Emoji</th>
                            <th>Name</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $moods->fetch_assoc()): ?>
                            <tr>
                                <td class="fs-4"><?php echo htmlspecialchars($row['icon']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="text-end">
                                    <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Delete this mood?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
