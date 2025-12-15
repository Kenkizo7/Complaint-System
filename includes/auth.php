<?php
require_once 'config.php';


// auth.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Function to require admin access

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

function generateResetToken($conn, $email) {
    $token = bin2hex(random_bytes(50));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sss', $token, $expiry, $email);
    mysqli_stmt_execute($stmt);
    
    return $token;
}

// Add the missing logActivity function
function logActivity($conn, $user_id, $activity) {
    // Ensure logs directory exists
    if (!file_exists('logs')) {
        mkdir('logs', 0777, true);
    }
    
    $log_file = 'logs/activity.log';
    $log_entry = date('Y-m-d H:i:s') . " | User ID: $user_id | Activity: $activity\n";
    
    // Use FILE_APPEND to add to the log file
    if (file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX) === false) {
        // If logging fails, you might want to log to PHP error log
        error_log("Failed to write to activity log: $log_entry");
    }
}

// Add sanitizeInput function
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Add validateEmail function
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Add validatePhone function
function validatePhone($phone) {
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

// Add checkUserRole function
function checkUserRole($conn, $user_id) {
    $sql = "SELECT student_id FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user && $user['student_id'] == 'ADMIN001') {
        return 'admin';
    }
    return 'student';
}
?>