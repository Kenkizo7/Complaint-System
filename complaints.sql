-- ============================================
-- Add missing columns to existing tables
-- For database: u194078580_complaint
-- ============================================

USE u194078580_complaint;

-- 1. Check and add role column to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS role ENUM('student', 'admin', 'super_admin') DEFAULT 'student' AFTER email;

-- 2. Add permissions column
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS permissions TEXT AFTER status;

-- 3. Add admin-related columns
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL DEFAULT NULL AFTER permissions,
ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP NULL DEFAULT NULL AFTER last_login,
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0 AFTER password_changed_at,
ADD COLUMN IF NOT EXISTS last_failed_login TIMESTAMP NULL DEFAULT NULL AFTER failed_login_attempts,
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL AFTER last_failed_login,
ADD COLUMN IF NOT EXISTS username VARCHAR(50) UNIQUE AFTER email;

-- 4. Create system_settings table if not exists
CREATE TABLE IF NOT EXISTS system_settings (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create complaint_settings table if not exists
CREATE TABLE IF NOT EXISTS complaint_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    auto_assign TINYINT(1) DEFAULT 1,
    default_priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    escalation_days INT DEFAULT 3,
    reminder_frequency ENUM('daily', 'weekly', 'never') DEFAULT 'daily',
    max_file_size INT DEFAULT 5242880,
    allowed_file_types VARCHAR(255) DEFAULT 'jpg,jpeg,png,pdf,doc,docx',
    require_evidence TINYINT(1) DEFAULT 0,
    auto_close_days INT DEFAULT 30,
    notify_complainant TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create email_settings table if not exists
CREATE TABLE IF NOT EXISTS email_settings (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Create notification_settings table if not exists
CREATE TABLE IF NOT EXISTS notification_settings (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Create security_settings table if not exists
CREATE TABLE IF NOT EXISTS security_settings (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Create activity_logs table if not exists
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Create admin_logs table if not exists
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50),
    target_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Create admin_profiles table if not exists
CREATE TABLE IF NOT EXISTS admin_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    department VARCHAR(100),
    phone VARCHAR(20),
    office_location VARCHAR(100),
    bio TEXT,
    signature TEXT,
    theme_preference VARCHAR(20) DEFAULT 'light',
    notification_preferences TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id),
    INDEX idx_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Create login_attempts table if not exists
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    username VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    success TINYINT(1) DEFAULT 0,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempt_time),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Create backup_logs table if not exists
CREATE TABLE IF NOT EXISTS backup_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backup_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_backup_name (backup_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert default data
-- ============================================

-- Insert default system settings
INSERT IGNORE INTO system_settings (id, site_name, site_email, timezone) 
VALUES (1, 'College Complaint System', 'support@college.edu', 'Asia/Kolkata');

-- Insert default complaint settings
INSERT IGNORE INTO complaint_settings (id) VALUES (1);

-- Insert default email settings
INSERT IGNORE INTO email_settings (id) VALUES (1);

-- Insert default notification settings
INSERT IGNORE INTO notification_settings (id) VALUES (1);

-- Insert default security settings
INSERT IGNORE INTO security_settings (id) VALUES (1);

-- Update existing admin user to have admin role
UPDATE users 
SET role = 'admin', 
    username = 'admin',
    permissions = '["manage_complaints","manage_students","view_reports","system_settings","view_logs"]'
WHERE student_id = 'ADMIN001' OR email = 'admin@college.edu';

-- Set all other users to student role
UPDATE users 
SET role = 'student' 
WHERE (role IS NULL OR role = '') 
AND student_id != 'ADMIN001';

-- ============================================
-- Fix PHP code to handle missing columns gracefully
-- ============================================

-- After running this SQL, update your PHP files to handle the new structure

SELECT 'Database update completed successfully!' as message;

SELECT 
    'Columns added to users table:' as info,
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'u194078580_complaint' 
     AND TABLE_NAME = 'users') as total_columns;

SELECT 
    'Settings tables created:' as info,
    (SELECT COUNT(*) FROM system_settings) as system_settings,
    (SELECT COUNT(*) FROM complaint_settings) as complaint_settings,
    (SELECT COUNT(*) FROM email_settings) as email_settings,
    (SELECT COUNT(*) FROM notification_settings) as notification_settings,
    (SELECT COUNT(*) FROM security_settings) as security_settings;

SELECT 
    'User roles updated:' as info,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
    SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as student_users
FROM users;