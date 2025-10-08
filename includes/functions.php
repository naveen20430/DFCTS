<?php
/**
 * Utility Functions for DFCTS
 */

require_once 'db.php';

/**
 * Log user activity
 * @param int $userId User ID
 * @param string $action Action description
 * @param string $module Module name
 * @param string $details Additional details
 */
function logActivity($userId, $action, $module, $details = null) {
    try {
        $sql = "INSERT INTO audit_logs (user_id, action, module, details, ip_address) 
                VALUES (?, ?, ?, ?, ?)";
        
        executeQuery($sql, [
            $userId,
            $action,
            $module,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Sanitize input data
 * @param string $input Input string
 * @return string Sanitized string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * @param string $email Email address
 * @return bool Valid status
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate mobile number (Indian format)
 * @param string $mobile Mobile number
 * @return bool Valid status
 */
function validateMobile($mobile) {
    return preg_match('/^[6-9]\d{9}$/', $mobile);
}

/**
 * Generate FIR number
 * @param string $district District code
 * @return string FIR number
 */
function generateFIRNumber($district) {
    $year = date('Y');
    $districtCode = strtoupper(substr($district, 0, 3));
    
    // Get next sequential number
    $sql = "SELECT COUNT(*) as count FROM firs WHERE fir_number LIKE ?";
    $result = fetchRow($sql, ["{$districtCode}/{$year}/%"]);
    $nextNumber = ($result['count'] ?? 0) + 1;
    
    return sprintf("%s/%s/%04d", $districtCode, $year, $nextNumber);
}

/**
 * Get case status badge HTML
 * @param string $status Status
 * @return string HTML badge
 */
function getStatusBadge($status) {
    $badges = [
        'submitted' => '<span class="badge bg-primary">Submitted</span>',
        'under_review' => '<span class="badge bg-warning">Under Review</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        'received' => '<span class="badge bg-info">Received</span>',
        'in_process' => '<span class="badge bg-warning">In Process</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        'on_hold' => '<span class="badge bg-secondary">On Hold</span>',
        'pending' => '<span class="badge bg-warning">Pending</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-light">Unknown</span>';
}

/**
 * Get priority badge HTML
 * @param string $priority Priority level
 * @return string HTML badge
 */
function getPriorityBadge($priority) {
    $badges = [
        'low' => '<span class="badge bg-light text-dark">Low</span>',
        'medium' => '<span class="badge bg-primary">Medium</span>',
        'high' => '<span class="badge bg-warning">High</span>',
        'urgent' => '<span class="badge bg-danger">Urgent</span>'
    ];
    
    return $badges[$priority] ?? '<span class="badge bg-light">Normal</span>';
}

/**
 * Format date for display
 * @param string $date Date string
 * @param string $format Format string
 * @return string Formatted date
 */
function formatDate($date, $format = 'd-m-Y H:i') {
    if (empty($date)) return '-';
    
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Get lab options for dropdown
 * @return array Lab options
 */
function getLabOptions() {
    return [
        'SFSL Junga' => 'State Forensic Science Laboratory, Junga',
        'RFSL Dharamshala' => 'Regional Forensic Science Laboratory, Dharamshala',
        'RFSL Mandi' => 'Regional Forensic Science Laboratory, Mandi'
    ];
}

/**
 * Get case type options
 * @return array Case type options
 */
function getCaseTypeOptions() {
    return [
        'Digital Evidence' => 'Digital Evidence',
        'Mobile Forensics' => 'Mobile Forensics',
        'Computer Forensics' => 'Computer Forensics',
        'Network Analysis' => 'Network Analysis',
        'Data Recovery' => 'Data Recovery',
        'Cyber Crime' => 'Cyber Crime',
        'Other' => 'Other'
    ];
}

/**
 * Get Himachal Pradesh districts
 * @return array Districts list
 */
function getDistricts() {
    return [
        'Bilaspur', 'Chamba', 'Hamirpur', 'Kangra', 'Kinnaur',
        'Kullu', 'Lahaul & Spiti', 'Mandi', 'Shimla', 'Sirmaur', 'Solan', 'Una'
    ];
}

/**
 * Upload file
 * @param array $file File data from $_FILES
 * @param string $folder Upload folder
 * @param array $allowedTypes Allowed file types
 * @return array Upload result
 */
function uploadFile($file, $folder = 'uploads/', $allowedTypes = ['pdf', 'doc', 'docx']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    // Validate file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File size too large (max 10MB)'];
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
    }
    
    // Generate unique filename
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = $folder . $fileName;
    
    // Create directory if it doesn't exist
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $fileName, 'path' => $uploadPath];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

/**
 * Send email using PHPMailer
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body
 * @param string $name Recipient name
 * @return bool Success status
 */
function sendEmail($to, $subject, $body, $name = '') {
    // For development, we'll just log emails instead of actually sending
    // In production, implement PHPMailer configuration
    
    $logEntry = [
        'to' => $to,
        'name' => $name,
        'subject' => $subject,
        'body' => $body,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    error_log("Email sent: " . json_encode($logEntry));
    return true;
    
    /* 
    // PHPMailer implementation (uncomment and configure for production)
    require_once 'vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('noreply@dfcts.gov.in', 'DFCTS System');
        $mail->addAddress($to, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
    */
}

/**
 * Send registration notification to admin
 * @param array $userData User data
 */
function sendRegistrationNotification($userData) {
    $subject = 'New Police Station Registration - DFCTS';
    $body = "
    <h3>New Police Station Registration</h3>
    <p>A new police station has registered for DFCTS access:</p>
    <ul>
        <li><strong>Station Name:</strong> {$userData['station_name']}</li>
        <li><strong>District:</strong> {$userData['district']}</li>
        <li><strong>Officer Name:</strong> {$userData['name']}</li>
        <li><strong>Email:</strong> {$userData['email']}</li>
        <li><strong>Mobile:</strong> {$userData['mobile']}</li>
    </ul>
    <p>Please review and approve this registration in the admin panel.</p>
    ";
    
    sendEmail('admin@dfcts.gov.in', $subject, $body, 'DFCTS Admin');
}

/**
 * Send approval email to user
 * @param array $user User data
 */
function sendApprovalEmail($user) {
    $subject = 'DFCTS Registration Approved';
    $body = "
    <h3>Registration Approved</h3>
    <p>Dear {$user['name']},</p>
    <p>Your registration for the Digital Forensic Crime Tracking System has been approved.</p>
    <p>You can now login using your registered email and password.</p>
    <p><a href='http://localhost/dfcts/login.php'>Login to DFCTS</a></p>
    <p>Best regards,<br>DFCTS Team</p>
    ";
    
    sendEmail($user['email'], $subject, $body, $user['name']);
}

/**
 * Send FIR forensic request notification
 * @param array $firData FIR data
 */
function sendForensicRequestNotification($firData) {
    $subject = 'New Forensic Investigation Request - DFCTS';
    $body = "
    <h3>New Forensic Investigation Request</h3>
    <p>A new FIR has been submitted requiring forensic investigation:</p>
    <ul>
        <li><strong>FIR Number:</strong> {$firData['fir_number']}</li>
        <li><strong>Case Type:</strong> {$firData['case_type']}</li>
        <li><strong>Lab Assigned:</strong> {$firData['lab_assigned']}</li>
        <li><strong>Police Station:</strong> {$firData['station_name']}</li>
        <li><strong>District:</strong> {$firData['district']}</li>
    </ul>
    <p>Please review and assign to appropriate forensic officer.</p>
    ";
    
    // Send to admin
    sendEmail('admin@dfcts.gov.in', $subject, $body, 'DFCTS Admin');
    
    // Send to police station
    sendEmail($firData['police_email'], 'FIR Submitted for Forensic Analysis', 
        "Your FIR {$firData['fir_number']} has been submitted for forensic analysis.", 
        $firData['police_name']);
}

/**
 * Get forensic officers by lab
 * @param string $lab Lab name
 * @return array Officers list
 */
function getForensicOfficers($lab = null) {
    $sql = "SELECT id, name, email, lab_name FROM users WHERE role = 'forensic' AND status = 'approved'";
    $params = [];
    
    if ($lab) {
        $sql .= " AND lab_name = ?";
        $params[] = $lab;
    }
    
    return fetchAll($sql, $params);
}

/**
 * Get pending user registrations
 * @return array Pending users
 */
function getPendingUsers() {
    $sql = "SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC";
    return fetchAll($sql);
}

/**
 * Paginate results
 * @param string $sql Base SQL query
 * @param array $params Query parameters
 * @param int $page Current page
 * @param int $perPage Items per page
 * @return array Paginated results and metadata
 */
function paginate($sql, $params = [], $page = 1, $perPage = 10) {
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as temp";
    $totalResult = fetchRow($countSql, $params);
    $total = $totalResult['total'] ?? 0;
    
    // Calculate pagination
    $totalPages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    
    // Add LIMIT and OFFSET to original query
    $limitSql = $sql . " LIMIT ? OFFSET ?";
    $limitParams = array_merge($params, [$perPage, $offset]);
    
    $results = fetchAll($limitSql, $limitParams);
    
    return [
        'data' => $results,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $perPage
    ];
}

/**
 * Generate pagination HTML
 * @param array $pagination Pagination data
 * @param string $baseUrl Base URL for pagination links
 * @return string Pagination HTML
 */
function generatePaginationHTML($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($pagination['current_page'] > 1) {
        $prevPage = $pagination['current_page'] - 1;
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}?page={$prevPage}'>Previous</a></li>";
    }
    
    // Page numbers
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        $active = ($i == $pagination['current_page']) ? 'active' : '';
        $html .= "<li class='page-item {$active}'><a class='page-link' href='{$baseUrl}?page={$i}'>{$i}</a></li>";
    }
    
    // Next button
    if ($pagination['current_page'] < $pagination['total_pages']) {
        $nextPage = $pagination['current_page'] + 1;
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}?page={$nextPage}'>Next</a></li>";
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}
?>