<?php
/**
 * Authentication and Session Management for DFCTS
 */

require_once 'db.php';
require_once 'functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if user has specific role
 * @param string|array $roles Role(s) to check
 * @return bool
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_string($roles)) {
        return $_SESSION['user_role'] === $roles;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['user_role'], $roles);
    }
    
    return false;
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require specific role - redirect with error if unauthorized
 * @param string|array $roles Required role(s)
 */
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        $_SESSION['error'] = 'Access denied. Insufficient privileges.';
        header('Location: login.php');
        exit;
    }
}

/**
 * Login user
 * @param string $email User email
 * @param string $password Plain text password
 * @return bool Success status
 */
function loginUser($email, $password) {
    $sql = "SELECT id, name, email, password, role, status, station_name, district, lab_name 
            FROM users WHERE email = ? AND status = 'approved'";
    
    $user = fetchRow($sql, [$email]);
    
    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['station_name'] = $user['station_name'];
        $_SESSION['district'] = $user['district'];
        $_SESSION['lab_name'] = $user['lab_name'];
        $_SESSION['login_time'] = time();
        
        // Log login activity
        logActivity($user['id'], 'User Login', 'Authentication');
        
        return true;
    }
    
    return false;
}

/**
 * Logout user
 */
function logoutUser() {
    if (isLoggedIn()) {
        // Log logout activity
        logActivity($_SESSION['user_id'], 'User Logout', 'Authentication');
        
        // Clear session
        session_unset();
        session_destroy();
        
        // Start new session
        session_start();
        session_regenerate_id(true);
    }
}

/**
 * Register new user
 * @param array $userData User data
 * @return bool Success status
 */
function registerUser($userData) {
    try {
        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password, role, station_name, district, mobile) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        executeQuery($sql, [
            $userData['name'],
            $userData['email'],
            $hashedPassword,
            'police', // Default role for registration
            $userData['station_name'],
            $userData['district'],
            $userData['mobile']
        ]);
        
        $userId = getLastInsertId();
        
        // Log registration
        logActivity($userId, 'User Registration', 'Authentication');
        
        // Send notification to admin
        sendRegistrationNotification($userData);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Registration failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Approve user registration
 * @param int $userId User ID
 * @return bool Success status
 */
function approveUser($userId) {
    try {
        $sql = "UPDATE users SET status = 'approved' WHERE id = ?";
        executeQuery($sql, [$userId]);
        
        // Get user details for email
        $user = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);
        
        if ($user) {
            // Send approval email
            sendApprovalEmail($user);
            
            // Log approval
            logActivity($_SESSION['user_id'], "Approved user registration for {$user['email']}", 'User Management');
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("User approval failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Reject user registration
 * @param int $userId User ID
 * @return bool Success status
 */
function rejectUser($userId) {
    try {
        $sql = "UPDATE users SET status = 'rejected' WHERE id = ?";
        executeQuery($sql, [$userId]);
        
        // Get user details
        $user = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);
        
        if ($user) {
            // Log rejection
            logActivity($_SESSION['user_id'], "Rejected user registration for {$user['email']}", 'User Management');
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("User rejection failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool Valid status
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get current user data
 * @return array|null User data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'station_name' => $_SESSION['station_name'] ?? null,
        'district' => $_SESSION['district'] ?? null,
        'lab_name' => $_SESSION['lab_name'] ?? null
    ];
}

/**
 * Check session timeout (30 minutes)
 */
function checkSessionTimeout() {
    if (isLoggedIn()) {
        $timeout = 30 * 60; // 30 minutes
        
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
            logoutUser();
            $_SESSION['error'] = 'Session expired. Please login again.';
            header('Location: login.php');
            exit;
        }
    }
}

// Check session timeout on every request
checkSessionTimeout();
?>