<?php
/**
 * Login Page for DFCTS - All User Types
 */

require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            if (loginUser($email, $password)) {
                // Redirect based on role
                switch ($_SESSION['user_role']) {
                    case 'admin':
                        header('Location: admin_dashboard.php');
                        break;
                    case 'police':
                        header('Location: police_dashboard.php');
                        break;
                    case 'forensic':
                        header('Location: forensic_dashboard.php');
                        break;
                    default:
                        header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Invalid email or password, or account not approved yet.';
            }
        }
    }
}

// Check for session messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

$success = '';
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DFCTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
        }
        .user-type-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .user-type-card:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="login-card">
                        <div class="row g-0">
                            <div class="col-lg-6 bg-primary text-white d-flex flex-column justify-content-center p-5">
                                <div class="text-center">
                                    <i class="fas fa-shield-alt display-2 mb-4"></i>
                                    <h2 class="mb-3">Welcome to DFCTS</h2>
                                    <p class="lead mb-4">Digital Forensic Crime Tracking System</p>
                                    <div class="row text-center">
                                        <div class="col-12 mb-3">
                                            <div class="user-type-card">
                                                <i class="fas fa-user-tie display-6 mb-2"></i>
                                                <h5>Police Stations</h5>
                                                <small>Submit FIRs & Track Cases</small>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <div class="user-type-card">
                                                <i class="fas fa-user-cog display-6 mb-2"></i>
                                                <h5>Forensic Admin</h5>
                                                <small>Manage Cases & Users</small>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="user-type-card">
                                                <i class="fas fa-microscope display-6 mb-2"></i>
                                                <h5>Lab Officers</h5>
                                                <small>Update Case Status & Reports</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 p-5">
                                <div class="text-center mb-4">
                                    <h3>Sign In</h3>
                                    <p class="text-muted">Enter your credentials to access the system</p>
                                </div>

                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($success)): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?php echo htmlspecialchars($success); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    
                                    <div class="mb-4">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>Email Address
                                        </label>
                                        <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                               placeholder="Enter your email" required>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock me-1"></i>Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control form-control-lg" id="password" name="password" 
                                                   placeholder="Enter your password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">
                                            Remember me
                                        </label>
                                    </div>

                                    <div class="d-grid mb-4">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                        </button>
                                    </div>
                                </form>

                                <div class="text-center">
                                    <p class="text-muted mb-2">
                                        Don't have an account?
                                    </p>
                                    <a href="register.php" class="btn btn-outline-primary">
                                        <i class="fas fa-user-plus me-2"></i>Register Police Station
                                    </a>
                                </div>

                                <hr class="my-4">

                                <!-- Demo Accounts Info -->
                                <div class="text-center">
                                    <small class="text-muted">
                                        <strong>Demo Accounts:</strong><br>
                                        Admin: admin@dfcts.gov.in | password<br>
                                        Forensic: rajesh@sfsl-junga.gov.in | password
                                    </small>
                                </div>

                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <a href="#" class="text-decoration-none">Forgot Password?</a> | 
                                        <a href="#" class="text-decoration-none">Contact Support</a>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">System Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="badge bg-success me-3">Online</div>
                        <div>
                            <strong>Database Connection</strong><br>
                            <small class="text-muted">All systems operational</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="badge bg-success me-3">Active</div>
                        <div>
                            <strong>Email Service</strong><br>
                            <small class="text-muted">Notifications enabled</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="badge bg-success me-3">Secure</div>
                        <div>
                            <strong>Security Status</strong><br>
                            <small class="text-muted">SSL encryption active</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="position-fixed bottom-0 start-0 p-3">
        <small class="text-white">
            <i class="fas fa-info-circle me-1"></i>
            <a href="#" class="text-white text-decoration-none" data-bs-toggle="modal" data-bs-target="#statusModal">
                System Status
            </a>
        </small>
    </div>

    <div class="position-fixed bottom-0 end-0 p-3">
        <small class="text-white">
            <i class="fas fa-phone me-1"></i>Help: 1800-XXX-XXXX
        </small>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please enter both email and password.');
                return;
            }
            
            if (!email.includes('@')) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });

        // Auto-focus on email field
        document.getElementById('email').focus();

        // Show loading state on form submission
        document.querySelector('form').addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            const original = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing in...';
            button.disabled = true;
            
            // Reset button after 5 seconds (in case of error)
            setTimeout(() => {
                button.innerHTML = original;
                button.disabled = false;
            }, 5000);
        });
    </script>
</body>
</html>