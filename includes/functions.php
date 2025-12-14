<?php
require_once 'config.php';

function getComplaints($conn, $user_id = null, $status = null, $limit = null) {
    $sql = "SELECT c.*, u.name, u.student_id FROM complaints c 
            JOIN users u ON c.user_id = u.id";
    
    $conditions = [];
    $params = [];
    $types = '';
    
    if ($user_id) {
        $conditions[] = "c.user_id = ?";
        $params[] = $user_id;
        $types .= 'i';
    }
    
    if ($status) {
        $conditions[] = "c.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY 
        CASE c.status 
            WHEN 'Pending' THEN 1
            WHEN 'Under Investigation' THEN 2
            WHEN 'Resolved' THEN 3
        END,
        CASE c.priority
            WHEN 'Urgent' THEN 1
            WHEN 'High' THEN 2
            WHEN 'Medium' THEN 3
            WHEN 'Low' THEN 4
        END,
        c.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
        $types .= 'i';
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $complaints = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $complaints[] = $row;
    }
    
    return $complaints;
}

function getComplaintStats($conn, $user_id) {
    $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Under Investigation' THEN 1 ELSE 0 END) as under_investigation,
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved
            FROM complaints WHERE user_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $stats = mysqli_fetch_assoc($result);
    
    // Ensure all values are set
    $defaults = [
        'total' => 0, 
        'pending' => 0, 
        'under_investigation' => 0, 
        'resolved' => 0
    ];
    return array_merge($defaults, $stats ?: []);
}

// Get complaint details with student info
function getComplaintDetails($conn, $complaint_id, $user_id = null) {
    $sql = "SELECT c.*, u.name, u.student_id, u.email 
            FROM complaints c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.id = ?";
    
    $params = [$complaint_id];
    $types = 'i';
    
    if ($user_id && $_SESSION['role'] != 'admin') {
        $sql .= " AND c.user_id = ?";
        $params[] = $user_id;
        $types .= 'i';
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

function uploadFile($file) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            return ["error" => "Failed to create uploads directory."];
        }
    }
    
    $file_name = time() . '_' . basename($file["name"]);
    $target_file = $target_dir . $file_name;
    
    // Check file size
    if ($file["size"] > MAX_FILE_SIZE) {
        return ["error" => "File is too large. Maximum size is 5MB."];
    }
    
    // Allow certain file formats
    $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    if (!in_array($file_type, $allowed_types)) {
        return ["error" => "Only PDF, DOC, JPG, PNG files are allowed."];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => $target_file];
    } else {
        return ["error" => "Sorry, there was an error uploading your file."];
    }
}

function logActivity($conn, $user_id, $activity) {
    $log_file = 'logs/activity.log';
    $log_entry = date('Y-m-d H:i:s') . " | User: $user_id | Activity: $activity\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

function getStatusBadge($status) {
    $status_class = strtolower(str_replace(' ', '-', $status));
    $status_text = $status;
    
    switch ($status) {
        case 'Pending':
            $color = '#f39c12';
            break;
        case 'Under Investigation':
            $color = '#3498db';
            break;
        case 'Resolved':
            $color = '#27ae60';
            break;
        default:
            $color = '#7f8c8d';
    }
    
    return "<span style='background-color: $color; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;'>$status_text</span>";
}

?>