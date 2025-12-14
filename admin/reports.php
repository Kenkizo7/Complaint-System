<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if ($_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Get data for charts
// Complaints by month for current year
$monthly_complaints_sql = "SELECT 
    DATE_FORMAT(created_at, '%b') as month,
    COUNT(*) as count
    FROM complaints 
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at), DATE_FORMAT(created_at, '%b')
    ORDER BY MONTH(created_at)";

$monthly_result = mysqli_query($conn, $monthly_complaints_sql);
$monthly_data = [];
$monthly_labels = [];
$monthly_counts = [];
while ($row = mysqli_fetch_assoc($monthly_result)) {
    $monthly_labels[] = $row['month'];
    $monthly_counts[] = $row['count'];
}

// Students per college
$college_students_sql = "SELECT 
    college,
    COUNT(*) as count
    FROM users 
    WHERE student_id != 'ADMIN001' AND college IS NOT NULL
    GROUP BY college 
    ORDER BY count DESC 
    LIMIT 10";

$college_result = mysqli_query($conn, $college_students_sql);
$college_labels = [];
$college_counts = [];
$college_colors = [];
while ($row = mysqli_fetch_assoc($college_result)) {
    $college_labels[] = $row['college'];
    $college_counts[] = $row['count'];
    $college_colors[] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}

// Complaint status distribution
$status_distribution_sql = "SELECT 
    status,
    COUNT(*) as count
    FROM complaints 
    GROUP BY status 
    ORDER BY FIELD(status, 'Pending', 'Under Investigation', 'Resolved')";

$status_result = mysqli_query($conn, $status_distribution_sql);
$status_labels = [];
$status_counts = [];
$status_colors = ['#f39c12', '#3498db', '#27ae60'];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_labels[] = $row['status'];
    $status_counts[] = $row['count'];
}

// Top complainants
$top_complainants_sql = "SELECT 
    u.name,
    u.student_id,
    COUNT(c.id) as complaint_count
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    GROUP BY u.id
    ORDER BY complaint_count DESC
    LIMIT 10";

$top_complainants_result = mysqli_query($conn, $top_complainants_sql);
$top_complainants = [];
while ($row = mysqli_fetch_assoc($top_complainants_result)) {
    $top_complainants[] = $row;
}

// Get generated reports
$reports_sql = "SELECT r.*, u.name as generated_by_name 
               FROM reports r
               JOIN users u ON r.generated_by = u.id
               ORDER BY r.created_at DESC 
               LIMIT 10";
