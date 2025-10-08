<?php
/**
 * Registration Page for Police Stations - DFCTS
 */

require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        // Collect and sanitize form data
        $userData = [
            'name' => sanitizeInput($_POST['name'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'station_name' => sanitizeInput($_POST['station_name'] ?? ''),
            'district' => sanitizeInput($_POST['district'] ?? ''),
            'mobile' => sanitizeInput($_POST['mobile'] ?? '')
        ];

        // Validation
        if (empty($userData['name'])) {
            $errors[] = 'Officer name is required.';
        }

        if (empty($userData['email']) || !validateEmail($userData['email'])) {
            $errors[] = 'Valid email address is required.';
        }

        if (empty($userData['password']) || strlen($userData['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if ($userData['password'] !== $userData['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($userData['station_name'])) {
            $errors[] = 'Police station name is required.';
        }

        if (empty($userData['district'])) {
            $errors[] = 'District selection is required.';
        }

        if (empty($userData['mobile']) || !validateMobile($userData['mobile'])) {
            $errors[] = 'Valid mobile number is required.';
        }

        // Check if email already exists
        if (empty($errors)) {
            $existingUser = fetchRow("SELECT id FROM users WHERE email = ?", [$userData['email']]);
            if ($existingUser) {
                $errors[] = 'Email address is already registered.';
            }
        }

        // Register user if no errors
        if (empty($errors)) {
            if (registerUser($userData)) {
                $success = true;
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police Station Registration - DFCTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .registration-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        .registration-card {
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
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="registration-card">
                        <div class="row g-0">
                            <div class="col-md-5 bg-primary text-white d-flex flex-column justify-content-center p-5">
                                <div class="text-center">
                                    <i class="fas fa-shield-alt display-2 mb-4"></i>
                                    <h3 class="mb-3">DFCTS Registration</h3>
                                    <p class="lead">Register your police station to access the Digital Forensic Crime Tracking System</p>
                                    <hr class="my-4">
                                    <div class="small">
                                        <i class="fas fa-check-circle me-2"></i>Secure Platform<br>
                                        <i class="fas fa-check-circle me-2"></i>Real-time Tracking<br>
                                        <i class="fas fa-check-circle me-2"></i>Expert Support
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7 p-5">
                                <div class="text-center mb-4">
                                    <h4>Police Station Registration</h4>
                                    <p class="text-muted">Fill out the form to request access</p>
                                </div>

                                <?php if ($success): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Registration Successful!</strong><br>
                                        Your registration has been submitted and is pending approval. 
                                        You will receive an email notification once your account is approved.
                                    </div>
                                    <div class="text-center">
                                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                                    </div>
                                <?php else: ?>
                                    <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-circle me-2"></i>
                                            <strong>Please correct the following errors:</strong>
                                            <ul class="mb-0 mt-2">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?php echo htmlspecialchars($error); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" novalidate>
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="name" class="form-label">
                                                    <i class="fas fa-user me-1"></i>Officer Name *
                                                </label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="email" class="form-label">
                                                    <i class="fas fa-envelope me-1"></i>Email Address *
                                                </label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="password" class="form-label">
                                                    <i class="fas fa-lock me-1"></i>Password *
                                                </label>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                                <div class="form-text">Minimum 8 characters</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="confirm_password" class="form-label">
                                                    <i class="fas fa-lock me-1"></i>Confirm Password *
                                                </label>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="station_name" class="form-label">
                                                    <i class="fas fa-building me-1"></i>Police Station Name *
                                                </label>
                                                <input type="text" class="form-control" id="station_name" name="station_name" 
                                                       value="<?php echo htmlspecialchars($_POST['station_name'] ?? ''); ?>" 
                                                       placeholder="e.g., Police Station Shimla" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="district" class="form-label">
                                                    <i class="fas fa-map-marker-alt me-1"></i>District *
                                                </label>
                                                <select class="form-select" id="district" name="district" required>
                                                    <option value="">Select District</option>
                                                    <?php foreach (getDistricts() as $district): ?>
                                                        <option value="<?php echo htmlspecialchars($district); ?>"
                                                                <?php echo (($_POST['district'] ?? '') === $district) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($district); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="mobile" class="form-label">
                                                    <i class="fas fa-phone me-1"></i>Mobile Number *
                                                </label>
                                                <input type="tel" class="form-control" id="mobile" name="mobile" 
                                                       value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>" 
                                                       placeholder="10-digit mobile number" maxlength="10" required>
                                            </div>
                                        </div>

                                        <div class="form-check mb-4">
                                            <input class="form-check-input" type="checkbox" id="terms" required>
                                            <label class="form-check-label" for="terms">
                                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a> *
                                            </label>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-user-plus me-2"></i>Submit Registration
                                            </button>
                                        </div>
                                    </form>

                                    <div class="text-center mt-4">
                                        <p class="text-muted">
                                            Already have an account? 
                                            <a href="login.php" class="text-primary">Login here</a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms & Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms & Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By registering for the Digital Forensic Crime Tracking System (DFCTS), you agree to comply with these terms and conditions.</p>
                    
                    <h6>2. Authorized Use</h6>
                    <p>This system is for official police department use only. Access is restricted to authorized personnel for legitimate forensic investigation purposes.</p>
                    
                    <h6>3. Data Security</h6>
                    <p>Users must maintain the confidentiality of their login credentials and report any unauthorized access immediately.</p>
                    
                    <h6>4. Compliance</h6>
                    <p>All users must comply with applicable laws and departmental policies when using this system.</p>
                    
                    <h6>5. System Monitoring</h6>
                    <p>All activities within the system are logged and monitored for security and compliance purposes.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const mobile = document.getElementById('mobile').value;
            const terms = document.getElementById('terms').checked;
            
            let isValid = true;
            let errors = [];
            
            if (password.length < 8) {
                errors.push('Password must be at least 8 characters long.');
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                errors.push('Passwords do not match.');
                isValid = false;
            }
            
            if (!/^[6-9]\d{9}$/.test(mobile)) {
                errors.push('Please enter a valid 10-digit mobile number.');
                isValid = false;
            }
            
            if (!terms) {
                errors.push('You must agree to the Terms & Conditions.');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please correct the following errors:\n\n' + errors.join('\n'));
            }
        });
        
        // Mobile number input restriction
        document.getElementById('mobile').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>