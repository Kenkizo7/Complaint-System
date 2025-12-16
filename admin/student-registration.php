<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

if ($_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Define colleges based on your database
$colleges = [
    'College of Engineering',
    'College of Science', 
    'College of Medicine',
    'College of Law',
    'College of Teacher Education'
];

// Define year levels
$years = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $college = mysqli_real_escape_string($conn, $_POST['college']);
    $year_level = mysqli_real_escape_string($conn, $_POST['year_level']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validation
    $errors = [];
    
    if (empty($student_id)) {
        $errors[] = "Student ID is required";
    } elseif (!preg_match('/^[A-Z0-9]{5,20}$/', $student_id)) {
        $errors[] = "Student ID must be 5-20 characters (letters and numbers only)";
    }
    
    if (empty($name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($college)) {
        $errors[] = "College is required";
    }
    
    if (empty($year_level)) {
        $errors[] = "Year level is required";
    }
    
    // Check if student ID already exists
    $check_id_sql = "SELECT id FROM users WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $check_id_sql);
    mysqli_stmt_bind_param($stmt, 's', $student_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = "Student ID already exists";
    }
    
    // Check if email already exists
    $check_email_sql = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_email_sql);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = "Email already exists";
    }
    
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert student
        $sql = "INSERT INTO users (student_id, name, email, contact_number, password, 
                                   college, year_level, course, date_of_birth, address, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sssssssssss', 
            $student_id, $name, $email, $contact_number, $hashed_password,
            $college, $year_level, $course, $date_of_birth, $address, $status
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $student_user_id = mysqli_insert_id($conn);
            
            // Log admin action
            logActivity($conn, $_SESSION['user_id'], "Registered new student: $student_id ($name)");
            
            // Log admin action in admin_logs
            $admin_log_sql = "INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) 
                             VALUES (?, 'student_registration', 'user', ?, ?)";
            $admin_log_stmt = mysqli_prepare($conn, $admin_log_sql);
            $details = "Registered student: $name ($student_id) - College: $college - Status: $status";
            mysqli_stmt_bind_param($admin_log_stmt, 'iis', $_SESSION['user_id'], $student_user_id, $details);
            mysqli_stmt_execute($admin_log_stmt);
            
            $message = "Student registered successfully! Student ID: $student_id";
            
            // Clear form (optional)
            if (isset($_POST['clear_form']) && $_POST['clear_form'] == 'yes') {
                // Form will be cleared by not setting the values
            } else {
                // Keep form data for editing
                $_POST = [];
            }
        } else {
            $error = "Error registering student: " . mysqli_error($conn);
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include 'admin-sidebar-styles.php'; ?>
    <style>
        
        .registration-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .registration-form {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .section-header i {
            color: #3498db;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group label.required::after {
            content: " *";
            color: #e74c3c;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .form-control:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        .password-strength {
            margin-top: 5px;
            height: 5px;
            background-color: #eee;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .strength-weak { background-color: #e74c3c; width: 25%; }
        .strength-fair { background-color: #f39c12; width: 50%; }
        .strength-good { background-color: #3498db; width: 75%; }
        .strength-strong { background-color: #27ae60; width: 100%; }
        
        .password-hint {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .student-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .preview-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3498db, #2980b9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
            margin: 0 auto 15px;
        }
        
        .preview-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .preview-field {
            padding: 10px;
            background: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .preview-field strong {
            display: block;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .preview-field span {
            color: #495057;
            font-size: 14px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn-register {
            padding: 12px 30px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-clear {
            padding: 12px 30px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .auto-generate {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-generate {
            padding: 6px 12px;
            font-size: 12px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-generate:hover {
            background-color: #5a6268;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .quick-action-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .quick-action-btn:hover {
            background-color: #f8f9fa;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-register, .btn-clear {
                width: 100%;
                justify-content: center;
            }
            
            .preview-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <div class="page-title">
                    <h1><i class="fas fa-user-plus"></i> Student Registration</h1>
                    <p>Register new students in the system</p>
                </div>
                <div class="header-actions">
                    <a href="manage-students.php" class="btn">
                        <i class="fas fa-users"></i> View All Students
                    </a>
                    <div class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                </div>
            </header>
            
            <div class="registration-container">
                <!-- Success/Error Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                        <div style="margin-top: 10px;">
                            <a href="student-registration.php" class="btn btn-success" style="padding: 8px 15px;">
                                <i class="fas fa-plus"></i> Register Another Student
                            </a>
                            <a href="manage-students.php" class="btn" style="padding: 8px 15px;">
                                <i class="fas fa-eye"></i> View Student
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Student Preview -->
                <div class="student-preview" id="studentPreview" style="display: none;">
                    <div class="preview-header">
                        <h3 style="margin: 0; color: #2c3e50;">Student Preview</h3>
                        <button type="button" class="btn" onclick="hidePreview()">
                            <i class="fas fa-times"></i> Hide
                        </button>
                    </div>
                    <div id="previewContent"></div>
                </div>
                
                <!-- Registration Form -->
                <form method="POST" action="" class="registration-form" id="registrationForm">
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-id-card"></i>
                            <h3>Basic Information</h3>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="student_id" class="required">Student ID</label>
                                <input type="text" id="student_id" name="student_id" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>"
                                       placeholder="e.g., STU2024001"
                                       pattern="[A-Z0-9]{5,20}"
                                       title="5-20 characters (letters and numbers only)">
                                <div class="auto-generate">
                                    <span style="font-size: 12px; color: #7f8c8d;">Auto-generate ID:</span>
                                    <button type="button" class="btn-generate" onclick="generateStudentId()">
                                        <i class="fas fa-magic"></i> Generate
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="name" class="required">Full Name</label>
                                <input type="text" id="name" name="name" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       placeholder="Last Name, First Name Middle Initial">
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="required">Email Address</label>
                                <input type="email" id="email" name="email" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="student@college.edu">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_number">Contact Number</label>
                                <input type="tel" id="contact_number" name="contact_number" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>"
                                       placeholder="0912 345 6789">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>"
                                       max="<?php echo date('Y-m-d', strtotime('-15 years')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" class="form-control" rows="3"
                                          placeholder="Complete permanent address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-graduation-cap"></i>
                            <h3>Academic Information</h3>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="college" class="required">College</label>
                                <select id="college" name="college" class="form-control" required>
                                    <option value="">Select College</option>
                                    <?php foreach ($colleges as $college): ?>
                                        <option value="<?php echo $college; ?>" 
                                            <?php echo (isset($_POST['college']) && $_POST['college'] == $college) ? 'selected' : ''; ?>>
                                            <?php echo $college; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="year_level" class="required">Year Level</label>
                                <select id="year_level" name="year_level" class="form-control" required>
                                    <option value="">Select Year Level</option>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?php echo $year; ?>"
                                            <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == $year) ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="course">Course/Program</label>
                                <input type="text" id="course" name="course" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['course'] ?? ''); ?>"
                                       placeholder="e.g., Computer Science">
                            </div>
                            
                            <div class="form-group">
                                <label for="status" class="required">Account Status</label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo (isset($_POST['status']) && $_POST['status'] == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-lock"></i>
                            <h3>Account Security</h3>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="password" class="required">Password</label>
                                <input type="password" id="password" name="password" 
                                       class="form-control" required
                                       placeholder="Minimum 6 characters"
                                       minlength="6"
                                       oninput="checkPasswordStrength(this.value)">
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrength"></div>
                                </div>
                                <div class="password-hint" id="passwordHint"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="required">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control" required
                                       placeholder="Re-enter password"
                                       oninput="checkPasswordMatch()">
                                <div class="password-hint" id="passwordMatch"></div>
                            </div>
                            
                            <div class="form-group">
                                <label>Auto-generate Password</label>
                                <div class="quick-actions">
                                    <button type="button" class="quick-action-btn" onclick="generatePassword(8)">
                                        <i class="fas fa-key"></i> Generate 8 chars
                                    </button>
                                    <button type="button" class="quick-action-btn" onclick="generatePassword(12)">
                                        <i class="fas fa-key"></i> Generate 12 chars
                                    </button>
                                    <button type="button" class="quick-action-btn" onclick="generatePassword(16)">
                                        <i class="fas fa-key"></i> Generate 16 chars
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-cog"></i>
                            <h3>Registration Options</h3>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="clear_form" value="yes" checked>
                                <span>Clear form after successful registration</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="send_welcome_email" value="yes">
                                <span>Send welcome email to student</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-clear" onclick="clearForm()">
                            <i class="fas fa-eraser"></i> Clear Form
                        </button>
                        <button type="button" class="btn" onclick="showPreview()">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button type="submit" class="btn btn-primary btn-register">
                            <i class="fas fa-user-plus"></i> Register Student
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
    // Mobile menu toggle
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // Generate Student ID
    function generateStudentId() {
        const year = new Date().getFullYear();
        const randomNum = Math.floor(1000 + Math.random() * 9000);
        const studentId = `STU${year}${randomNum}`;
        
        document.getElementById('student_id').value = studentId;
        document.getElementById('student_id').dispatchEvent(new Event('input'));
    }
    
    // Password strength checker
    function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('passwordStrength');
        const hint = document.getElementById('passwordHint');
        
        let strength = 0;
        let hintText = '';
        
        // Length check
        if (password.length >= 6) strength += 25;
        
        // Lowercase check
        if (/[a-z]/.test(password)) strength += 25;
        
        // Uppercase check
        if (/[A-Z]/.test(password)) strength += 25;
        
        // Number/special char check
        if (/[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password)) strength += 25;
        
        // Update strength bar
        strengthBar.className = 'password-strength-bar';
        
        if (strength >= 100) {
            strengthBar.classList.add('strength-strong');
            hintText = 'Strong password';
        } else if (strength >= 75) {
            strengthBar.classList.add('strength-good');
            hintText = 'Good password';
        } else if (strength >= 50) {
            strengthBar.classList.add('strength-fair');
            hintText = 'Fair password - add uppercase letters or numbers';
        } else if (strength >= 25) {
            strengthBar.classList.add('strength-weak');
            hintText = 'Weak password - needs improvement';
        } else {
            hintText = 'Enter a password';
        }
        
        hint.textContent = hintText;
    }
    
    // Check password match
    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const matchHint = document.getElementById('passwordMatch');
        
        if (confirmPassword === '') {
            matchHint.textContent = '';
            matchHint.style.color = '';
        } else if (password === confirmPassword) {
            matchHint.textContent = '✓ Passwords match';
            matchHint.style.color = '#27ae60';
        } else {
            matchHint.textContent = '✗ Passwords do not match';
            matchHint.style.color = '#e74c3c';
        }
    }
    
    // Generate password
    function generatePassword(length) {
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
        let password = "";
        
        // Ensure at least one of each type
        password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)];
        password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)];
        password += "0123456789"[Math.floor(Math.random() * 10)];
        password += "!@#$%^&*"[Math.floor(Math.random() * 8)];
        
        // Fill the rest
        for (let i = 4; i < length; i++) {
            password += charset[Math.floor(Math.random() * charset.length)];
        }
        
        // Shuffle the password
        password = password.split('').sort(() => 0.5 - Math.random()).join('');
        
        // Set password field
        document.getElementById('password').value = password;
        document.getElementById('confirm_password').value = password;
        
        // Trigger events
        checkPasswordStrength(password);
        checkPasswordMatch();
    }
    
    // Show preview
    function showPreview() {
        const form = document.getElementById('registrationForm');
        const preview = document.getElementById('studentPreview');
        const previewContent = document.getElementById('previewContent');
        
        // Get form values
        const studentId = document.getElementById('student_id').value || 'Not set';
        const name = document.getElementById('name').value || 'Not set';
        const email = document.getElementById('email').value || 'Not set';
        const contact = document.getElementById('contact_number').value || 'Not set';
        const dob = document.getElementById('date_of_birth').value || 'Not set';
        const address = document.getElementById('address').value || 'Not set';
        const college = document.getElementById('college').value || 'Not set';
        const yearLevel = document.getElementById('year_level').value || 'Not set';
        const course = document.getElementById('course').value || 'Not set';
        const status = document.getElementById('status').value || 'active';
        
        // Format date of birth
        let dobFormatted = 'Not set';
        if (dob !== 'Not set') {
            const dobDate = new Date(dob);
            dobFormatted = dobDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        // Create preview content
        previewContent.innerHTML = `
            <div class="preview-avatar">
                ${name.charAt(0).toUpperCase()}
            </div>
            
            <div class="preview-info">
                <div class="preview-field">
                    <strong>Student ID</strong>
                    <span>${studentId}</span>
                </div>
                <div class="preview-field">
                    <strong>Full Name</strong>
                    <span>${name}</span>
                </div>
                <div class="preview-field">
                    <strong>Email</strong>
                    <span>${email}</span>
                </div>
                <div class="preview-field">
                    <strong>Contact Number</strong>
                    <span>${contact}</span>
                </div>
                <div class="preview-field">
                    <strong>Date of Birth</strong>
                    <span>${dobFormatted}</span>
                </div>
                <div class="preview-field">
                    <strong>College</strong>
                    <span>${college}</span>
                </div>
                <div class="preview-field">
                    <strong>Year Level</strong>
                    <span>${yearLevel}</span>
                </div>
                <div class="preview-field">
                    <strong>Course</strong>
                    <span>${course}</span>
                </div>
                <div class="preview-field">
                    <strong>Account Status</strong>
                    <span style="text-transform: capitalize;">${status}</span>
                </div>
            </div>
            
            ${address !== 'Not set' ? `
            <div style="margin-top: 15px;">
                <div class="preview-field">
                    <strong>Address</strong>
                    <span>${address}</span>
                </div>
            </div>
            ` : ''}
        `;
        
        // Show preview
        preview.style.display = 'block';
        
        // Scroll to preview
        preview.scrollIntoView({ behavior: 'smooth' });
    }
    
    // Hide preview
    function hidePreview() {
        document.getElementById('studentPreview').style.display = 'none';
    }
    
    // Clear form
    function clearForm() {
        if (confirm('Are you sure you want to clear the form? All entered data will be lost.')) {
            document.getElementById('registrationForm').reset();
            document.getElementById('passwordStrength').className = 'password-strength-bar';
            document.getElementById('passwordHint').textContent = '';
            document.getElementById('passwordMatch').textContent = '';
            hidePreview();
        }
    }
    
    // Form validation before submission
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        const studentId = document.getElementById('student_id').value;
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const college = document.getElementById('college').value;
        const yearLevel = document.getElementById('year_level').value;
        
        // Check required fields
        if (!studentId || !name || !email || !password || !college || !yearLevel) {
            e.preventDefault();
            alert('Please fill in all required fields (marked with *).');
            return false;
        }
        
        // Check password match
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match. Please check and try again.');
            return false;
        }
        
        // Check password strength
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long.');
            return false;
        }
        
        // Check student ID format
        if (!/^[A-Z0-9]{5,20}$/.test(studentId)) {
            e.preventDefault();
            alert('Student ID must be 5-20 characters (letters and numbers only).');
            return false;
        }
        
        // Check email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            return false;
        }
        
        // Confirm submission
        const confirmed = confirm(`Register student ${name} (${studentId})?`);
        if (!confirmed) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // Auto-generate student ID on page load (optional)
    window.onload = function() {
        // Uncomment to auto-generate ID on page load
        // generateStudentId();
    };
    </script>
</body>
</html>