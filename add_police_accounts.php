<?php
/**
 * Add Police Station Accounts to DFCTS Database
 * All accounts will be created with 'pending' status for admin approval
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'dfcts';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<h2>👮‍♂️ Adding Police Station Accounts</h2>\n";
    echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>\n";
    
    // Default password hash for 'password123'
    $defaultPassword = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    // Police station accounts data
    $policeAccounts = [
        // Shimla District
        ['Inspector Rajesh Sharma', 'shimla.city@hppolice.gov.in', 'Shimla City Police Station', 'Shimla', '9876543210'],
        ['Inspector Sunita Devi', 'shimla.south@hppolice.gov.in', 'Shimla South Police Station', 'Shimla', '9876543211'],
        ['Inspector Vikram Singh', 'shimla.north@hppolice.gov.in', 'Shimla North Police Station', 'Shimla', '9876543212'],
        
        // Kangra District
        ['Inspector Mohit Kumar', 'kangra.dharamshala@hppolice.gov.in', 'Dharamshala Police Station', 'Kangra', '9876543213'],
        ['Inspector Kavita Sharma', 'kangra.palampur@hppolice.gov.in', 'Palampur Police Station', 'Kangra', '9876543214'],
        ['Inspector Ramesh Thakur', 'kangra.city@hppolice.gov.in', 'Kangra City Police Station', 'Kangra', '9876543215'],
        
        // Mandi District
        ['Inspector Anil Chauhan', 'mandi.city@hppolice.gov.in', 'Mandi City Police Station', 'Mandi', '9876543216'],
        ['Inspector Pooja Devi', 'mandi.sundernagar@hppolice.gov.in', 'Sundernagar Police Station', 'Mandi', '9876543217'],
        
        // Kullu District
        ['Inspector Suresh Negi', 'kullu.city@hppolice.gov.in', 'Kullu City Police Station', 'Kullu', '9876543218'],
        ['Inspector Asha Kumari', 'kullu.manali@hppolice.gov.in', 'Manali Police Station', 'Kullu', '9876543219'],
        
        // Other Districts
        ['Inspector Deepak Verma', 'bilaspur.city@hppolice.gov.in', 'Bilaspur City Police Station', 'Bilaspur', '9876543220'],
        ['Inspector Sanjay Kumar', 'hamirpur.city@hppolice.gov.in', 'Hamirpur City Police Station', 'Hamirpur', '9876543221'],
        ['Inspector Neha Thakur', 'solan.city@hppolice.gov.in', 'Solan City Police Station', 'Solan', '9876543222'],
        ['Inspector Rohit Sharma', 'solan.kasauli@hppolice.gov.in', 'Kasauli Police Station', 'Solan', '9876543223'],
        ['Inspector Priya Kumari', 'una.city@hppolice.gov.in', 'Una City Police Station', 'Una', '9876543224'],
        ['Inspector Arjun Singh', 'chamba.city@hppolice.gov.in', 'Chamba City Police Station', 'Chamba', '9876543225'],
        ['Inspector Meera Devi', 'sirmaur.nahan@hppolice.gov.in', 'Nahan Police Station', 'Sirmaur', '9876543226'],
        ['Inspector Tenzin Norbu', 'kinnaur.reckongpeo@hppolice.gov.in', 'Reckong Peo Police Station', 'Kinnaur', '9876543227'],
        ['Inspector Karma Dolma', 'lahaulspiti.keylong@hppolice.gov.in', 'Keylong Police Station', 'Lahaul & Spiti', '9876543228']
    ];
    
    echo "📝 Creating " . count($policeAccounts) . " police station accounts...\n\n";
    
    $created = 0;
    $existing = 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $insertStmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, station_name, district, mobile, status) 
        VALUES (?, ?, ?, 'police', ?, ?, ?, 'pending')
    ");
    
    foreach ($policeAccounts as $account) {
        $name = $account[0];
        $email = $account[1];
        $station = $account[2];
        $district = $account[3];
        $mobile = $account[4];
        
        // Check if user already exists
        $stmt->execute([$email]);
        
        if ($stmt->fetchColumn() == 0) {
            // Create the user
            $insertStmt->execute([
                $name, 
                $email, 
                $defaultPassword, 
                $station, 
                $district, 
                $mobile
            ]);
            
            echo "✅ Created: $name ($station, $district)\n";
            $created++;
        } else {
            echo "ℹ️  Already exists: $name ($email)\n";
            $existing++;
        }
    }
    
    echo "\n📊 Summary:\n";
    echo "   ✅ Created: $created accounts\n";
    echo "   ℹ️  Already existed: $existing accounts\n";
    echo "   📝 Status: All accounts created with 'pending' status\n\n";
    
    // Create some sample FIRs for testing (only for first 6 police accounts)
    echo "📋 Creating sample FIRs for testing...\n";
    
    $sampleFirs = [
        [5, 'SHI/2024/0001', '2024-01-15', 'Cyber Crime', 'Online fraud case involving fake investment scheme. Multiple victims reported losses totaling Rs. 5 lakhs.', true, 'SFSL Junga', 'Section 420, 66C IT Act', 'approved'],
        [6, 'SHI/2024/0002', '2024-01-20', 'Digital Evidence', 'Mobile phone seizure in drug trafficking case. WhatsApp chats and call records need analysis.', true, 'SFSL Junga', 'Section 8 NDPS Act', 'under_review'],
        [7, 'SHI/2024/0003', '2024-02-01', 'Computer Forensics', 'Computer hacking incident at local bank. Unauthorized access to customer database.', true, 'RFSL Dharamshala', 'Section 66 IT Act', 'submitted'],
        [8, 'KAN/2024/0001', '2024-01-25', 'Mobile Forensics', 'Deleted messages recovery from suspects phone in murder case.', true, 'RFSL Dharamshala', 'Section 302 IPC', 'approved'],
        [9, 'KAN/2024/0002', '2024-02-05', 'Network Analysis', 'WiFi network intrusion at government office. Sensitive data potentially compromised.', true, 'RFSL Dharamshala', 'Section 66F IT Act', 'submitted'],
        [10, 'KAN/2024/0003', '2024-02-10', 'Data Recovery', 'Hard disk recovery from fire damaged computer in arson case.', true, 'RFSL Mandi', 'Section 436 IPC', 'approved']
    ];
    
    $firStmt = $pdo->prepare("
        INSERT INTO firs (police_id, fir_number, fir_date, case_type, summary, forensic_required, lab_assigned, law_section, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $createdFirs = 0;
    foreach ($sampleFirs as $fir) {
        try {
            $firStmt->execute($fir);
            $createdFirs++;
            echo "   ✅ Created FIR: {$fir[1]} ({$fir[3]})\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "   ℹ️  FIR already exists: {$fir[1]}\n";
            } else {
                echo "   ❌ Error creating FIR {$fir[1]}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Create forensic cases for some FIRs
    if ($createdFirs > 0) {
        echo "\n🔬 Creating forensic cases...\n";
        $forensicCases = [
            [1, 'SFSL Junga', 2, 'high', 'in_process'],
            [4, 'RFSL Dharamshala', 3, 'urgent', 'received'],
            [6, 'RFSL Mandi', 4, 'medium', 'completed']
        ];
        
        $caseStmt = $pdo->prepare("
            INSERT INTO forensic_cases (fir_id, lab_name, assigned_officer_id, priority, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($forensicCases as $case) {
            try {
                $caseStmt->execute($case);
                echo "   ✅ Created forensic case for FIR ID: {$case[0]}\n";
            } catch (PDOException $e) {
                echo "   ℹ️  Forensic case may already exist for FIR ID: {$case[0]}\n";
            }
        }
    }
    
    // Add some audit log entries
    echo "\n📝 Creating sample audit log entries...\n";
    $auditEntries = [
        [5, 'FIR Submitted', 'FIR Management', 'Submitted FIR SHI/2024/0001 for forensic analysis', '192.168.1.100'],
        [6, 'FIR Submitted', 'FIR Management', 'Submitted FIR SHI/2024/0002 for digital evidence analysis', '192.168.1.101'],
        [1, 'User Registration Approved', 'User Management', 'Approved registration for police station', '192.168.1.1'],
        [2, 'Case Status Updated', 'Case Management', 'Updated case status to in_process', '192.168.1.50']
    ];
    
    $auditStmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, module, details, ip_address) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($auditEntries as $entry) {
        try {
            $auditStmt->execute($entry);
            echo "   ✅ Created audit entry: {$entry[1]}\n";
        } catch (PDOException $e) {
            echo "   ℹ️  Audit entry may already exist\n";
        }
    }
    
    echo "\n🎉 <strong>Police accounts setup completed successfully!</strong>\n\n";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;'>\n";
    echo "<h3>⚠️ Important: Admin Approval Required</h3>\n";
    echo "<p>All police accounts are created with <strong>'pending'</strong> status.</p>\n";
    echo "<p><strong>Next Steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Login as admin: <a href='login.php'>login.php</a> (admin@dfcts.gov.in / password123)</li>\n";
    echo "<li>Go to Admin Dashboard to see pending registrations</li>\n";
    echo "<li>Approve police accounts to allow them to login</li>\n";
    echo "<li>Run <code>approve_test_accounts.php</code> to auto-approve 3 test accounts</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0;'>\n";
    echo "<h3>✅ What was created:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>$created police station accounts</strong> (status: pending)</li>\n";
    echo "<li><strong>$createdFirs sample FIRs</strong> for testing</li>\n";
    echo "<li><strong>3 forensic cases</strong> linked to FIRs</li>\n";
    echo "<li><strong>Sample audit log entries</strong></li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "</div>\n";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0;'>\n";
    echo "<h3>❌ Database Error</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>Solution:</strong> Make sure you've run setup_database.php first and MySQL is running.</p>\n";
    echo "</div>\n";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0;'>\n";
    echo "<h3>❌ Setup Error</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?>