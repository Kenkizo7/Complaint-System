<?php
/**
 * Database setup and initialization script
 * This file creates the database and tables if they don't exist
 */

function setupDatabase($conn) {
    // Create database if it doesn't exist
    $createDBQuery = "CREATE DATABASE IF NOT EXISTS college_complaint_system 
                      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createDBQuery)) {
        return ["error" => "Failed to create database: " . mysqli_error($conn)];
    }
    
    // Select the database
    mysqli_select_db($conn, "college_complaint_system");
    
    // Add to setupDatabase function, after creating users table:

// Add user status and college fields to users table
$alterUsersTable = "ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS college VARCHAR(100),
    ADD COLUMN IF NOT EXISTS course VARCHAR(100),
    ADD COLUMN IF NOT EXISTS year_level VARCHAR(20),
    ADD COLUMN IF NOT EXISTS date_of_birth DATE,
    ADD COLUMN IF NOT EXISTS address TEXT,
    ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255),
    ADD COLUMN IF NOT EXISTS last_login DATETIME,
    ADD COLUMN IF NOT EXISTS login_count INT DEFAULT 0,
    ADD INDEX idx_status (status),
    ADD INDEX idx_college (college)";

if (!mysqli_query($conn, $alterUsersTable)) {
    // If ALTER fails, try creating columns individually
    $columnsToAdd = [
        "status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'",
        "college VARCHAR(100)",
        "course VARCHAR(100)",
        "year_level VARCHAR(20)",
        "date_of_birth DATE",
        "address TEXT",
        "profile_picture VARCHAR(255)",
        "last_login DATETIME",
        "login_count INT DEFAULT 0"
    ];
    
    foreach ($columnsToAdd as $column) {
        $checkColumn = explode(' ', $column)[0];
        $checkQuery = "SHOW COLUMNS FROM users LIKE '$checkColumn'";
        $result = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($result) == 0) {
            $addColumnQuery = "ALTER TABLE users ADD COLUMN $column";
            mysqli_query($conn, $addColumnQuery);
        }
    }
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

// Add sample colleges and update existing users
$colleges = ['College of Engineering', 'College of Science', 'College of Medicine', 'College of Law', 'College of Teacher Education'];
foreach ($colleges as $college) {
    $updateQuery = "UPDATE users SET college = '$college' 
                   WHERE college IS NULL AND student_id LIKE 'STU%' 
                   ORDER BY RAND() LIMIT 3";
    mysqli_query($conn, $updateQuery);
}
    // Create users table
    $createUsersTable = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        contact_number VARCHAR(15),
        password VARCHAR(255) NOT NULL,
        reset_token VARCHAR(255),
        reset_token_expiry DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_student_id (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createUsersTable)) {
        return ["error" => "Failed to create users table: " . mysqli_error($conn)];
    }
    
    // Create complaints table (removed single co-complainant fields)
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
    
    // Check if admin user exists, if not create one
    $checkAdminQuery = "SELECT id FROM users WHERE email = 'admin@college.edu'";
    $result = mysqli_query($conn, $checkAdminQuery);
    
    if (mysqli_num_rows($result) == 0) {
        // Create admin user
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $createAdminQuery = "INSERT INTO users (student_id, name, email, contact_number, password) 
                            VALUES ('ADMIN001', 'System Administrator', 'admin@college.edu', '9876543210', '$hashedPassword')";
        
        if (!mysqli_query($conn, $createAdminQuery)) {
            return ["error" => "Failed to create admin user: " . mysqli_error($conn)];
        }
        
        // Create sample student users
        $studentPassword = password_hash('student123', PASSWORD_DEFAULT);
        $students = [
            ['STU2024001', 'John Doe', 'john.doe@college.edu', '9876543211'],
            ['STU2024002', 'Jane Smith', 'jane.smith@college.edu', '9876543212'],
            ['STU2024003', 'Robert Johnson', 'robert.johnson@college.edu', '9876543213'],
            ['STU2024004', 'Emily Davis', 'emily.davis@college.edu', '9876543214']
        ];
        
        foreach ($students as $student) {
            $createStudentQuery = "INSERT INTO users (student_id, name, email, contact_number, password) 
                                  VALUES ('{$student[0]}', '{$student[1]}', '{$student[2]}', '{$student[3]}', '$studentPassword')";
            mysqli_query($conn, $createStudentQuery);
        }
        
        return [
            "success" => true,
            "message" => "Database setup completed successfully!",
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
            ]
        ];
    }
    
    return [
        "success" => true,
        "message" => "Database already exists and is ready to use."
    ];
}

function checkDatabaseExists($conn, $dbname) {
    $result = mysqli_query($conn, "SHOW DATABASES LIKE '$dbname'");
    return mysqli_num_rows($result) > 0;
}

function checkTablesExist($conn) {
    $tables = ['users', 'complaints', 'co_complainants', 'complaint_witnesses'];
    $missingTables = [];
    
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
        
        if (mysqli_num_rows($result) > 0) {
            $alterQuery = "ALTER TABLE complaints DROP COLUMN $field";
            if (mysqli_query($conn, $alterQuery)) {
                $updates[] = "Removed old $field column";
            }
        }
    }
    
    // Remove priority column if it exists
    $checkPriorityQuery = "SHOW COLUMNS FROM complaints LIKE 'priority'";
    $result = mysqli_query($conn, $checkPriorityQuery);
    
    if (mysqli_num_rows($result) > 0) {
        $removePriorityQuery = "ALTER TABLE complaints DROP COLUMN priority";
        if (mysqli_query($conn, $removePriorityQuery)) {
            $updates[] = "Removed priority column";
        }
    }
    
    return $updates;
}
?>