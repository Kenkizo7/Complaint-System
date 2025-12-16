<?php
// Go up one level to include config files
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Check if user is admin
// Using 'user_role' instead of 'role' based on common table structures
if ($_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_admin'])) {
        // Add new admin
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user_role = 'admin'; // Changed variable name
        $status = 'active';
        $permissions = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : '[]';
        
        // Generate unique admin ID
        $admin_id = 'ADMIN' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Check which column exists in your table - you may need to adjust this
        $sql = "INSERT INTO users (name, email, username, password, user_role, status, student_id, permissions, created_at) 
                VALUES ('$name', '$email', '$username', '$password', '$user_role', '$status', '$admin_id', '$permissions', NOW())";
        
        if (mysqli_query($conn, $sql)) {
            logActivity("Added new admin: $name");
            $success_msg = "Admin added successfully!";
        } else {
            $error_msg = "Error adding admin: " . mysqli_error($conn);
        }
    }
    
    elseif (isset($_POST['update_admin'])) {
        // Update admin
        $admin_id = (int)$_POST['admin_id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $permissions = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : '[]';
        
        // Check the correct column name for role in your database
        $sql = "UPDATE users SET 
                name = '$name',
                email = '$email',
                username = '$username',
                status = '$status',
                permissions = '$permissions',
                updated_at = NOW()
                WHERE id = $admin_id AND user_role = 'admin'";
        
        if (mysqli_query($conn, $sql)) {
            logActivity("Updated admin profile ID: $admin_id");
            $success_msg = "Admin updated successfully!";
        } else {
            $error_msg = "Error updating admin: " . mysqli_error($conn);
        }
    }
    
    elseif (isset($_POST['reset_password'])) {
        // Reset admin password
        $admin_id = (int)$_POST['admin_id'];
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET 
                password = '$new_password',
                password_changed_at = NOW(),
                updated_at = NOW()
                WHERE id = $admin_id AND user_role = 'admin'";
        
        if (mysqli_query($conn, $sql)) {
            logActivity("Reset password for admin ID: $admin_id");
            $success_msg = "Password reset successfully!";
        } else {
            $error_msg = "Error resetting password: " . mysqli_error($conn);
        }
    }
    
    elseif (isset($_POST['delete_admin'])) {
        // Delete admin (soft delete)
        $admin_id = (int)$_POST['admin_id'];
        
        // Don't allow deleting own account
        if ($admin_id == $_SESSION['user_id']) {
            $error_msg = "You cannot delete your own account!";
        } else {
            $sql = "UPDATE users SET 
                    status = 'deleted',
                    deleted_at = NOW(),
                    updated_at = NOW()
                    WHERE id = $admin_id AND user_role = 'admin'";
            
            if (mysqli_query($conn, $sql)) {
                logActivity("Deleted admin profile ID: $admin_id");
                $success_msg = "Admin deleted successfully!";
            } else {
                $error_msg = "Error deleting admin: " . mysqli_error($conn);
            }
        }
    }
}

// Debug: Check what columns exist in users table
// Uncomment this for debugging:
/*
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM users");
echo "Columns in users table: <br>";
while ($col = mysqli_fetch_assoc($check_columns)) {
    echo $col['Field'] . "<br>";
}
*/

// Fetch all admins
// IMPORTANT: Check the actual column name for role in your database
// Common column names: 'role', 'user_role', 'user_type', 'type'
$sql = "SELECT * FROM users WHERE user_role = 'admin' AND status != 'deleted' ORDER BY created_at DESC";
$admins_result = mysqli_query($conn, $sql);

if (!$admins_result) {
    // Show SQL error for debugging
    die("SQL Error: " . mysqli_error($conn) . "<br>SQL: " . $sql);
}

$admins = [];
while ($row = mysqli_fetch_assoc($admins_result)) {
    $row['permissions'] = json_decode($row['permissions'] ?? '[]', true);
    $admins[] = $row;
}

// Activity logs for admins
$activity_sql = "SELECT al.*, u.name as admin_name 
                 FROM activity_logs al 
                 JOIN users u ON al.user_id = u.id 
                 WHERE (u.role = 'admin' OR u.user_role = 'admin') 
                 ORDER BY al.created_at DESC 
                 LIMIT 50";
