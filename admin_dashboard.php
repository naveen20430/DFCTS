<?php
/**
 * Admin Dashboard - DFCTS
 */

require_once 'includes/auth.php';

// Require admin role
requireRole('admin');

$user = getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        switch ($_POST['action']) {
            case 'approve_user':
                if (isset($_POST['user_id'])) {
                    if (approveUser($_POST['user_id'])) {
                        $_SESSION['success'] = 'User approved successfully.';
                    } else {
                        $_SESSION['error'] = 'Failed to approve user.';
                    }
                }
                break;
                
            case 'reject_user':
                if (isset($_POST['user_id'])) {
                    if (rejectUser($_POST['user_id'])) {
                        $_SESSION['success'] = 'User rejected successfully.';
                    } else {
                        $_SESSION['error'] = 'Failed to reject user.';
                    }
                }
                break;
                
            case 'assign_officer':
                if (isset($_POST['case_id']) && isset($_POST['officer_id'])) {
                    $sql = "UPDATE forensic_cases SET assigned_officer_id = ? WHERE case_id = ?";
                    try {
                        executeQuery($sql, [$_POST['officer_id'], $_POST['case_id']]);
                        logActivity($user['id'], "Assigned officer to case ID {$_POST['case_id']}", 'Case Management');
                        $_SESSION['success'] = 'Officer assigned successfully.';
                    } catch (Exception $e) {
                        $_SESSION['error'] = 'Failed to assign officer.';
                    }
                }
                break;
                
            case 'update_priority':
                if (isset($_POST['case_id']) && isset($_POST['priority'])) {
                    $sql = "UPDATE forensic_cases SET priority = ? WHERE case_id = ?";
                    try {
                        executeQuery($sql, [$_POST['priority'], $_POST['case_id']]);
                        logActivity($user['id'], "Updated priority for case ID {$_POST['case_id']}", 'Case Management');
                        $_SESSION['success'] = 'Priority updated successfully.';
                    } catch (Exception $e) {
                        $_SESSION['error'] = 'Failed to update priority.';
                    }
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header('Location: admin_dashboard.php');
        exit;
    }
}

