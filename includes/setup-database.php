<?php
/**
 * Database setup and initialization script
 * This file creates the database and tables if they don't exist
 */
function createDatabaseTables($conn) {
    // Create system_settings table
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        site_name VARCHAR(100) NOT NULL DEFAULT 'College Complaint System',
        site_email VARCHAR(100) NOT NULL DEFAULT 'support@college.edu',
        contact_phone VARCHAR(20),
        address TEXT,
        timezone VARCHAR(50) DEFAULT 'Asia/Kolkata',
        logo_url VARCHAR(255),
        favicon_url VARCHAR(255),
        meta_description TEXT,
        meta_keywords TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        return "Error creating system_settings table: " . mysqli_error($conn);
    }
    
    // Insert default system settings
    $sql = "INSERT IGNORE INTO system_settings (id, site_name, site_email, timezone) 
            VALUES (1, 'College Complaint System', 'support@college.edu', 'Asia/Kolkata')";
    mysqli_query($conn, $sql);
    
    // Create complaint_settings table
    $sql = "CREATE TABLE IF NOT EXISTS complaint_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        auto_assign TINYINT(1) DEFAULT 1,
        escalation_days INT DEFAULT 3,
        reminder_frequency ENUM('daily', 'weekly', 'never') DEFAULT 'daily',
        max_file_size INT DEFAULT 5242880,
        allowed_file_types VARCHAR(255) DEFAULT 'jpg,jpeg,png,pdf,doc,docx',
        require_evidence TINYINT(1) DEFAULT 0,
        auto_close_days INT DEFAULT 30,
        notify_complainant TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        return "Error creating complaint_settings table: " . mysqli_error($conn);
    }
    
    // Insert default complaint settings
    $sql = "INSERT IGNORE INTO complaint_settings (id) VALUES (1)";
    mysqli_query($conn, $sql);
    
    // Create email_settings table
    $sql = "CREATE TABLE IF NOT EXISTS email_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        smtp_host VARCHAR(100) DEFAULT 'smtp.gmail.com',
        smtp_port INT DEFAULT 587,
        smtp_username VARCHAR(100),
        smtp_password TEXT,
        smtp_encryption ENUM('', 'ssl', 'tls') DEFAULT 'tls',
        from_email VARCHAR(100),
        from_name VARCHAR(100),
        email_tested TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        return "Error creating email_settings table: " . mysqli_error($conn);
    }
    
    // Insert default email settings
    $sql = "INSERT IGNORE INTO email_settings (id) VALUES (1)";
    mysqli_query($conn, $sql);
    
    // Create notification_settings table
    $sql = "CREATE TABLE IF NOT EXISTS notification_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email_notifications TINYINT(1) DEFAULT 1,
        new_complaint_notify TINYINT(1) DEFAULT 1,
        status_change_notify TINYINT(1) DEFAULT 1,
        student_reg_notify TINYINT(1) DEFAULT 1,
        daily_summary TINYINT(1) DEFAULT 1,
        weekly_report TINYINT(1) DEFAULT 0,
        monthly_report TINYINT(1) DEFAULT 1,
        notify_assigned_admin TINYINT(1) DEFAULT 1,
        notify_complainant TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        return "Error creating notification_settings table: " . mysqli_error($conn);
    }
    
    // Insert default notification settings
    $sql = "INSERT IGNORE INTO notification_settings (id) VALUES (1)";
    mysqli_query($conn, $sql);
    
    // Create security_settings table
    $sql = "CREATE TABLE IF NOT EXISTS security_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        max_login_attempts INT DEFAULT 5,
        lockout_time INT DEFAULT 30,
        session_timeout INT DEFAULT 60,
        password_expiry INT DEFAULT 90,
        two_factor_auth TINYINT(1) DEFAULT 0,
        ip_whitelist TEXT,
        force_ssl TINYINT(1) DEFAULT 0,
        require_strong_password TINYINT(1) DEFAULT 1,
        password_history_count INT DEFAULT 5,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        return "Error creating security_settings table: " . mysqli_error($conn);
    }
    
    // Insert default security settings
    $sql = "INSERT IGNORE INTO security_settings (id) VALUES (1)";
    mysqli_query($conn, $sql);
    
    // Create activity_logs table (if not exists from previous setup)
    $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        action_type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_action_type (action_type),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (!mysqli_query($conn, $sql)) {
        return "Error creating activity_logs table: " . mysqli_error($conn);
    }
    
    // Add permissions column to users table if not exists
    $sql = "SHOW COLUMNS FROM users LIKE 'permissions'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) == 0) {
        $sql = "ALTER TABLE users ADD COLUMN permissions TEXT AFTER status";
        mysqli_query($conn, $sql);
    }
    
    // Add additional columns to users table for admin profiles
    $columns_to_add = [
        'last_login' => 'TIMESTAMP NULL DEFAULT NULL',
        'password_changed_at' => 'TIMESTAMP NULL DEFAULT NULL',
        'failed_login_attempts' => 'INT DEFAULT 0',
        'last_failed_login' => 'TIMESTAMP NULL DEFAULT NULL',
        'deleted_at' => 'TIMESTAMP NULL DEFAULT NULL'
    ];
    
    foreach ($columns_to_add as $column => $definition) {
        $sql = "SHOW COLUMNS FROM users LIKE '$column'";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) == 0) {
            $sql = "ALTER TABLE users ADD COLUMN $column $definition";
            mysqli_query($conn, $sql);
        }
    }
    
    return true;
}
function setupDatabase($conn) {
    // Create database if it doesn't exist
    $createDBQuery = "CREATE DATABASE IF NOT EXISTS college_complaint_system 
                      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createDBQuery)) {
        return ["error" => "Failed to create database: " . mysqli_error($conn)];
    }
    
    // Select the database
    mysqli_select_db($conn, "college_complaint_system");
    
    // Create users table first (with all columns including notification columns)
    $createUsersTable = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        contact_number VARCHAR(15),
        password VARCHAR(255) NOT NULL,
        reset_token VARCHAR(255),
        reset_token_expiry DATETIME,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        college VARCHAR(100),
        course VARCHAR(100),
        year_level VARCHAR(20),
        date_of_birth DATE,
        address TEXT,
        profile_picture VARCHAR(255),
        last_login DATETIME,
        login_count INT DEFAULT 0,
        -- Email notification columns
        notifications_enabled BOOLEAN DEFAULT TRUE,
        email_verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_student_id (student_id),
        INDEX idx_status (status),
        INDEX idx_college (college),
        INDEX idx_notifications (notifications_enabled)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createUsersTable)) {
        return ["error" => "Failed to create users table: " . mysqli_error($conn)];
    }
    
    // Create complaints table (with notification tracking columns)
    $createComplaintsTable = "CREATE TABLE IF NOT EXISTS complaints (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT NOT NULL,
        category ENUM('Academic', 'Administrative', 'Facilities', 'Hostel', 'Library', 'Faculty', 'Staff', 'Student', 'Other') NOT NULL,
        status ENUM('Pending', 'Under Investigation', 'Resolved') DEFAULT 'Pending',
        
        -- Complaint against fields
        complaint_against_type ENUM('Faculty', 'Staff', 'Student', 'Department', 'System', 'Other') DEFAULT 'Other',
        complaint_against_name VARCHAR(100),
        complaint_against_department VARCHAR(100),
        complaint_against_position VARCHAR(100),
        complaint_against_details TEXT,
        
        attachment_path VARCHAR(255),
        admin_notes TEXT,
        resolved_date DATETIME,
        -- Email notification tracking
        last_notification_status VARCHAR(50),
        notifications_sent INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_category (category),
        INDEX idx_complaint_against_type (complaint_against_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createComplaintsTable)) {
        return ["error" => "Failed to create complaints table: " . mysqli_error($conn)];
    }
    
    // Create co_complainants table for multiple co-complainants
    $createCoComplainantsTable = "CREATE TABLE IF NOT EXISTS co_complainants (
        id INT PRIMARY KEY AUTO_INCREMENT,
        complaint_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        student_id VARCHAR(20),
        email VARCHAR(100),
        contact_number VARCHAR(15),
        relationship_to_complaint VARCHAR(100),
        additional_info TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
        INDEX idx_complaint_id (complaint_id),
        INDEX idx_student_id (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createCoComplainantsTable)) {
        return ["error" => "Failed to create co_complainants table: " . mysqli_error($conn)];
    }
    
    // Create complaint_witnesses table
    $createWitnessesTable = "CREATE TABLE IF NOT EXISTS complaint_witnesses (
        id INT PRIMARY KEY AUTO_INCREMENT,
        complaint_id INT NOT NULL,
        witness_name VARCHAR(100) NOT NULL,
        witness_student_id VARCHAR(20),
        witness_email VARCHAR(100),
        witness_contact VARCHAR(15),
        witness_statement TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
        INDEX idx_complaint_id (complaint_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createWitnessesTable)) {
        return ["error" => "Failed to create complaint_witnesses table: " . mysqli_error($conn)];
    }
    
    // Create admin_logs table for tracking admin actions
    $createAdminLogsTable = "CREATE TABLE IF NOT EXISTS admin_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        target_type VARCHAR(50),
        target_id INT,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_admin_id (admin_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createAdminLogsTable)) {
        return ["error" => "Failed to create admin_logs table: " . mysqli_error($conn)];
    }
    
    // Create reports table
    $createReportsTable = "CREATE TABLE IF NOT EXISTS reports (
        id INT PRIMARY KEY AUTO_INCREMENT,
        report_type VARCHAR(50) NOT NULL,
        report_name VARCHAR(200) NOT NULL,
        generated_by INT NOT NULL,
        file_path VARCHAR(255),
        parameters TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_report_type (report_type),
        INDEX idx_generated_by (generated_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createReportsTable)) {
        return ["error" => "Failed to create reports table: " . mysqli_error($conn)];
    }
    
    // Create email_templates table
    $createEmailTemplatesTable = "CREATE TABLE IF NOT EXISTS email_templates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        template_name VARCHAR(100) NOT NULL UNIQUE,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        variables TEXT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_template_name (template_name),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createEmailTemplatesTable)) {
        return ["error" => "Failed to create email_templates table: " . mysqli_error($conn)];
    }
    
    // Create email_logs table
    $createEmailLogsTable = "CREATE TABLE IF NOT EXISTS email_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        recipient_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        type VARCHAR(50) NOT NULL,
        reference_id VARCHAR(100),
        error_message TEXT,
        sent_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_recipient_email (recipient_email),
        INDEX idx_status (status),
        INDEX idx_type (type),
        INDEX idx_reference_id (reference_id),
        INDEX idx_created_at (created_at),
        INDEX idx_sent_at (sent_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createEmailLogsTable)) {
        return ["error" => "Failed to create email_logs table: " . mysqli_error($conn)];
    }
    
    // Create email_settings table
    $createEmailSettingsTable = "CREATE TABLE IF NOT EXISTS email_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_description VARCHAR(255),
        is_encrypted BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_setting_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createEmailSettingsTable)) {
        return ["error" => "Failed to create email_settings table: " . mysqli_error($conn)];
    }
    
    // Insert default email templates (with properly escaped quotes)
    $templates = [
        [
            'student_registration',
            'Welcome to University Management System',
            'Dear {student_name},

Welcome to our University Management System!

Your account has been successfully registered with the following details:
Student ID: {student_id}
Email: {email}
Status: {status}

Please use your student ID and the password you created to log in.

If you have any questions or need assistance, please don\'\'t hesitate to contact us.

Best regards,
University Administration',
            'student_name,student_id,email,status'
        ],
        [
            'complaint_status_change',
            'Complaint Status Update - {complaint_title}',
            'Dear {student_name},

Your complaint titled \"{complaint_title}\" has been updated.

Old Status: {old_status}
New Status: {new_status}
Admin Notes: {admin_notes}

Complaint ID: #{complaint_id}
Date: {update_date}

You can view the updated status by logging into your account.

Thank you for using our complaint system.

Best regards,
University Administration',
            'student_name,complaint_title,old_status,new_status,admin_notes,complaint_id,update_date'
        ],
        [
            'account_status_change',
            'Account Status Update',
            'Dear {student_name},

Your account status has been updated.

New Status: {status}
Reason: {reason}
Effective Date: {date}

If you believe this is an error or have any questions about this change, please contact the administration office immediately.

Best regards,
University Administration',
            'student_name,status,reason,date'
        ],
        [
            'complaint_submitted',
            'Complaint Submitted Successfully - #{complaint_id}',
            'Dear {student_name},

Your complaint has been submitted successfully.

Complaint Title: {complaint_title}
Complaint ID: #{complaint_id}
Submission Date: {submission_date}
Status: {status}

We will review your complaint and update you on its progress. You can track the status of your complaint by logging into your account.

Thank you for bringing this matter to our attention.

Best regards,
University Administration',
            'student_name,complaint_title,complaint_id,submission_date,status'
        ],
        [
            'password_reset',
            'Password Reset Request',
            'Dear {student_name},

We received a request to reset your password. If you made this request, please use the following link to reset your password:

Reset Link: {reset_link}
This link will expire in 1 hour.

If you did not request a password reset, please ignore this email or contact support if you have concerns.

Best regards,
University Administration',
            'student_name,reset_link'
        ]
    ];
    
    foreach ($templates as $template) {
        $checkQuery = "SELECT id FROM email_templates WHERE template_name = '" . mysqli_real_escape_string($conn, $template[0]) . "'";
        $result = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($result) == 0) {
            $template_name = mysqli_real_escape_string($conn, $template[0]);
            $subject = mysqli_real_escape_string($conn, $template[1]);
            $body = mysqli_real_escape_string($conn, $template[2]);
            $variables = mysqli_real_escape_string($conn, $template[3]);
            
            $insertQuery = "INSERT INTO email_templates (template_name, subject, body, variables, is_active) 
                           VALUES ('$template_name', '$subject', '$body', '$variables', TRUE)";
            
            if (!mysqli_query($conn, $insertQuery)) {
                return ["error" => "Failed to insert email template '{$template[0]}': " . mysqli_error($conn)];
            }
        }
    }
    
    // Insert default email settings
    $defaultSettings = [
        ['smtp_host', 'smtp.gmail.com', 'SMTP Server Host'],
        ['smtp_port', '587', 'SMTP Port'],
        ['smtp_secure', 'tls', 'SMTP Security Protocol'],
        ['smtp_auth', 'true', 'SMTP Authentication Required'],
        ['from_email', 'noreply@college.edu', 'Default From Email Address'],
        ['from_name', 'College Complaint System', 'Default From Name'],
        ['enable_registration_notifications', 'true', 'Enable student registration notifications'],
        ['enable_complaint_notifications', 'true', 'Enable complaint status notifications'],
        ['enable_account_status_notifications', 'true', 'Enable account status notifications'],
        ['email_retry_attempts', '3', 'Number of retry attempts for failed emails'],
        ['email_retry_delay', '60', 'Delay between retry attempts (seconds)']
    ];
    
    foreach ($defaultSettings as $setting) {
        $checkQuery = "SELECT id FROM email_settings WHERE setting_key = '" . mysqli_real_escape_string($conn, $setting[0]) . "'";
        $result = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($result) == 0) {
            $key = mysqli_real_escape_string($conn, $setting[0]);
            $value = mysqli_real_escape_string($conn, $setting[1]);
            $desc = mysqli_real_escape_string($conn, $setting[2]);
            
            $insertQuery = "INSERT INTO email_settings (setting_key, setting_value, setting_description) 
                           VALUES ('$key', '$value', '$desc')";
            
            if (!mysqli_query($conn, $insertQuery)) {
                return ["error" => "Failed to insert email setting '{$setting[0]}': " . mysqli_error($conn)];
            }
        }
    }
    
    // Check if admin user exists, if not create one
    $checkAdminQuery = "SELECT id FROM users WHERE email = 'admin@college.edu'";
    $result = mysqli_query($conn, $checkAdminQuery);
    
    if (mysqli_num_rows($result) == 0) {
        // Create admin user
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $createAdminQuery = "INSERT INTO users (student_id, name, email, contact_number, password, college, course, status) 
                            VALUES ('ADMIN001', 'System Administrator', 'admin@college.edu', '9876543210', '$hashedPassword', 'Administration', 'System Admin', 'active')";
        
        if (!mysqli_query($conn, $createAdminQuery)) {
            return ["error" => "Failed to create admin user: " . mysqli_error($conn)];
        }
        
        // Create sample student users
        $studentPassword = password_hash('student123', PASSWORD_DEFAULT);
        $students = [
            ['STU2024001', 'John Doe', 'john.doe@college.edu', '9876543211', 'Engineering', 'Computer Engineering'],
            ['STU2024002', 'Jane Smith', 'jane.smith@college.edu', '9876543212', 'Science', 'Computer Science'],
            ['STU2024003', 'Robert Johnson', 'robert.johnson@college.edu', '9876543213', 'Arts', 'Psychology'],
            ['STU2024004', 'Emily Davis', 'emily.davis@college.edu', '9876543214', 'Business', 'Business Administration']
        ];
        
        foreach ($students as $student) {
            $student_id = mysqli_real_escape_string($conn, $student[0]);
            $name = mysqli_real_escape_string($conn, $student[1]);
            $email = mysqli_real_escape_string($conn, $student[2]);
            $contact = mysqli_real_escape_string($conn, $student[3]);
            $college = mysqli_real_escape_string($conn, $student[4]);
            $course = mysqli_real_escape_string($conn, $student[5]);
            
            $createStudentQuery = "INSERT INTO users (student_id, name, email, contact_number, password, college, course, status, notifications_enabled) 
                                  VALUES ('$student_id', '$name', '$email', '$contact', '$studentPassword', '$college', '$course', 'active', TRUE)";
            
            if (!mysqli_query($conn, $createStudentQuery)) {
                return ["error" => "Failed to create student user '{$student[1]}': " . mysqli_error($conn)];
            }
        }
        
        // Add more sample data for other colleges
        $college_courses = [
            'Engineering' => ['Electrical Engineering', 'Mechanical Engineering', 'Civil Engineering'],
            'Science' => ['Mathematics', 'Physics', 'Chemistry', 'Biology'],
            'Arts' => ['English Literature', 'History', 'Political Science', 'Sociology'],
            'Business' => ['Accounting', 'Finance', 'Marketing', 'Economics'],
            'Medicine' => ['Medicine', 'Nursing', 'Pharmacy', 'Dentistry'],
            'Law' => ['Law', 'Criminal Justice'],
            'Education' => ['Elementary Education', 'Secondary Education', 'Special Education']
        ];
        
        // Add additional sample users
        $studentIdCounter = 2024005;
        foreach ($college_courses as $college => $courses) {
            foreach ($courses as $course) {
                $studentId = 'STU' . $studentIdCounter++;
                $studentName = 'Sample Student ' . ($studentIdCounter - 2024005);
                $studentEmail = strtolower(str_replace(' ', '.', $studentName)) . '@college.edu';
                $studentEmail = str_replace('.', '', $studentEmail); // Clean up email
                
                $studentIdEsc = mysqli_real_escape_string($conn, $studentId);
                $studentNameEsc = mysqli_real_escape_string($conn, $studentName);
                $studentEmailEsc = mysqli_real_escape_string($conn, $studentEmail);
                $collegeEsc = mysqli_real_escape_string($conn, $college);
                $courseEsc = mysqli_real_escape_string($conn, $course);
                $contact = '9876543' . rand(100, 999);
                
                $createStudentQuery = "INSERT INTO users (student_id, name, email, contact_number, password, college, course, status, notifications_enabled) 
                                      VALUES ('$studentIdEsc', '$studentNameEsc', '$studentEmailEsc', '$contact', '$studentPassword', '$collegeEsc', '$courseEsc', 'active', TRUE)";
                
                mysqli_query($conn, $createStudentQuery);
            }
        }
        
        // Create some sample complaints for testing email notifications
        createSampleComplaints($conn);
        
        return [
            "success" => true,
            "message" => "Database setup completed successfully with email notification system!",
            "admin_credentials" => [
                "email" => "admin@college.edu",
                "password" => "admin123"
            ],
            "student_credentials" => [
                [
                    "email" => "john.doe@college.edu",
                    "password" => "student123"
                ],
                [
                    "email" => "jane.smith@college.edu", 
                    "password" => "student123"
                ]
            ],
            "email_features" => [
                "templates_created" => count($templates),
                "default_settings" => count($defaultSettings),
                "notification_types" => ["student_registration", "complaint_status_change", "account_status_change", "complaint_submitted", "password_reset"]
            ]
        ];
    }
    
    return [
        "success" => true,
        "message" => "Database already exists and is ready to use."
    ];
}

