<?php
/**
 * Forensic Officer Dashboard - DFCTS
 */

require_once 'includes/auth.php';

// Require forensic role
requireRole('forensic');

$user = getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        switch ($_POST['action']) {
            case 'update_status':
                if (isset($_POST['case_id']) && isset($_POST['status'])) {
                    $sql = "UPDATE forensic_cases SET status = ?, updated_at = NOW() WHERE case_id = ? AND assigned_officer_id = ?";
                    try {
                        executeQuery($sql, [$_POST['status'], $_POST['case_id'], $user['id']]);
                        logActivity($user['id'], "Updated case status to {$_POST['status']} for case ID {$_POST['case_id']}", 'Case Management');
                        $_SESSION['success'] = 'Case status updated successfully.';
                    } catch (Exception $e) {
                        $_SESSION['error'] = 'Failed to update case status.';
                    }
                }
                break;
                
            case 'add_report_note':
                if (isset($_POST['case_id']) && isset($_POST['officer_notes'])) {
                    $sql = "UPDATE forensic_cases SET officer_notes = ?, updated_at = NOW() WHERE case_id = ? AND assigned_officer_id = ?";
                    try {
                        executeQuery($sql, [$_POST['officer_notes'], $_POST['case_id'], $user['id']]);
                        logActivity($user['id'], "Added officer notes for case ID {$_POST['case_id']}", 'Case Management');
                        $_SESSION['success'] = 'Officer notes added successfully.';
                    } catch (Exception $e) {
                        $_SESSION['error'] = 'Failed to add officer notes.';
                    }
                }
                break;
                
            case 'upload_report':
                if (isset($_POST['case_id']) && isset($_FILES['report_file'])) {
                    // Handle file upload logic here
                    $targetDir = "uploads/reports/";
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    $fileName = time() . "_" . basename($_FILES["report_file"]["name"]);
                    $targetFile = $targetDir . $fileName;
                    
                    if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $targetFile)) {
                        $sql = "UPDATE forensic_cases SET report_link = ?, status = 'completed', updated_at = NOW() WHERE case_id = ? AND assigned_officer_id = ?";
                        try {
                            executeQuery($sql, [$targetFile, $_POST['case_id'], $user['id']]);
                            logActivity($user['id'], "Uploaded final report for case ID {$_POST['case_id']}", 'Case Management');
                            $_SESSION['success'] = 'Report uploaded and case marked as completed.';
                        } catch (Exception $e) {
                            $_SESSION['error'] = 'Failed to update case with report.';
                        }
                    } else {
                        $_SESSION['error'] = 'Failed to upload report file.';
                    }
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header('Location: forensic_dashboard.php');
        exit;
    }
}

// Get dashboard statistics
$stats = [];
$stats['assigned_cases'] = fetchRow(
    "SELECT COUNT(*) as count FROM forensic_cases WHERE assigned_officer_id = ?", 
    [$user['id']]
)['count'] ?? 0;

$stats['pending_cases'] = fetchRow(
    "SELECT COUNT(*) as count FROM forensic_cases WHERE assigned_officer_id = ? AND status IN ('received', 'in_process')", 
    [$user['id']]
)['count'] ?? 0;

$stats['completed_cases'] = fetchRow(
    "SELECT COUNT(*) as count FROM forensic_cases WHERE assigned_officer_id = ? AND status = 'completed'", 
    [$user['id']]
)['count'] ?? 0;

$stats['high_priority'] = fetchRow(
    "SELECT COUNT(*) as count FROM forensic_cases WHERE assigned_officer_id = ? AND priority = 'high' AND status IN ('received', 'in_process')", 
    [$user['id']]
)['count'] ?? 0;

// Get my assigned cases
$myCases = fetchAll(
    "SELECT fc.*, f.fir_number, f.case_type, f.fir_date, f.summary,
            u.name as police_name, u.station_name, u.district,
            DATEDIFF(NOW(), fc.created_at) as days_pending
     FROM forensic_cases fc
     JOIN firs f ON fc.fir_id = f.fir_id
     JOIN users u ON f.police_id = u.id
     WHERE fc.assigned_officer_id = ?
     ORDER BY fc.priority DESC, fc.created_at ASC
     LIMIT 20", 
    [$user['id']]
);

