<?php
// functions.php

// First, check if session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Don't try to include config.php here - let the calling file handle it
// We'll assume that config.php has already been included by the calling script

// Function to check if user is logged in
// Function to require login

// Function to get user data
function getUserData($conn, $user_id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Function to log activity
function logActivity($conn, $user_id, $action, $details = '') {
    if (!$conn) {
        return false; // No database connection
    }
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'issss', $user_id, $action, $details, $ip_address, $user_agent);
        mysqli_stmt_execute($stmt);
        return true;
    }
    return false;
}

// Function to upload file
function uploadFile($file) {
    $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    // Check for upload errors
    if ($file_error !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload error: ' . $file_error];
    }
    
    // Check file size
    if ($file_size > $max_size) {
        return ['error' => 'File size exceeds 5MB limit'];
    }
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file extension
    if (!in_array($file_ext, $allowed_extensions)) {
        return ['error' => 'File type not allowed. Allowed types: ' . implode(', ', $allowed_extensions)];
    }
    
    // Generate unique file name
    $unique_name = uniqid('', true) . '.' . $file_ext;
    $upload_dir = 'uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $destination = $upload_dir . $unique_name;
    
    // Move uploaded file
    if (move_uploaded_file($file_tmp, $destination)) {
        return ['success' => $destination];
    } else {
        return ['error' => 'Failed to move uploaded file'];
    }
}

// Function to get all complaints for a user
function getUserComplaints($conn, $user_id) {
    $sql = "SELECT c.*, 
                   (SELECT COUNT(*) FROM co_complainants cc WHERE cc.complaint_id = c.id) as co_complainant_count
            FROM complaints c 
            WHERE c.user_id = ? 
            ORDER BY c.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $complaints = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $complaints[] = $row;
    }
    return $complaints;
}

// Function to get complaint details
function getComplaintDetails($conn, $complaint_id, $user_id = null) {
    $sql = "SELECT c.*, u.name as complainant_name, u.student_id, u.email
            FROM complaints c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.id = ?";
    
    if ($user_id) {
        $sql .= " AND c.user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $complaint_id, $user_id);
    } else {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $complaint_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Function to get co-complainants for a complaint
function getCoComplainants($conn, $complaint_id) {
    $sql = "SELECT * FROM co_complainants WHERE complaint_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $complaint_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $co_complainants = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $co_complainants[] = $row;
    }
    return $co_complainants;
}

// Function to get witnesses for a complaint
function getWitnesses($conn, $complaint_id) {
    $sql = "SELECT * FROM complaint_witnesses WHERE complaint_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $complaint_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $witnesses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $witnesses[] = $row;
    }
    return $witnesses;
}

// Function to update complaint status
function updateComplaintStatus($conn, $complaint_id, $status, $admin_notes = null) {
    $sql = "UPDATE complaints SET status = ?, admin_notes = ?";
    if ($status == 'Resolved') {
        $sql .= ", resolved_date = NOW()";
    } else {
        $sql .= ", resolved_date = NULL";
    }
    $sql .= " WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssi', $status, $admin_notes, $complaint_id);
    return mysqli_stmt_execute($stmt);
}

// Function to check if database exists
function checkDatabaseExists($conn, $dbname) {
    $result = mysqli_query($conn, "SHOW DATABASES LIKE '$dbname'");
    return mysqli_num_rows($result) > 0;
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}
?>