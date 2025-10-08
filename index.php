<?php
/**
 * Landing Page for Digital Forensic Crime Tracking System (DFCTS)
 */

require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
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
            header('Location: login.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Forensic Crime Tracking System (DFCTS)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 86, 179, 0.8), rgba(0, 86, 179, 0.8)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23f8f9fa" width="1200" height="600"/><path fill="%23dee2e6" d="M0 400h1200v200H0z"/></svg>');
            background-size: cover;
            background-position: center;
            color: white;
            min-height: 80vh;
            display: flex;
            align-items: center;
        }
        .feature-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .navbar-brand img {
            height: 40px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-shield-alt me-2"></i>
                <span>DFCTS</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Digital Forensic Crime Tracking System</h1>
                    <p class="lead mb-4">
                        A comprehensive platform for managing forensic investigations across Himachal Pradesh. 
                        Streamline FIR submissions, track forensic cases, and enhance coordination between 
                        police stations and forensic laboratories.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="login.php" class="btn btn-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
                        </a>
                        <a href="register.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Register Station
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-microscope display-1 mb-3"></i>
                        <h3>Secure • Efficient • Transparent</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="mb-4">About DFCTS</h2>
                    <p class="lead">
                        The Digital Forensic Crime Tracking System is designed to modernize and streamline 
                        the forensic investigation process in Himachal Pradesh. It provides a centralized 
                        platform for police stations to submit FIRs requiring forensic analysis and enables 
                        efficient case management across multiple forensic laboratories.
                    </p>
                </div>
            </div>
            <div class="row mt-5">
                <div class="col-md-4 text-center">
                    <i class="fas fa-users-cog text-primary display-4 mb-3"></i>
                    <h4>Multi-Role Access</h4>
                    <p>Separate interfaces for Police Stations, Forensic Admin, and Lab Officers</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-clock text-primary display-4 mb-3"></i>
                    <h4>Real-Time Tracking</h4>
                    <p>Monitor case progress and status updates in real-time</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-shield-alt text-primary display-4 mb-3"></i>
                    <h4>Secure & Compliant</h4>
                    <p>Built with security best practices and audit logging</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Key Features</h2>
                <p class="lead">Comprehensive tools for efficient forensic case management</p>
            </div>
            <div class="row">
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-file-alt text-primary display-5 mb-3"></i>
                            <h5 class="card-title">FIR Management</h5>
                            <p class="card-text">Submit and track FIRs with forensic investigation requirements</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-microscope text-primary display-5 mb-3"></i>
                            <h5 class="card-title">Lab Assignment</h5>
                            <p class="card-text">Automatic assignment to SFSL Junga, RFSL Dharamshala, or RFSL Mandi</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line text-primary display-5 mb-3"></i>
                            <h5 class="card-title">Status Tracking</h5>
                            <p class="card-text">Real-time case status updates from submission to completion</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-check text-primary display-5 mb-3"></i>
                            <h5 class="card-title">User Management</h5>
                            <p class="card-text">Role-based access control for different user types</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-envelope text-primary display-5 mb-3"></i>
                            <h5 class="card-title">Email Notifications</h5>
                            <p class="card-text">Automated email alerts for important case updates</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-history text-primary display-5 mb-3"></i>
                            <h5 class="card-title">Audit Logging</h5>
                            <p class="card-text">Comprehensive audit trail for all system activities</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Labs Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Connected Forensic Laboratories</h2>
                <p class="lead">Integrated with leading forensic facilities in Himachal Pradesh</p>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-building text-primary display-5 mb-3"></i>
                            <h5 class="card-title">SFSL Junga</h5>
                            <p class="card-text">State Forensic Science Laboratory, Junga</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-building text-primary display-5 mb-3"></i>
                            <h5 class="card-title">RFSL Dharamshala</h5>
                            <p class="card-text">Regional Forensic Science Laboratory, Dharamshala</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-building text-primary display-5 mb-3"></i>
                            <h5 class="card-title">RFSL Mandi</h5>
                            <p class="card-text">Regional Forensic Science Laboratory, Mandi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-primary text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Digital Forensic Crime Tracking System</h5>
                    <p class="mb-0">Himachal Pradesh Police Department</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">
                        <i class="fas fa-phone me-2"></i>Support: 1800-XXX-XXXX<br>
                        <i class="fas fa-envelope me-2"></i>Email: support@dfcts.gov.in
                    </p>
                </div>
            </div>
            <hr class="my-3">
            <div class="text-center">
                <small>&copy; <?php echo date('Y'); ?> DFCTS - All rights reserved | Government of Himachal Pradesh</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>