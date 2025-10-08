<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DFCTS Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .setup-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin: 2rem auto;
            max-width: 1000px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .step-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
            transition: all 0.3s ease;
        }
        .step-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .step-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
        .btn-setup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: transform 0.2s ease;
            display: inline-block;
        }
        .btn-setup:hover {
            transform: scale(1.05);
            color: white;
            text-decoration: none;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <div class="header">
                <h1><i class="fas fa-shield-alt me-3"></i>DFCTS Database Setup</h1>
                <p class="mb-0">Digital Forensics Crime Tracking System</p>
            </div>
            
            <div class="p-4">
                <div class="row mb-4">
                    <div class="col-md-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <h5>Complete Database</h5>
                        <p class="text-muted small">Tables, indexes, and relationships</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5>User Accounts</h5>
                        <p class="text-muted small">Admin, Police, and Forensic users</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-shield-check"></i>
                        </div>
                        <h5>Security Features</h5>
                        <p class="text-muted small">Authentication and approval system</p>
                    </div>
                </div>

                <h3 class="mb-4"><i class="fas fa-rocket text-primary me-2"></i>Setup Instructions</h3>

                <div class="step-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number">1</div>
                        <div>
                            <h5 class="mb-1">Create Database & Tables</h5>
                            <p class="text-muted mb-0">Set up the complete database structure with all tables and indexes</p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary">Required</span>
                            <span class="text-muted ms-2">Creates: users, firs, forensic_cases, audit_logs</span>
                        </div>
                        <a href="setup_database.php" class="btn-setup">
                            <i class="fas fa-database me-2"></i>Run Setup
                        </a>
                    </div>
                </div>

                <div class="step-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number">2</div>
                        <div>
                            <h5 class="mb-1">Add Police Station Accounts</h5>
                            <p class="text-muted mb-0">Create 19 police station accounts across all Himachal Pradesh districts</p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-warning">Pending Status</span>
                            <span class="text-muted ms-2">Creates: 19 police accounts + sample FIRs</span>
                        </div>
                        <a href="add_police_accounts.php" class="btn-setup">
                            <i class="fas fa-user-plus me-2"></i>Add Police
                        </a>
                    </div>
                </div>

                <div class="step-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number">3</div>
                        <div>
                            <h5 class="mb-1">Approve Test Accounts</h5>
                            <p class="text-muted mb-0">Auto-approve 3 police accounts for immediate testing</p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-success">Optional</span>
                            <span class="text-muted ms-2">Approves: Shimla, Kangra, Mandi police stations</span>
                        </div>
                        <a href="approve_test_accounts.php" class="btn-setup">
                            <i class="fas fa-check-circle me-2"></i>Auto Approve
                        </a>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-sign-in-alt text-primary me-2"></i>Access System</h5>
                        <div class="list-group">
                            <a href="login.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Page
                                <span class="text-muted d-block small">Main system login</span>
                            </a>
                            <a href="admin_dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user-shield me-2"></i>Admin Dashboard
                                <span class="text-muted d-block small">admin@dfcts.gov.in / password123</span>
                            </a>
                            <a href="police_dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user-check me-2"></i>Police Dashboard
                                <span class="text-muted d-block small">shimla.city@hppolice.gov.in / password123</span>
                            </a>
                            <a href="forensic_dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-microscope me-2"></i>Forensic Dashboard
                                <span class="text-muted d-block small">rajesh@sfsl-junga.gov.in / password123</span>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-info-circle text-info me-2"></i>Documentation</h5>
                        <div class="list-group">
                            <a href="LOGIN_CREDENTIALS.txt" class="list-group-item list-group-item-action" target="_blank">
                                <i class="fas fa-key me-2"></i>Login Credentials
                                <span class="text-muted d-block small">All account details</span>
                            </a>
                            <a href="ADMIN_APPROVAL_GUIDE.md" class="list-group-item list-group-item-action" target="_blank">
                                <i class="fas fa-book me-2"></i>Admin Guide
                                <span class="text-muted d-block small">Complete setup instructions</span>
                            </a>
                            <div class="list-group-item">
                                <i class="fas fa-database me-2"></i>Database Status
                                <span class="text-muted d-block small">
                                    <?php
                                    try {
                                        $pdo = new PDO("mysql:host=localhost;dbname=dfcts;charset=utf8mb4", "root", "", [
                                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                                        ]);
                                        
                                        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                                        $pendingCount = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
                                        
                                        echo "<span class='badge bg-success'>Connected</span> ";
                                        echo "$userCount users, $pendingCount pending";
                                    } catch (Exception $e) {
                                        echo "<span class='badge bg-danger'>Not Connected</span> Run setup first";
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <h6><i class="fas fa-lightbulb me-2"></i>Quick Start</h6>
                    <p class="mb-2">For fastest setup:</p>
                    <ol class="mb-0">
                        <li>Run <strong>Setup Database</strong> (Step 1)</li>
                        <li>Run <strong>Add Police</strong> (Step 2)</li>
                        <li>Run <strong>Auto Approve</strong> (Step 3) - Optional but recommended</li>
                        <li>Login as admin and start testing!</li>
                    </ol>
                </div>

                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Requirements</h6>
                    <ul class="mb-0">
                        <li><strong>MySQL Server:</strong> Must be running</li>
                        <li><strong>PHP Extensions:</strong> PDO, PDO_MySQL</li>
                        <li><strong>Database Access:</strong> root user with create privileges</li>
                        <li><strong>Web Server:</strong> Apache/Nginx with PHP support</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>