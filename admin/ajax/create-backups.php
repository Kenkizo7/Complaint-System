<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();

if ($_SESSION['role'] != 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$backup_name = $_POST['backup_name'] ?? '';
$include_files = $_POST['include_files'] ?? 0;

// Create backup directory if it doesn't exist
$backup_dir = '../../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Generate filename
$timestamp = date('Y-m-d_H-i-s');
$filename = $backup_name ? "{$backup_name}_{$timestamp}.sql" : "backup_{$timestamp}.sql";
$filepath = $backup_dir . $filename;

// Database backup
$command = "mysqldump --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > " . $filepath;
system($command, $output);

if ($output === 0 && file_exists($filepath)) {
    logActivity("Created database backup: $filename");
    echo json_encode(['success' => true, 'filename' => $filename]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create backup']);
}
?>