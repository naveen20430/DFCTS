<?php
/**
 * Police Station Dashboard - DFCTS
 */

require_once 'includes/auth.php';

// Require police role
requireRole('police');

$user = getCurrentUser();

// Get dashboard statistics
$stats = [];
$stats['total_firs'] = fetchRow(
    "SELECT COUNT(*) as count FROM firs WHERE police_id = ?", 
    [$user['id']]
)['count'] ?? 0;

$stats['forensic_cases'] = fetchRow(
    "SELECT COUNT(*) as count FROM firs WHERE police_id = ? AND forensic_required = 1", 
    [$user['id']]
)['count'] ?? 0;

$stats['pending_cases'] = fetchRow(
    "SELECT COUNT(*) as count FROM forensic_cases fc 
     JOIN firs f ON fc.fir_id = f.fir_id 
     WHERE f.police_id = ? AND fc.status IN ('received', 'in_process')", 
    [$user['id']]
)['count'] ?? 0;

$stats['completed_cases'] = fetchRow(
    "SELECT COUNT(*) as count FROM forensic_cases fc 
     JOIN firs f ON fc.fir_id = f.fir_id 
     WHERE f.police_id = ? AND fc.status = 'completed'", 
    [$user['id']]
)['count'] ?? 0;

// Get recent FIRs
$recentFirs = fetchAll(
    "SELECT f.*, fc.status as forensic_status, fc.priority, fc.assigned_officer_id, u.name as officer_name
     FROM firs f 
     LEFT JOIN forensic_cases fc ON f.fir_id = fc.fir_id
     LEFT JOIN users u ON fc.assigned_officer_id = u.id
     WHERE f.police_id = ? 
     ORDER BY f.created_at DESC 
     LIMIT 10", 
    [$user['id']]
);

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police Dashboard - DFCTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #2c3e50, #34495e);
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
            background-color: rgba(255,255,255,0.1);
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
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
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
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
                                <small>Police Dashboard</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-3 border-bottom border-secondary">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-circle fa-2x me-3"></i>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                                <small class="text-light"><?php echo htmlspecialchars($user['station_name']); ?></small>
                                <br><small class="text-light"><?php echo htmlspecialchars($user['district']); ?></small>
                            </div>
                        </div>
                    </div>

                    <nav class="nav flex-column p-3">
                        <a href="police_dashboard.php" class="nav-link active">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="add_fir.php" class="nav-link">
                            <i class="fas fa-plus-circle me-2"></i>Submit FIR
                        </a>
                        <a href="?view=firs" class="nav-link">
                            <i class="fas fa-file-alt me-2"></i>My FIRs
                        </a>
                        <a href="?view=forensic" class="nav-link">
                            <i class="fas fa-microscope me-2"></i>Forensic Cases
                        </a>
                        <hr class="border-secondary">
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
                            <h2>Police Dashboard</h2>
                            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['name']); ?></p>
                        </div>
                        <div>
                            <a href="add_fir.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Submit New FIR
                            </a>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="mb-1"><?php echo $stats['total_firs']; ?></h3>
                                        <p class="mb-0">Total FIRs</p>
                                    </div>
                                    <i class="fas fa-file-alt fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card info">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="mb-1"><?php echo $stats['forensic_cases']; ?></h3>
                                        <p class="mb-0">Forensic Cases</p>
                                    </div>
                                    <i class="fas fa-microscope fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card warning">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h3 class="mb-1"><?php echo $stats['pending_cases']; ?></h3>
                                        <p class="mb-0">Pending Cases</p>
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
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent FIRs Table -->
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt me-2"></i>Recent FIRs
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentFirs)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No FIRs submitted yet</h5>
                                    <p class="text-muted">Click the button above to submit your first FIR</p>
                                    <a href="add_fir.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Submit FIR
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>FIR Number</th>
                                                <th>Date</th>
                                                <th>Case Type</th>
                                                <th>Forensic Required</th>
                                                <th>Lab Assigned</th>
                                                <th>Status</th>
                                                <th>Priority</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentFirs as $fir): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($fir['fir_number']); ?></strong>
                                                    </td>
                                                    <td><?php echo formatDate($fir['fir_date'], 'd-m-Y'); ?></td>
                                                    <td><?php echo htmlspecialchars($fir['case_type']); ?></td>
                                                    <td>
                                                        <?php if ($fir['forensic_required']): ?>
                                                            <span class="badge bg-primary">Yes</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($fir['lab_assigned']): ?>
                                                            <small><?php echo htmlspecialchars($fir['lab_assigned']); ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($fir['forensic_required']): ?>
                                                            <?php echo getStatusBadge($fir['forensic_status'] ?? 'received'); ?>
                                                        <?php else: ?>
                                                            <?php echo getStatusBadge($fir['status']); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($fir['priority']): ?>
                                                            <?php echo getPriorityBadge($fir['priority']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-outline-primary" 
                                                                    onclick="viewFIR(<?php echo $fir['fir_id']; ?>)">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <?php if ($fir['forensic_required'] && $fir['forensic_status'] === 'completed'): ?>
                                                                <button type="button" class="btn btn-outline-success" 
                                                                        onclick="viewReport(<?php echo $fir['fir_id']; ?>)">
                                                                    <i class="fas fa-download"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-bolt me-2"></i>Quick Actions
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="add_fir.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Submit New FIR
                                        </a>
                                        <a href="?view=forensic" class="btn btn-outline-info">
                                            <i class="fas fa-microscope me-2"></i>View Forensic Cases
                                        </a>
                                        <a href="?view=firs" class="btn btn-outline-secondary">
                                            <i class="fas fa-file-alt me-2"></i>View All FIRs
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-info-circle me-2"></i>System Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="small">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Station:</span>
                                            <strong><?php echo htmlspecialchars($user['station_name']); ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>District:</span>
                                            <strong><?php echo htmlspecialchars($user['district']); ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Last Login:</span>
                                            <strong><?php echo date('d-m-Y H:i'); ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>System Status:</span>
                                            <span class="badge bg-success">Online</span>
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

    <!-- FIR Details Modal -->
    <div class="modal fade" id="firModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">FIR Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="firModalBody">
                    <!-- FIR details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewFIR(firId) {
            // Show loading state
            document.getElementById('firModalBody').innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading FIR details...</p>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('firModal'));
            modal.show();
            
            // Load FIR details (would typically be an AJAX call)
            setTimeout(() => {
                document.getElementById('firModalBody').innerHTML = `
                    <div class="alert alert-info">
                        <h6>FIR #${firId}</h6>
                        <p>This would show detailed FIR information including case summary, evidence, and current status.</p>
                        <p><small>In a complete implementation, this would fetch real data via AJAX.</small></p>
                    </div>
                `;
            }, 1000);
        }

        function viewReport(firId) {
            alert(`Download report for FIR #${firId}\n\nIn a complete implementation, this would download the forensic report.`);
        }

        // Auto-refresh statistics every 30 seconds
        setInterval(() => {
            // In a complete implementation, this would refresh the statistics via AJAX
            console.log('Refreshing dashboard statistics...');
        }, 30000);
    </script>
</body>
</html>