$reports_result = mysqli_query($conn, $reports_sql);
$recent_reports = [];
while ($row = mysqli_fetch_assoc($reports_result)) {
    $recent_reports[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js">
    <style>
        <?php include 'admin-sidebar-styles.php'; ?>
        
        .date-range-picker {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card-large {
            grid-column: span 2;
        }
        
        .report-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .report-type-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .report-type-card:hover {
            transform: translateY(-5px);
        }
        
        .report-icon {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
            color: white;
        }
        
        .report-type-card h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        
        .report-type-card p {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .report-actions {
            display: flex;
            gap: 10px;
        }
        
        .format-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .format-pdf { background-color: #e74c3c; color: white; }
        .format-excel { background-color: #27ae60; color: white; }
        .format-word { background-color: #3498db; color: white; }
        
        .generated-reports {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .report-file {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .report-file:last-child {
            border-bottom: none;
        }
        
        .file-icon {
            font-size: 24px;
            color: #3498db;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-info h5 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .file-meta {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .download-btn {
            padding: 6px 12px;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        @media (max-width: 1200px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-card-large {
                grid-column: span 1;
            }
        }
        
        @media (max-width: 768px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .report-types {
                grid-template-columns: 1fr;
            }
            
            .date-range-picker {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'admin-sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <div class="page-title">
                    <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
                    <p>Comprehensive system analytics and report generation</p>
                </div>
                <div class="header-actions">
                    <div class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                </div>
            </header>
            
            <!-- Date Range Picker -->
            <div class="date-range-picker">
                <div>
                    <label for="startDate">From Date</label>
                    <input type="date" id="startDate" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div>
                    <label for="endDate">To Date</label>
                    <input type="date" id="endDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <button class="btn btn-primary" onclick="updateCharts()">
                        <i class="fas fa-sync-alt"></i> Update Charts
                    </button>
                </div>
                <div>
                    <button class="btn btn-success" onclick="generateReport()">
                        <i class="fas fa-download"></i> Generate Full Report
                    </button>
                </div>
            </div>
            
            <!-- Charts Grid -->
            <div class="chart-grid">
                <div class="chart-card chart-card-large">
                    <div class="chart-header">
                        <h3>Monthly Complaints Trend</h3>
                    </div>
                    <div class="chart-container" style="height: 400px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Complaint Status Distribution</h3>
                    </div>
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Students per College</h3>
                    </div>
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="collegeChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Report Types -->
            <div class="report-types">
                <div class="report-type-card" onclick="generateComplaintReport()">
                    <div class="report-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h4>Complaints Report</h4>
                    <p>Detailed report of all complaints with filters and analysis</p>
                    <div class="report-actions">
                        <button class="format-btn format-pdf" onclick="event.stopPropagation(); generateComplaintReport('pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="format-btn format-excel" onclick="event.stopPropagation(); generateComplaintReport('excel')">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="format-btn format-word" onclick="event.stopPropagation(); generateComplaintReport('word')">
                            <i class="fas fa-file-word"></i> Word
                        </button>
                    </div>
                </div>
                
                <div class="report-type-card" onclick="generateStudentReport()">
                    <div class="report-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4>Students Report</h4>
                    <p>Complete student database with demographics and activity</p>
                    <div class="report-actions">
                        <button class="format-btn format-pdf" onclick="event.stopPropagation(); generateStudentReport('pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="format-btn format-excel" onclick="event.stopPropagation(); generateStudentReport('excel')">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="format-btn format-word" onclick="event.stopPropagation(); generateStudentReport('word')">
                            <i class="fas fa-file-word"></i> Word
                        </button>
                    </div>
                </div>
                
                <div class="report-type-card" onclick="generateActivityReport()">
                    <div class="report-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>Activity Report</h4>
                    <p>System usage statistics and user activity patterns</p>
                    <div class="report-actions">
                        <button class="format-btn format-pdf" onclick="event.stopPropagation(); generateActivityReport('pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="format-btn format-excel" onclick="event.stopPropagation(); generateActivityReport('excel')">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="format-btn format-word" onclick="event.stopPropagation(); generateActivityReport('word')">
                            <i class="fas fa-file-word"></i> Word
                        </button>
                    </div>
                </div>
                
                <div class="report-type-card" onclick="generatePerformanceReport()">
                    <div class="report-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h4>Performance Report</h4>
                    <p>System performance metrics and resolution statistics</p>
                    <div class="report-actions">
                        <button class="format-btn format-pdf" onclick="event.stopPropagation(); generatePerformanceReport('pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="format-btn format-excel" onclick="event.stopPropagation(); generatePerformanceReport('excel')">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="format-btn format-word" onclick="event.stopPropagation(); generatePerformanceReport('word')">
                            <i class="fas fa-file-word"></i> Word
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Top Complainants -->
            <div class="table-card">
                <div class="table-header">
                    <h3>Top Complainants</h3>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student</th>
                                <th>Student ID</th>
                                <th>Complaints Filed</th>
                                <th>Resolved</th>
                                <th>Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; ?>
                            <?php foreach ($top_complainants as $complainant): ?>
                                <tr>
                                    <td>#<?php echo $rank++; ?></td>
                                    <td><?php echo htmlspecialchars($complainant['name']); ?></td>
                                    <td><?php echo htmlspecialchars($complainant['student_id']); ?></td>
                                    <td><strong><?php echo $complainant['complaint_count']; ?></strong></td>
                                    <td>
                                        <?php 
                                        $resolved_sql = "SELECT COUNT(*) as count FROM complaints WHERE user_id = (SELECT id FROM users WHERE student_id = '{$complainant['student_id']}') AND status = 'Resolved'";
                                        $resolved_result = mysqli_query($conn, $resolved_sql);
                                        $resolved = mysqli_fetch_assoc($resolved_result)['count'];
                                        echo $resolved;
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $pending_sql = "SELECT COUNT(*) as count FROM complaints WHERE user_id = (SELECT id FROM users WHERE student_id = '{$complainant['student_id']}') AND status = 'Pending'";
                                        $pending_result = mysqli_query($conn, $pending_sql);
                                        $pending = mysqli_fetch_assoc($pending_result)['count'];
                                        echo $pending;
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Generated Reports -->
            <div class="generated-reports">
                <div class="table-header">
                    <h3>Recently Generated Reports</h3>
                </div>
                <div>
                    <?php if (empty($recent_reports)): ?>
                        <p style="text-align: center; padding: 40px; color: #7f8c8d;">
                            <i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 15px;"></i><br>
                            No reports generated yet
                        </p>
                    <?php else: ?>
                        <?php foreach ($recent_reports as $report): ?>
                            <div class="report-file">
                                <div class="file-icon">
                                    <?php 
                                    $icon = 'fa-file-alt';
                                    if (strpos($report['report_type'], 'pdf') !== false) $icon = 'fa-file-pdf';
                                    elseif (strpos($report['report_type'], 'excel') !== false) $icon = 'fa-file-excel';
                                    elseif (strpos($report['report_type'], 'word') !== false) $icon = 'fa-file-word';
                                    ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="file-info">
                                    <h5><?php echo htmlspecialchars($report['report_name']); ?></h5>
                                    <div class="file-meta">
                                        Generated by <?php echo htmlspecialchars($report['generated_by_name']); ?> 
                                        on <?php echo date('M d, Y h:i A', strtotime($report['created_at'])); ?>
                                        | Type: <?php echo htmlspecialchars($report['report_type']); ?>
                                    </div>
                                </div>
                                <?php if ($report['file_path']): ?>
                                    <a href="<?php echo $report['file_path']; ?>" class="download-btn" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Mobile menu toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // Initialize charts
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const collegeCtx = document.getElementById('collegeChart').getContext('2d');
    
    // Monthly Complaints Chart
    const monthlyChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($monthly_labels); ?>,
            datasets: [{
                label: 'Complaints',
                data: <?php echo json_encode($monthly_counts); ?>,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
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
                        text: 'Month'
                    }
                }
            }
        }
    });
    
    // Status Distribution Chart
    const statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($status_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($status_counts); ?>,
                backgroundColor: <?php echo json_encode($status_colors); ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // College Distribution Chart
    const collegeChart = new Chart(collegeCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($college_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($college_counts); ?>,
                backgroundColor: <?php echo json_encode($college_colors); ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Update charts with date range
    function updateCharts() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        // Show loading
        alert('Updating charts with new date range...');
        // In a real implementation, you would make an AJAX call to update the charts
    }
    
    // Generate reports
    function generateReport() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        if (confirm('Generate comprehensive report for the selected period?')) {
            window.location.href = `generate-full-report.php?start_date=${startDate}&end_date=${endDate}`;
        }
    }
    
    function generateComplaintReport(format = 'pdf') {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        window.location.href = `generate-complaint-report.php?format=${format}&start_date=${startDate}&end_date=${endDate}`;
    }
    
    function generateStudentReport(format = 'pdf') {
        window.location.href = `generate-student-report.php?format=${format}`;
    }
    
    function generateActivityReport(format = 'pdf') {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        window.location.href = `generate-activity-report.php?format=${format}&start_date=${startDate}&end_date=${endDate}`;
    }
    
    function generatePerformanceReport(format = 'pdf') {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        window.location.href = `generate-performance-report.php?format=${format}&start_date=${startDate}&end_date=${endDate}`;
    }
    
    // Auto-refresh charts every 5 minutes
    setInterval(() => {
        // Update charts with current data
        console.log('Auto-refreshing charts...');
    }, 300000);
    </script>
</body>
</html>