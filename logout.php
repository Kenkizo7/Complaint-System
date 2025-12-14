<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Log logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    logActivity($conn, $user_id, 'User logged out');
}

// Destroy the session
session_destroy();

// Redirect to login page with success message
header('Location: login.php?logout=success');
exit();
?>