// Get recent activities for my cases (simplified - show user's own activities)
$recentActivities = fetchAll(
    "SELECT al.* 
     FROM audit_logs al 
     WHERE al.user_id = ? AND al.module = 'Case Management'
     ORDER BY al.timestamp DESC 
     LIMIT 10",
    [$user['id']]
);

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: login.php');
    exit;
}

// Get success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forensic Dashboard - DFCTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #8e44ad, #9b59b6);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.15);
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            padding: 25px;
            color: white;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card.primary {
            background: linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%);
        }
        .stat-card.success {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #e67e22 0%, #f39c12 100%);
        }
        .stat-card.danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .case-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        .case-card.high-priority {
            border-left-color: #e74c3c;
        }
        .case-card.medium-priority {
            border-left-color: #f39c12;
        }
        .case-card.low-priority {
            border-left-color: #27ae60;
        }
        .case-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .activity-item {
            border-left: 3px solid #8e44ad;
            padding: 10px 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 0 8px 8px 0;
        }
        .lab-badge {
            background: linear-gradient(45deg, #8e44ad, #9b59b6);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3 border-bottom border-secondary">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-microscope display-6 me-3"></i>
                            <div>
                                <h5 class="mb-0">DFCTS</h5>
                                <small>Forensic Lab</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-3 border-bottom border-secondary">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-md fa-2x me-3"></i>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                                <small class="text-light">Forensic Officer</small>
                                <?php if (isset($user['lab_name'])): ?>
                                    <br><small class="text-light"><?php echo htmlspecialchars($user['lab_name']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <nav class="nav flex-column p-3">
                        <a href="forensic_dashboard.php" class="nav-link active">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="forensic_dashboard.php?view=my_cases" class="nav-link">
                            <i class="fas fa-tasks me-2"></i>My Cases
                        </a>
                        <a href="forensic_dashboard.php?view=pending" class="nav-link">
                            <i class="fas fa-clock me-2"></i>Pending Cases
                        </a>
                        <a href="forensic_dashboard.php?view=completed" class="nav-link">
                            <i class="fas fa-check-circle me-2"></i>Completed Cases
                        </a>
                        <a href="forensic_dashboard.php?view=reports" class="nav-link">
                            <i class="fas fa-file-medical me-2"></i>Reports
                        </a>
                        <hr class="border-secondary">
                        <a href="forensic_dashboard.php?view=equipment" class="nav-link">
                            <i class="fas fa-cogs me-2"></i>Lab Equipment
                        </a>
                        <a href="forensic_dashboard.php?view=profile" class="nav-link">
                            <i class="fas fa-user-cog me-2"></i>Profile
                        </a>
                        <a href="?logout=1" class="nav-link text-warning">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-0">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>Forensic Dashboard</h2>
                            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['name']); ?></p>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="badge bg-primary">
                                <i class="fas fa-microscope me-1"></i>Lab Active
                            </div>
                            <div class="small text-muted">
                                Last updated: <?php echo date('d-m-Y H:i'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card primary">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="mb-1"><?php echo $stats['assigned_cases']; ?></h3>
                                        <p class="mb-0">Assigned Cases</p>
                                        <small class="opacity-75">Total workload</small>
                                    </div>
                                    <i class="fas fa-clipboard-list fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card warning">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="mb-1"><?php echo $stats['pending_cases']; ?></h3>
                                        <p class="mb-0">Pending Cases</p>
                                        <small class="opacity-75">Need attention</small>
                                    </div>
                                    <i class="fas fa-hourglass-half fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card success">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="mb-1"><?php echo $stats['completed_cases']; ?></h3>
                                        <p class="mb-0">Completed Cases</p>
                                        <small class="opacity-75">This month</small>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card danger">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="mb-1"><?php echo $stats['high_priority']; ?></h3>
                                        <p class="mb-0">High Priority</p>
                                        <small class="opacity-75">Urgent cases</small>
                                    </div>
                                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Rows -->
                    <div class="row">
                        <!-- My Assigned Cases -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-tasks me-2 text-primary"></i>My Assigned Cases
                                    </h5>
                                    <a href="forensic_dashboard.php?view=my_cases" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                                    <?php if (empty($myCases)): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-tasks fa-3x mb-3"></i>
                                            <h5>No cases assigned yet</h5>
                                            <p class="mb-0">New cases will appear here when assigned by admin</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach (array_slice($myCases, 0, 6) as $case): ?>
                                            <div class="case-card card <?php echo strtolower($case['priority']); ?>-priority">
                                                <div class="card-body py-3">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8">
                                                            <h6 class="mb-1">
                                                                <a href="#" onclick="viewCaseDetails(<?php echo $case['case_id']; ?>)" 
                                                                   class="text-decoration-none text-dark">
                                                                    <strong><?php echo htmlspecialchars($case['fir_number']); ?></strong>
                                                                </a>
                                                            </h6>
                                                            <p class="mb-1 text-muted small">
                                                                <i class="fas fa-gavel me-1"></i><?php echo htmlspecialchars($case['case_type']); ?>
                                                            </p>
                                                            <p class="mb-1 small text-muted">
                                                                <i class="fas fa-file-alt me-1"></i>
                                                                <?php echo htmlspecialchars(substr($case['summary'], 0, 100)); ?>...
                                                            </p>
                                                            <p class="mb-1 small text-muted">
                                                                <i class="fas fa-building me-1"></i>
                                                                <?php echo htmlspecialchars($case['station_name']); ?>, <?php echo htmlspecialchars($case['district']); ?>
                                                            </p>
                                                            <p class="mb-0 small text-muted">
                                                                <i class="fas fa-calendar me-1"></i>
                                                                Assigned: <?php echo formatDate($case['created_at'], 'd-m-Y'); ?>
                                                                <?php if ($case['days_pending'] > 0): ?>
                                                                    <span class="text-warning ms-2">
                                                                        (<?php echo $case['days_pending']; ?> days ago)
                                                                    </span>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-4 text-end">
                                                            <div class="mb-2">
                                                                <?php echo getStatusBadge($case['status']); ?>
                                                            </div>
                                                            <div class="mb-2">
                                                                <?php echo getPriorityBadge($case['priority']); ?>
                                                            </div>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button type="button" class="btn btn-outline-primary" 
                                                                        onclick="viewCaseDetails(<?php echo $case['case_id']; ?>)">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                <?php if ($case['status'] !== 'completed'): ?>
                                                                    <button type="button" class="btn btn-outline-success" 
                                                                            onclick="updateCaseStatus(<?php echo $case['case_id']; ?>)">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                                <?php if ($case['status'] === 'completed' && $case['report_link']): ?>
                                                                    <button type="button" class="btn btn-outline-info" 
                                                                            onclick="viewReport(<?php echo $case['case_id']; ?>)">
                                                                        <i class="fas fa-download"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity & Quick Actions -->
                        <div class="col-lg-4 mb-4">
                            <!-- Quick Actions -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-bolt me-2"></i>Quick Actions
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary" onclick="startAnalysis()">
                                            <i class="fas fa-play me-2"></i>Start Analysis
                                        </button>
                                        <button class="btn btn-outline-success" onclick="uploadReport()">
                                            <i class="fas fa-upload me-2"></i>Upload Report
                                        </button>
                                        <a href="forensic_dashboard.php?view=pending" class="btn btn-outline-warning">
                                            <i class="fas fa-clock me-2"></i>View Pending
                                        </a>
                                        <button class="btn btn-outline-info" onclick="equipmentStatus()">
                                            <i class="fas fa-cogs me-2"></i>Equipment Status
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activity -->
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-history me-2"></i>Recent Activity
                                    </h6>
                                </div>
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    <?php if (empty($recentActivities)): ?>
                                        <div class="text-center py-3 text-muted">
                                            <i class="fas fa-history fa-2x mb-2"></i>
                                            <p class="mb-0 small">No recent activity</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach (array_slice($recentActivities, 0, 5) as $activity): ?>
                                            <div class="activity-item">
                                                <div class="small">
                                                    <div class="fw-bold mb-1">
                                                        <?php echo htmlspecialchars($activity['action']); ?>
                                                    </div>
                                                    <div class="text-muted">
                                                        <?php echo htmlspecialchars($activity['details'] ?? 'No details available'); ?>
                                                    </div>
                                                    <div class="text-muted">
                                                        <?php echo formatDate($activity['timestamp'], 'd-m-Y H:i'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="text-center mt-3">
                                            <button class="btn btn-sm btn-outline-secondary" onclick="viewAllActivity()">
                                                View All Activity
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lab Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-flask me-2"></i>Lab Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <i class="fas fa-thermometer-half fa-2x text-primary mb-2"></i>
                                                <h6 class="mb-0">Temperature</h6>
                                                <small class="text-muted">22°C</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <i class="fas fa-tint fa-2x text-info mb-2"></i>
                                                <h6 class="mb-0">Humidity</h6>
                                                <small class="text-muted">45%</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="small">Equipment Status:</span>
                                            <span class="badge bg-success small">All Operational</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="small">Active Analysis:</span>
                                            <strong class="small"><?php echo $stats['pending_cases']; ?> cases</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="small">Lab Capacity:</span>
                                            <strong class="small">85%</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>Performance Metrics
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small">Cases Completed This Month</span>
                                            <span class="small font-weight-bold"><?php echo $stats['completed_cases']; ?></span>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo min(100, ($stats['completed_cases'] * 5)); ?>%" 
                                                 aria-valuenow="<?php echo $stats['completed_cases']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="20"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small">Average Processing Time</span>
                                            <span class="small font-weight-bold">3.2 days</span>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: 65%" 
                                                 aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small">Quality Score</span>
                                            <span class="small font-weight-bold">96%</span>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: 96%" 
                                                 aria-valuenow="96" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Case Details Modal -->
    <div class="modal fade" id="caseModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Case Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="caseModalBody">
                    <!-- Case details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Case Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="statusForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="case_id" id="statusCaseId">
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status</label>
                            <select class="form-select" name="status" id="status" required>
                                <option value="">Select Status</option>
                                <option value="received">Received</option>
                                <option value="in_process">In Process</option>
                                <option value="analysis_complete">Analysis Complete</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="officer_notes" class="form-label">Officer Notes (Optional)</label>
                            <textarea class="form-control" name="officer_notes" id="officer_notes" rows="3" 
                                      placeholder="Add any notes about the current status or findings..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewCaseDetails(caseId) {
            // Show loading state
            document.getElementById('caseModalBody').innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading case details...</p>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('caseModal'));
            modal.show();
            
            // In a complete implementation, this would fetch real data via AJAX
            setTimeout(() => {
                document.getElementById('caseModalBody').innerHTML = `
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="text-primary mb-3">Case Information</h6>
                            <div class="mb-3">
                                <strong>Case #${caseId}</strong> - This would show detailed case information including:
                            </div>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-file-alt me-2"></i>FIR details and case type</li>
                                <li><i class="fas fa-user me-2"></i>Complainant information</li>
                                <li><i class="fas fa-map-marker-alt me-2"></i>Location and jurisdiction</li>
                                <li><i class="fas fa-evidence me-2"></i>Evidence items and chain of custody</li>
                                <li><i class="fas fa-calendar me-2"></i>Timeline and deadlines</li>
                                <li><i class="fas fa-notes-medical me-2"></i>Current analysis status</li>
                            </ul>
                            
                            <div class="alert alert-info mt-3">
                                <small>In a complete implementation, this would fetch and display real case data including evidence photos, witness statements, and analysis progress.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-secondary mb-3">Case Timeline</h6>
                            <div class="timeline">
                                <div class="timeline-item">
                                    <span class="badge bg-primary">Received</span>
                                    <small class="text-muted d-block">Case assigned to lab</small>
                                </div>
                                <div class="timeline-item mt-3">
                                    <span class="badge bg-warning">In Process</span>
                                    <small class="text-muted d-block">Analysis in progress</small>
                                </div>
                                <div class="timeline-item mt-3">
                                    <span class="badge bg-success">Complete</span>
                                    <small class="text-muted d-block">Report submitted</small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }, 1000);
        }

        function updateCaseStatus(caseId) {
            document.getElementById('statusCaseId').value = caseId;
            const modal = new bootstrap.Modal(document.getElementById('statusModal'));
            modal.show();
        }

        function viewReport(caseId) {
            alert(`Download report for Case #${caseId}\n\nIn a complete implementation, this would download the forensic analysis report.`);
        }

        function startAnalysis() {
            alert('Start Analysis\n\nThis would open a interface to begin forensic analysis of evidence for selected cases.');
        }

        function uploadReport() {
            alert('Upload Report\n\nThis would open a file upload dialog to submit completed forensic reports.');
        }

        function equipmentStatus() {
            alert('Equipment Status\n\nThis would show the current status of all lab equipment including availability and maintenance schedules.');
        }

        function viewAllActivity() {
            alert('View All Activity\n\nThis would show a detailed log of all forensic activities and case updates.');
        }

        // Auto-refresh dashboard every 60 seconds
        setInterval(() => {
            console.log('Refreshing forensic dashboard data...');
            // In production, this would refresh statistics and case updates via AJAX
        }, 60000);

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>