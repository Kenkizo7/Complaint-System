<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if database exists, if not redirect to setup
$dbExists = checkDatabaseExists($conn, DB_NAME);

if (!$dbExists) {
    header('Location: setup.php');
    exit();
}

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // First, ensure we're using the correct database
    if (!mysqli_select_db($conn, DB_NAME)) {
        $error = "Database configuration error. Please run setup.";
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
            
            header('Location: index.php');
            exit();
        } else {
            $error = "Invalid email or password";
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
    </style>
</head>
<body class="login-container">
    <div class="login-box">
        <h2><i class="fas fa-university"></i> College Complaint System</h2>
        
        <?php if (isset($_GET['setup']) && $_GET['setup'] == 'success'): ?>
            <div class="alert alert-success">
                Database setup completed successfully! You can now login.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required
                       placeholder="admin@college.edu">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required
                       placeholder="admin123">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="login-links">
            <p>
                <a href="forgot-password.php">
                    <i class="fas fa-key"></i> Forgot Password?
                </a>
            </p>
            
            <?php if ($dbExists && mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users")) == 0): ?>
                <div class="setup-link">
                    <p><strong>No users found!</strong></p>
                    <a href="setup.php" class="btn btn-warning">
                        <i class="fas fa-cogs"></i> Run Setup Again
                    </a>
                </div>
            <?php endif; ?>
            
            <p>Don't have an account? Contact administrator for registration.</p>
        </div>
    </div>
</body>
</html>