// Function to create sample complaints for testing
function createSampleComplaints($conn) {
    // Get some student IDs
    $studentQuery = "SELECT id, student_id, name, email FROM users WHERE student_id LIKE 'STU%' LIMIT 5";
    $result = mysqli_query($conn, $studentQuery);
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    
    if (empty($students)) return;
    
    // Sample complaints data
    $sampleComplaints = [
        [
            'title' => 'Network Issues in Computer Lab',
            'description' => 'The internet connection in computer lab A is very slow during peak hours. Unable to access online learning materials.',
            'category' => 'Facilities',
            'status' => 'Pending',
            'complaint_against_type' => 'Department',
            'complaint_against_name' => 'IT Department',
            'complaint_against_details' => 'Network infrastructure needs upgrade'
        ],
        [
            'title' => 'Late Submission of Grades',
            'description' => 'Professor has not submitted grades for the mid-term examination even after 2 weeks of submission deadline.',
            'category' => 'Academic',
            'status' => 'Under Investigation',
            'complaint_against_type' => 'Faculty',
            'complaint_against_name' => 'Dr. Smith',
            'complaint_against_position' => 'Mathematics Professor'
        ],
        [
            'title' => 'Library Book Availability',
            'description' => 'Required textbooks for Computer Science 101 are always checked out. Need more copies.',
            'category' => 'Library',
            'status' => 'Resolved',
            'complaint_against_type' => 'Department',
            'complaint_against_name' => 'Library Department',
            'complaint_against_details' => 'Insufficient copies of textbooks'
        ],
        [
            'title' => 'Hostel Water Supply Issues',
            'description' => 'No water supply in Hostel B during morning hours for the past week.',
            'category' => 'Hostel',
            'status' => 'Pending',
            'complaint_against_type' => 'Department',
            'complaint_against_name' => 'Hostel Maintenance',
            'complaint_against_details' => 'Water pump issues'
        ],
        [
            'title' => 'Unfair Grading in Physics',
            'description' => 'Grading in Physics 201 appears to be inconsistent and unfair compared to other sections.',
            'category' => 'Academic',
            'status' => 'Under Investigation',
            'complaint_against_type' => 'Faculty',
            'complaint_against_name' => 'Dr. Johnson',
            'complaint_against_position' => 'Physics Professor'
        ]
    ];
    
    foreach ($sampleComplaints as $index => $complaintData) {
        $student = $students[$index % count($students)];
        
        // Escape all input values
        $title = mysqli_real_escape_string($conn, $complaintData['title']);
        $description = mysqli_real_escape_string($conn, $complaintData['description']);
        $category = mysqli_real_escape_string($conn, $complaintData['category']);
        $status = mysqli_real_escape_string($conn, $complaintData['status']);
        $against_type = mysqli_real_escape_string($conn, $complaintData['complaint_against_type']);
        $against_name = mysqli_real_escape_string($conn, $complaintData['complaint_against_name']);
        $against_position = isset($complaintData['complaint_against_position']) ? mysqli_real_escape_string($conn, $complaintData['complaint_against_position']) : '';
        $against_details = isset($complaintData['complaint_against_details']) ? mysqli_real_escape_string($conn, $complaintData['complaint_against_details']) : '';
        
        $insertQuery = "INSERT INTO complaints (
            user_id, title, description, category, status,
            complaint_against_type, complaint_against_name, 
            complaint_against_position, complaint_against_details,
            notifications_sent
        ) VALUES (
            '{$student['id']}', 
            '$title', 
            '$description', 
            '$category', 
            '$status',
            '$against_type', 
            '$against_name', 
            '$against_position', 
            '$against_details',
            1
        )";
        
        mysqli_query($conn, $insertQuery);
    }
}

