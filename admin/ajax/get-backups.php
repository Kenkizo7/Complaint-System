<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();

if ($_SESSION['role'] != 'admin') {
    die(json_encode(['error' => 'Unauthorized']));
}

$backups = [];
$backup_dir = '../../backups/';

if (file_exists($backup_dir)) {
    $files = glob($backup_dir . '*.sql');
    
    foreach ($files as $file) {
        $backups[] = [
            'name' => basename($file),
            'date' => date('Y-m-d H:i:s', filemtime($file)),
            'size' => formatBytes(filesize($file))
        ];
    }
}

echo json_encode($backups);
?>