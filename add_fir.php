<?php
/**
 * Add FIR Page - DFCTS
 */

require_once 'includes/auth.php';

// Require police role
requireRole('police');

$user = getCurrentUser();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        // Collect and sanitize form data
        $firData = [
            'fir_date' => sanitizeInput($_POST['fir_date'] ?? ''),
            'case_type' => sanitizeInput($_POST['case_type'] ?? ''),
            'summary' => sanitizeInput($_POST['summary'] ?? ''),
            'forensic_required' => isset($_POST['forensic_required']),
            'lab_assigned' => sanitizeInput($_POST['lab_assigned'] ?? ''),
            'law_section' => sanitizeInput($_POST['law_section'] ?? ''),
            'approval_notes' => sanitizeInput($_POST['approval_notes'] ?? '')
        ];

        // Validation
        if (empty($firData['fir_date'])) {
            $errors[] = 'FIR date is required.';
        }

        if (empty($firData['case_type'])) {
            $errors[] = 'Case type is required.';
        }

        if (empty($firData['summary']) || strlen($firData['summary']) < 20) {
            $errors[] = 'Case summary is required and must be at least 20 characters.';
        }

        if ($firData['forensic_required'] && empty($firData['lab_assigned'])) {
            $errors[] = 'Lab assignment is required when forensic investigation is requested.';
        }

        if ($firData['forensic_required'] && empty($firData['law_section'])) {
            $errors[] = 'Law section is required when forensic investigation is requested.';
        }

        // Submit FIR if no errors
        if (empty($errors)) {
            try {
                beginTransaction();

                // Generate FIR number
                $firNumber = generateFIRNumber($user['district']);

                // Insert FIR
                $sql = "INSERT INTO firs (police_id, fir_number, fir_date, case_type, summary, 
                                        forensic_required, lab_assigned, law_section, approval_notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                executeQuery($sql, [
                    $user['id'],
                    $firNumber,
                    $firData['fir_date'],
                    $firData['case_type'],
                    $firData['summary'],
                    $firData['forensic_required'] ? 1 : 0,
                    $firData['lab_assigned'] ?: null,
                    $firData['law_section'] ?: null,
                    $firData['approval_notes'] ?: null
                ]);

                $firId = getLastInsertId();

                // If forensic required, create forensic case entry
                if ($firData['forensic_required']) {
                    $forensicSql = "INSERT INTO forensic_cases (fir_id, lab_name, status) 
                                   VALUES (?, ?, 'received')";
                    
                    executeQuery($forensicSql, [$firId, $firData['lab_assigned']]);

                    // Send notification
                    $notificationData = array_merge($firData, [
                        'fir_number' => $firNumber,
                        'station_name' => $user['station_name'],
                        'district' => $user['district'],
                        'police_email' => $user['email'],
                        'police_name' => $user['name']
                    ]);
                    
                    sendForensicRequestNotification($notificationData);
                }

                // Log activity
                logActivity(
                    $user['id'], 
                    "Submitted FIR {$firNumber}" . ($firData['forensic_required'] ? ' with forensic request' : ''), 
                    'FIR Management',
                    "Lab: {$firData['lab_assigned']}, Case Type: {$firData['case_type']}"
                );

                commit();
                $success = true;
                $generatedFirNumber = $firNumber;

            } catch (Exception $e) {
                rollback();
                error_log("FIR submission failed: " . $e->getMessage());
                $errors[] = 'Failed to submit FIR. Please try again.';
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
    <title>Submit FIR - DFCTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .form-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        .forensic-section {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .forensic-section.active {
            background: #e3f2fd;
            border-color: #2196f3;
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
                        <a href="police_dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="add_fir.php" class="nav-link active">
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
                            <h2>Submit New FIR</h2>
                            <p class="text-muted mb-0">Fill out the form below to submit a new FIR</p>
                        </div>
                        <a href="police_dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>

                    <?php if ($success): ?>
                        <div class="form-section">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>FIR Submitted Successfully!</strong><br>
                                Your FIR has been assigned number: <strong><?php echo htmlspecialchars($generatedFirNumber); ?></strong>
                                <?php if (isset($_POST['forensic_required'])): ?>
                                    <br>Forensic investigation request has been sent to <?php echo htmlspecialchars($_POST['lab_assigned']); ?>.
                                <?php endif; ?>
                            </div>
                            <div class="text-center">
                                <a href="police_dashboard.php" class="btn btn-primary me-2">
                                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                                </a>
                                <a href="add_fir.php" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i>Submit Another FIR
                                </a>
                            </div>
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

                        <form method="POST" id="firForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <!-- Basic FIR Information -->
                            <div class="form-section">
                                <h4 class="mb-4">
                                    <i class="fas fa-file-alt me-2 text-primary"></i>Basic FIR Information
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="fir_date" class="form-label">
                                            <i class="fas fa-calendar me-1"></i>FIR Date *
                                        </label>
                                        <input type="date" class="form-control" id="fir_date" name="fir_date" 
                                               value="<?php echo $_POST['fir_date'] ?? date('Y-m-d'); ?>" 
                                               max="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label for="case_type" class="form-label">
                                            <i class="fas fa-tags me-1"></i>Case Type *
                                        </label>
                                        <select class="form-select" id="case_type" name="case_type" required>
                                            <option value="">Select Case Type</option>
                                            <?php foreach (getCaseTypeOptions() as $key => $value): ?>
                                                <option value="<?php echo htmlspecialchars($key); ?>"
                                                        <?php echo (($_POST['case_type'] ?? '') === $key) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($value); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="summary" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>Case Summary *
                                    </label>
                                    <textarea class="form-control" id="summary" name="summary" rows="6" 
                                              placeholder="Provide a detailed summary of the case..." required
                                              minlength="20"><?php echo htmlspecialchars($_POST['summary'] ?? ''); ?></textarea>
                                    <div class="form-text">
                                        <span id="summaryCount">0</span> characters (minimum 20 required)
                                    </div>
                                </div>
                            </div>

                            <!-- Forensic Investigation Section -->
                            <div class="form-section">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="forensic_required" 
                                               name="forensic_required" <?php echo isset($_POST['forensic_required']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="forensic_required">
                                            <i class="fas fa-microscope me-2 text-primary"></i>
                                            Forensic Investigation Required
                                        </label>
                                    </div>
                                </div>

                                <div class="forensic-section" id="forensicSection" 
                                     style="<?php echo isset($_POST['forensic_required']) ? '' : 'display: none;'; ?>">
                                    <h5 class="mb-3">
                                        <i class="fas fa-flask me-2"></i>Forensic Investigation Details
                                    </h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="lab_assigned" class="form-label">
                                                <i class="fas fa-building me-1"></i>Assign to Laboratory *
                                            </label>
                                            <select class="form-select" id="lab_assigned" name="lab_assigned">
                                                <option value="">Select Laboratory</option>
                                                <?php foreach (getLabOptions() as $key => $value): ?>
                                                    <option value="<?php echo htmlspecialchars($key); ?>"
                                                            <?php echo (($_POST['lab_assigned'] ?? '') === $key) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($value); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="law_section" class="form-label">
                                                <i class="fas fa-gavel me-1"></i>Law Section(s) *
                                            </label>
                                            <input type="text" class="form-control" id="law_section" name="law_section"
                                                   value="<?php echo htmlspecialchars($_POST['law_section'] ?? ''); ?>"
                                                   placeholder="e.g., IPC 420, 468, 471">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="approval_notes" class="form-label">
                                            <i class="fas fa-comment me-1"></i>Additional Notes for Lab
                                        </label>
                                        <textarea class="form-control" id="approval_notes" name="approval_notes" rows="3"
                                                  placeholder="Any specific requirements or notes for the forensic team..."><?php echo htmlspecialchars($_POST['approval_notes'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Note:</strong> Once submitted, this FIR will be automatically forwarded to the selected laboratory 
                                        and the forensic admin will be notified via email.
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Section -->
                            <div class="form-section">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-2">Review and Submit</h5>
                                        <p class="text-muted mb-0">
                                            Please review all information before submitting. Once submitted, 
                                            the FIR cannot be modified.
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button type="reset" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-undo me-2"></i>Reset Form
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Submit FIR
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle forensic section
        document.getElementById('forensic_required').addEventListener('change', function() {
            const forensicSection = document.getElementById('forensicSection');
            const labSelect = document.getElementById('lab_assigned');
            const lawSection = document.getElementById('law_section');
            
            if (this.checked) {
                forensicSection.style.display = 'block';
                forensicSection.classList.add('active');
                labSelect.required = true;
                lawSection.required = true;
            } else {
                forensicSection.style.display = 'none';
                forensicSection.classList.remove('active');
                labSelect.required = false;
                lawSection.required = false;
                labSelect.value = '';
                lawSection.value = '';
                document.getElementById('approval_notes').value = '';
            }
        });

        // Character counter for summary
        const summaryField = document.getElementById('summary');
        const summaryCount = document.getElementById('summaryCount');
        
        function updateCharCount() {
            const count = summaryField.value.length;
            summaryCount.textContent = count;
            summaryCount.className = count >= 20 ? 'text-success' : 'text-danger';
        }
        
        summaryField.addEventListener('input', updateCharCount);
        updateCharCount();

        // Form validation
        document.getElementById('firForm').addEventListener('submit', function(e) {
            const summary = document.getElementById('summary').value;
            const forensicRequired = document.getElementById('forensic_required').checked;
            const labAssigned = document.getElementById('lab_assigned').value;
            const lawSection = document.getElementById('law_section').value;
            
            let errors = [];
            
            if (summary.length < 20) {
                errors.push('Case summary must be at least 20 characters long.');
            }
            
            if (forensicRequired) {
                if (!labAssigned) {
                    errors.push('Please select a laboratory for forensic investigation.');
                }
                if (!lawSection.trim()) {
                    errors.push('Please specify the law sections.');
                }
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Please correct the following errors:\n\n' + errors.join('\n'));
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            submitBtn.disabled = true;
            
            // Reset button after 10 seconds (in case of error)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });

        // Initialize forensic section state
        document.getElementById('forensic_required').dispatchEvent(new Event('change'));
    </script>
</body>
</html>