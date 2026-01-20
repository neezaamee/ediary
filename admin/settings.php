<?php
// admin/settings.php
require_once '../includes/admin_auth.php';

// System Info
$php_version = phpversion();
$server_software = $_SERVER['SERVER_SOFTWARE'];
$db_version = $conn->server_info;

// Database Size
$db_name_res = $conn->query("SELECT DATABASE()");
$db_name_row = $db_name_res->fetch_row();
$current_db = $db_name_row[0];

$size_res = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = '$current_db'");
$db_size = $size_res->fetch_assoc()['size_mb'];

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Settings & System Info</h2>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fa-solid fa-server"></i> System Information</h5>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span>PHP Version</span>
                    <span class="fw-bold"><?php echo $php_version; ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>MySQL Version</span>
                    <span class="fw-bold"><?php echo $db_version; ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Web Server</span>
                    <span class="fw-bold text-end" style="max-width: 200px;"><?php echo $server_software; ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Database Name</span>
                    <span class="fw-bold"><?php echo $current_db; ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Database Size</span>
                    <span class="fw-bold"><?php echo $db_size; ?> MB</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fa-solid fa-shield-halved"></i> Security Overview</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">CSRF Protection</label>
                    <div class="d-flex align-items-center text-success">
                        <i class="fa-solid fa-circle-check me-2"></i> Enabled globally
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin Authentication</label>
                    <div class="d-flex align-items-center text-success">
                        <i class="fa-solid fa-circle-check me-2"></i> Role-based (Middleware Active)
                    </div>
                </div>
                 <div class="mb-3">
                    <label class="form-label">Audit Logging</label>
                    <div class="d-flex align-items-center text-success">
                        <i class="fa-solid fa-circle-check me-2"></i> Active (Storing IPs & Actions)
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fa-solid fa-circle-info me-2"></i> To change application settings (Timezone, App Name), please edit <code>config/config.php</code> directly on the server.
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
