<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $complaint_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT * FROM complaints WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $complaint_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $complaint = mysqli_fetch_assoc($result);
    
    if ($complaint) {
        echo json_encode($complaint);
    } else {
        echo json_encode(['error' => 'Complaint not found']);
    }
} else {
    echo json_encode(['error' => 'No complaint ID provided']);
}
?>