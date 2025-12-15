<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

if ($_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Handle search and filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "SELECT c.*, u.name as complainant_name, u.student_id, u.college 
          FROM complaints c 
          JOIN users u ON c.user_id = u.id 
          WHERE 1=1";

$params = [];
$types = '';

if ($search) {
    $query .= " AND (c.title LIKE ? OR c.description LIKE ? OR u.name LIKE ? OR u.student_id LIKE ?)";
    $search_term = "%$search%";
    $params = array_fill(0, 4, $search_term);
    $types = str_repeat('s', 4);
}

if ($status_filter) {
    $query .= " AND c.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($category_filter) {
    $query .= " AND c.category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

if ($date_from) {
    $query .= " AND DATE(c.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $query .= " AND DATE(c.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$query .= " ORDER BY c.created_at DESC";

// Get total count for pagination
$count_query = str_replace("SELECT c.*, u.name as complainant_name, u.student_id, u.college", "SELECT COUNT(*) as total", $query);
$stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_complaints = mysqli_fetch_assoc($count_result)['total'];

// Pagination
$page = $_GET['page'] ?? 1;
$per_page = 20;
$total_pages = ceil($total_complaints / $per_page);
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

$complaints = [];
while ($row = mysqli_fetch_assoc($result)) {
    $complaints[] = $row;
}

// Get unique categories for filter
$categories_result = mysqli_query($conn, "SELECT DISTINCT category FROM complaints ORDER BY category");
$categories = [];
while ($row = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $row['category'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include 'admin-sidebar-styles.php'; ?>
    <style>
        /* Use the same sidebar styles from admin-dashboard.php */
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
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
        
        .export-pdf {
            background-color: #e74c3c;
            color: white;
        }
        
        .export-excel {
            background-color: #27ae60;
            color: white;
        }
        
        .export-word {
            background-color: #3498db;
            color: white;
        }
        
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
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .page-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #3498db;
        }
        
        .page-link.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .page-link:hover {
            background-color: #f8f9fa;
        }
        
        .quick-status {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .status-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-pending { background-color: #fff3cd; color: #856404; }
        .btn-investigation { background-color: #cce5ff; color: #004085; }
        .btn-resolved { background-color: #d4edda; color: #155724; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <div class="page-title">
                    <h1><i class="fas fa-exclamation-circle"></i> Manage Complaints</h1>
                    <p>Total: <?php echo $total_complaints; ?> complaints found</p>
                </div>
                <div class="header-actions">
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
                               placeholder="Search by title, student, or ID...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All Status</option>
                            <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Under Investigation" <?php echo $status_filter == 'Under Investigation' ? 'selected' : ''; ?>>Under Investigation</option>
                            <option value="Resolved" <?php echo $status_filter == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category; ?>" <?php echo $category_filter == $category ? 'selected' : ''; ?>>
                                    <?php echo $category; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="manage-complaints.php" class="btn">
                            <i class="fas fa-redo"></i> Clear
                        </a>
                    </div>
                </form>
                
                <div class="export-options">
                    <button class="export-btn export-pdf" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="export-btn export-excel" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                    <button class="export-btn export-word" onclick="exportToWord()">
                        <i class="fas fa-file-word"></i> Export Word
                    </button>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <select class="bulk-select" id="bulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="pending">Mark as Pending</option>
                    <option value="investigation">Mark as Under Investigation</option>
                    <option value="resolved">Mark as Resolved</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button class="bulk-btn btn-primary" onclick="applyBulkAction()">
                    <i class="fas fa-check"></i> Apply
                </button>
            </div>
            
            <!-- Complaints Table -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Student</th>
                                <th>College</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($complaints)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                                        <p>No complaints found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($complaints as $complaint): ?>
                                    <tr>
                                        <td><input type="checkbox" class="complaint-checkbox" value="<?php echo $complaint['id']; ?>"></td>
                                        <td>#<?php echo $complaint['id']; ?></td>
                                        <td><?php echo htmlspecialchars(substr($complaint['title'], 0, 40)) . '...'; ?></td>
                                        <td><?php echo htmlspecialchars($complaint['complainant_name']); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['college'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['category']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $complaint['status'])); ?>">
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
                                                <button class="action-btn btn-delete" onclick="deleteComplaint(<?php echo $complaint['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <div class="quick-status">
                                                    <button class="status-btn btn-pending" onclick="updateStatus(<?php echo $complaint['id']; ?>, 'Pending')">Pending</button>
                                                    <button class="status-btn btn-investigation" onclick="updateStatus(<?php echo $complaint['id']; ?>, 'Under Investigation')">Investigate</button>
                                                    <button class="status-btn btn-resolved" onclick="updateStatus(<?php echo $complaint['id']; ?>, 'Resolved')">Resolve</button>
                                                </div>
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
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                               class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h3>Update Complaint Status</h3>
            <form id="statusForm">
                <input type="hidden" id="complaintId">
                <div class="form-group">
                    <label for="newStatus">New Status</label>
                    <select id="newStatus" class="form-control" required>
                        <option value="Pending">Pending</option>
                        <option value="Under Investigation">Under Investigation</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="adminNotes">Admin Notes</label>
                    <textarea id="adminNotes" class="form-control" rows="4" 
                              placeholder="Add notes about the status update..."></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Status</button>
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Mobile menu toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // Select all checkboxes
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.complaint-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    // Bulk actions
    function applyBulkAction() {
        const action = document.getElementById('bulkAction').value;
        const selected = Array.from(document.querySelectorAll('.complaint-checkbox:checked'))
                            .map(cb => cb.value);
        
        if (selected.length === 0) {
            alert('Please select at least one complaint');
            return;
        }
        
        if (action === 'delete') {
            if (confirm(`Are you sure you want to delete ${selected.length} complaint(s)?`)) {
                // Implement delete bulk action
                console.log('Deleting:', selected);
            }
        } else {
            // Implement status update bulk action
            console.log('Updating status to', action, 'for:', selected);
        }
    }
    
    // Export functions
    function exportToPDF() {
        // Implement PDF export
        alert('PDF export feature will be implemented');
    }
    
    function exportToExcel() {
        // Implement Excel export
        window.location.href = `export-complaints.php?format=excel&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>`;
    }
    
    function exportToWord() {
        // Implement Word export
        alert('Word export feature will be implemented');
    }
    
    // Status update
    function updateStatus(id, status) {
        document.getElementById('complaintId').value = id;
        document.getElementById('newStatus').value = status;
        document.getElementById('statusModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('statusModal').style.display = 'none';
    }
    
    // Submit status form
    document.getElementById('statusForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const id = document.getElementById('complaintId').value;
        const status = document.getElementById('newStatus').value;
        const notes = document.getElementById('adminNotes').value;
        
        fetch(`ajax/update-complaint-status.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                status: status,
                notes: notes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Status updated successfully');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error updating status');
        });
    });
    
    // Delete complaint
    function deleteComplaint(id) {
        if (confirm('Are you sure you want to delete this complaint?')) {
            fetch(`ajax/delete-complaint.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Complaint deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('statusModal');
        if (event.target === modal) {
            closeModal();
        }
    };
    </script>
</body>
</html>