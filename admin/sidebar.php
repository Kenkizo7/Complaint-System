<?php
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
?>
<div class="admin-sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-university"></i> Complaint System</h3>
        <div class="admin-info">
            <div class="admin-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="admin-details">
                <h4><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></h4>
                <span class="admin-role">Administrator</span>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="admin-dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin-dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="manage-complaints.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-complaints.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Manage Complaints</span>
                </a>
            </li>
            <li>
                <a href="manage-student.php">
                    <i class="fas fa-student"></i>
                    <span>Manage Student</span>
                </a>
            </li>
            <li>
                <a href="categories.php">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports & Analytics</span>
                </a>
            </li>
            <li>
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-divider"></li>
            <li>
                <a href="../includes/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="system-status">
            <div class="status-indicator online"></div>
            <span>System Online</span>
        </div>
        <div class="version">
            v2.1.0
        </div>
    </div>
</div>