$activity_result = mysqli_query($conn, $activity_sql);
$activities = [];
if ($activity_result) {
    while ($row = mysqli_fetch_assoc($activity_result)) {
        $activities[] = $row;
    }
} else {
    // Log error but don't break the page
    error_log("Activity logs query error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profiles - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .admin-profiles-container {
        display: flex;
        gap: 20px;
        margin-top: 20px;
    }
    
    .admins-list {
        width: 300px;
        flex-shrink: 0;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
    }
    
    .admins-list h3 {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .admin-item {
        display: flex;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .admin-item:hover {
        background: #f8f9fa;
    }
    
    .admin-item.active {
        background: #e3f2fd;
        border-left: 4px solid #3498db;
    }
    
    .admin-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #3498db;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 18px;
    }
    
    .admin-info h4 {
        margin: 0;
        font-size: 14px;
    }
    
    .admin-info .status {
        font-size: 12px;
        color: #666;
    }
    
    .admin-details {
        flex: 1;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 30px;
    }
    
    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-top: 20px;
    }
    
    .permission-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #3498db;
    }
    
    .permission-item label {
        display: flex;
        align-items: center;
        cursor: pointer;
        font-weight: 500;
    }
    
    .permission-item input[type="checkbox"] {
        margin-right: 10px;
    }
    
    .activity-log {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 30px;
        margin-top: 30px;
    }
    
    .activity-item {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e3f2fd;
        color: #3498db;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-content h5 {
        margin: 0 0 5px 0;
        font-size: 14px;
    }
    
    .activity-content p {
        margin: 0;
        color: #666;
        font-size: 13px;
    }
    
    .activity-time {
        font-size: 12px;
        color: #999;
    }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    .modal.active {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }
    </style>
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
                    <h1><i class="fas fa-user-cog"></i> Admin Profiles</h1>
                    <p>Manage administrator accounts and permissions</p>
                </div>
                
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openAddAdminModal()">
                        <i class="fas fa-user-plus"></i> Add New Admin
                    </button>
                    <div class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                </div>
            </header>
            
            <!-- Messages -->
            <?php if ($success_msg): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-profiles-container">
                <!-- Admins List -->
                <div class="admins-list">
                    <h3>Administrators</h3>
                    <?php if (empty($admins)): ?>
                        <p class="text-muted">No administrators found</p>
                    <?php else: ?>
                        <?php foreach ($admins as $admin): ?>
                            <div class="admin-item <?php echo $admin['id'] == $_SESSION['user_id'] ? 'active' : ''; ?>" 
                                 onclick="selectAdmin(<?php echo $admin['id']; ?>)">
                                <div class="admin-avatar">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="admin-info">
                                    <h4><?php echo htmlspecialchars($admin['name']); ?></h4>
                                    <div class="status">
                                        <?php echo ucfirst($admin['status'] ?? 'active'); ?>
                                        <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                            <span class="text-primary">(You)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Admin Details -->
                <div class="admin-details" id="adminDetails">
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-user-shield fa-3x text-muted"></i>
                        </div>
                        <h4>Select an administrator to view details</h4>
                        <p class="text-muted">Click on any admin from the list to view and edit their profile</p>
                    </div>
                </div>
            </div>
            
            <!-- Activity Log -->
            <div class="activity-log">
                <h3><i class="fas fa-history"></i> Recent Admin Activity</h3>
                <div class="mt-4">
                    <?php if (empty($activities)): ?>
                        <p class="text-center text-muted py-3">No recent activity found</p>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <h5><?php echo htmlspecialchars($activity['admin_name']); ?></h5>
                                    <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                </div>
                                <div class="activity-time">
                                    <?php echo time_elapsed_string($activity['created_at']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Admin Modal -->
    <div class="modal" id="addAdminModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Add New Administrator</h3>
                <button class="modal-close" onclick="closeModal('addAdminModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addAdminForm" method="POST">
                    <div class="settings-form-group">
                        <label for="new_name">Full Name</label>
                        <input type="text" id="new_name" name="name" required>
                    </div>
                    
                    <div class="settings-form-group">
                        <label for="new_email">Email Address</label>
                        <input type="email" id="new_email" name="email" required>
                    </div>
                    
                    <div class="settings-form-group">
                        <label for="new_username">Username</label>
                        <input type="text" id="new_username" name="username" required>
                    </div>
                    
                    <div class="settings-form-group">
                        <label for="new_password">Password</label>
                        <input type="password" id="new_password" name="password" required>
                    </div>
                    
                    <div class="settings-form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="permissions-grid">
                        <div class="permission-item">
                            <label>
                                <input type="checkbox" name="permissions[]" value="manage_complaints">
                                Manage Complaints
                            </label>
                        </div>
                        <div class="permission-item">
                            <label>
                                <input type="checkbox" name="permissions[]" value="manage_students">
                                Manage Students
                            </label>
                        </div>
                        <div class="permission-item">
                            <label>
                                <input type="checkbox" name="permissions[]" value="manage_admins">
                                Manage Admins
                            </label>
                        </div>
                        <div class="permission-item">
                            <label>
                                <input type="checkbox" name="permissions[]" value="view_reports">
                                View Reports
                            </label>
                        </div>
                        <div class="permission-item">
                            <label>
                                <input type="checkbox" name="permissions[]" value="system_settings">
                                System Settings
                            </label>
                        </div>
                        <div class="permission-item">
                            <label>
                                <input type="checkbox" name="permissions[]" value="manage_categories">
                                Manage Categories
                            </label>
                        </div>
                    </div>
                    
                    <div class="text-right mt-4">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addAdminModal')">Cancel</button>
                        <button type="submit" name="add_admin" class="btn btn-primary">Add Administrator</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Reset Password Modal -->
    <div class="modal" id="resetPasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Reset Password</h3>
                <button class="modal-close" onclick="closeModal('resetPasswordModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="resetPasswordForm" method="POST">
                    <input type="hidden" id="reset_admin_id" name="admin_id">
                    
                    <div class="settings-form-group">
                        <label for="new_password_reset">New Password</label>
                        <input type="password" id="new_password_reset" name="new_password" required>
                    </div>
                    
                    <div class="settings-form-group">
                        <label for="confirm_password_reset">Confirm Password</label>
                        <input type="password" id="confirm_password_reset" name="confirm_password_reset" required>
                    </div>
                    
                    <div class="text-right mt-4">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('resetPasswordModal')">Cancel</button>
                        <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Mobile menu toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    let selectedAdminId = null;
    
    // Select admin
    function selectAdmin(adminId) {
        selectedAdminId = adminId;
        
        // Update active state in list
        document.querySelectorAll('.admin-item').forEach(item => {
            item.classList.remove('active');
        });
        event.currentTarget.classList.add('active');
        
        // Load admin details via AJAX
        fetch(`ajax/get-admin-details.php?id=${adminId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderAdminDetails(data.admin);
                } else {
                    alert('Error loading admin details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading admin details. Please check console for details.');
            });
    }
    
    // Render admin details
    function renderAdminDetails(admin) {
        const container = document.getElementById('adminDetails');
        
        // Generate permissions HTML
        let permissionsHtml = '';
        const allPermissions = [
            { id: 'manage_complaints', label: 'Manage Complaints' },
            { id: 'manage_students', label: 'Manage Students' },
            { id: 'manage_admins', label: 'Manage Admins' },
            { id: 'view_reports', label: 'View Reports' },
            { id: 'system_settings', label: 'System Settings' },
            { id: 'manage_categories', label: 'Manage Categories' },
            { id: 'view_logs', label: 'View System Logs' },
            { id: 'export_data', label: 'Export Data' },
            { id: 'backup_restore', label: 'Backup & Restore' }
        ];
        
        allPermissions.forEach(perm => {
            const isChecked = admin.permissions && admin.permissions.includes(perm.id) ? 'checked' : '';
            permissionsHtml += `
                <div class="permission-item">
                    <label>
                        <input type="checkbox" name="permissions[]" value="${perm.id}" ${isChecked}>
                        ${perm.label}
                    </label>
                </div>
            `;
        });
        
        container.innerHTML = `
            <h3>Admin Profile</h3>
            
            <form id="editAdminForm" method="POST">
                <input type="hidden" name="admin_id" value="${admin.id}">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="settings-form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" value="${admin.name}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="settings-form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="${admin.email}" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="settings-form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="${admin.username}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="settings-form-group">
                            <label>Status</label>
                            <select name="status" required>
                                <option value="active" ${admin.status === 'active' ? 'selected' : ''}>Active</option>
                                <option value="inactive" ${admin.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                <option value="suspended" ${admin.status === 'suspended' ? 'selected' : ''}>Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="settings-form-group">
                    <label>Permissions</label>
                    <div class="permissions-grid">
                        ${permissionsHtml}
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="settings-form-group">
                            <label>Created At</label>
                            <input type="text" value="${admin.created_at}" readonly style="background: #f8f9fa;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="settings-form-group">
                            <label>Last Login</label>
                            <input type="text" value="${admin.last_login || 'Never'}" readonly style="background: #f8f9fa;">
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons mt-4">
                    <button type="submit" name="update_admin" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    
                    <button type="button" class="btn btn-warning" onclick="openResetPasswordModal(${admin.id})">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                    
                    ${admin.id != <?php echo $_SESSION['user_id']; ?> ? `
                        <button type="button" class="btn btn-danger" onclick="deleteAdmin(${admin.id})">
                            <i class="fas fa-trash"></i> Delete Admin
                        </button>
                    ` : ''}
                </div>
            </form>
        `;
    }
    
    // Modal functions
    function openAddAdminModal() {
        document.getElementById('addAdminModal').classList.add('active');
    }
    
    function openResetPasswordModal(adminId) {
        document.getElementById('reset_admin_id').value = adminId;
        document.getElementById('resetPasswordModal').classList.add('active');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    // Delete admin
    function deleteAdmin(adminId) {
        if (confirm('Are you sure you want to delete this administrator?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="admin_id" value="${adminId}">
                <input type="hidden" name="delete_admin" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Form validation
    document.getElementById('addAdminForm')?.addEventListener('submit', function(e) {
        const password = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
            return false;
        }
    });
    
    document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
        const password = document.getElementById('new_password_reset').value;
        const confirmPassword = document.getElementById('confirm_password_reset').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
            return false;
        }
    });
    
    // Close modals on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
    
    // Close modals when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
    </script>
</body>
</html>