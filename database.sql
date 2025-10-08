-- Digital Forensic Crime Tracking System (DFCTS) Database Schema
-- Created: 2024

-- Create database
CREATE DATABASE IF NOT EXISTS dfcts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dfcts;

-- Users table (Police Stations, Forensic Admin, Forensic Officers)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('police', 'admin', 'forensic') NOT NULL,
    station_name VARCHAR(255) DEFAULT NULL,
    district VARCHAR(100) DEFAULT NULL,
    mobile VARCHAR(15) DEFAULT NULL,
    lab_name VARCHAR(100) DEFAULT NULL, -- For forensic officers
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- FIRs table
CREATE TABLE firs (
    fir_id INT PRIMARY KEY AUTO_INCREMENT,
    police_id INT NOT NULL,
    fir_number VARCHAR(50) UNIQUE NOT NULL,
    fir_date DATE NOT NULL,
    case_type VARCHAR(100) NOT NULL,
    summary TEXT NOT NULL,
    forensic_required BOOLEAN DEFAULT FALSE,
    lab_assigned VARCHAR(100) DEFAULT NULL,
    law_section VARCHAR(200) DEFAULT NULL,
    approval_notes TEXT DEFAULT NULL,
    status ENUM('submitted', 'under_review', 'approved', 'rejected') DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (police_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Forensic cases table
CREATE TABLE forensic_cases (
    case_id INT PRIMARY KEY AUTO_INCREMENT,
    fir_id INT NOT NULL,
    lab_name VARCHAR(100) NOT NULL,
    assigned_officer_id INT DEFAULT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('received', 'in_process', 'completed', 'on_hold') DEFAULT 'received',
    report_link VARCHAR(500) DEFAULT NULL,
    officer_notes TEXT DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fir_id) REFERENCES firs(fir_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_officer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Audit logs table
CREATE TABLE audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    module VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (name, email, password, role, status) VALUES 
('System Admin', 'admin@dfcts.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved');

-- Insert sample forensic officers
INSERT INTO users (name, email, password, role, lab_name, status) VALUES 
('Dr. Rajesh Kumar', 'rajesh@sfsl-junga.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'forensic', 'SFSL Junga', 'approved'),
('Dr. Priya Sharma', 'priya@rfsl-dharamshala.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'forensic', 'RFSL Dharamshala', 'approved'),
('Dr. Amit Singh', 'amit@rfsl-mandi.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'forensic', 'RFSL Mandi', 'approved');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_firs_police_id ON firs(police_id);
CREATE INDEX idx_firs_status ON firs(status);
CREATE INDEX idx_forensic_cases_fir_id ON forensic_cases(fir_id);
CREATE INDEX idx_forensic_cases_officer_id ON forensic_cases(assigned_officer_id);
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_timestamp ON audit_logs(timestamp);