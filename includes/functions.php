<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$message = '';
$error = '';

// Get current user data
$user_id = $_SESSION['user_id'];
$user_data = getUserData($conn, $user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $user_id = $_SESSION['user_id'];
    
    // Complaint against fields
    $complaint_against_type = mysqli_real_escape_string($conn, $_POST['complaint_against_type'] ?? 'Other');
    $complaint_against_name = mysqli_real_escape_string($conn, $_POST['complaint_against_name'] ?? '');
    $complaint_against_department = mysqli_real_escape_string($conn, $_POST['complaint_against_department'] ?? '');
    $complaint_against_position = mysqli_real_escape_string($conn, $_POST['complaint_against_position'] ?? '');
    $complaint_against_details = mysqli_real_escape_string($conn, $_POST['complaint_against_details'] ?? '');
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($category)) {
        $error = "Please fill in all required fields (Title, Description, and Category).";
    } elseif (empty($complaint_against_type)) {
        $error = "Please specify who/what the complaint is against.";
    } else {
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
            // Insert complaint
            $sql = "INSERT INTO complaints (
                user_id, title, description, category, status,
                complaint_against_type, complaint_against_name, complaint_against_department, 
                complaint_against_position, complaint_against_details, attachment_path
            ) VALUES (?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'issssssssss', 
                $user_id, $title, $description, $category,
                $complaint_against_type, $complaint_against_name, $complaint_against_department,
                $complaint_against_position, $complaint_against_details, $attachment_path
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $complaint_id = mysqli_insert_id($conn);
                
                // Handle co-complainants
                if (isset($_POST['co_complainant_name']) && is_array($_POST['co_complainant_name'])) {
                    foreach ($_POST['co_complainant_name'] as $index => $co_name) {
                        if (!empty(trim($co_name))) {
                            $co_student_id = mysqli_real_escape_string($conn, $_POST['co_complainant_student_id'][$index] ?? '');
                            $co_email = mysqli_real_escape_string($conn, $_POST['co_complainant_email'][$index] ?? '');
                            $co_contact = mysqli_real_escape_string($conn, $_POST['co_complainant_contact'][$index] ?? '');
                            $co_relationship = mysqli_real_escape_string($conn, $_POST['co_complainant_relationship'][$index] ?? '');
                            $co_additional_info = mysqli_real_escape_string($conn, $_POST['co_complainant_additional_info'][$index] ?? '');
                            
                            $co_sql = "INSERT INTO co_complainants (complaint_id, name, student_id, email, contact_number, relationship_to_complaint, additional_info) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)";
                            $co_stmt = mysqli_prepare($conn, $co_sql);
                            mysqli_stmt_bind_param($co_stmt, 'issssss', $complaint_id, $co_name, $co_student_id, $co_email, $co_contact, $co_relationship, $co_additional_info);
                            mysqli_stmt_execute($co_stmt);
                        }
                    }
                }
                
                // Handle witnesses
                if (isset($_POST['witness_name']) && is_array($_POST['witness_name'])) {
                    foreach ($_POST['witness_name'] as $index => $witness_name) {
                        if (!empty(trim($witness_name))) {
                            $witness_student_id = mysqli_real_escape_string($conn, $_POST['witness_student_id'][$index] ?? '');
                            $witness_email = mysqli_real_escape_string($conn, $_POST['witness_email'][$index] ?? '');
                            $witness_contact = mysqli_real_escape_string($conn, $_POST['witness_contact'][$index] ?? '');
                            $witness_statement = mysqli_real_escape_string($conn, $_POST['witness_statement'][$index] ?? '');
                            
                            $witness_sql = "INSERT INTO complaint_witnesses (complaint_id, witness_name, witness_student_id, witness_email, witness_contact, witness_statement) 
                                           VALUES (?, ?, ?, ?, ?, ?)";
                            $witness_stmt = mysqli_prepare($conn, $witness_sql);
                            mysqli_stmt_bind_param($witness_stmt, 'isssss', $complaint_id, $witness_name, $witness_student_id, $witness_email, $witness_contact, $witness_statement);
                            mysqli_stmt_execute($witness_stmt);
                        }
                    }
                }
                
                $message = "Complaint filed successfully! Your complaint ID is #" . $complaint_id;
                
                // Log activity
                logActivity($conn, $user_id, "Filed complaint #$complaint_id with " . 
                    (isset($_POST['co_complainant_name']) ? count(array_filter($_POST['co_complainant_name'])) : 0) . " co-complainant(s)");
            } else {
                $error = "Error filing complaint. Please try again. Error: " . mysqli_error($conn);
            }
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
    <style>
        .section-header {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 25px 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .dynamic-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .section-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .section-title {
            margin: 0;
            color: #2c3e50;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .remove-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .remove-btn:hover {
            background-color: #c0392b;
        }
        
        .add-btn {
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .add-btn:hover {
            background-color: #219653;
        }
        
        .required::after {
            content: " *";
            color: #e74c3c;
        }
        
        .field-hint {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .counter-badge {
            background-color: #3498db;
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .section-summary {
            background-color: #e8f4f8;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 10px;
            margin-top: 10px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .section-header-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="file-complaint.php" class="active"><i class="fas fa-plus-circle"></i> File Complaint</a></li>
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
        <h1>File a New Complaint</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                <div style="margin-top: 10px;">
                    <a href="view-complaints.php" class="btn btn-success">
                        <i class="fas fa-eye"></i> View Your Complaint
                    </a>
                    <a href="file-complaint.php" class="btn">
                        <i class="fas fa-plus"></i> File Another Complaint
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <p style="margin-bottom: 20px; color: #7f8c8d;">
                <i class="fas fa-info-circle"></i> Please fill in all required fields (*). You can add multiple co-complainants and witnesses.
            </p>
            
            <form method="POST" action="" enctype="multipart/form-data" id="complaintForm">
                
                <!-- Section 1: Basic Complaint Information -->
                <div class="section-header">
                    <i class="fas fa-file-alt"></i>
                    <h3>Complaint Details</h3>
                </div>
                
                <div class="form-group">
                    <label for="title" class="required">Complaint Title</label>
                    <input type="text" class="form-control" id="title" name="title" required 
                           placeholder="Brief summary of your complaint">
                    <div class="field-hint">Be specific and concise (e.g., "Late Submission of Grades for CS101")</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category" class="required">Category</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Academic">Academic (Courses, Grades, Exams)</option>
                            <option value="Faculty">Faculty (Professors, Instructors)</option>
                            <option value="Staff">Staff (Administrative Staff)</option>
                            <option value="Student">Student (Other Students)</option>
                            <option value="Administrative">Administrative (Office Procedures)</option>
                            <option value="Facilities">Facilities (Buildings, Equipment)</option>
                            <option value="Hostel">Hostel (Accommodation)</option>
                            <option value="Library">Library (Resources, Services)</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="required">Detailed Description</label>
                    <textarea class="form-control" id="description" name="description" required 
                              rows="6" placeholder="Please provide detailed information about your complaint..."></textarea>
                    <div class="field-hint">Provide complete details including dates, times, locations, and specific incidents</div>
                </div>
                
                <!-- Section 2: Co-Complainants -->
                <div class="section-header">
                    <i class="fas fa-users"></i>
                    <h3>Co-Complainants <span id="coComplainantCount" class="counter-badge">0</span></h3>
                </div>
                
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    <i class="fas fa-info-circle"></i> If you are filing this complaint together with other students, you can add them as co-complainants. This is optional.
                </p>
                
                <div id="coComplainantsContainer">
                    <!-- Co-complainant sections will be added here dynamically -->
                </div>
                
                <button type="button" class="add-btn" onclick="addCoComplainant()">
                    <i class="fas fa-user-plus"></i> Add Co-Complainant
                </button>
                
                <!-- Section 3: Complaint Against -->
                <div class="section-header">
                    <i class="fas fa-user-times"></i>
                    <h3>Complaint Against (Required)</h3>
                </div>
                
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    <i class="fas fa-info-circle"></i> Please provide details about the person, department, or system you are complaining about.
                </p>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="complaint_against_type" class="required">Type</label>
                        <select class="form-control" id="complaint_against_type" name="complaint_against_type" required onchange="updateComplaintAgainstFields()">
                            <option value="">Select Type</option>
                            <option value="Faculty">Faculty Member</option>
                            <option value="Staff">Staff Member</option>
                            <option value="Student">Student</option>
                            <option value="Department">Department</option>
                            <option value="System">System/Process</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="complaint_against_name" id="against_name_label">Name</label>
                        <input type="text" class="form-control" id="complaint_against_name" name="complaint_against_name" 
                               placeholder="Name of person/department">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="complaint_against_department">Department</label>
                        <input type="text" class="form-control" id="complaint_against_department" name="complaint_against_department" 
                               placeholder="Department name">
                    </div>
                    <div class="form-group">
                        <label for="complaint_against_position">Position/Role</label>
                        <input type="text" class="form-control" id="complaint_against_position" name="complaint_against_position" 
                               placeholder="Position or role">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="complaint_against_details">Additional Details</label>
                    <textarea class="form-control" id="complaint_against_details" name="complaint_against_details" 
                              rows="4" placeholder="Additional details about who/what you're complaining against..."></textarea>
                    <div class="field-hint">Provide any additional identifying information</div>
                </div>
                
                <!-- Section 4: Witnesses -->
                <div class="section-header">
                    <i class="fas fa-user-check"></i>
                    <h3>Witnesses <span id="witnessCount" class="counter-badge">0</span></h3>
                </div>
                
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    <i class="fas fa-info-circle"></i> If there were witnesses to the incident, you can add their details here. This is optional.
                </p>
                
                <div id="witnessesContainer">
                    <!-- Witness sections will be added here dynamically -->
                </div>
                
                <button type="button" class="add-btn" onclick="addWitness()">
                    <i class="fas fa-plus"></i> Add Witness
                </button>
                
                <!-- Section 5: Evidence/Attachments -->
                <div class="section-header">
                    <i class="fas fa-paperclip"></i>
                    <h3>Evidence & Attachments</h3>
                </div>
                
                <div class="form-group">
                    <label for="attachment">Attachment (Optional)</label>
                    <input type="file" class="form-control" id="attachment" name="attachment">
                    <div class="field-hint">
                        Supported files: PDF, DOC, DOCX, JPG, PNG (Max 5MB each)<br>
                        You can attach screenshots, documents, photos, or other evidence.
                    </div>
                </div>
                
                <!-- Section 6: Your Information (Read-only) -->
                <div class="section-header">
                    <i class="fas fa-user"></i>
                    <h3>Your Information (Primary Complainant)</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Your Student ID</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['student_id']); ?>" readonly>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Your Contact Number</label>
                        <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user_data['contact_number'] ?? 'Not provided'); ?>" readonly>
                    </div>
                </div>
                
                <!-- Submit Section -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                    <div class="form-group">
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                            <h4 style="margin-top: 0; color: #2c3e50;">
                                <i class="fas fa-shield-alt"></i> Important Notes
                            </h4>
                            <ul style="margin-bottom: 0; color: #7f8c8d; padding-left: 20px;">
                                <li>Your complaint will be treated with confidentiality</li>
                                <li>Co-complainants will receive notifications about complaint status</li>
                                <li>False complaints may result in disciplinary action</li>
                                <li>You can track your complaint status in the "View Complaints" section</li>
                            </ul>
                        </div>
                        
                        <div id="formSummary" class="section-summary" style="display: none;">
                            <h5 style="margin-top: 0;">Form Summary</h5>
                            <p id="summaryContent"></p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px;">
                            <i class="fas fa-paper-plane"></i> Submit Complaint
                        </button>
                        <button type="button" class="btn" onclick="showSummary()" style="padding: 12px 30px;">
                            <i class="fas fa-list"></i> Show Summary
                        </button>
                        <a href="index.php" class="btn" style="padding: 12px 30px;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Process Information -->
        <div class="card">
            <h2><i class="fas fa-info-circle"></i> About Co-Complainants</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 15px;">
                <div style="padding: 15px; background: #e8f4f8; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #0c5460;"><i class="fas fa-user-friends"></i> What is a Co-Complainant?</h4>
                    <p>A co-complainant is another student who is also affected by the issue and wants to join the complaint.</p>
                </div>
                <div style="padding: 15px; background: #f8f9fa; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #495057;"><i class="fas fa-bell"></i> Notifications</h4>
                    <p>Co-complainants will receive email updates about the complaint status and resolution.</p>
                </div>
                <div style="padding: 15px; background: #fff3cd; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #856404;"><i class="fas fa-handshake"></i> Shared Responsibility</h4>
                    <p>All complainants share responsibility for the accuracy of the information provided.</p>
                </div>
                <div style="padding: 15px; background: #d4edda; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #155724;"><i class="fas fa-users"></i> Collective Impact</h4>
                    <p>Multiple complainants can demonstrate that an issue affects more than one person.</p>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> College Complaint Filing System. All rights reserved.</p>
    </footer>

    <script>
    let coComplainantCounter = 0;
    let witnessCounter = 0;
    
    // Initialize with empty containers
    document.addEventListener('DOMContentLoaded', function() {
        updateCounters();
    });
    
    function addCoComplainant() {
        coComplainantCounter++;
        const container = document.getElementById('coComplainantsContainer');
        
        const section = document.createElement('div');
        section.className = 'dynamic-section';
        section.id = `coComplainant-${coComplainantCounter}`;
        
        section.innerHTML = `
            <div class="section-header-row">
                <h4 class="section-title">
                    <i class="fas fa-user-friends"></i> Co-Complainant ${coComplainantCounter}
                </h4>
                <button type="button" class="remove-btn" onclick="removeCoComplainant(${coComplainantCounter})" title="Remove this co-complainant">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="co_complainant_name_${coComplainantCounter}">Full Name</label>
                    <input type="text" class="form-control" id="co_complainant_name_${coComplainantCounter}" 
                           name="co_complainant_name[]" placeholder="Full name of co-complainant">
                </div>
                <div class="form-group">
                    <label for="co_complainant_student_id_${coComplainantCounter}">Student ID</label>
                    <input type="text" class="form-control" id="co_complainant_student_id_${coComplainantCounter}" 
                           name="co_complainant_student_id[]" placeholder="Student ID">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="co_complainant_email_${coComplainantCounter}">Email Address</label>
                    <input type="email" class="form-control" id="co_complainant_email_${coComplainantCounter}" 
                           name="co_complainant_email[]" placeholder="Email address">
                </div>
                <div class="form-group">
                    <label for="co_complainant_contact_${coComplainantCounter}">Contact Number</label>
                    <input type="tel" class="form-control" id="co_complainant_contact_${coComplainantCounter}" 
                           name="co_complainant_contact[]" placeholder="Contact number">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="co_complainant_relationship_${coComplainantCounter}">Relationship to Complaint</label>
                    <input type="text" class="form-control" id="co_complainant_relationship_${coComplainantCounter}" 
                           name="co_complainant_relationship[]" placeholder="How are they affected by this issue?">
                </div>
            </div>
            
            <div class="form-group">
                <label for="co_complainant_additional_info_${coComplainantCounter}">Additional Information (Optional)</label>
                <textarea class="form-control" id="co_complainant_additional_info_${coComplainantCounter}" 
                          name="co_complainant_additional_info[]" rows="2" 
                          placeholder="Any additional information about this co-complainant..."></textarea>
            </div>
        `;
        
        container.appendChild(section);
        updateCounters();
        
        // Scroll to the new section
        section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    function removeCoComplainant(id) {
        const section = document.getElementById(`coComplainant-${id}`);
        if (section) {
            section.remove();
            updateCounters();
        }
    }
    
    function addWitness() {
        witnessCounter++;
        const container = document.getElementById('witnessesContainer');
        
        const section = document.createElement('div');
        section.className = 'dynamic-section';
        section.id = `witness-${witnessCounter}`;
        
        section.innerHTML = `
            <div class="section-header-row">
                <h4 class="section-title">
                    <i class="fas fa-user-check"></i> Witness ${witnessCounter}
                </h4>
                <button type="button" class="remove-btn" onclick="removeWitness(${witnessCounter})" title="Remove this witness">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="witness_name_${witnessCounter}">Witness Name</label>
                    <input type="text" class="form-control" id="witness_name_${witnessCounter}" 
                           name="witness_name[]" placeholder="Full name of witness">
                </div>
                <div class="form-group">
                    <label for="witness_student_id_${witnessCounter}">Student ID</label>
                    <input type="text" class="form-control" id="witness_student_id_${witnessCounter}" 
                           name="witness_student_id[]" placeholder="Student ID (if applicable)">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="witness_email_${witnessCounter}">Email Address</label>
                    <input type="email" class="form-control" id="witness_email_${witnessCounter}" 
                           name="witness_email[]" placeholder="Email address">
                </div>
                <div class="form-group">
                    <label for="witness_contact_${witnessCounter}">Contact Number</label>
                    <input type="tel" class="form-control" id="witness_contact_${witnessCounter}" 
                           name="witness_contact[]" placeholder="Contact number">
                </div>
            </div>
            
            <div class="form-group">
                <label for="witness_statement_${witnessCounter}">Witness Statement (Optional)</label>
                <textarea class="form-control" id="witness_statement_${witnessCounter}" 
                          name="witness_statement[]" rows="3" 
                          placeholder="What did the witness see/hear?"></textarea>
            </div>
        `;
        
        container.appendChild(section);
        updateCounters();
        
        // Scroll to the new section
        section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    function removeWitness(id) {
        const section = document.getElementById(`witness-${id}`);
        if (section) {
            section.remove();
            updateCounters();
        }
    }
    
    function updateCounters() {
        // Count visible co-complainant sections
        const coSections = document.querySelectorAll('[id^="coComplainant-"]');
        const witnessSections = document.querySelectorAll('[id^="witness-"]');
        
        document.getElementById('coComplainantCount').textContent = coSections.length;
        document.getElementById('witnessCount').textContent = witnessSections.length;
    }
    
    function updateComplaintAgainstFields() {
        const typeSelect = document.getElementById('complaint_against_type');
        const nameLabel = document.getElementById('against_name_label');
        const nameInput = document.getElementById('complaint_against_name');
        const deptInput = document.getElementById('complaint_against_department');
        const positionInput = document.getElementById('complaint_against_position');
        
        const selectedType = typeSelect.value;
        
        switch(selectedType) {
            case 'Faculty':
                nameLabel.textContent = 'Faculty Name';
                nameInput.placeholder = 'Name of faculty member';
                deptInput.placeholder = 'Department of faculty member';
                positionInput.placeholder = 'Designation (e.g., Professor, Assistant Professor)';
                break;
            case 'Staff':
                nameLabel.textContent = 'Staff Name';
                nameInput.placeholder = 'Name of staff member';
                deptInput.placeholder = 'Office/Department';
                positionInput.placeholder = 'Position/Role';
                break;
            case 'Student':
                nameLabel.textContent = 'Student Name';
                nameInput.placeholder = 'Name of student';
                deptInput.placeholder = 'Department/Program';
                positionInput.placeholder = 'Year/Semester';
                break;
            case 'Department':
                nameLabel.textContent = 'Department Name';
                nameInput.placeholder = 'Name of department';
                deptInput.disabled = true;
                deptInput.value = '';
                deptInput.placeholder = 'Same as above';
                positionInput.placeholder = 'Specific office/unit';
                break;
            case 'System':
                nameLabel.textContent = 'System/Process Name';
                nameInput.placeholder = 'Name of system or process';
                deptInput.placeholder = 'Related department';
                positionInput.placeholder = 'Specific aspect/component';
                break;
            default:
                nameLabel.textContent = 'Name';
                nameInput.placeholder = 'Name of person/department';
                deptInput.placeholder = 'Department name';
                positionInput.placeholder = 'Position or role';
        }
        
        deptInput.disabled = (selectedType === 'Department');
    }
    
    function showSummary() {
        const title = document.getElementById('title').value;
        const category = document.getElementById('category').value;
        const againstType = document.getElementById('complaint_against_type').value;
        const againstName = document.getElementById('complaint_against_name').value;
        
        const coSections = document.querySelectorAll('[id^="coComplainant-"]');
        const witnessSections = document.querySelectorAll('[id^="witness-"]');
        
        let summary = `
            <strong>Complaint Title:</strong> ${title || 'Not provided'}<br>
            <strong>Category:</strong> ${category || 'Not selected'}<br>
            <strong>Complaint Against:</strong> ${againstType || 'Not selected'} ${againstName ? `(${againstName})` : ''}<br>
            <strong>Co-Complainants:</strong> ${coSections.length} added<br>
            <strong>Witnesses:</strong> ${witnessSections.length} added
        `;
        
        document.getElementById('summaryContent').innerHTML = summary;
        document.getElementById('formSummary').style.display = 'block';
        
        // Scroll to summary
        document.getElementById('formSummary').scrollIntoView({ behavior: 'smooth' });
    }
    
    // Form validation
    document.getElementById('complaintForm').addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const category = document.getElementById('category').value;
        const againstType = document.getElementById('complaint_against_type').value;
        
        if (!title || !description || !category || !againstType) {
            e.preventDefault();
            alert('Please fill in all required fields (marked with *).');
            return false;
        }
        
        // Validate email fields
        const emailInputs = document.querySelectorAll('input[type="email"]');
        for (let input of emailInputs) {
            if (input.value.trim() && !validateEmail(input.value)) {
                e.preventDefault();
                alert('Please enter a valid email address for: ' + input.placeholder);
                input.focus();
                return false;
            }
        }
        
        // Ask for confirmation if there are co-complainants
        const coSections = document.querySelectorAll('[id^="coComplainant-"]');
        if (coSections.length > 0) {
            const confirmed = confirm(`You have added ${coSections.length} co-complainant(s). They will receive notifications about this complaint. Continue?`);
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    });
    
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Add one co-complainant field by default (optional)
    // Uncomment if you want one empty co-complainant field by default
    // window.onload = function() {
    //     addCoComplainant();
    // };
    </script>
</body>
</html>