function checkDatabaseExists($conn, $dbname) {
    $result = mysqli_query($conn, "SHOW DATABASES LIKE '$dbname'");
    return mysqli_num_rows($result) > 0;
}

function checkTablesExist($conn) {
    $tables = [
        'users', 
        'complaints', 
        'co_complainants', 
        'complaint_witnesses', 
        'admin_logs', 
        'reports',
        'email_templates',
        'email_logs',
        'email_settings'
    ];
    $missingTables = [];
    
    mysqli_select_db($conn, "college_complaint_system");
    
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) == 0) {
            $missingTables[] = $table;
        }
    }
    
    return $missingTables;
}

// Function to update existing tables if needed
function updateTablesIfNeeded($conn) {
    $updates = [];
    
    mysqli_select_db($conn, "college_complaint_system");
    
    // Remove old single co-complainant fields if they exist
    $oldFields = [
        'co_complainant_name',
        'co_complainant_student_id',
        'co_complainant_email',
        'co_complainant_contact'
    ];
    
    foreach ($oldFields as $field) {
        $checkQuery = "SHOW COLUMNS FROM complaints LIKE '$field'";
        $result = mysqli_query($conn, $checkQuery);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $alterQuery = "ALTER TABLE complaints DROP COLUMN $field";
            if (mysqli_query($conn, $alterQuery)) {
                $updates[] = "Removed old $field column";
            }
        }
    }
    
    // Remove priority column if it exists
    $checkPriorityQuery = "SHOW COLUMNS FROM complaints LIKE 'priority'";
    $result = mysqli_query($conn, $checkPriorityQuery);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $removePriorityQuery = "ALTER TABLE complaints DROP COLUMN priority";
        if (mysqli_query($conn, $removePriorityQuery)) {
            $updates[] = "Removed priority column";
        }
    }
    
    // Check and add new columns to users table
    $userColumns = [
        "status" => "ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'",
        "college" => "ADD COLUMN IF NOT EXISTS college VARCHAR(100)",
        "course" => "ADD COLUMN IF NOT EXISTS course VARCHAR(100)",
        "year_level" => "ADD COLUMN IF NOT EXISTS year_level VARCHAR(20)",
        "date_of_birth" => "ADD COLUMN IF NOT EXISTS date_of_birth DATE",
        "address" => "ADD COLUMN IF NOT EXISTS address TEXT",
        "profile_picture" => "ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255)",
        "last_login" => "ADD COLUMN IF NOT EXISTS last_login DATETIME",
        "login_count" => "ADD COLUMN IF NOT EXISTS login_count INT DEFAULT 0",
        "notifications_enabled" => "ADD COLUMN IF NOT EXISTS notifications_enabled BOOLEAN DEFAULT TRUE",
        "email_verified" => "ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE"
    ];
    
    foreach ($userColumns as $columnName => $alterStatement) {
        $checkQuery = "SHOW COLUMNS FROM users LIKE '$columnName'";
        $result = mysqli_query($conn, $checkQuery);
        
        if ($result && mysqli_num_rows($result) == 0) {
            $alterQuery = "ALTER TABLE users $alterStatement";
            if (mysqli_query($conn, $alterQuery)) {
                $updates[] = "Added $columnName column to users table";
            }
        }
    }
    
    // Check and add notification columns to complaints table
    $complaintColumns = [
        "last_notification_status" => "ADD COLUMN IF NOT EXISTS last_notification_status VARCHAR(50)",
        "notifications_sent" => "ADD COLUMN IF NOT EXISTS notifications_sent INT DEFAULT 0"
    ];
    
    foreach ($complaintColumns as $columnName => $alterStatement) {
        $checkQuery = "SHOW COLUMNS FROM complaints LIKE '$columnName'";
        $result = mysqli_query($conn, $checkQuery);
        
        if ($result && mysqli_num_rows($result) == 0) {
            $alterQuery = "ALTER TABLE complaints $alterStatement";
            if (mysqli_query($conn, $alterQuery)) {
                $updates[] = "Added $columnName column to complaints table";
            }
        }
    }
    
    // Update existing users to have notifications enabled by default
    $updateUsersQuery = "UPDATE users SET notifications_enabled = TRUE WHERE notifications_enabled IS NULL";
    if (mysqli_query($conn, $updateUsersQuery)) {
        $updates[] = "Updated existing users to enable notifications by default";
    }
    
    // Create email notification tables if they don't exist
    $emailTables = [
        "email_templates" => "CREATE TABLE IF NOT EXISTS email_templates (
            id INT PRIMARY KEY AUTO_INCREMENT,
            template_name VARCHAR(100) NOT NULL UNIQUE,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            variables TEXT NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "email_logs" => "CREATE TABLE IF NOT EXISTS email_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            recipient_email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            type VARCHAR(50) NOT NULL,
            reference_id VARCHAR(100),
            error_message TEXT,
            sent_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "email_settings" => "CREATE TABLE IF NOT EXISTS email_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_description VARCHAR(255),
            is_encrypted BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    foreach ($emailTables as $tableName => $createQuery) {
        $checkQuery = "SHOW TABLES LIKE '$tableName'";
        $result = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($result) == 0) {
            if (mysqli_query($conn, $createQuery)) {
                $updates[] = "Created $tableName table";
                
                // Insert default data for email_templates
                if ($tableName === 'email_templates') {
                    $templates = [
                        ['student_registration', 'Welcome to University Management System', 'Dear {student_name}, Welcome to our University Management System!', 'student_name,student_id,email,status'],
                        ['complaint_status_change', 'Complaint Status Update - {complaint_title}', 'Dear {student_name}, Your complaint has been updated.', 'student_name,complaint_title,old_status,new_status,admin_notes'],
                        ['account_status_change', 'Account Status Update', 'Dear {student_name}, Your account status has been updated.', 'student_name,status,reason,date']
                    ];
                    
                    foreach ($templates as $template) {
                        $template_name = mysqli_real_escape_string($conn, $template[0]);
                        $subject = mysqli_real_escape_string($conn, $template[1]);
                        $body = mysqli_real_escape_string($conn, $template[2]);
                        $variables = mysqli_real_escape_string($conn, $template[3]);
                        
                        $insertQuery = "INSERT IGNORE INTO email_templates (template_name, subject, body, variables, is_active) 
                                       VALUES ('$template_name', '$subject', '$body', '$variables', TRUE)";
                        mysqli_query($conn, $insertQuery);
                    }
                }
                
                // Insert default data for email_settings
                if ($tableName === 'email_settings') {
                    $defaultSettings = [
                        ['smtp_host', 'smtp.gmail.com', 'SMTP Server Host'],
                        ['smtp_port', '587', 'SMTP Port'],
                        ['from_email', 'noreply@college.edu', 'Default From Email Address'],
                        ['from_name', 'College Complaint System', 'Default From Name']
                    ];
                    
                    foreach ($defaultSettings as $setting) {
                        $key = mysqli_real_escape_string($conn, $setting[0]);
                        $value = mysqli_real_escape_string($conn, $setting[1]);
                        $desc = mysqli_real_escape_string($conn, $setting[2]);
                        
                        $insertQuery = "INSERT IGNORE INTO email_settings (setting_key, setting_value, setting_description) 
                                       VALUES ('$key', '$value', '$desc')";
                        mysqli_query($conn, $insertQuery);
                    }
                }
            }
        }
    }
    
    return $updates;
}
?>