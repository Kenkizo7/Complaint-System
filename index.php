<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Check if database and tables exist
$missingTables = checkTablesExist($conn);
if (!empty($missingTables)) {
    header('Location: setup.php?missing_tables=' . urlencode(implode(',', $missingTables)));
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = getUserData($conn, $user_id);
$stats = getComplaintStats($conn, $user_id);
$recent_complaints = getComplaints($conn, $user_id, null, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - College Complaint System</title>
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
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="file-complaint.php"><i class="fas fa-plus-circle"></i> File Complaint</a></li>
                <li><a href="view-complaints.php"><i class="fas fa-list"></i> View Complaints</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="admin-dashboard.php"><i class="fas fa-cog"></i> Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
        
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <div class="alert alert-info" style="margin-bottom: 20px;">
                <i class="fas fa-shield-alt"></i> <strong>Admin Mode:</strong> You have administrative privileges to manage complaints.
            </div>
        <?php endif; ?>
        
        <!-- Dashboard Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Complaints</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['pending']; ?></h3>
                <p>Pending</p>
                <div style="margin-top: 5px;">
                    <span class="status-badge status-pending">Pending</span>
                </div>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['under_investigation']; ?></h3>
                <p>Under Investigation</p>
                <div style="margin-top: 5px;">
                    <span class="status-badge status-under-investigation">Under Investigation</span>
                </div>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['resolved']; ?></h3>
                <p>Resolved</p>
                <div style="margin-top: 5px;">
                    <span class="status-badge status-resolved">Resolved</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h2>Quick Actions</h2>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="file-complaint.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> File New Complaint
                </a>
                <a href="view-complaints.php" class="btn btn-success">
                    <i class="fas fa-list"></i> View All Complaints
                </a>
                <a href="profile.php" class="btn btn-warning">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <a href="admin-dashboard.php" class="btn btn-danger">
                        <i class="fas fa-cog"></i> Admin Panel
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Complaints -->
        <div class="card">
            <h2>Recent Complaints</h2>
            <?php if (empty($recent_complaints)): ?>
                <p>No complaints filed yet. <a href="file-complaint.php">File your first complaint</a></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_complaints as $complaint): ?>
                                <tr>
                                    <td>#<?php echo $complaint['id']; ?></td>
                                    <td><?php echo htmlspecialchars($complaint['title']); ?></td>
                                    <td><?php echo htmlspecialchars($complaint['category']); ?></td>
                                    <td>
                                        <span class="priority-badge priority-<?php echo strtolower($complaint['priority']); ?>">
                                            <?php echo $complaint['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = strtolower(str_replace(' ', '-', $complaint['status']));
                                        ?>
                                        <span class="status-badge status-<?php echo $status_class; ?>">
                                            <?php echo $complaint['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($complaint['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px;">
                    <a href="view-complaints.php" class="btn">View All Complaints</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Status Explanation -->
        <div class="card">
            <h2>Complaint Status Guide</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div style="padding: 15px; background: #f8f9fa; border-radius: 4px;">
                    <h3 style="color: #f39c12; margin-top: 0;">
                        <span class="status-badge status-pending">Pending</span>
                    </h3>
                    <p>Your complaint has been received and is waiting to be reviewed by the administration.</p>
                </div>
                <div style="padding: 15px; background: #f8f9fa; border-radius: 4px;">
                    <h3 style="color: #3498db; margin-top: 0;">
                        <span class="status-badge status-under-investigation">Under Investigation</span>
                    </h3>
                    <p>The administration is currently investigating your complaint. You will be updated on the progress.</p>
                </div>
                <div style="padding: 15px; background: #f8f9fa; border-radius: 4px;">
                    <h3 style="color: #27ae60; margin-top: 0;">
                        <span class="status-badge status-resolved">Resolved</span>
                    </h3>
                    <p>Your complaint has been resolved. Check the details for resolution information and admin notes.</p>
                </div>
            </div>
        </div>

        <!-- User Information -->
        <div class="card">
            <h2>Your Information</h2>
            <div class="profile-info">
                <div class="profile-field">
                    <label>Student ID:</label>
                    <span><?php echo htmlspecialchars($user_data['student_id']); ?></span>
                </div>
                <div class="profile-field">
                    <label>Full Name:</label>
                    <span><?php echo htmlspecialchars($user_data['name']); ?></span>
                </div>
                <div class="profile-field">
                    <label>Email:</label>
                    <span><?php echo htmlspecialchars($user_data['email']); ?></span>
                </div>
                <div class="profile-field">
                    <label>Contact Number:</label>
                    <span><?php echo htmlspecialchars($user_data['contact_number'] ?? 'Not provided'); ?></span>
                </div>
                <div class="profile-field">
                    <label>Member Since:</label>
                    <span><?php echo date('F j, Y', strtotime($user_data['created_at'])); ?></span>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> College Complaint Filing System. All rights reserved.</p>
        <p>For support, contact: <?php echo SITE_EMAIL; ?></p>
    </footer>
</body>
</html>