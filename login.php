<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check for database errors in session
$db_error = '';
if (isset($_SESSION['db_error'])) {
    $db_error = $_SESSION['db_error'];
    unset($_SESSION['db_error']); // Clear the error after displaying
}

// Check if database exists
$dbExists = checkDatabaseExists($conn, DB_NAME);

if (isLoggedIn()) {
    // Check if user is admin and redirect to admin panel
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/admin-dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // First, ensure we're using the correct database
    if (!mysqli_select_db($conn, DB_NAME)) {
        $error = "Database not configured. Please run setup first.";
    } else {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['student_id'] == 'ADMIN001' ? 'admin' : 'student';
            
            // Log login activity
            logActivity($conn, $user['id'], 'User logged in');
            
            // Set a success message
            $_SESSION['login_success'] = true;
            
            // Redirect admin to admin panel, students to main index
            if ($_SESSION['role'] == 'admin') {
                header('Location: admin/admin-dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            $error = "Invalid email or password";
            
            // Log failed login attempt
            if (function_exists('logActivity')) {
                logActivity($conn, 0, "Failed login attempt with email: $email");
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - College Complaint System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .setup-link {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }
        
        .demo-credentials {
            background-color: #e8f4f8;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .demo-credentials h4 {
            margin-top: 0;
            color: #0c5460;
        }
        
        .db-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .welcome-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="login-container">
    <div class="login-box">
        <h2><i class="fas fa-university"></i> College Complaint System</h2>
        
        <?php if (isset($_GET['setup']) && $_GET['setup'] == 'success'): ?>
            <div class="welcome-message">
                <i class="fas fa-check-circle"></i> Database setup completed successfully! You can now login.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You have been successfully logged out.
            </div>
        <?php endif; ?>
        
        <?php if ($db_error): ?>
            <div class="db-error">
                <i class="fas fa-exclamation-triangle"></i> <strong>Database Error:</strong> <?php echo htmlspecialchars($db_error); ?>
                <div style="margin-top: 10px;">
                    <a href="setup.php" class="btn btn-warning" style="padding: 8px 15px; font-size: 14px;">
                        <i class="fas fa-cogs"></i> Run Setup Now
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$dbExists): ?>
            <div class="db-error">
                <i class="fas fa-database"></i> <strong>Database Not Found</strong>
                <p style="margin: 10px 0;">The system database does not exist yet.</p>
                <div>
                    <a href="setup.php" class="btn btn-primary" style="padding: 10px 20px;">
                        <i class="fas fa-cogs"></i> Setup Database First
                    </a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="form-control" id="email" name="email" required
                               placeholder="Enter your email address">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" required
                               placeholder="Enter your password">
                    </div>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center;">
                        <input type="checkbox" name="remember" style="margin-right: 8px;">
                        Remember me
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <!-- Demo Credentials Section -->
            <div class="demo-credentials">
                <h4><i class="fas fa-info-circle"></i> Demo Credentials:</h4>
                <p><strong>Admin:</strong> admin@college.edu / admin123</p>
                <p><strong>Student:</strong> john.doe@college.edu / student123</p>
                <p><strong>Note:</strong> Use admin credentials to access the admin panel.</p>
            </div>
        <?php endif; ?>
        
        <div class="login-links">
            <p>
                <a href="forgot-password.php">
                    <i class="fas fa-key"></i> Forgot Password?
                </a>
            </p>
            
            <?php if (!$dbExists): ?>
                <div class="setup-link">
                    <p><strong>First time setup required</strong></p>
                    <a href="setup.php" class="btn btn-primary">
                        <i class="fas fa-cogs"></i> Setup Database
                    </a>
                </div>
            <?php endif; ?>
            
            <p>Don't have an account? Contact administrator for registration.</p>
        </div>
    </div>
    
    <script>
    // Add show/hide password functionality
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            toggleBtn.style.position = 'absolute';
            toggleBtn.style.right = '10px';
            toggleBtn.style.top = '50%';
            toggleBtn.style.transform = 'translateY(-50%)';
            toggleBtn.style.background = 'none';
            toggleBtn.style.border = 'none';
            toggleBtn.style.cursor = 'pointer';
            toggleBtn.style.color = '#666';
            
            const inputWrapper = passwordInput.parentElement;
            inputWrapper.style.position = 'relative';
            inputWrapper.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordInput.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
        }
    });
    </script>
</body>
</html>