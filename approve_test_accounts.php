<?php
/**
 * Approve Test Accounts for Immediate Testing
 * This script will approve 3 specific police accounts for immediate access
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
    
    echo "<h2>✅ Approving Test Accounts</h2>\n";
    echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>\n";
    
    // Test accounts to approve for immediate testing
    $testAccounts = [
        'shimla.city@hppolice.gov.in',      // Inspector Rajesh Sharma (Shimla)
        'kangra.dharamshala@hppolice.gov.in', // Inspector Mohit Kumar (Kangra)
        'mandi.city@hppolice.gov.in'        // Inspector Anil Chauhan (Mandi)
    ];
    
    echo "📝 Approving " . count($testAccounts) . " test accounts for immediate access...\n\n";
    
    $approved = 0;
    $alreadyApproved = 0;
    $notFound = 0;
    
    // Prepare statements
    $checkStmt = $pdo->prepare("SELECT id, name, status FROM users WHERE email = ?");
    $approveStmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE email = ?");
    $auditStmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, module, details, ip_address) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($testAccounts as $email) {
        // Check if user exists and get current status
        $checkStmt->execute([$email]);
        $user = $checkStmt->fetch();
        
        if ($user) {
            if ($user['status'] === 'pending') {
                // Approve the user
                $approveStmt->execute([$email]);
                
                // Add audit log
                $auditStmt->execute([
                    1, // Admin user ID
                    'User Registration Approved',
                    'User Management',
                    "Approved registration for {$user['name']} ($email)",
                    '127.0.0.1'
                ]);
                
                echo "✅ Approved: {$user['name']} ($email)\n";
                $approved++;
            } else {
                echo "ℹ️  Already {$user['status']}: {$user['name']} ($email)\n";
                $alreadyApproved++;
            }
        } else {
            echo "❌ Not found: $email\n";
            $notFound++;
        }
    }
    
    echo "\n📊 Summary:\n";
    echo "   ✅ Newly approved: $approved accounts\n";
    echo "   ℹ️  Already approved: $alreadyApproved accounts\n";
    echo "   ❌ Not found: $notFound accounts\n\n";
    
    if ($approved > 0) {
        echo "🎉 <strong>$approved accounts have been approved and can now login!</strong>\n\n";
        
        echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0;'>\n";
        echo "<h3>✅ Ready to Test!</h3>\n";
        echo "<p><strong>These accounts can now login to the police dashboard:</strong></p>\n";
        echo "<ul>\n";
        echo "<li><strong>shimla.city@hppolice.gov.in</strong> - Inspector Rajesh Sharma (Shimla)</li>\n";
        echo "<li><strong>kangra.dharamshala@hppolice.gov.in</strong> - Inspector Mohit Kumar (Kangra)</li>\n";
        echo "<li><strong>mandi.city@hppolice.gov.in</strong> - Inspector Anil Chauhan (Mandi)</li>\n";
        echo "</ul>\n";
        echo "<p><strong>Password for all accounts:</strong> password123</p>\n";
        echo "<p><strong>Login URL:</strong> <a href='login.php'>login.php</a></p>\n";
        echo "</div>\n";
        
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;'>\n";
        echo "<h3>ℹ️ No New Approvals</h3>\n";
        echo "<p>All specified test accounts were already approved or don't exist.</p>\n";
        echo "<p>You can still use existing approved accounts to test the system.</p>\n";
        echo "</div>\n";
    }
    
    // Show current pending users count
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    $pendingStmt->execute();
    $pendingCount = $pendingStmt->fetchColumn();
    
    echo "<div style='background: #e2e3e5; padding: 15px; border-left: 4px solid #6c757d; margin: 15px 0;'>\n";
    echo "<h3>📊 Current System Status</h3>\n";
    echo "<p><strong>Pending police registrations:</strong> $pendingCount accounts</p>\n";
    echo "<p>Login as admin to approve more accounts: admin@dfcts.gov.in / password123</p>\n";
    echo "</div>\n";
    
    echo "</div>\n";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0;'>\n";
    echo "<h3>❌ Database Error</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>Solution:</strong> Make sure the database exists and police accounts have been created.</p>\n";
    echo "</div>\n";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0;'>\n";
    echo "<h3>❌ Setup Error</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?>