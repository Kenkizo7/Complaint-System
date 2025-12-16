<?php
require_once 'config.php';


// auth.php
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
    
    // Check if role is set in session, if not fetch from database
    if (!isset($_SESSION['role'])) {
        global $conn;
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT role FROM users WHERE id = $user_id";
        $result = mysqli_query($conn, $sql);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['role'] = $row['role'] ?? 'student';
        } else {
            $_SESSION['role'] = 'student';
        }
    }
}

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