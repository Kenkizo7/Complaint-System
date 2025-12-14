<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if database exists
$dbExists = checkDatabaseExists($conn, DB_NAME);

if (!$dbExists) {
    $_SESSION['db_error'] = "Database not found. Please run setup first.";
    header('Location: login.php');
    exit();
}

// Check if tables exist
$missingTables = checkTablesExist($conn);
if (!empty($missingTables)) {
    $_SESSION['db_error'] = "Database tables missing. Please run setup.";
    header('Location: login.php');
    exit();
}

// Now require login
requireLogin();

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
    <!-- FIXED CSS PATH -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Additional styles to ensure everything displays properly */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stat-card p {
            color: #7f8c8d;
            font-weight: 600;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th {
            background-color: #2c3e50;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        table tr:hover {
            background-color: #f5f5f5;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .btn-primary {
            background-color: #3498db;
        }
        
        .btn-success {
            background-color: #27ae60;
        }
        
        .btn-warning {
            background-color: #f39c12;
        }
        
        .btn-danger {
            background-color: #e74c3c;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            min-width: 120px;
            text-align: center;
        }
        
        .status-pending {
            background-color: #f39c12;
            color: white;
            border: 1px solid #e67e22;
        }
        
        .status-under-investigation {
            background-color: #3498db;
            color: white;
            border: 1px solid #2980b9;
        }
        
        .status-resolved {
            background-color: #27ae60;
            color: white;
            border: 1px solid #219653;
        }
        
        .priority-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .priority-low {
            background-color: #27ae60;
            color: white;
        }
        
        .priority-medium {
            background-color: #f39c12;
            color: white;
        }
        
        .priority-high {
            background-color: #e74c3c;
            color: white;
        }
        
        .priority-urgent {
            background-color: #9b59b6;
            color: white;
        }
        
        .profile-info {
            margin-top: 20px;
        }
        
        .profile-field {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .profile-field:last-child {
            border-bottom: none;
        }
        
        .profile-field label {
            font-weight: 600;
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }
        
        .profile-field span {
            display: block;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        /* Header styles */
        header {
            background: linear-gradient(135deg, #2c3e50, #4a6491);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            font-size: 24px;
        }
        
        .logo h1 {
            font-size: 1.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            list-style: none;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .nav-links a.active {
            background-color: rgba(255,255,255,0.2);
        }
        
        /* Footer styles */
        footer {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        footer p {
            margin: 5px 0;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .nav-links {
                flex-direction: column;
                gap: 10px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            nav {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .container {
                padding: 0 15px;
            }
        }
    </style>
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