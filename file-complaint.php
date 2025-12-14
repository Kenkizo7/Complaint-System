<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $user_id = $_SESSION['user_id'];
    
    $attachment_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $upload_result = uploadFile($_FILES['attachment']);
        if (isset($upload_result['success'])) {
            $attachment_path = $upload_result['success'];
        } else {
            $error = $upload_result['error'];
        }
    }
    
    if (!$error) {
        $sql = "INSERT INTO complaints (user_id, title, description, category, priority, attachment_path) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'isssss', $user_id, $title, $description, $category, $priority, $attachment_path);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Complaint filed successfully! Your complaint ID is #" . mysqli_insert_id($conn);
        } else {
            $error = "Error filing complaint. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Complaint - College Complaint System</title>
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
                <li><a href="file-complaint.php" class="active"><i class="fas fa-plus-circle"></i> File Complaint</a></li>
                <li><a href="view-complaints.php"><i class="fas fa-list"></i> View Complaints</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h1>File a New Complaint</h1>
        
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
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Complaint Title *</label>
                    <input type="text" class="form-control" id="title" name="title" required 
                           placeholder="Brief description of your complaint">
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select class="form-control" id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Academic">Academic</option>
                        <option value="Administrative">Administrative</option>
                        <option value="Facilities">Facilities</option>
                        <option value="Hostel">Hostel</option>
                        <option value="Library">Library</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority Level *</label>
                    <select class="form-control" id="priority" name="priority" required>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Detailed Description *</label>
                    <textarea class="form-control" id="description" name="description" required 
                              placeholder="Please provide detailed information about your complaint..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="attachment">Attachment (Optional)</label>
                    <input type="file" class="form-control" id="attachment" name="attachment">
                    <small>Supported files: PDF, DOC, JPG, PNG (Max 5MB)</small>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Complaint
                    </button>
                    <a href="index.php" class="btn">Cancel</a>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>Complaint Guidelines</h2>
            <ul>
                <li>Provide clear and concise information about your complaint</li>
                <li>Select the appropriate category for faster processing</li>
                <li>Attach relevant documents if available</li>
                <li>Use appropriate priority level (Urgent for time-sensitive issues)</li>
                <li>You will receive updates on your complaint via email</li>
                <li>Check the status of your complaints in the "View Complaints" section</li>
            </ul>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> College Complaint Filing System. All rights reserved.</p>
    </footer>
</body>
</html>