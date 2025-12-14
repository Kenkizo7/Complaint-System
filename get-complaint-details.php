<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $complaint_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    require_once 'includes/functions.php';
    
    // Use the new function that includes relations
    $complaint = getComplaintDetailsWithRelations($conn, $complaint_id, $user_id);
    
    if ($complaint) {
        // Format dates
        $complaint['created_at_formatted'] = date('F j, Y, g:i a', strtotime($complaint['created_at']));
        $complaint['updated_at_formatted'] = date('F j, Y, g:i a', strtotime($complaint['updated_at']));
        
        if ($complaint['resolved_date']) {
            $complaint['resolved_date_formatted'] = date('F j, Y, g:i a', strtotime($complaint['resolved_date']));
        }
        
        echo json_encode($complaint);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Complaint not found or access denied']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No complaint ID provided']);
}
?>