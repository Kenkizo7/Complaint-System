<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_complaint_system');
define('BASE_URL', 'http://localhost/college-complaint-system');

// Site configuration
define('SITE_NAME', 'College Complaint System');
define('SITE_EMAIL', 'support@college.edu');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB in bytes

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create initial connection without database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Include database setup
require_once 'setup-database.php';

// Check if database exists, if not redirect to setup
$dbExists = checkDatabaseExists($conn, DB_NAME);

if (!$dbExists) {
    // Check if we're not already on the setup page
    if (basename($_SERVER['PHP_SELF']) != 'setup.php' && basename($_SERVER['PHP_SELF']) != 'login.php') {
        header('Location: setup.php');
        exit();
    }
} else {
    // Select the database
    if (!mysqli_select_db($conn, DB_NAME)) {
        die("Failed to select database: " . mysqli_error($conn));
    }
    
    // Check if tables exist
    $missingTables = checkTablesExist($conn);
    if (!empty($missingTables) && basename($_SERVER['PHP_SELF']) != 'setup.php') {
        header('Location: setup.php?missing_tables=' . urlencode(implode(',', $missingTables)));
        exit();
    }
}

// Initialize uploads directory
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// Initialize logs directory
if (!file_exists('logs')) {
    mkdir('logs', 0777, true);
}
?>