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
    
    // Create complaints table with updated status enum
    $createComplaintsTable = "CREATE TABLE IF NOT EXISTS complaints (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT NOT NULL,
        category ENUM('Academic', 'Administrative', 'Facilities', 'Hostel', 'Library', 'Other') NOT NULL,
        status ENUM('Pending', 'Under Investigation', 'Resolved') DEFAULT 'Pending',
        attachment_path VARCHAR(255),
        admin_notes TEXT,
        resolved_date DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_category (category),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createComplaintsTable)) {
        return ["error" => "Failed to create complaints table: " . mysqli_error($conn)];
    }
    
    // Check if admin user exists, if not create one
    $checkAdminQuery = "SELECT id FROM users WHERE email = 'admin@college.edu'";
    $result = mysqli_query($conn, $checkAdminQuery);
    
    if (mysqli_num_rows($result) == 0) {
        // Create admin user with default password: admin123
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $createAdminQuery = "INSERT INTO users (student_id, name, email, contact_number, password) 
                            VALUES ('ADMIN001', 'System Administrator', 'admin@college.edu', '9876543210', '$hashedPassword')";
        
        if (!mysqli_query($conn, $createAdminQuery)) {
            return ["error" => "Failed to create admin user: " . mysqli_error($conn)];
        }
        
        // Create a sample student user (optional)
        $studentPassword = password_hash('student123', PASSWORD_DEFAULT);
        $createStudentQuery = "INSERT INTO users (student_id, name, email, contact_number, password) 
                              VALUES ('STU2024001', 'John Doe', 'john.doe@college.edu', '9876543211', '$studentPassword')";
        mysqli_query($conn, $createStudentQuery);
        
        return [
            "success" => true,
            "message" => "Database setup completed successfully!",
            "admin_credentials" => [
                "email" => "admin@college.edu",
                "password" => "admin123"
            ],
            "student_credentials" => [
                "email" => "john.doe@college.edu",
                "password" => "student123"
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
    $tables = ['users', 'complaints'];
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
    // Check if status column needs to be updated
    $checkColumnQuery = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_NAME = 'complaints' AND COLUMN_NAME = 'status' 
                        AND TABLE_SCHEMA = 'college_complaint_system'";
    
    $result = mysqli_query($conn, $checkColumnQuery);
    if ($row = mysqli_fetch_assoc($result)) {
        $columnType = $row['COLUMN_TYPE'];
        
        // Check if the column has the new enum values
        if (strpos($columnType, "'Under Investigation'") === false) {
            // Update the status column to new enum values
            $updateQuery = "ALTER TABLE complaints 
                           MODIFY COLUMN status ENUM('Pending', 'Under Investigation', 'Resolved') DEFAULT 'Pending'";
            
            if (mysqli_query($conn, $updateQuery)) {
                return ["success" => "Updated status column to new values"];
            } else {
                return ["error" => "Failed to update status column: " . mysqli_error($conn)];
            }
        }
    }
    
    return ["success" => "Tables are up to date"];
}
?>