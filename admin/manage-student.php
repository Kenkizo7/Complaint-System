<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if ($_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Handle search and filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$college_filter = $_GET['college'] ?? '';
$course_filter = $_GET['course'] ?? '';

// Build query
$query = "SELECT student_id, name, email, college, course, year_level, status, 
                 created_at, last_login, login_count 
          FROM users 
          WHERE student_id != 'ADMIN001'";

$params = [];
$types = '';

if ($search) {
    $query .= " AND (student_id LIKE ? OR name LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $params = array_fill(0, 3, $search_term);
    $types = str_repeat('s', 3);
}

if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($college_filter) {
    $query .= " AND college = ?";
    $params[] = $college_filter;
    $types .= 's';
}

if ($course_filter) {
    $query .= " AND course LIKE ?";
    $params[] = "%$course_filter%";
    $types .= 's';
}

$query .= " ORDER BY created_at DESC";

// Get total count
$count_query = str_replace("SELECT student_id, name, email, college, course, year_level, status, created_at, last_login, login_count", 
                          "SELECT COUNT(*) as total", $query);
$stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_students = mysqli_fetch_assoc($count_result)['total'];

// Pagination
$page = $_GET['page'] ?? 1;
$per_page = 20;
$total_pages = ceil($total_students / $per_page);
$offset = ($page - 1) * $per_page;

$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

// Execute query
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

// Get unique colleges and courses for filters
$colleges_result = mysqli_query($conn, "SELECT DISTINCT college FROM users WHERE college IS NOT NULL AND college != '' ORDER BY college");
$colleges = [];
while ($row = mysqli_fetch_assoc($colleges_result)) {
    $colleges[] = $row['college'];
}

$courses_result = mysqli_query($conn, "SELECT DISTINCT course FROM users WHERE course IS NOT NULL AND course != '' ORDER BY course");
$courses = [];
while ($row = mysqli_fetch_assoc($courses_result)) {
    $courses[] = $row['course'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        <?php include 'admin-sidebar-styles.php'; ?>
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .student-details {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .export-options {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .export-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .export-pdf { background-color: #e74c3c; color: white; }
        .export-excel { background-color: #27ae60; color: white; }
        .export-word { background-color: #3498db; color: white; }
        
        .bulk-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .bulk-select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .bulk-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .quick-actions {
            display: flex;
            gap: 5px;
        }
        
        .action-small {
            padding: 4px 8px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'admin-sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <div class="page-title">
                    <h1><i class="fas fa-users"></i> Manage Students</h1>
                    <p>Total: <?php echo $total_students; ?> students registered</p>
                </div>
                <div class="header-actions">
                    <a href="student-registration.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add New Student
                    </a>
                    <div class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                </div>
            </header>
            
            <!-- Filters -->
            <div class="filter-section">
                <form method="GET" action="" class="filter-form">
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by name, ID, or email...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="college">College</label>
                        <select id="college" name="college">
                            <option value="">All Colleges</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?php echo $college; ?>" <?php echo $college_filter == $college ? 'selected' : ''; ?>>
                                    <?php echo $college; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="course">Course</label>
                        <select id="course" name="course">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course; ?>" <?php echo $course_filter == $course ? 'selected' : ''; ?>>
                                    <?php echo $course; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="manage-students.php" class="btn">
                            <i class="fas fa-redo"></i> Clear
                        </a>
                    </div>
                </form>
                
                <div class="export-options">
                    <button class="export-btn export-pdf" onclick="exportStudentsPDF()">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="export-btn export-excel" onclick="exportStudentsExcel()">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                    <button class="export-btn export-word" onclick="exportStudentsWord()">
                        <i class="fas fa-file-word"></i> Export Word
                    </button>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <select class="bulk-select" id="bulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate Selected</option>
                    <option value="deactivate">Deactivate Selected</option>
                    <option value="suspend">Suspend Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button class="bulk-btn btn-primary" onclick="applyStudentBulkAction()">
                    <i class="fas fa-check"></i> Apply
                </button>
            </div>
            
            <!-- Students Table -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllStudents"></th>
                                <th>Student</th>
                                <th>Student ID</th>
                                <th>Email</th>
                                <th>College</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-user-slash" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                                        <p>No students found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><input type="checkbox" class="student-checkbox" value="<?php echo $student['student_id']; ?>"></td>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-avatar">
                                                    <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                                                    <div class="student-details">
                                                        Joined: <?php echo date('M d, Y', strtotime($student['created_at'])); ?>
                                                        <?php if ($student['login_count'] > 0): ?>
                                                            | Logins: <?php echo $student['login_count']; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['college'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $student['status']; ?>">
                                                <?php echo ucfirst($student['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($student['last_login']): ?>
                                                <?php echo date('M d, Y', strtotime($student['last_login'])); ?><br>
                                                <small><?php echo date('h:i A', strtotime($student['last_login'])); ?></small>
                                            <?php else: ?>
                                                Never
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="quick-actions">
                                                <button class="action-btn btn-view action-small" onclick="viewStudent('<?php echo $student['student_id']; ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn btn-edit action-small" onclick="editStudent('<?php echo $student['student_id']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn btn-delete action-small" onclick="deleteStudent('<?php echo $student['student_id']; ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php if ($student['status'] == 'active'): ?>
                                                    <button class="action-btn btn-suspend action-small" onclick="updateStudentStatus('<?php echo $student['student_id']; ?>', 'suspend')">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php elseif ($student['status'] == 'suspended'): ?>
                                                    <button class="action-btn btn-activate action-small" onclick="updateStudentStatus('<?php echo $student['student_id']; ?>', 'activate')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="action-btn btn-activate action-small" onclick="updateStudentStatus('<?php echo $student['student_id']; ?>', 'activate')">
                                                        <i class="fas fa-power-off"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&college=<?php echo urlencode($college_filter); ?>&course=<?php echo urlencode($course_filter); ?>" 
                               class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Student Details Modal -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <h3>Student Details</h3>
            <div id="studentDetails"></div>
        </div>
    </div>
    
    <script>
    // Mobile menu toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // Select all students
    document.getElementById('selectAllStudents').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    // Bulk actions for students
    function applyStudentBulkAction() {
        const action = document.getElementById('bulkAction').value;
        const selected = Array.from(document.querySelectorAll('.student-checkbox:checked'))
                            .map(cb => cb.value);
        
        if (selected.length === 0) {
            alert('Please select at least one student');
            return;
        }
        
        if (action === 'delete') {
            if (confirm(`Are you sure you want to delete ${selected.length} student(s)? This action cannot be undone.`)) {
                fetch(`ajax/delete-students.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        students: selected
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Students deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        } else {
            if (confirm(`Are you sure you want to ${action} ${selected.length} student(s)?`)) {
                fetch(`ajax/update-students-status.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        students: selected,
                        action: action
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Students ${action}d successfully`);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    }
    
    // Export functions
    function exportStudentsPDF() {
        alert('PDF export feature will be implemented');
    }
    
    function exportStudentsExcel() {
        window.location.href = `export-students.php?format=excel&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&college=<?php echo urlencode($college_filter); ?>&course=<?php echo urlencode($course_filter); ?>`;
    }
    
    function exportStudentsWord() {
        alert('Word export feature will be implemented');
    }
    
    // View student details
    function viewStudent(studentId) {
        fetch(`ajax/get-student-details.php?id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                const modal = document.getElementById('studentModal');
                const details = document.getElementById('studentDetails');
                
                details.innerHTML = `
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: #3498db; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: bold;">
                                ${data.name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <h4 style="margin: 0 0 5px 0;">${data.name}</h4>
                                <p style="margin: 0; color: #7f8c8d;">${data.student_id}</p>
                                <p style="margin: 0; color: #7f8c8d;">${data.email}</p>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <strong>College:</strong>
                                <p>${data.college || 'N/A'}</p>
                            </div>
                            <div>
                                <strong>Course:</strong>
                                <p>${data.course || 'N/A'}</p>
                            </div>
                            <div>
                                <strong>Year Level:</strong>
                                <p>${data.year_level || 'N/A'}</p>
                            </div>
                            <div>
                                <strong>Status:</strong>
                                <span class="status-badge status-${data.status}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span>
                            </div>
                            <div>
                                <strong>Contact:</strong>
                                <p>${data.contact_number || 'N/A'}</p>
                            </div>
                            <div>
                                <strong>Joined:</strong>
                                <p>${new Date(data.created_at).toLocaleDateString()}</p>
                            </div>
                        </div>
                        
                        ${data.address ? `
                        <div style="margin-top: 15px;">
                            <strong>Address:</strong>
                            <p>${data.address}</p>
                        </div>
                        ` : ''}
                        
                        <div style="margin-top: 15px;">
                            <strong>Login Activity:</strong>
                            <p>Last Login: ${data.last_login ? new Date(data.last_login).toLocaleString() : 'Never'}</p>
                            <p>Total Logins: ${data.login_count || 0}</p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button class="btn btn-primary" onclick="editStudent('${data.student_id}')">
                            <i class="fas fa-edit"></i> Edit Student
                        </button>
                        <button class="btn" onclick="closeStudentModal()">
                            Close
                        </button>
                    </div>
                `;
                
                modal.style.display = 'block';
            })
            .catch(error => {
                alert('Error loading student details');
            });
    }
    
    function closeStudentModal() {
        document.getElementById('studentModal').style.display = 'none';
    }
    
    // Edit student
    function editStudent(studentId) {
        window.location.href = `edit-student.php?id=${studentId}`;
    }
    
    // Update student status
    function updateStudentStatus(studentId, action) {
        const actions = {
            'activate': 'activate',
            'deactivate': 'deactivate',
            'suspend': 'suspend'
        };
        
        const actionText = actions[action];
        if (confirm(`Are you sure you want to ${actionText} this student?`)) {
            fetch(`ajax/update-student-status.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    student_id: studentId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Student ${actionText}ed successfully`);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error updating student status');
            });
        }
    }
    
    // Delete student
    function deleteStudent(studentId) {
        if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
            fetch(`ajax/delete-student.php?id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Student deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error deleting student');
                });
        }
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('studentModal');
        if (event.target === modal) {
            closeStudentModal();
        }
    };
    </script>
</body>
</html>