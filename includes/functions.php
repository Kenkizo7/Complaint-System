<?php
// functions.php

// First, check if session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to log user activity
// Function to log user activity (UPDATED to match your database structure)
function logActivity($conn, $description, $action_type = null, $details = null) {
    // Get user ID from session
    $user_id = $_SESSION['user_id'] ?? null;
    
    // If no user ID, try to get it from session data
    if (!$user_id) {
        return false;
    }
    
    // Get user's IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Get user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Default action_type if not provided
    if ($action_type === null) {
        $action_type = 'general';
    }
    
    // Note: Your table doesn't have 'details' or 'page_url' columns, so we'll omit them
    // The description should contain all necessary information
    
    // Insert into activity_logs table - match your column names
    $sql = "INSERT INTO activity_logs (user_id, action_type, description, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Failed to prepare statement for logActivity: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'issss', $user_id, $action_type, $description, $ip_address, $user_agent);
    
    $result = mysqli_stmt_execute($stmt);
    if (!$result) {
        error_log("Failed to execute logActivity: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_close($stmt);
    return true;
}

// Function to get activity icon based on action_type (UPDATED parameter name)
function getActivityIcon($action_type) {
    $icons = [
        'login' => 'sign-in-alt',
        'logout' => 'sign-out-alt',
        'create' => 'plus-circle',
        'update' => 'edit',
        'delete' => 'trash',
        'view' => 'eye',
        'download' => 'download',
        'upload' => 'upload',
        'status_change' => 'sync',
        'password_change' => 'key',
        'profile_update' => 'user-edit',
        'system' => 'cog',
        'report' => 'chart-bar',
        'backup' => 'database',
        'restore' => 'history',
        'export' => 'file-export',
        'import' => 'file-import',
        'add_user' => 'user-plus',
        'remove_user' => 'user-minus',
        'general' => 'info-circle'
    ];
    
    return $icons[$action_type] ?? 'info-circle';
}

// Function to format time elapsed
// Function to format time elapsed
// Function to format time elapsed (clean version)
// Function to format time elapsed (safe version)
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    // Create an array with time values
    $timeValues = [
        'y' => $diff->y,
        'm' => $diff->m,
        'd' => $diff->d,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s
    ];
    
    // Calculate weeks
    $timeValues['w'] = floor($timeValues['d'] / 7);
    $timeValues['d'] = $timeValues['d'] % 7;
    
    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    $result = [];
    foreach ($string as $key => $value) {
        if ($timeValues[$key] > 0) {
            $result[] = $timeValues[$key] . ' ' . $value . ($timeValues[$key] > 1 ? 's' : '');
        }
    }
    
    if (!$full && !empty($result)) {
        $result = [array_shift($result)];
    }
    
    return $result ? implode(', ', $result) . ' ago' : 'just now';
}
// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to get user data
function getUserData($conn, $user_id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Function to get complaint statistics for a user
function getComplaintStats($conn, $user_id) {
    // Initialize stats array
    $stats = [
        'total' => 0,
        'pending' => 0,
        'under_investigation' => 0,
        'resolved' => 0
    ];
    
    // Get total complaints count
    $sql = "SELECT status, COUNT(*) as count FROM complaints WHERE user_id = ? GROUP BY status";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['total'] += $row['count'];
        
        // Map status to array keys
        switch($row['status']) {
            case 'Pending':
                $stats['pending'] = $row['count'];
                break;
            case 'Under Investigation':
                $stats['under_investigation'] = $row['count'];
                break;
            case 'Resolved':
                $stats['resolved'] = $row['count'];
                break;
        }
    }
    
    return $stats;
}

// Function to get complaints
function getComplaints($conn, $user_id, $status = null, $limit = null) {
    $sql = "SELECT * FROM complaints WHERE user_id = ?";
    
    if ($status) {
        $sql .= " AND status = ?";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($status && $limit) {
        mysqli_stmt_bind_param($stmt, 'isi', $user_id, $status, $limit);
    } elseif ($status) {
        mysqli_stmt_bind_param($stmt, 'is', $user_id, $status);
    } elseif ($limit) {
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $limit);
    } else {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $complaints = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $complaints[] = $row;
    }
    return $complaints;
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

// Function to check if user is admin
?>