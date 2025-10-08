<?php
/**
 * Assign Lab and Manage Cases - DFCTS
 */

require_once 'includes/auth.php';

// Require admin role
requireRole('admin');

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'assign_officer':
                $caseId = $_POST['case_id'] ?? 0;
                $officerId = $_POST['officer_id'] ?? 0;
                
                if ($caseId && $officerId) {
                    try {
                        $sql = "UPDATE forensic_cases SET assigned_officer_id = ?, updated_at = NOW() WHERE case_id = ?";
                        executeQuery($sql, [$officerId, $caseId]);
                        
                        // Get officer and case details for logging
                        $officer = fetchRow("SELECT name FROM users WHERE id = ?", [$officerId]);
                        $case = fetchRow("SELECT f.fir_number FROM forensic_cases fc JOIN firs f ON fc.fir_id = f.fir_id WHERE fc.case_id = ?", [$caseId]);
                        
                        logActivity($user['id'], "Assigned {$officer['name']} to case {$case['fir_number']}", 'Case Management');
                        $_SESSION['success'] = 'Officer assigned successfully.';
                    } catch (Exception $e) {
                        $_SESSION['error'] = 'Failed to assign officer.';
                    }
                }
                break;
                
            case 'update_priority':
                $caseId = $_POST['case_id'] ?? 0;
                $priority = $_POST['priority'] ?? '';
                
                if ($caseId && $priority) {
                    try {
                        $sql = "UPDATE forensic_cases SET priority = ?, updated_at = NOW() WHERE case_id = ?";
                        executeQuery($sql, [$priority, $caseId]);
                        
                        $case = fetchRow("SELECT f.fir_number FROM forensic_cases fc JOIN firs f ON fc.fir_id = f.fir_id WHERE fc.case_id = ?", [$caseId]);
                        logActivity($user['id'], "Updated priority to {$priority} for case {$case['fir_number']}", 'Case Management');
                        $_SESSION['success'] = 'Priority updated successfully.';
                    } catch (Exception $e) {
                        $_SESSION['error'] = 'Failed to update priority.';
                    }
                }
                break;
                
            case 'add_notes':
                $caseId = $_POST['case_id'] ?? 0;
                $notes = sanitizeInput($_POST['admin_notes'] ?? '');
                
                if ($caseId && $notes) {
                    try {
                        $sql = "UPDATE forensic_cases SET admin_notes = ?, updated_at = NOW() WHERE case_id = ?";
                        executeQuery($sql, [$notes, $caseId]);
                        
                        $case = fetchRow("SELECT f.fir_number FROM forensic_cases fc JOIN firs f ON fc.fir_id = f.fir_id WHERE fc.case_id = ?", [$caseId]);
                        logActivity($user['id'], "Added admin notes to case {$case['fir_number']}", 'Case Management');
                        $_SESSION['success'] = 'Notes added successfully.';
                    } catch (Exception $e) {
                        $_SESSION['error'] = 'Failed to add notes.';
                    }
                }
                break;
        }
        
        header('Location: assign_lab.php');
        exit;
    }
}

// Get filter parameters
$labFilter = $_GET['lab'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];

if ($labFilter) {
    $whereConditions[] = "fc.lab_name = ?";
    $params[] = $labFilter;
}

if ($statusFilter) {
    $whereConditions[] = "fc.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter) {
    $whereConditions[] = "fc.priority = ?";
    $params[] = $priorityFilter;
}