// Get dashboard statistics
$stats = [];
$stats['total_users'] = fetchRow("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
$stats['pending_users'] = fetchRow("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'] ?? 0;
$stats['total_firs'] = fetchRow("SELECT COUNT(*) as count FROM firs")['count'] ?? 0;
$stats['forensic_cases'] = fetchRow("SELECT COUNT(*) as count FROM forensic_cases")['count'] ?? 0;
$stats['pending_cases'] = fetchRow("SELECT COUNT(*) as count FROM forensic_cases WHERE status IN ('received', 'in_process')")['count'] ?? 0;
$stats['completed_cases'] = fetchRow("SELECT COUNT(*) as count FROM forensic_cases WHERE status = 'completed'")['count'] ?? 0;

// Get recent activities
$recentActivities = fetchAll(
    "SELECT al.*, u.name as user_name, u.role 
     FROM audit_logs al 
     JOIN users u ON al.user_id = u.id 
     ORDER BY al.timestamp DESC 
     LIMIT 10"
);

// Get pending user registrations
$pendingUsers = getPendingUsers();

// Get recent forensic cases
$recentCases = fetchAll(
    "SELECT fc.*, f.fir_number, f.case_type, f.fir_date, 
            u1.name as police_name, u1.station_name, u1.district,
            u2.name as officer_name
     FROM forensic_cases fc
     JOIN firs f ON fc.fir_id = f.fir_id
     JOIN users u1 ON f.police_id = u1.id
     LEFT JOIN users u2 ON fc.assigned_officer_id = u2.id
     ORDER BY fc.created_at DESC
     LIMIT 15"
);

// Get forensic officers for assignment
$forensicOfficers = getForensicOfficers();

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
    <title>Admin Dashboard - DFCTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1e3a8a, #1e40af);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card.success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .activity-item {
            border-left: 3px solid #007bff;
            padding: 10px 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 0 8px 8px 0;
        }
        .case-card {
            border-left: 4px solid #17a2b8;
            transition: all 0.3s ease;
        }
        .case-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .user-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: white;
        }
        .user-card.pending {
            border-left: 4px solid #ffc107;
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
                            <i class="fas fa-shield-alt display-6 me-3"></i>
                            <div>
                                <h5 class="mb-0">DFCTS</h5>
                                <small>Admin Panel</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-3 border-bottom border-secondary">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-crown fa-2x me-3"></i>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                                <small class="text-light">System Administrator</small>
                            </div>
                        </div>
                    </div>

                    <nav class="nav flex-column p-3">
                        <a href="admin_dashboard.php" class="nav-link active">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="admin_dashboard.php?view=users" class="nav-link">
                            <i class="fas fa-users me-2"></i>User Management
                        </a>
                        <a href="admin_dashboard.php?view=cases" class="nav-link">
                            <i class="fas fa-microscope me-2"></i>Forensic Cases
                        </a>
                        <a href="admin_dashboard.php?view=firs" class="nav-link">
                            <i class="fas fa-file-alt me-2"></i>All FIRs
                        </a>
                        <a href="admin_dashboard.php?view=officers" class="nav-link">
                            <i class="fas fa-user-md me-2"></i>Forensic Officers
                        </a>
                        <a href="audit_log.php" class="nav-link">
                            <i class="fas fa-history me-2"></i>Audit Logs
                        </a>
                        <hr class="border-secondary">
                        <a href="admin_dashboard.php?view=settings" class="nav-link">
                            <i class="fas fa-cog me-2"></i>Settings
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
                            <h2>Admin Dashboard</h2>
                            <p class="text-muted mb-0">System overview and management</p>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="badge bg-success">
                                <i class="fas fa-circle me-1"></i>System Online
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
                                        <h3 class="mb-1"><?php echo $stats['total_users']; ?></h3>
                                        <p class="mb-0">Total Users</p>
                                        <small class="opacity-75"><?php echo $stats['pending_users']; ?> pending approval</small>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card info">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="mb-1"><?php echo $stats['total_firs']; ?></h3>
                                        <p class="mb-0">Total FIRs</p>
                                        <small class="opacity-75"><?php echo $stats['forensic_cases']; ?> need forensic analysis</small>
                                    </div>
                                    <i class="fas fa-file-alt fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card warning">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="mb-1"><?php echo $stats['pending_cases']; ?></h3>
                                        <p class="mb-0">Pending Cases</p>
                                        <small class="opacity-75">Needs attention</small>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-50"></i>
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
                    </div>

                    <!-- Content Rows -->
                    <div class="row">
                        <!-- Pending User Registrations -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-user-plus me-2 text-warning"></i>Pending Registrations
                                    </h5>
                                    <span class="badge bg-warning"><?php echo count($pendingUsers); ?></span>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    <?php if (empty($pendingUsers)): ?>
                                        <div class="text-center py-3 text-muted">
                                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                                            <p class="mb-0">No pending registrations</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($pendingUsers as $pendingUser): ?>
                                            <div class="user-card pending">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($pendingUser['name']); ?></h6>
                                                        <p class="mb-1 text-muted small">
                                                            <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($pendingUser['station_name']); ?>
                                                        </p>
                                                        <p class="mb-1 text-muted small">
                                                            <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($pendingUser['district']); ?>
                                                        </p>
                                                        <p class="mb-1 text-muted small">
                                                            <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($pendingUser['email']); ?>
                                                        </p>
                                                        <small class="text-muted">
                                                            Registered: <?php echo formatDate($pendingUser['created_at']); ?>
                                                        </small>
                                                    </div>
                                                    <div class="btn-group btn-group-sm">
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="approve_user">
                                                            <input type="hidden" name="user_id" value="<?php echo $pendingUser['id']; ?>">
                                                            <button type="submit" class="btn btn-success" 
                                                                    onclick="return confirm('Approve this registration?')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="reject_user">
                                                            <input type="hidden" name="user_id" value="<?php echo $pendingUser['id']; ?>">
                                                            <button type="submit" class="btn btn-danger" 
                                                                    onclick="return confirm('Reject this registration?')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Forensic Cases -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-microscope me-2 text-info"></i>Recent Cases
                                    </h5>
                                    <a href="admin_dashboard.php?view=cases" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    <?php if (empty($recentCases)): ?>
                                        <div class="text-center py-3 text-muted">
                                            <i class="fas fa-microscope fa-2x mb-2"></i>
                                            <p class="mb-0">No recent cases</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach (array_slice($recentCases, 0, 5) as $case): ?>
                                            <div class="case-card card mb-2">
                                                <div class="card-body py-2">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1">
                                                                <a href="#" onclick="viewCaseDetails(<?php echo $case['case_id']; ?>)" 
                                                                   class="text-decoration-none">
                                                                    <?php echo htmlspecialchars($case['fir_number']); ?>
                                                                </a>
                                                            </h6>
                                                            <p class="mb-1 small text-muted">
                                                                <?php echo htmlspecialchars($case['case_type']); ?> • 
                                                                <?php echo htmlspecialchars($case['lab_name']); ?>
                                                            </p>
                                                            <p class="mb-1 small">
                                                                <strong><?php echo htmlspecialchars($case['station_name']); ?></strong>, 
                                                                <?php echo htmlspecialchars($case['district']); ?>
                                                            </p>
                                                            <?php if ($case['officer_name']): ?>
                                                                <p class="mb-1 small text-success">
                                                                    <i class="fas fa-user-md me-1"></i><?php echo htmlspecialchars($case['officer_name']); ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-end">
                                                            <?php echo getStatusBadge($case['status']); ?>
                                                            <br>
                                                            <?php echo getPriorityBadge($case['priority']); ?>
                                                            <div class="mt-1">
                                                                <small class="text-muted">
                                                                    <?php echo formatDate($case['created_at'], 'd-m-Y'); ?>
                                                                </small>
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
                    </div>

                    <!-- Recent Activity -->
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>Recent System Activity
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentActivities)): ?>
                                <div class="text-center py-3 text-muted">
                                    <i class="fas fa-history fa-2x mb-2"></i>
                                    <p class="mb-0">No recent activity</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="activity-item">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <div class="fw-bold small">
                                                            <?php echo htmlspecialchars($activity['action']); ?>
                                                        </div>
                                                        <div class="text-muted small">
                                                            by <?php echo htmlspecialchars($activity['user_name']); ?> 
                                                            (<?php echo ucfirst($activity['role']); ?>)
                                                        </div>
                                                        <div class="text-muted small">
                                                            <?php echo formatDate($activity['timestamp']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="text-muted">
                                                        <i class="fas fa-<?php 
                                                            echo $activity['module'] === 'Authentication' ? 'sign-in-alt' : 
                                                                ($activity['module'] === 'FIR Management' ? 'file-alt' : 
                                                                ($activity['module'] === 'Case Management' ? 'microscope' : 'cog')); 
                                                        ?>"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="audit_log.php" class="btn btn-outline-primary">
                                        <i class="fas fa-history me-2"></i>View All Activity
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Case Details Modal -->
    <div class="modal fade" id="caseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewCaseDetails(caseId) {
            // Show loading state
            document.getElementById('caseModalBody').innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border" role="status">
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
                    <div class="alert alert-info">
                        <h6>Case #${caseId}</h6>
                        <p>This would show detailed case information including FIR details, assigned officer, current status, and case history.</p>
                        <p><small>In a complete implementation, this would fetch real data via AJAX.</small></p>
                    </div>
                `;
            }, 1000);
        }

        // Auto-refresh dashboard every 60 seconds
        setInterval(() => {
            // In production, this would refresh statistics via AJAX
            console.log('Refreshing dashboard data...');
        }, 60000);

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>