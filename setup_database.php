<?php
/**
 * Database Setup Script for DFCTS
 * This script will create and populate the entire database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'dfcts';

try {
    // Create connection without database selection first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<h2>🚀 DFCTS Database Setup</h2>\n";
    echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>\n";
    
    // Create database
    echo "📁 Creating database '$database'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database created successfully!\n\n";
    
    // Select the database
    $pdo->exec("USE $database");
    
    // Create tables
    echo "📋 Creating tables...\n";
    
    // Users table
    echo "   Creating users table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('police', 'admin', 'forensic') NOT NULL,
            station_name VARCHAR(255) DEFAULT NULL,
            district VARCHAR(100) DEFAULT NULL,
            mobile VARCHAR(15) DEFAULT NULL,
            lab_name VARCHAR(100) DEFAULT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // FIRs table
    echo "   Creating firs table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS firs (
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
        )
    ");
    
    // Forensic cases table
    echo "   Creating forensic_cases table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forensic_cases (
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
        )
    ");
    
    // Audit logs table
    echo "   Creating audit_logs table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            log_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            action VARCHAR(255) NOT NULL,
            module VARCHAR(100) NOT NULL,
            details TEXT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    echo "✅ All tables created successfully!\n\n";
    
    // Create indexes
    echo "🔗 Creating indexes...\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
        "CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)",
        "CREATE INDEX IF NOT EXISTS idx_firs_police_id ON firs(police_id)",
        "CREATE INDEX IF NOT EXISTS idx_firs_status ON firs(status)",
        "CREATE INDEX IF NOT EXISTS idx_forensic_cases_fir_id ON forensic_cases(fir_id)",
        "CREATE INDEX IF NOT EXISTS idx_forensic_cases_officer_id ON forensic_cases(assigned_officer_id)",
        "CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id ON audit_logs(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_audit_logs_timestamp ON audit_logs(timestamp)"
    ];
    
    foreach ($indexes as $index) {
        $pdo->exec($index);
    }
    echo "✅ All indexes created successfully!\n\n";
    
    // Insert default users
    echo "👤 Creating default users...\n";
    
    // Default password hash for 'password123'
    $defaultPassword = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute(['admin@dfcts.gov.in']);
    
    if ($stmt->fetchColumn() == 0) {
        // Insert admin user
        echo "   Creating admin user...\n";
        $pdo->prepare("
            INSERT INTO users (name, email, password, role, status) 
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            'System Admin', 
            'admin@dfcts.gov.in', 
            $defaultPassword, 
            'admin', 
            'approved'
        ]);
        echo "   ✅ Admin user created (admin@dfcts.gov.in)\n";
    } else {
        echo "   ℹ️  Admin user already exists\n";
    }
    
    // Insert forensic officers
    echo "   Creating forensic officers...\n";
    $forensicOfficers = [
        ['Dr. Rajesh Kumar', 'rajesh@sfsl-junga.gov.in', 'SFSL Junga'],
        ['Dr. Priya Sharma', 'priya@rfsl-dharamshala.gov.in', 'RFSL Dharamshala'],
        ['Dr. Amit Singh', 'amit@rfsl-mandi.gov.in', 'RFSL Mandi']
    ];
    
    foreach ($forensicOfficers as $officer) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$officer[1]]);
        
        if ($stmt->fetchColumn() == 0) {
            $pdo->prepare("
                INSERT INTO users (name, email, password, role, lab_name, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([
                $officer[0], 
                $officer[1], 
                $defaultPassword, 
                'forensic', 
                $officer[2], 
                'approved'
            ]);
            echo "   ✅ Created: {$officer[0]} ({$officer[1]})\n";
        } else {
            echo "   ℹ️  Already exists: {$officer[0]}\n";
        }
    }
    
    echo "✅ Default users created successfully!\n\n";
    echo "🎉 <strong>Database setup completed successfully!</strong>\n\n";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0;'>\n";
    echo "<h3>✅ Setup Complete!</h3>\n";
    echo "<p><strong>Next Steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Run <code>add_police_accounts.php</code> to add police station accounts</li>\n";
    echo "<li>Access admin dashboard: <a href='login.php'>login.php</a></li>\n";
    echo "<li>Admin login: admin@dfcts.gov.in / password123</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "</div>\n";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0;'>\n";
    echo "<h3>❌ Database Error</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>Solution:</strong> Make sure MySQL server is running and credentials are correct.</p>\n";
    echo "</div>\n";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0;'>\n";
    echo "<h3>❌ Setup Error</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?>