<?php
require_once 'includes/config.php';

$message = '';
$error = '';
$success = false;
$showCredentials = false;
$adminCredentials = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['setup_database'])) {
        $result = setupDatabase($conn);
        
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            $success = true;
            $message = $result['message'];
            
            if (isset($result['admin_credentials'])) {
                $showCredentials = true;
                $adminCredentials = $result['admin_credentials'];
            }
        }
    }
}

// Check current status
$dbExists = checkDatabaseExists($conn, DB_NAME);
$missingTables = [];
if ($dbExists) {
    mysqli_select_db($conn, DB_NAME);
    $missingTables = checkTablesExist($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - College Complaint System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-ok {
            background-color: #27ae60;
        }
        
        .status-warning {
            background-color: #f39c12;
        }
        
        .status-error {
            background-color: #e74c3c;
        }
        
        .credentials-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .setup-steps {
            margin: 30px 0;
        }
        
        .setup-steps ol {
            margin-left: 20px;
            line-height: 2;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <i class="fas fa-university"></i>
                <h1>College Complaint System - Setup</h1>
            </div>
        </nav>
    </header>

    <main class="container">
        <div class="setup-container">
            <div class="card">
                <h2><i class="fas fa-cogs"></i> System Setup</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <strong>Error:</strong> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($showCredentials): ?>
                    <div class="credentials-box">
                        <h3><i class="fas fa-key"></i> Admin Credentials</h3>
                        <p><strong>Important:</strong> Save these credentials. You should change the password after first login.</p>
                        <table style="width: 100%; margin-top: 15px;">
                            <tr>
                                <td style="padding: 8px 0;"><strong>Email:</strong></td>
                                <td><?php echo $adminCredentials['email']; ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0;"><strong>Password:</strong></td>
                                <td><?php echo $adminCredentials['password']; ?></td>
                            </tr>
                        </table>
                        <div style="margin-top: 20px;">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Go to Login
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="setup-steps">
                    <h3>Setup Status</h3>
                    <ul style="list-style: none; padding-left: 0;">
                        <li>
                            <span class="status-indicator <?php echo $dbExists ? 'status-ok' : 'status-warning'; ?>"></span>
                            Database: <?php echo $dbExists ? 'Exists' : 'Not found'; ?>
                        </li>
                        <li>
                            <span class="status-indicator <?php echo empty($missingTables) ? 'status-ok' : 'status-warning'; ?>"></span>
                            Tables: <?php echo empty($missingTables) ? 'All tables exist' : 'Missing tables: ' . implode(', ', $missingTables); ?>
                        </li>
                        <li>
                            <span class="status-indicator status-ok"></span>
                            Uploads directory: <?php echo is_dir('uploads') ? 'Ready' : 'Not ready'; ?>
                        </li>
                    </ul>
                </div>
                
                <?php if (!$success): ?>
                    <div class="setup-steps">
                        <h3>Setup Instructions</h3>
                        <ol>
                            <li>Ensure your MySQL server is running</li>
                            <li>Verify database credentials in <code>includes/config.php</code></li>
                            <li>Click the "Setup Database" button below</li>
                            <li>Note down the admin credentials</li>
                            <li>Login and change the default password</li>
                        </ol>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <button type="submit" name="setup_database" class="btn btn-primary" style="width: 100%; padding: 15px;">
                                <i class="fas fa-database"></i> Setup Database
                            </button>
                        </div>
                        <div class="form-group" style="text-align: center;">
                            <p>This will create the database, tables, and an admin user.</p>
                        </div>
                    </form>
                <?php endif; ?>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <h4>Troubleshooting</h4>
                    <ul>
                        <li>Check if MySQL is running: <code>sudo service mysql status</code></li>
                        <li>Verify database user permissions</li>
                        <li>Check PHP error logs for more details</li>
                        <li>Ensure PHP has MySQLi extension enabled</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> College Complaint Filing System</p>
        <p>Setup Wizard | Version 1.0</p>
    </footer>
</body>
</html>