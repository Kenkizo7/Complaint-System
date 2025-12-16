<?php
// Go up one level to include config files
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Check if user is admin
if ($_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

// Handle log filter
$filter = [
    'type' => $_GET['type'] ?? 'all',
    'user' => $_GET['user'] ?? 'all',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Build query
$where = [];
$params = [];

if ($filter['type'] != 'all') {
    $where[] = "al.action_type = ?";
    $params[] = $filter['type'];
}

if ($filter['user'] != 'all') {
    $where[] = "al.user_id = ?";
    $params[] = $filter['user'];
}

if ($filter['date_from']) {
    $where[] = "DATE(al.created_at) >= ?";
    $params[] = $filter['date_from'];
}

if ($filter['date_to']) {
    $where[] = "DATE(al.created_at) <= ?";
    $params[] = $filter['date_to'];
}

if ($filter['search']) {
    $where[] = "(al.description LIKE ? OR al.ip_address LIKE ? OR al.user_agent LIKE ?)";
    $params[] = "%{$filter['search']}%";
    $params[] = "%{$filter['search']}%";
    $params[] = "%{$filter['search']}%";
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM activity_logs al $where_clause";
$count_stmt = mysqli_prepare($conn, $count_sql);
if ($params) {
    $types = str_repeat('s', count($params));
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_logs = mysqli_fetch_assoc($count_result)['total'];

// Pagination
$per_page = 50;
$total_pages = ceil($total_logs / $per_page);
$page = isset($_GET['page']) ? max(1, min($total_pages, (int)$_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Get logs
$sql = "SELECT al.*, u.name as user_name, u.role as user_role 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        $where_clause 
        ORDER BY al.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;

$stmt = mysqli_prepare($conn, $sql);
$types = str_repeat('s', count($params) - 2) . 'ii';
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$logs = [];
while ($row = mysqli_fetch_assoc($result)) {
    $logs[] = $row;
}

// Get log types for filter
$log_types_sql = "SELECT DISTINCT action_type FROM activity_logs ORDER BY action_type";
$log_types_result = mysqli_query($conn, $log_types_sql);
$log_types = ['all' => 'All Types'];
while ($row = mysqli_fetch_assoc($log_types_result)) {
    $log_types[$row['action_type']] = ucwords(str_replace('_', ' ', $row['action_type']));
}

// Get users for filter
$users_sql = "SELECT DISTINCT u.id, u.name 
              FROM activity_logs al 
              JOIN users u ON al.user_id = u.id 
              ORDER BY u.name";
$users_result = mysqli_query($conn, $users_sql);
$users = ['all' => 'All Users'];
while ($row = mysqli_fetch_assoc($users_result)) {
    $users[$row['id']] = $row['name'];
}

// Get system info
$system_info = [
    'php_version' => phpversion(),
    'mysql_version' => mysqli_get_server_info($conn),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'os' => php_uname('s') . ' ' . php_uname('r'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
];

// Get error log info
$error_log_file = '../logs/error.log';
$error_log_size = file_exists($error_log_file) ? filesize($error_log_file) : 0;
$error_log_lines = 0;
if ($error_log_size > 0) {
    $error_log_lines = count(file($error_log_file));
}

// Handle actions
if (isset($_POST['clear_logs'])) {
    $type = $_POST['log_type'] ?? 'all';
    $days = (int)($_POST['days'] ?? 0);
    
    $sql = "DELETE FROM activity_logs";
    $where = [];
    $params = [];
    
    if ($type != 'all') {
        $where[] = "action_type = ?";
        $params[] = $type;
    }
    
    if ($days > 0) {
        $where[] = "created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params[] = $days;
    }
    
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($params) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $deleted_rows = mysqli_affected_rows($conn);
        logActivity("Cleared system logs ($type)");
        $success_msg = "Successfully cleared $deleted_rows log entries!";
    } else {
        $error_msg = "Error clearing logs: " . mysqli_error($conn);
    }
}

if (isset($_POST['export_logs'])) {
    // Export logs to CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="system_logs_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Timestamp', 'User', 'Action Type', 'Description', 'IP Address', 'User Agent']);
    
    $export_sql = "SELECT al.id, al.created_at, u.name as user_name, 
                          al.action_type, al.description, al.ip_address, al.user_agent 
                   FROM activity_logs al 
                   LEFT JOIN users u ON al.user_id = u.id 
                   ORDER BY al.created_at DESC";
    
    $export_result = mysqli_query($conn, $export_sql);
    while ($row = mysqli_fetch_assoc($export_result)) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .logs-container {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 20px;
        margin-top: 20px;
    }
    
    .filters-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        height: fit-content;
    }
    
    .logs-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
    }
    
    .log-entry {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: flex-start;
        transition: all 0.3s;
    }
    
    .log-entry:hover {
        background: #f8f9fa;
    }
    
    .log-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;
        font-size: 16px;
    }
    
    .log-icon.login { background: #d4edda; color: #155724; }
    .log-icon.logout { background: #f8d7da; color: #721c24; }
    .log-icon.create { background: #cce5ff; color: #004085; }
    .log-icon.update { background: #fff3cd; color: #856404; }
    .log-icon.delete { background: #f8d7da; color: #721c24; }
    .log-icon.view { background: #d1ecf1; color: #0c5460; }
    .log-icon.system { background: #e2e3e5; color: #383d41; }
    .log-icon.error { background: #f8d7da; color: #721c24; }
    .log-icon.security { background: #f8d7da; color: #721c24; }
    
    .log-content {
        flex: 1;
        min-width: 0;
    }
    
    .log-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 5px;
    }
    
    .log-user {
        font-weight: 600;
        color: #333;
    }
    
    .log-type {
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 12px;
        background: #e9ecef;
        color: #495057;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .log-description {
        color: #666;
        font-size: 14px;
        margin-bottom: 5px;
        word-break: break-word;
    }
    
    .log-meta {
        font-size: 12px;
        color: #888;
        display: flex;
        gap: 15px;
    }
    
    .log-meta span {
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    
    .system-info-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        margin-top: 20px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    
    .info-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        border-left: 4px solid #3498db;
    }
    
    .info-item h5 {
        margin: 0 0 5px 0;
        font-size: 14px;
        color: #666;
    }
    
    .info-item p {
        margin: 0;
        font-weight: 600;
        color: #333;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 20px;
    }
    
    .pagination a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 6px;
        background: white;
        border: 1px solid #dee2e6;
        color: #3498db;
        text-decoration: none;
        font-weight: 500;
    }
    
    .pagination a.active {
        background: #3498db;
        color: white;
        border-color: #3498db;
    }
    
    .pagination a:hover:not(.active) {
        background: #f8f9fa;
    }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    .modal.active {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
    }
    
    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }
    
    .modal-body {
        padding: 20px;
    }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Include Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="page-title">
                    <h1><i class="fas fa-clipboard-list"></i> System Logs</h1>
                    <p>Monitor system activities and user actions</p>
                </div>
                
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="exportLogs()">
                        <i class="fas fa-download"></i> Export Logs
                    </button>
                    <button class="btn btn-danger" onclick="openClearLogsModal()">
                        <i class="fas fa-trash"></i> Clear Logs
                    </button>
                    <div class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                </div>
            </header>
            
            <!-- Messages -->
            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>
            
            <div class="logs-container">
                <!-- Filters -->
                <div class="filters-card">
                    <h3><i class="fas fa-filter"></i> Filters</h3>
                    
                    <form method="GET" class="mt-4">
                        <div class="settings-form-group">
                            <label for="log_type">Log Type</label>
                            <select id="log_type" name="type" class="form-control">
                                <?php foreach ($log_types as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" 
                                        <?php echo $filter['type'] == $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="settings-form-group">
                            <label for="log_user">User</label>
                            <select id="log_user" name="user" class="form-control">
                                <?php foreach ($users as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" 
                                        <?php echo $filter['user'] == $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="settings-form-group">
                            <label for="date_from">Date From</label>
                            <input type="date" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($filter['date_from']); ?>" 
                                   class="form-control">
                        </div>
                        
                        <div class="settings-form-group">
                            <label for="date_to">Date To</label>
                            <input type="date" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($filter['date_to']); ?>" 
                                   class="form-control">
                        </div>
                        
                        <div class="settings-form-group">
                            <label for="log_search">Search</label>
                            <input type="text" id="log_search" name="search" 
                                   value="<?php echo htmlspecialchars($filter['search']); ?>" 
                                   class="form-control" placeholder="Search in logs...">
                        </div>
                        
                        <div class="settings-form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <a href="system-logs.php" class="btn btn-secondary btn-block mt-2">
                                <i class="fas fa-redo"></i> Reset Filters
                            </a>
                        </div>
                    </form>
                    
                    <!-- Stats -->
                    <div class="mt-4 pt-4 border-top">
                        <h4>Log Statistics</h4>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo number_format($total_logs); ?></div>
                                <div class="stat-label">Total Logs</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo number_format($per_page); ?></div>
                                <div class="stat-label">Per Page</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $total_pages; ?></div>
                                <div class="stat-label">Total Pages</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Logs List -->
                <div class="logs-card">
                    <h3>Activity Logs</h3>
                    <p class="text-muted mb-4">Showing <?php echo count($logs); ?> of <?php echo number_format($total_logs); ?> logs</p>
                    
                    <?php if (empty($logs)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h4>No logs found</h4>
                            <p class="text-muted">Try adjusting your filters or check back later</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <div class="log-entry">
                                <div class="log-icon <?php echo getLogIconClass($log['action_type']); ?>">
                                    <i class="fas fa-<?php echo getLogIcon($log['action_type']); ?>"></i>
                                </div>
                                
                                <div class="log-content">
                                    <div class="log-header">
                                        <div class="log-user">
                                            <?php if ($log['user_name']): ?>
                                                <?php echo htmlspecialchars($log['user_name']); ?>
                                                <small class="text-muted">(<?php echo $log['user_role']; ?>)</small>
                                            <?php else: ?>
                                                <span class="text-muted">System</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="log-type"><?php echo $log['action_type']; ?></div>
                                    </div>
                                    
                                    <div class="log-description">
                                        <?php echo htmlspecialchars($log['description']); ?>
                                    </div>
                                    
                                    <div class="log-meta">
                                        <span title="Timestamp">
                                            <i class="far fa-clock"></i>
                                            <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                        </span>
                                        <span title="IP Address">
                                            <i class="fas fa-network-wired"></i>
                                            <?php echo htmlspecialchars($log['ip_address']); ?>
                                        </span>
                                        <?php if ($log['user_agent']): ?>
                                            <span title="User Agent" class="text-truncate" style="max-width: 200px;">
                                                <i class="fas fa-desktop"></i>
                                                <?php echo htmlspecialchars(substr($log['user_agent'], 0, 50)); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($filter, ['page' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <a href="?<?php echo http_build_query(array_merge($filter, ['page' => $i])); ?>"
                                           class="<?php echo $i == $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                        <span>...</span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($filter, ['page' => $page + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- System Information -->
            <div class="system-info-card">
                <h3><i class="fas fa-info-circle"></i> System Information</h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <h5>PHP Version</h5>
                        <p><?php echo $system_info['php_version']; ?></p>
                    </div>
                    
                    <div class="info-item">
                        <h5>MySQL Version</h5>
                        <p><?php echo $system_info['mysql_version']; ?></p>
                    </div>
                    
                    <div class="info-item">
                        <h5>Server Software</h5>
                        <p><?php echo $system_info['server_software']; ?></p>
                    </div>
                    
                    <div class="info-item">
                        <h5>Operating System</h5>
                        <p><?php echo $system_info['os']; ?></p>
                    </div>
                    
                    <div class="info-item">
                        <h5>Memory Limit</h5>
                        <p><?php echo $system_info['memory_limit']; ?></p>
                    </div>
                    
                    <div class="info-item">
                        <h5>Upload Max Filesize</h5>
                        <p><?php echo $system_info['upload_max_filesize']; ?></p>
                    </div>
                    
                    <div class="info-item">
                        <h5>Post Max Size</h5>
                        <p><?php echo $system_info['post_max_size']; ?></p>
                    </div>
                    
                    <div class="info-item">
                        <h5>Error Log Size</h5>
                        <p><?php echo formatBytes($error_log_size); ?> (<?php echo number_format($error_log_lines); ?> lines)</p>
                    </div>
                    
                    <div class="info-item">
                        <h5>Database Size</h5>
                        <?php
                        $db_size_sql = "SELECT 
                            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
                            FROM information_schema.tables 
                            WHERE table_schema = '" . DB_NAME . "'";
                        $db_size_result = mysqli_query($conn, $db_size_sql);
                        $db_size = mysqli_fetch_assoc($db_size_result)['size_mb'] ?? 0;
                        ?>
                        <p><?php echo $db_size; ?> MB</p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="view-error-log.php" class="btn btn-warning">
                        <i class="fas fa-file-alt"></i> View Error Log
                    </a>
                    
                    <button onclick="clearErrorLog()" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Clear Error Log
                    </button>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Clear Logs Modal -->
    <div class="modal" id="clearLogsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-trash"></i> Clear System Logs</h3>
                <button class="modal-close" onclick="closeModal('clearLogsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="clearLogsForm">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action cannot be undone. Logs will be permanently deleted.
                    </div>
                    
                    <div class="settings-form-group">
                        <label for="clear_log_type">Log Type to Clear</label>
                        <select id="clear_log_type" name="log_type" class="form-control" required>
                            <option value="all">All Log Types</option>
                            <?php foreach ($log_types as $value => $label): ?>
                                <?php if ($value != 'all'): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="settings-form-group">
                        <label for="clear_days">Older Than (Days)</label>
                        <select id="clear_days" name="days" class="form-control" required>
                            <option value="0">All logs (regardless of age)</option>
                            <option value="7">Older than 7 days</option>
                            <option value="30">Older than 30 days</option>
                            <option value="90">Older than 90 days</option>
                            <option value="365">Older than 1 year</option>
                        </select>
                        <div class="settings-help">Select 0 to delete all logs of the selected type</div>
                    </div>
                    
                    <div class="settings-form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="confirm_delete" required>
                            <label class="form-check-label" for="confirm_delete">
                                I understand this action cannot be undone
                            </label>
                        </div>
                    </div>
                    
                    <div class="text-right mt-4">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('clearLogsModal')">Cancel</button>
                        <button type="submit" name="clear_logs" class="btn btn-danger">Clear Logs</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Mobile menu toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // Modal functions
    function openClearLogsModal() {
        document.getElementById('clearLogsModal').classList.add('active');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    // Export logs
    function exportLogs() {
        // Get current filter parameters
        const params = new URLSearchParams(window.location.search);
        window.location.href = 'system-logs.php?' + params.toString() + '&export=1';
    }
    
    // Clear error log
    function clearErrorLog() {
        if (confirm('Are you sure you want to clear the error log?')) {
            fetch('ajax/clear-error-log.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Error log cleared successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }
    }
    
    // Auto-refresh logs every 30 seconds
    let autoRefresh = true;
    
    function refreshLogs() {
        if (autoRefresh && !document.querySelector('.modal.active')) {
            const params = new URLSearchParams(window.location.search);
            params.set('refresh', Date.now());
            
            fetch('system-logs.php?' + params.toString())
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newLogs = doc.querySelector('.logs-card');
                    if (newLogs) {
                        document.querySelector('.logs-card').innerHTML = newLogs.innerHTML;
                    }
                });
        }
    }
    
    // Start auto-refresh
    setInterval(refreshLogs, 30000);
    
    // Stop auto-refresh when user is interacting
    document.addEventListener('mousemove', () => {
        autoRefresh = false;
        setTimeout(() => {
            autoRefresh = true;
        }, 60000); // Resume after 1 minute of inactivity
    });
    
    // Close modals on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
    
    // Close modals when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
    </script>
</body>
</html>