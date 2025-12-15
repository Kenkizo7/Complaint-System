<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_data = getUserData($conn, $user_id);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    
    $sql = "UPDATE users SET name = ?, contact_number = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssi', $name, $contact_number, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['name'] = $name;
        $message = "Profile updated successfully!";
        $user_data = getUserData($conn, $user_id); // Refresh data
    } else {
        $error = "Error updating profile. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - College Complaint System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <i class="fas fa-university"></i>
                <h1>College Complaint System</h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="file-complaint.php"><i class="fas fa-plus-circle"></i> File Complaint</a></li>
                <li><a href="view-complaints.php"><i class="fas fa-list"></i> View Complaints</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h1>My Profile</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user_data['name']); ?></h2>
                    <p><?php echo htmlspecialchars($user_data['student_id']); ?></p>
                    <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" class="form-control" id="student_id" 
                           value="<?php echo htmlspecialchars($user_data['student_id']); ?>" disabled>
                    <small>Student ID cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?php echo htmlspecialchars($user_data['name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" 
                           value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
                    <small>Email cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="tel" class="form-control" id="contact_number" name="contact_number"
                           value="<?php echo htmlspecialchars($user_data['contact_number'] ?? ''); ?>"
                           placeholder="Enter your contact number">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                    <a href="index.php" class="btn">Cancel</a>
                </div>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <div>
                <h3>Account Security</h3>
                <p>Last profile update: <?php echo date('d M Y, h:i A', strtotime($user_data['created_at'])); ?></p>
                <a href="forgot-password.php" class="btn btn-warning">
                    <i class="fas fa-key"></i> Change Password
                </a>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> College Complaint Filing System. All rights reserved.</p>
    </footer>
</body>
</html>