-- Police Login Accounts for DFCTS Police Dashboard
-- Password for all accounts: password123
-- (Hashed using PHP password_hash with PASSWORD_DEFAULT)

USE dfcts;

-- Insert police station user accounts (PENDING approval from admin)
INSERT INTO users (name, email, password, role, station_name, district, mobile, status) VALUES 

-- Shimla District Police Stations
('Inspector Rajesh Sharma', 'shimla.city@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Shimla City Police Station', 'Shimla', '9876543210', 'pending'),
('Inspector Sunita Devi', 'shimla.south@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Shimla South Police Station', 'Shimla', '9876543211', 'pending'),
('Inspector Vikram Singh', 'shimla.north@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Shimla North Police Station', 'Shimla', '9876543212', 'pending'),

-- Kangra District Police Stations
('Inspector Mohit Kumar', 'kangra.dharamshala@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Dharamshala Police Station', 'Kangra', '9876543213', 'pending'),
('Inspector Kavita Sharma', 'kangra.palampur@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Palampur Police Station', 'Kangra', '9876543214', 'pending'),
('Inspector Ramesh Thakur', 'kangra.city@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Kangra City Police Station', 'Kangra', '9876543215', 'pending'),

-- Mandi District Police Stations
('Inspector Anil Chauhan', 'mandi.city@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Mandi City Police Station', 'Mandi', '9876543216', 'pending'),
('Inspector Pooja Devi', 'mandi.sundernagar@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Sundernagar Police Station', 'Mandi', '9876543217', 'pending'),

-- Kullu District Police Stations
('Inspector Suresh Negi', 'kullu.city@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Kullu City Police Station', 'Kullu', '9876543218', 'pending'),
('Inspector Asha Kumari', 'kullu.manali@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Manali Police Station', 'Kullu', '9876543219', 'pending'),

-- Bilaspur District Police Stations
('Inspector Deepak Verma', 'bilaspur.city@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Bilaspur City Police Station', 'Bilaspur', '9876543220', 'pending'),

-- Hamirpur District Police Stations
('Inspector Sanjay Kumar', 'hamirpur.city@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Hamirpur City Police Station', 'Hamirpur', '9876543221', 'pending'),

-- Solan District Police Stations
('Inspector Neha Thakur', 'solan.city@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Solan City Police Station', 'Solan', '9876543222', 'pending'),
('Inspector Rohit Sharma', 'solan.kasauli@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Kasauli Police Station', 'Solan', '9876543223', 'pending'),

-- Una District Police Stations  
('Inspector Priya Kumari', 'una.city@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Una City Police Station', 'Una', '9876543224', 'pending'),

-- Chamba District Police Stations
('Inspector Arjun Singh', 'chamba.city@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Chamba City Police Station', 'Chamba', '9876543225', 'pending'),

-- Sirmaur District Police Stations
('Inspector Meera Devi', 'sirmaur.nahan@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Nahan Police Station', 'Sirmaur', '9876543226', 'pending'),

-- Kinnaur District Police Stations
('Inspector Tenzin Norbu', 'kinnaur.reckongpeo@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Reckong Peo Police Station', 'Kinnaur', '9876543227', 'pending'),

-- Lahaul & Spiti District Police Stations
('Inspector Karma Dolma', 'lahaulspiti.keylong@hppolice.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Keylong Police Station', 'Lahaul & Spiti', '9876543228', 'pending');

-- Insert some sample FIRs for testing the police dashboard
INSERT INTO firs (police_id, fir_number, fir_date, case_type, summary, forensic_required, lab_assigned, law_section, status) VALUES 
(5, 'SHI/2024/0001', '2024-01-15', 'Cyber Crime', 'Online fraud case involving fake investment scheme. Multiple victims reported losses totaling Rs. 5 lakhs.', TRUE, 'SFSL Junga', 'Section 420, 66C IT Act', 'approved'),
(6, 'SHI/2024/0002', '2024-01-20', 'Digital Evidence', 'Mobile phone seizure in drug trafficking case. WhatsApp chats and call records need analysis.', TRUE, 'SFSL Junga', 'Section 8 NDPS Act', 'under_review'),
(7, 'SHI/2024/0003', '2024-02-01', 'Computer Forensics', 'Computer hacking incident at local bank. Unauthorized access to customer database.', TRUE, 'RFSL Dharamshala', 'Section 66 IT Act', 'submitted'),
(8, 'KAN/2024/0001', '2024-01-25', 'Mobile Forensics', 'Deleted messages recovery from suspects phone in murder case.', TRUE, 'RFSL Dharamshala', 'Section 302 IPC', 'approved'),
(9, 'KAN/2024/0002', '2024-02-05', 'Network Analysis', 'WiFi network intrusion at government office. Sensitive data potentially compromised.', TRUE, 'RFSL Dharamshala', 'Section 66F IT Act', 'submitted'),
(10, 'KAN/2024/0003', '2024-02-10', 'Data Recovery', 'Hard disk recovery from fire damaged computer in arson case.', TRUE, 'RFSL Mandi', 'Section 436 IPC', 'approved');

-- Insert forensic cases for the submitted FIRs
INSERT INTO forensic_cases (fir_id, lab_name, assigned_officer_id, priority, status) VALUES 
(1, 'SFSL Junga', 2, 'high', 'in_process'),
(4, 'RFSL Dharamshala', 3, 'urgent', 'received'),
(6, 'RFSL Mandi', 4, 'medium', 'completed');

-- Add some audit log entries for demonstration
INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES 
(5, 'FIR Submitted', 'FIR Management', 'Submitted FIR SHI/2024/0001 for forensic analysis', '192.168.1.100'),
(6, 'FIR Submitted', 'FIR Management', 'Submitted FIR SHI/2024/0002 for digital evidence analysis', '192.168.1.101'),
(7, 'FIR Submitted', 'FIR Management', 'Submitted FIR SHI/2024/0003 for computer forensics', '192.168.1.102'),
(8, 'FIR Submitted', 'FIR Management', 'Submitted FIR KAN/2024/0001 for mobile forensics', '192.168.1.103'),
(1, 'User Registration Approved', 'User Management', 'Approved registration for Inspector Rajesh Sharma', '192.168.1.1'),
(2, 'Case Status Updated', 'Case Management', 'Updated case status to in_process for case ID 1', '192.168.1.50');