if ($searchTerm) {
    $whereConditions[] = "(f.fir_number LIKE ? OR f.case_type LIKE ? OR u1.station_name LIKE ?)";
    $params[] = "%{$searchTerm}%";
    $params[] = "%{$searchTerm}%";
    $params[] = "%{$searchTerm}%";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get forensic cases with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;

$sql = "SELECT fc.*, f.fir_number, f.case_type, f.fir_date, f.summary, f.law_section,
               u1.name as police_name, u1.station_name, u1.district, u1.email as police_email,
               u2.name as officer_name, u2.email as officer_email
        FROM forensic_cases fc
        JOIN firs f ON fc.fir_id = f.fir_id
        JOIN users u1 ON f.police_id = u1.id
        LEFT JOIN users u2 ON fc.assigned_officer_id = u2.id
        {$whereClause}
        ORDER BY 
            CASE fc.priority 
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            fc.created_at DESC";

$pagination = paginate($sql, $params, $page, $perPage);
$cases = $pagination['data'];

// Get forensic officers for assignment
$forensicOfficers = getForensicOfficers();

// Get lab options for filter
$labOptions = getLabOptions();

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
    <title>Case Management - DFCTS</title>
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
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .case-row {
            transition: all 0.3s ease;
        }
        .case-row:hover {
            background-color: #f8f9fa !important;
        }
        .priority-urgent { border-left: 4px solid #dc3545; }
        .priority-high { border-left: 4px solid #fd7e14; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #6c757d; }
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .case-card {
            background: white;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .case-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
                        <a href="admin_dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="admin_dashboard.php?view=users" class="nav-link">
                            <i class="fas fa-users me-2"></i>User Management
                        </a>
                        <a href="assign_lab.php" class="nav-link active">
                            <i class="fas fa-microscope me-2"></i>Case Management
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
                            <h2>Forensic Case Management</h2>
                            <p class="text-muted mb-0">Assign officers, set priorities, and manage case workflow</p>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="badge bg-info">
                                <i class="fas fa-microscope me-1"></i><?php echo $pagination['total_records']; ?> Cases
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

                    <!-- Filters -->
                    <div class="filter-card">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="lab" class="form-label">Laboratory</label>
                                <select class="form-select" name="lab" id="lab">
                                    <option value="">All Labs</option>
                                    <?php foreach ($labOptions as $key => $value): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>" 
                                                <?php echo ($labFilter === $key) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($key); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="">All Status</option>
                                    <option value="received" <?php echo ($statusFilter === 'received') ? 'selected' : ''; ?>>Received</option>
                                    <option value="in_process" <?php echo ($statusFilter === 'in_process') ? 'selected' : ''; ?>>In Process</option>
                                    <option value="completed" <?php echo ($statusFilter === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="on_hold" <?php echo ($statusFilter === 'on_hold') ? 'selected' : ''; ?>>On Hold</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" name="priority" id="priority">
                                    <option value="">All Priority</option>
                                    <option value="urgent" <?php echo ($priorityFilter === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                                    <option value="high" <?php echo ($priorityFilter === 'high') ? 'selected' : ''; ?>>High</option>
                                    <option value="medium" <?php echo ($priorityFilter === 'medium') ? 'selected' : ''; ?>>Medium</option>
                                    <option value="low" <?php echo ($priorityFilter === 'low') ? 'selected' : ''; ?>>Low</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" id="search" 
                                       placeholder="FIR number, case type..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php if ($labFilter || $statusFilter || $priorityFilter || $searchTerm): ?>
                            <div class="mt-3">
                                <a href="assign_lab.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Cases List -->
                    <?php if (empty($cases)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-microscope fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No cases found</h5>
                            <p class="text-muted">Try adjusting your filters or check back later.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cases as $case): ?>
                            <div class="case-card card priority-<?php echo $case['priority']; ?>">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <!-- Case Info -->
                                        <div class="col-md-4">
                                            <h6 class="mb-1">
                                                <a href="#" onclick="viewCaseDetails(<?php echo $case['case_id']; ?>)" 
                                                   class="text-decoration-none text-primary">
                                                    <?php echo htmlspecialchars($case['fir_number']); ?>
                                                </a>
                                            </h6>
                                            <p class="mb-1 text-muted small">
                                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($case['case_type']); ?>
                                            </p>
                                            <p class="mb-1 text-muted small">
                                                <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($case['lab_name']); ?>
                                            </p>
                                            <p class="mb-0 text-muted small">
                                                <i class="fas fa-calendar me-1"></i><?php echo formatDate($case['fir_date'], 'd-m-Y'); ?>
                                            </p>
                                        </div>

                                        <!-- Police Station Info -->
                                        <div class="col-md-3">
                                            <p class="mb-1 small">
                                                <strong><?php echo htmlspecialchars($case['police_name']); ?></strong>
                                            </p>
                                            <p class="mb-1 text-muted small">
                                                <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($case['station_name']); ?>
                                            </p>
                                            <p class="mb-0 text-muted small">
                                                <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($case['district']); ?>
                                            </p>
                                        </div>

                                        <!-- Status & Priority -->
                                        <div class="col-md-2">
                                            <?php echo getStatusBadge($case['status']); ?>
                                            <br>
                                            <?php echo getPriorityBadge($case['priority']); ?>
                                            <?php if ($case['officer_name']): ?>
                                                <br>
                                                <small class="text-success">
                                                    <i class="fas fa-user-md me-1"></i><?php echo htmlspecialchars($case['officer_name']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Actions -->
                                        <div class="col-md-3 text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        data-bs-toggle="modal" data-bs-target="#assignModal"
                                                        onclick="setAssignData(<?php echo $case['case_id']; ?>, '<?php echo htmlspecialchars($case['fir_number']); ?>')">
                                                    <i class="fas fa-user-plus"></i> Assign
                                                </button>
                                                <button type="button" class="btn btn-outline-warning" 
                                                        data-bs-toggle="modal" data-bs-target="#priorityModal"
                                                        onclick="setPriorityData(<?php echo $case['case_id']; ?>, '<?php echo $case['priority']; ?>')">
                                                    <i class="fas fa-exclamation-triangle"></i> Priority
                                                </button>
                                                <button type="button" class="btn btn-outline-info" 
                                                        data-bs-toggle="modal" data-bs-target="#notesModal"
                                                        onclick="setNotesData(<?php echo $case['case_id']; ?>, '<?php echo htmlspecialchars($case['admin_notes'] ?? ''); ?>')">
                                                    <i class="fas fa-sticky-note"></i> Notes
                                                </button>
                                            </div>
                                            <div class="mt-1">
                                                <small class="text-muted">
                                                    Updated: <?php echo formatDate($case['updated_at']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                <small class="text-muted">
                                    Showing <?php echo (($pagination['current_page'] - 1) * $pagination['per_page']) + 1; ?> 
                                    to <?php echo min($pagination['current_page'] * $pagination['per_page'], $pagination['total_records']); ?> 
                                    of <?php echo $pagination['total_records']; ?> cases
                                </small>
                            </div>
                            <?php echo generatePaginationHTML($pagination, 'assign_lab.php'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Officer Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="assign_officer">
                    <input type="hidden" name="case_id" id="assign_case_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Forensic Officer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Assigning officer to case: <strong id="assign_case_number"></strong></p>
                        <div class="mb-3">
                            <label for="officer_id" class="form-label">Select Officer</label>
                            <select class="form-select" name="officer_id" id="officer_id" required>
                                <option value="">Choose officer...</option>
                                <?php foreach ($forensicOfficers as $officer): ?>
                                    <option value="<?php echo $officer['id']; ?>">
                                        <?php echo htmlspecialchars($officer['name']); ?> 
                                        (<?php echo htmlspecialchars($officer['lab_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Officer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Priority Modal -->
    <div class="modal fade" id="priorityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_priority">
                    <input type="hidden" name="case_id" id="priority_case_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Update Case Priority</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="priority_select" class="form-label">Priority Level</label>
                            <select class="form-select" name="priority" id="priority_select" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Update Priority</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Admin Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add_notes">
                    <input type="hidden" name="case_id" id="notes_case_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Admin Notes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Notes</label>
                            <textarea class="form-control" name="admin_notes" id="admin_notes" rows="5"
                                      placeholder="Add administrative notes for this case..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info">Save Notes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setAssignData(caseId, caseNumber) {
            document.getElementById('assign_case_id').value = caseId;
            document.getElementById('assign_case_number').textContent = caseNumber;
        }

        function setPriorityData(caseId, currentPriority) {
            document.getElementById('priority_case_id').value = caseId;
            document.getElementById('priority_select').value = currentPriority;
        }

        function setNotesData(caseId, currentNotes) {
            document.getElementById('notes_case_id').value = caseId;
            document.getElementById('admin_notes').value = currentNotes;
        }

        function viewCaseDetails(caseId) {
            // This would typically show detailed case information
            alert(`View details for case ID: ${caseId}\n\nIn a complete implementation, this would show comprehensive case information.`);
        }
    </script>
</body>
</html>