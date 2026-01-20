<?php
// admin/database.php
require_once '../includes/admin_auth.php';

$success = '';
$error = '';

// Handle Backup Export
if (isset($_POST['action']) && $_POST['action'] == 'backup') {
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $return = "";
    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM " . $table);
        $num_fields = $result->field_count;

        $return .= "DROP TABLE IF EXISTS " . $table . ";";
        $row2 = $conn->query("SHOW CREATE TABLE " . $table)->fetch_row();
        $return .= "\n\n" . $row2[1] . ";\n\n";

        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = $result->fetch_row()) {
                $return .= "INSERT INTO " . $table . " VALUES(";
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }

    // Save file
    $filename = 'db-backup-' . time() . '.sql';
    
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . $filename . "\"");
    echo $return;
    
    // Log Activity
    $admin_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, target_details, ip_address) VALUES (?, 'BACKUP', 'Database Exported', ?)");
    $stmt->bind_param("is", $admin_id, $_SERVER['REMOTE_ADDR']);
    $stmt->execute();
    exit;
}

// Handle Restore Import
if (isset($_POST['action']) && $_POST['action'] == 'restore' && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file']['tmp_name'];
    if (file_exists($file)) {
        $sql = file_get_contents($file);
        
        // Execute multi-query
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());
            
            $success = "Database restored successfully!";
            
            // Log Activity
            $admin_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, target_details, ip_address) VALUES (?, 'RESTORE', 'Database Restored from Upload', ?)");
            $stmt->bind_param("is", $admin_id, $_SERVER['REMOTE_ADDR']);
            $stmt->execute();
        } else {
            $error = "Error restoring database: " . $conn->error;
        }
    } else {
        $error = "Please select a valid SQL file.";
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h2 class="mb-4">Database Maintenance</h2>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Backup Card -->
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center p-5">
                        <i class="fa-solid fa-download fa-4x text-primary mb-4"></i>
                        <h4>Export Backup</h4>
                        <p class="text-muted">Download a full SQL backup of all your content and settings.</p>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="backup">
                            <button type="submit" class="btn btn-primary btn-lg w-100">Prepare Backup</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Restore Card -->
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-warning">
                    <div class="card-body text-center p-5">
                        <i class="fa-solid fa-upload fa-4x text-warning mb-4"></i>
                        <h4>Restore Database</h4>
                        <p class="text-muted">Upload an SQL file to restore your database to a previous state.</p>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="restore">
                            <div class="mb-3">
                                <input type="file" name="sql_file" class="form-control" accept=".sql" required>
                            </div>
                            <button type="submit" class="btn btn-warning btn-lg w-100" onclick="return confirm('WARNING: This will overwrite existing data. Are you sure?')">Restore Backup</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info py-2">
            <i class="fa-solid fa-circle-info me-2"></i>
            Tip: Always perform a backup before restoring to avoid permanent data loss.
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
