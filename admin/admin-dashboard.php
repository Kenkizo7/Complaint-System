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

// Get admin stats
$admin_id = $_SESSION['user_id'];

// Dashboard statistics
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM users WHERE student_id != 'ADMIN001') as total_students,
    (SELECT COUNT(*) FROM complaints) as total_complaints,
    (SELECT COUNT(*) FROM complaints WHERE status = 'Pending') as pending_complaints,
    (SELECT COUNT(*) FROM complaints WHERE status = 'Under Investigation') as investigating_complaints,
    (SELECT COUNT(*) FROM complaints WHERE status = 'Resolved') as resolved_complaints,
    (SELECT COUNT(*) FROM users WHERE status = 'active' AND student_id != 'ADMIN001') as active_students,
    (SELECT COUNT(*) FROM users WHERE status = 'inactive' AND student_id != 'ADMIN001') as inactive_students,
    (SELECT COUNT(*) FROM users WHERE status = 'suspended' AND student_id != 'ADMIN001') as suspended_students";

$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent complaints
$recent_complaints_sql = "SELECT c.*, u.name as complainant_name, u.student_id 
                         FROM complaints c 
                         JOIN users u ON c.user_id = u.id 
                         ORDER BY c.created_at DESC LIMIT 10";
$recent_complaints_result = mysqli_query($conn, $recent_complaints_sql);
$recent_complaints = [];
while ($row = mysqli_fetch_assoc($recent_complaints_result)) {
    $recent_complaints[] = $row;
}

// Get recent students
$recent_students_sql = "SELECT student_id, name, email, college, created_at, status 
                       FROM users WHERE student_id != 'ADMIN001' 
                       ORDER BY created_at DESC LIMIT 10";
$recent_students_result = mysqli_query($conn, $recent_students_sql);
$recent_students = [];
while ($row = mysqli_fetch_assoc($recent_students_result)) {
    $recent_students[] = $row;
}

// Get complaints by college
$college_stats_sql = "SELECT u.college, COUNT(c.id) as complaint_count 
                     FROM complaints c 
                     JOIN users u ON c.user_id = u.id 
                     WHERE u.college IS NOT NULL 
                     GROUP BY u.college 
                     ORDER BY complaint_count DESC 
                     LIMIT 10";
$college_stats_result = mysqli_query($conn, $college_stats_sql);
$college_stats = [];
while ($row = mysqli_fetch_assoc($college_stats_result)) {
    $college_stats[] = $row;
}

// Get complaint status distribution
$status_distribution_sql = "SELECT status, COUNT(*) as count 
                           FROM complaints 
                           GROUP BY status 
                           ORDER BY FIELD(status, 'Pending', 'Under Investigation', 'Resolved')";
