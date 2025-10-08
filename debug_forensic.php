<?php
/**
 * Debug script for forensic dashboard database queries
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<h2>Database Connection Test</h2>\n";

try {
    // Test basic connection
    echo "<p>✓ Database connection successful</p>\n";
    
    // Test users table
    echo "<h3>Testing Users Table</h3>\n";
    $users = fetchAll("SELECT id, name, role FROM users LIMIT 5");
    echo "<p>Found " . count($users) . " users:</p>\n";
    foreach ($users as $user) {
        echo "<li>{$user['name']} ({$user['role']})</li>\n";
    }
    
    // Test forensic_cases table
    echo "<h3>Testing Forensic Cases Table</h3>\n";
    $cases = fetchAll("SELECT case_id, status, priority FROM forensic_cases LIMIT 5");
    echo "<p>Found " . count($cases) . " forensic cases:</p>\n";
    foreach ($cases as $case) {
        echo "<li>Case ID: {$case['case_id']}, Status: {$case['status']}, Priority: {$case['priority']}</li>\n";
    }
    
    // Test the main query from forensic dashboard
    echo "<h3>Testing Main Dashboard Query</h3>\n";
    
    // First check if we have any forensic users
    $forensicUsers = fetchAll("SELECT id, name FROM users WHERE role = 'forensic' LIMIT 3");
    echo "<p>Found " . count($forensicUsers) . " forensic users:</p>\n";
    foreach ($forensicUsers as $user) {
        echo "<li>ID: {$user['id']}, Name: {$user['name']}</li>\n";
    }
    
    if (!empty($forensicUsers)) {
        $testUserId = $forensicUsers[0]['id'];
        echo "<p>Testing with user ID: {$testUserId}</p>\n";
        
        // Test the statistics queries
        echo "<h4>Statistics Queries:</h4>\n";
        
        $assigned = fetchRow("SELECT COUNT(*) as count FROM forensic_cases WHERE assigned_officer_id = ?", [$testUserId]);
        echo "<p>Assigned cases: " . ($assigned['count'] ?? 0) . "</p>\n";
        
        $pending = fetchRow("SELECT COUNT(*) as count FROM forensic_cases WHERE assigned_officer_id = ? AND status IN ('received', 'in_process')", [$testUserId]);
        echo "<p>Pending cases: " . ($pending['count'] ?? 0) . "</p>\n";
        
        $completed = fetchRow("SELECT COUNT(*) as count FROM forensic_cases WHERE assigned_officer_id = ? AND status = 'completed'", [$testUserId]);
        echo "<p>Completed cases: " . ($completed['count'] ?? 0) . "</p>\n";
        
        // Test the main cases query
        echo "<h4>Main Cases Query:</h4>\n";
        try {
            $myCases = fetchAll(
                "SELECT fc.*, f.fir_number, f.case_type, f.fir_date, f.summary,
                        u.name as police_name, u.station_name, u.district,
                        DATEDIFF(NOW(), fc.created_at) as days_pending
                 FROM forensic_cases fc
                 JOIN firs f ON fc.fir_id = f.fir_id
                 JOIN users u ON f.police_id = u.id
                 WHERE fc.assigned_officer_id = ?
                 ORDER BY fc.priority DESC, fc.created_at ASC
                 LIMIT 5", 
                [$testUserId]
            );
            
            echo "<p>Query successful. Found " . count($myCases) . " cases for user ID {$testUserId}</p>\n";
            foreach ($myCases as $case) {
                echo "<li>Case: {$case['fir_number']}, Status: {$case['status']}, Priority: {$case['priority']}</li>\n";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error in main cases query: " . $e->getMessage() . "</p>\n";
        }
        
        // Test activity logs
        echo "<h4>Activity Logs Query:</h4>\n";
        try {
            $activities = fetchAll(
                "SELECT al.* 
                 FROM audit_logs al 
                 WHERE al.user_id = ? AND al.module = 'Case Management'
                 ORDER BY al.timestamp DESC 
                 LIMIT 5",
                [$testUserId]
            );
            
            echo "<p>Query successful. Found " . count($activities) . " activities for user ID {$testUserId}</p>\n";
            foreach ($activities as $activity) {
                echo "<li>Action: {$activity['action']}, Time: {$activity['timestamp']}</li>\n";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error in activities query: " . $e->getMessage() . "</p>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<hr><h3>Database Table Structure</h3>\n";
try {
    $tables = ['users', 'firs', 'forensic_cases', 'audit_logs'];
    
    foreach ($tables as $table) {
        echo "<h4>{$table} table:</h4>\n";
        $columns = fetchAll("DESCRIBE {$table}");
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>\n";
        }
        echo "<br>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error describing tables: " . $e->getMessage() . "</p>\n";
}
?>