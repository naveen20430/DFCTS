<?php
/**
 * Audit Log System - DFCTS
 */

require_once 'includes/auth.php';

// Require admin role
requireRole('admin');

$user = getCurrentUser();

// Get filter parameters
$userFilter = $_GET['user'] ?? '';
$moduleFilter = $_GET['module'] ?? '';
$dateFromFilter = $_GET['date_from'] ?? '';
$dateToFilter = $_GET['date_to'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];

if ($userFilter) {
    $whereConditions[] = "al.user_id = ?";
    $params[] = $userFilter;
}

if ($moduleFilter) {
    $whereConditions[] = "al.module = ?";
    $params[] = $moduleFilter;
}

if ($dateFromFilter) {
    $whereConditions[] = "DATE(al.timestamp) >= ?";
    $params[] = $dateFromFilter;
}

if ($dateToFilter) {
    $whereConditions[] = "DATE(al.timestamp) <= ?";
    $params[] = $dateToFilter;
}

if ($searchTerm) {
    $whereConditions[] = "(al.action LIKE ? OR al.details LIKE ?)";
    $params[] = "%{$searchTerm}%";
    $params[] = "%{$searchTerm}%";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get audit logs with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;

$sql = "SELECT al.*, u.name as user_name, u.role, u.email, u.station_name, u.district
        FROM audit_logs al
        JOIN users u ON al.user_id = u.id
        {$whereClause}
        ORDER BY al.timestamp DESC";

$pagination = paginate($sql, $params, $page, $perPage);
$logs = $pagination['data'];

// Get unique users for filter
$users = fetchAll("SELECT id, name, role, station_name FROM users ORDER BY name");

// Get unique modules for filter
$modules = fetchAll("SELECT DISTINCT module FROM audit_logs ORDER BY module");

// Get statistics
$stats = [];
$stats['total_logs'] = fetchRow("SELECT COUNT(*) as count FROM audit_logs")['count'] ?? 0;
$stats['today_logs'] = fetchRow("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(timestamp) = CURDATE()")['count'] ?? 0;
$stats['week_logs'] = fetchRow("SELECT COUNT(*) as count FROM audit_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0;
$stats['unique_users'] = fetchRow("SELECT COUNT(DISTINCT user_id) as count FROM audit_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - DFCTS</title>
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
        .stat-card {
            border: none;
            border-radius: 15px;
            padding: 20px;
            color: white;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
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
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .log-entry {
            background: white;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .log-entry:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .log-entry.auth {
            border-left-color: #28a745;
        }
        .log-entry.fir {
            border-left-color: #17a2b8;
        }
        .log-entry.case {
            border-left-color: #ffc107;
        }
        .log-entry.user {
            border-left-color: #dc3545;
        }
        .role-badge {
            font-size: 0.75em;
            padding: 2px 8px;
            border-radius: 10px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 10px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #007bff;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #007bff;
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
                        <a href="assign_lab.php" class="nav-link">
                            <i class="fas fa-microscope me-2"></i>Case Management
                        </a>
                        <a href="admin_dashboard.php?view=firs" class="nav-link">
                            <i class="fas fa-file-alt me-2"></i>All FIRs
                        </a>
                        <a href="admin_dashboard.php?view=officers" class="nav-link">
                            <i class="fas fa-user-md me-2"></i>Forensic Officers
                        </a>
                        <a href="audit_log.php" class="nav-link active">
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
                            <h2>System Audit Logs</h2>
                            <p class="text-muted mb-0">Track all system activities and user actions</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary" onclick="exportLogs()">
                                <i class="fas fa-download me-2"></i>Export Logs
                            </button>
                            <div class="badge bg-success">
                                <i class="fas fa-circle me-1"></i>Live Monitoring
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card primary">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h4 class="mb-1"><?php echo number_format($stats['total_logs']); ?></h4>
                                        <p class="mb-0">Total Logs</p>
                                        <small class="opacity-75">All time</small>
                                    </div>
                                    <i class="fas fa-history fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card success">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h4 class="mb-1"><?php echo number_format($stats['today_logs']); ?></h4>
                                        <p class="mb-0">Today's Activity</p>
                                        <small class="opacity-75"><?php echo date('d M Y'); ?></small>
                                    </div>
                                    <i class="fas fa-calendar-day fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card warning">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h4 class="mb-1"><?php echo number_format($stats['week_logs']); ?></h4>
                                        <p class="mb-0">This Week</p>
                                        <small class="opacity-75">Last 7 days</small>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="stat-card info">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h4 class="mb-1"><?php echo number_format($stats['unique_users']); ?></h4>
                                        <p class="mb-0">Active Users</p>
                                        <small class="opacity-75">Last 30 days</small>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filter-card">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="user" class="form-label">User</label>
                                <select class="form-select" name="user" id="user">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" 
                                                <?php echo ($userFilter == $u['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['name']); ?> 
                                            (<?php echo ucfirst($u['role']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="module" class="form-label">Module</label>
                                <select class="form-select" name="module" id="module">
                                    <option value="">All Modules</option>
                                    <?php foreach ($modules as $m): ?>
                                        <option value="<?php echo htmlspecialchars($m['module']); ?>" 
                                                <?php echo ($moduleFilter === $m['module']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($m['module']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" name="date_from" id="date_from" 
                                       value="<?php echo htmlspecialchars($dateFromFilter); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" name="date_to" id="date_to" 
                                       value="<?php echo htmlspecialchars($dateToFilter); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" id="search" 
                                       placeholder="Action, details..." value="<?php echo htmlspecialchars($searchTerm); ?>">
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
                        <?php if ($userFilter || $moduleFilter || $dateFromFilter || $dateToFilter || $searchTerm): ?>
                            <div class="mt-3">
                                <a href="audit_log.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </a>
                                <span class="text-muted ms-3">
                                    Showing <?php echo $pagination['total_records']; ?> filtered results
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Audit Logs -->
                    <?php if (empty($logs)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No audit logs found</h5>
                            <p class="text-muted">Try adjusting your filters or check back later.</p>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list me-2"></i>Activity Timeline
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php foreach ($logs as $log): ?>
                                        <div class="timeline-item">
                                            <div class="log-entry card 
                                                <?php 
                                                    echo strtolower(str_replace(' ', '', $log['module']));
                                                ?>">
                                                <div class="card-body py-3">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8">
                                                            <h6 class="mb-1">
                                                                <?php echo htmlspecialchars($log['action']); ?>
                                                            </h6>
                                                            <p class="text-muted small mb-1">
                                                                <i class="fas fa-user me-1"></i>
                                                                <strong><?php echo htmlspecialchars($log['user_name']); ?></strong>
                                                                <span class="role-badge bg-<?php 
                                                                    echo $log['role'] === 'admin' ? 'danger' : 
                                                                        ($log['role'] === 'forensic' ? 'warning' : 'info'); 
                                                                ?>">
                                                                    <?php echo ucfirst($log['role']); ?>
                                                                </span>
                                                                <?php if ($log['station_name']): ?>
                                                                    • <?php echo htmlspecialchars($log['station_name']); ?>
                                                                <?php endif; ?>
                                                            </p>
                                                            <?php if ($log['details']): ?>
                                                                <p class="text-muted small mb-0">
                                                                    <i class="fas fa-info-circle me-1"></i>
                                                                    <?php echo htmlspecialchars($log['details']); ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-md-4 text-end">
                                                            <div class="d-flex flex-column align-items-end">
                                                                <span class="badge bg-<?php 
                                                                    echo $log['module'] === 'Authentication' ? 'success' : 
                                                                        ($log['module'] === 'FIR Management' ? 'info' : 
                                                                        ($log['module'] === 'Case Management' ? 'warning' : 'secondary')); 
                                                                ?> mb-1">
                                                                    <?php echo htmlspecialchars($log['module']); ?>
                                                                </span>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    <?php echo formatDate($log['timestamp']); ?>
                                                                </small>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-globe me-1"></i>
                                                                    <?php echo htmlspecialchars($log['ip_address']); ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Pagination -->
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div>
                                        <small class="text-muted">
                                            Showing <?php echo (($pagination['current_page'] - 1) * $pagination['per_page']) + 1; ?> 
                                            to <?php echo min($pagination['current_page'] * $pagination['per_page'], $pagination['total_records']); ?> 
                                            of <?php echo $pagination['total_records']; ?> logs
                                        </small>
                                    </div>
                                    <?php 
                                    $queryParams = http_build_query($_GET);
                                    $baseUrl = 'audit_log.php' . ($queryParams ? '?' . $queryParams : '');
                                    echo generatePaginationHTML($pagination, 'audit_log.php');
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportLogs() {
            // Get current filters
            const params = new URLSearchParams(window.location.search);
            params.append('export', 'csv');
            
            // Create download link
            const url = 'audit_log.php?' + params.toString();
            
            // For demonstration - in production, you'd implement actual CSV export
            alert('Export functionality would be implemented here.\n\nThis would generate a CSV file with the current filtered logs.');
        }

        // Auto-refresh every 30 seconds when no filters are applied
        <?php if (!$userFilter && !$moduleFilter && !$dateFromFilter && !$dateToFilter && !$searchTerm): ?>
        setInterval(() => {
            if (window.location.search === '') {
                window.location.reload();
            }
        }, 30000);
        <?php endif; ?>

        // Highlight recent activities (less than 5 minutes old)
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const recentThreshold = 5 * 60 * 1000; // 5 minutes in milliseconds
            
            document.querySelectorAll('.timeline-item').forEach(item => {
                const timeElement = item.querySelector('small[title]');
                if (timeElement) {
                    const logTime = new Date(timeElement.getAttribute('title'));
                    if (now - logTime < recentThreshold) {
                        item.classList.add('recent-activity');
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-success position-absolute top-0 start-100 translate-middle';
                        badge.innerHTML = 'NEW';
                        badge.style.fontSize = '0.6em';
                        item.style.position = 'relative';
                        item.appendChild(badge);
                    }
                }
            });
        });
    </script>
</body>
</html>