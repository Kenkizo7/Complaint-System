<?php
// Go up one level to include config files
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Check if user is admin
if ($_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle settings updates
    if (isset($_POST['save_general'])) {
        // Update system settings
        $site_name = mysqli_real_escape_string($conn, $_POST['site_name']);
        $site_email = mysqli_real_escape_string($conn, $_POST['site_email']);
        
        // Check if system_settings table exists
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'system_settings'");
        if (mysqli_num_rows($table_check) > 0) {
            $update_sql = "UPDATE system_settings SET 
                          site_name = '$site_name',
                          site_email = '$site_email',
                          updated_at = NOW()
                          WHERE id = 1";
            
            if (mysqli_query($conn, $update_sql)) {
                $success_msg = "Settings updated successfully!";
            } else {
                $error_msg = "Error updating settings: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Settings table not found. Please run database setup.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include 'admin-sidebar-styles.php'; ?>
</head>
<body>
    <div class="admin-container">
        <!-- Include Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="page-title">
                    <h1><i class="fas fa-cog"></i> System Settings</h1>
                    <p>Configure system parameters</p>
                </div>
            </header>
            
            <!-- Messages -->
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <div class="settings-container">
                <div class="settings-card">
                    <h3>General Settings</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Site Name</label>
                            <input type="text" name="site_name" value="College Complaint System">
                        </div>
                        <div class="form-group">
                            <label>Site Email</label>
                            <input type="email" name="site_email" value="support@college.edu">
                        </div>
                        <button type="submit" name="save_general" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
                
                <div class="settings-card">
                    <h3>Database Status</h3>
                    <?php
                    // Check which tables exist
                    $tables = ['system_settings', 'complaint_settings', 'email_settings', 'notification_settings', 'security_settings'];
                    echo '<ul>';
                    foreach ($tables as $table) {
                        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
                        $exists = mysqli_num_rows($result) > 0 ? '✅' : '❌';
                        echo "<li>$exists $table table</li>";
                    }
                    
                    // Check role column
                    $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
                    $role_exists = mysqli_num_rows($result) > 0 ? '✅' : '❌';
                    echo "<li>$role_exists role column in users table</li>";
                    echo '</ul>';
                    ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>