$status_distribution_result = mysqli_query($conn, $status_distribution_sql);
$status_distribution = [];
while ($row = mysqli_fetch_assoc($status_distribution_result)) {
    $status_distribution[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - College Complaint System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-university"></i> Admin Panel</h2>
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-info">
                        <h4><?php echo htmlspecialchars($_SESSION['name']); ?></h4>
                        <span>Administrator</span>
                    </div>
                </div>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-section">Main</div>
                <a href="admin-dashboard.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                
                <div class="menu-section">Management</div>
                <a href="manage-complaints.php" class="menu-item">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Manage Complaints</span>
                    <span class="menu-badge"><?php echo $stats['pending_complaints']; ?></span>
                </a>
                <a href="manage-students.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Manage Students</span>
                    <span class="menu-badge"><?php echo $stats['total_students']; ?></span>
                </a>
                <a href="student-registration.php" class="menu-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Student Registration</span>
                </a>
                
                <div class="menu-section">Reports</div>
                <a href="reports.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports & Analytics</span>
                </a>
                
                <div class="menu-section">System</div>
                <a href="admin-profiles.php" class="menu-item">
                    <i class="fas fa-user-cog"></i>
                    <span>Admin Profiles</span>
                </a>
                <a href="system-logs.php" class="menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>System Logs</span>
                </a>
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                
                <div class="menu-section">Account</div>
                <a href="admin-profile.php" class="menu-item">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="page-title">
                    <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
                </div>
                
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="globalSearch" placeholder="Search anything...">
                    </div>
                    
                    <div class="notification-bell">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count">3</span>
                    </div>
                    
                    <div class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Stats -->
            <div class="dashboard-cards">
                <div class="stat-card complaints">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $stats['total_complaints']; ?></div>
                            <div class="stat-label">Total Complaints</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>12% from last month</span>
                    </div>
                </div>
                
                <div class="stat-card students">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>5 new this month</span>
                    </div>
                </div>
                
                <div class="stat-card pending">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $stats['pending_complaints']; ?></div>
                            <div class="stat-label">Pending Complaints</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-up"></i>
                        <span>Needs attention</span>
                    </div>
                </div>
                
                <div class="stat-card resolved">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $stats['resolved_complaints']; ?></div>
                            <div class="stat-label">Resolved Complaints</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>85% resolution rate</span>
                    </div>
                </div>
                
                <div class="stat-card inactive">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $stats['inactive_students']; ?></div>
                            <div class="stat-label">Inactive Students</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-user-slash"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card suspended">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $stats['suspended_students']; ?></div>
                            <div class="stat-label">Suspended Students</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Complaint Status Distribution</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="complaintStatusChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Complaints by College</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="collegeComplaintsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Tables Section -->
            <div class="tables-section">
                <div class="table-card">
                    <div class="table-header">
                        <h3>Recent Complaints</h3>
                        <a href="manage-complaints.php" class="view-all">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Student</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_complaints as $complaint): ?>
                                    <tr>
                                        <td>#<?php echo $complaint['id']; ?></td>
                                        <td><?php echo htmlspecialchars(substr($complaint['title'], 0, 30)) . '...'; ?></td>
                                        <td><?php echo htmlspecialchars($complaint['complainant_name']); ?></td>
                                        <td>
                                            <?php 
                                            $status_class = strtolower(str_replace(' ', '-', $complaint['status']));
                                            ?>
                                            <span class="status-badge status-<?php echo $status_class; ?>">
                                                <?php echo $complaint['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn btn-view" onclick="viewComplaint(<?php echo $complaint['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn btn-edit" onclick="editComplaint(<?php echo $complaint['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="table-card">
                    <div class="table-header">
                        <h3>Recent Students</h3>
                        <a href="manage-students.php" class="view-all">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>College</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['college'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $student['status']; ?>">
                                                <?php echo ucfirst($student['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn btn-view" onclick="viewStudent('<?php echo $student['student_id']; ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn btn-edit" onclick="editStudent('<?php echo $student['student_id']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($student['status'] == 'active'): ?>
                                                    <button class="action-btn btn-suspend" onclick="suspendStudent('<?php echo $student['student_id']; ?>')">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="action-btn btn-activate" onclick="activateStudent('<?php echo $student['student_id']; ?>')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h4>Register Student</h4>
                    <p>Add new student to the system</p>
                    <a href="student-registration.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Student
                    </a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <h4>Generate Report</h4>
                    <p>Create comprehensive reports</p>
                    <a href="generate-reports.php" class="btn btn-success">
                        <i class="fas fa-download"></i> Generate
                    </a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h4>System Settings</h4>
                    <p>Configure system parameters</p>
                    <a href="settings.php" class="btn btn-warning">
                        <i class="fas fa-sliders-h"></i> Settings
                    </a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>View Analytics</h4>
                    <p>Detailed system analytics</p>
                    <a href="reports.php" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </a>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Mobile menu toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');
        
        if (window.innerWidth <= 992 && 
            !sidebar.contains(event.target) && 
            !menuToggle.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    });
    
    // Global search functionality
    document.getElementById('globalSearch').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        // Implement search logic here
        console.log('Searching for:', searchTerm);
    });
    
    // Charts
    const complaintStatusCtx = document.getElementById('complaintStatusChart').getContext('2d');
    const collegeComplaintsCtx = document.getElementById('collegeComplaintsChart').getContext('2d');
    
    // Complaint Status Chart
    const complaintStatusChart = new Chart(complaintStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Under Investigation', 'Resolved'],
            datasets: [{
                data: [
                    <?php echo $stats['pending_complaints']; ?>,
                    <?php echo $stats['investigating_complaints']; ?>,
                    <?php echo $stats['resolved_complaints']; ?>
                ],
                backgroundColor: [
                    '#f39c12',
                    '#3498db',
                    '#27ae60'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed + ' complaints';
                            return label;
                        }
                    }
                }
            }
        }
    });
    
    // College Complaints Chart
    const collegeComplaintsChart = new Chart(collegeComplaintsCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($college_stats, 'college')); ?>,
            datasets: [{
                label: 'Complaints',
                data: <?php echo json_encode(array_column($college_stats, 'complaint_count')); ?>,
                backgroundColor: '#3498db',
                borderColor: '#2980b9',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Complaints'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'College'
                    }
                }
            }
        }
    });
    
    // Action functions
    function viewComplaint(id) {
        window.location.href = `view-complaint.php?id=${id}`;
    }
    
    function editComplaint(id) {
        window.location.href = `edit-complaint.php?id=${id}`;
    }
    
    function viewStudent(studentId) {
        window.location.href = `view-student.php?id=${studentId}`;
    }
    
    function editStudent(studentId) {
        window.location.href = `edit-student.php?id=${studentId}`;
    }
    
    function suspendStudent(studentId) {
        if (confirm('Are you sure you want to suspend this student?')) {
            fetch(`ajax/suspend-student.php?id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Student suspended successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }
    }
    
    function activateStudent(studentId) {
        if (confirm('Are you sure you want to activate this student?')) {
            fetch(`ajax/activate-student.php?id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Student activated successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }
    }
    
    // Auto-refresh dashboard every 5 minutes
    setInterval(() => {
        // You can implement partial refresh here
        console.log('Auto-refreshing dashboard...');
    }, 300000);
    </script>
</body>
</html>