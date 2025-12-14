<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_data = getUserData($conn, $user_id);
$status_filter = $_GET['status'] ?? null;
$complaints = getComplaints($conn, $user_id, $status_filter);

// Handle status update (for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if ($_SESSION['role'] == 'admin') {
        $complaint_id = intval($_POST['complaint_id']);
        $new_status = mysqli_real_escape_string($conn, $_POST['status']);
        $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes']);
        
        $sql = "UPDATE complaints SET status = ?, admin_notes = ?";
        
        if ($new_status == 'Resolved') {
            $sql .= ", resolved_date = NOW()";
        } else {
            $sql .= ", resolved_date = NULL";
        }
        
        $sql .= " WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssi', $new_status, $admin_notes, $complaint_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the status change
            logActivity($conn, $user_id, "Updated complaint #$complaint_id status to $new_status");
            
            // Refresh page to show updated status
            header('Location: view-complaints.php?updated=' . $complaint_id);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Complaints - College Complaint System</title>
    <link rel="stylesheet" href="css/style.css">
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
                <li><a href="view-complaints.php" class="active"><i class="fas fa-list"></i> View Complaints</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="admin-dashboard.php"><i class="fas fa-cog"></i> Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h1>Your Complaints</h1>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">
                Complaint status updated successfully!
            </div>
        <?php endif; ?>
        
        <!-- Status Progress Visualization -->
        <div class="card">
            <h2>Complaint Status Flow</h2>
            <div class="status-progress">
                <div class="status-step <?php echo ($status_filter == 'Pending' || !$status_filter) ? 'active' : ''; ?>">
                    <div class="step-circle">1</div>
                    <div class="step-label">Pending</div>
                </div>
                <div class="status-step <?php echo $status_filter == 'Under Investigation' ? 'active' : ''; ?>">
                    <div class="step-circle">2</div>
                    <div class="step-label">Under Investigation</div>
                </div>
                <div class="status-step <?php echo $status_filter == 'Resolved' ? 'active' : ''; ?>">
                    <div class="step-circle">3</div>
                    <div class="step-label">Resolved</div>
                </div>
            </div>
        </div>
        
        <!-- Filter Buttons -->
        <div class="card">
            <h3>Filter by Status</h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">
                <a href="view-complaints.php" class="btn <?php echo !$status_filter ? 'btn-primary' : ''; ?>">
                    All Complaints
                </a>
                <a href="?status=Pending" class="btn <?php echo $status_filter == 'Pending' ? 'btn-primary' : ''; ?>">
                    <span class="status-badge status-pending">Pending</span>
                </a>
                <a href="?status=Under Investigation" class="btn <?php echo $status_filter == 'Under Investigation' ? 'btn-primary' : ''; ?>">
                    <span class="status-badge status-under-investigation">Under Investigation</span>
                </a>
                <a href="?status=Resolved" class="btn <?php echo $status_filter == 'Resolved' ? 'btn-primary' : ''; ?>">
                    <span class="status-badge status-resolved">Resolved</span>
                </a>
            </div>
            
            <!-- Stats Summary -->
            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px;">
                <?php
                $stats = getComplaintStats($conn, $user_id);
                $total = $stats['total'];
                $pending = $stats['pending'];
                $investigation = $stats['under_investigation'] ?? 0;
                $resolved = $stats['resolved'] ?? 0;
                ?>
                <div style="flex: 1; min-width: 150px; background: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;">
                    <h3 style="margin: 0; font-size: 24px; color: #2c3e50;"><?php echo $total; ?></h3>
                    <p style="margin: 5px 0 0; color: #7f8c8d;">Total</p>
                </div>
                <div style="flex: 1; min-width: 150px; background: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;">
                    <h3 style="margin: 0; font-size: 24px; color: #f39c12;"><?php echo $pending; ?></h3>
                    <p style="margin: 5px 0 0; color: #7f8c8d;">Pending</p>
                </div>
                <div style="flex: 1; min-width: 150px; background: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;">
                    <h3 style="margin: 0; font-size: 24px; color: #3498db;"><?php echo $investigation; ?></h3>
                    <p style="margin: 5px 0 0; color: #7f8c8d;">Under Investigation</p>
                </div>
                <div style="flex: 1; min-width: 150px; background: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;">
                    <h3 style="margin: 0; font-size: 24px; color: #27ae60;"><?php echo $resolved; ?></h3>
                    <p style="margin: 5px 0 0; color: #7f8c8d;">Resolved</p>
                </div>
            </div>
        </div>
        
        <!-- Complaints Table -->
        <div class="card">
            <h2>Complaints List</h2>
            <?php if (empty($complaints)): ?>
                <p>No complaints found.</p>
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
                                <th>Created</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $complaint): ?>
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
                                    <td><?php echo date('d M Y', strtotime($complaint['updated_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button onclick="viewComplaintDetails(<?php echo $complaint['id']; ?>)" 
                                                    class="btn btn-success" style="padding: 5px 10px;">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($_SESSION['role'] == 'admin' && $complaint['status'] != 'Resolved'): ?>
                                                <button onclick="showStatusUpdateForm(<?php echo $complaint['id']; ?>)" 
                                                        class="btn btn-warning" style="padding: 5px 10px;">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Complaint Details Modal -->
    <div id="complaintModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div id="complaintDetails"></div>
        </div>
    </div>

    <!-- Status Update Modal (Admin Only) -->
    <?php if ($_SESSION['role'] == 'admin'): ?>
    <div id="statusUpdateModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeStatusModal()">&times;</span>
            <h2>Update Complaint Status</h2>
            <form id="statusUpdateForm" method="POST" action="">
                <input type="hidden" id="updateComplaintId" name="complaint_id">
                
                <div class="form-group">
                    <label for="status">New Status</label>
                    <select class="form-control status-select" id="status" name="status" required>
                        <option value="Pending">Pending</option>
                        <option value="Under Investigation">Under Investigation</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="admin_notes">Admin Notes</label>
                    <textarea class="admin-notes" id="admin_notes" name="admin_notes" 
                              placeholder="Add notes about the investigation or resolution..."></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="update_status" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                    <button type="button" class="btn" onclick="closeStatusModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> College Complaint Filing System. All rights reserved.</p>
    </footer>

    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }

        .close {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 28px;
            cursor: pointer;
            color: #7f8c8d;
        }

        .close:hover {
            color: #e74c3c;
        }

        .modal-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .modal-section:last-child {
            border-bottom: none;
        }
    </style>

    <script>
    function viewComplaintDetails(complaintId) {
        fetch(`get-complaint-details.php?id=${complaintId}`)
            .then(response => response.json())
            .then(data => {
                const detailsDiv = document.getElementById('complaintDetails');
                
                let statusClass = data.status.toLowerCase().replace(' ', '-');
                let timelineHtml = '';
                
                // Create timeline
                if (data.created_at) {
                    timelineHtml += `
                        <div class="timeline-item">
                            <div class="timeline-date">${new Date(data.created_at).toLocaleString()}</div>
                            <div class="timeline-content">
                                <strong>Complaint Filed</strong>
                                <p>Complaint was submitted by ${data.name}</p>
                            </div>
                        </div>
                    `;
                }
                
                if (data.updated_at && data.updated_at !== data.created_at) {
                    timelineHtml += `
                        <div class="timeline-item">
                            <div class="timeline-date">${new Date(data.updated_at).toLocaleString()}</div>
                            <div class="timeline-content">
                                <strong>Status Updated</strong>
                                <p>Status changed to <span class="status-badge status-${statusClass}">${data.status}</span></p>
                            </div>
                        </div>
                    `;
                }
                
                if (data.resolved_date) {
                    timelineHtml += `
                        <div class="timeline-item">
                            <div class="timeline-date">${new Date(data.resolved_date).toLocaleString()}</div>
                            <div class="timeline-content">
                                <strong>Complaint Resolved</strong>
                                <p>Complaint was marked as resolved</p>
                            </div>
                        </div>
                    `;
                }
                
                detailsDiv.innerHTML = `
                    <div class="modal-section">
                        <h2>${data.title}</h2>
                        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 15px;">
                            <div>
                                <strong>Complaint ID:</strong> #${data.id}
                            </div>
                            <div>
                                <strong>Student ID:</strong> ${data.student_id}
                            </div>
                            <div>
                                <strong>Status:</strong> <span class="status-badge status-${statusClass}">${data.status}</span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 10px;">
                            <div>
                                <strong>Priority:</strong> <span class="priority-badge priority-${data.priority.toLowerCase()}">${data.priority}</span>
                            </div>
                            <div>
                                <strong>Category:</strong> ${data.category}
                            </div>
                            <div>
                                <strong>Filed on:</strong> ${new Date(data.created_at).toLocaleDateString()}
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <h3>Description</h3>
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 4px; line-height: 1.6;">
                            ${data.description}
                        </div>
                    </div>
                    
                    ${data.admin_notes ? `
                    <div class="modal-section">
                        <h3>Admin Notes</h3>
                        <div style="background-color: #e8f4f8; padding: 20px; border-radius: 4px; border-left: 4px solid #3498db;">
                            ${data.admin_notes}
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="modal-section">
                        <h3>Complaint Timeline</h3>
                        <div class="timeline">
                            ${timelineHtml}
                        </div>
                    </div>
                    
                    ${data.attachment_path ? `
                    <div class="modal-section">
                        <h3>Attachment</h3>
                        <a href="${data.attachment_path}" target="_blank" class="btn">
                            <i class="fas fa-download"></i> Download Attachment
                        </a>
                    </div>
                    ` : ''}
                `;
                
                document.getElementById('complaintModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading complaint details.');
            });
    }

    <?php if ($_SESSION['role'] == 'admin'): ?>
    function showStatusUpdateForm(complaintId) {
        document.getElementById('updateComplaintId').value = complaintId;
        document.getElementById('statusUpdateModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    function closeStatusModal() {
        document.getElementById('statusUpdateModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    <?php endif; ?>

    function closeModal() {
        document.getElementById('complaintModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        }
    });
    </script>
</body>
</html>