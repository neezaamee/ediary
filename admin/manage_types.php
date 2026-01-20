<?php
// admin/manage_types.php
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
            $stmt = $conn->prepare("INSERT INTO memory_types (name, icon) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $icon);
            $stmt->execute();
            logAdminAction($conn, $_SESSION['user_id'], 'Add Memory Type', "Name: $name");
            setFlashMessage('success', 'Memory Type added.');
        } elseif ($_POST['action'] == 'edit') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE memory_types SET name = ?, icon = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $icon, $id);
            $stmt->execute();
            logAdminAction($conn, $_SESSION['user_id'], 'Edit Memory Type', "ID: $id");
            setFlashMessage('success', 'Memory Type updated.');
        } elseif ($_POST['action'] == 'delete') {
            $id = (int)$_POST['id'];
            $conn->query("DELETE FROM memory_types WHERE id = $id");
            logAdminAction($conn, $_SESSION['user_id'], 'Delete Memory Type', "ID: $id");
            setFlashMessage('success', 'Memory Type deleted.');
        }
    }
    redirect('admin/manage_types.php');
}

// Fetch for Edit
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM memory_types WHERE id = $edit_id");
    if ($res->num_rows > 0) {
        $edit_mode = true;
        $edit_data = $res->fetch_assoc();
    }
}

// List All
$types = $conn->query("SELECT * FROM memory_types ORDER BY name ASC");

require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?php echo $edit_mode ? 'Edit Type' : 'Add New Type'; ?></h5>
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
                        <label class="form-label">Icon (Font Awesome Class)</label>
                        <input type="text" name="icon" class="form-control" placeholder="fa-star" required value="<?php echo htmlspecialchars($edit_data['icon']); ?>">
                        <div class="form-text">e.g. <code>fa-heart</code>, <code>fa-car</code></div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Update Type' : 'Add Type'; ?></button>
                        <?php if ($edit_mode): ?>
                            <a href="manage_types.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Existing Memory Types</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Icon</th>
                            <th>Name</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $types->fetch_assoc()): ?>
                            <tr>
                                <td><i class="fa-solid <?php echo htmlspecialchars($row['icon']); ?> fa-lg text-secondary"></i></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="text-end">
                                    <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Delete this type?');">
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
