<?php
// moodle/landing.php

require_once('config.php');
require_once($CFG->dirroot . '/course/lib.php');

// Set up the page
$PAGE->set_url(new moodle_url('/landing.php'));
$PAGE->set_pagelayout('landing');  // This tells Moodle to use your landing.php layout
$PAGE->set_title('Welcome to University Learning Portal');
$PAGE->set_heading('');  // Empty heading

// Start output
echo $OUTPUT->header();

// moodle/landing.php - ADD THIS AT VERY TOP
require_once('config.php');

// If already logged in, NEVER show landing page
if (isloggedin() && !isguestuser()) {
    // Immediately redirect to dashboard
header('Location: ' . $CFG->wwwroot . '/my/');
exit;
}

// Rest of your landing page code...
?>


<!-- Your custom HTML for landing page -->
<div class="landing-page">
    <div class="hero-section">
        <div class="container">
            <h1>Welcome to University Learning Portal</h1>
            <p class="lead">Transform your teaching and learning experience</p>
            
            <div class="row mt-5">
                <div class="col-md-6 mb-4">
                    <div class="card teacher-card">
                        <div class="card-body text-center">
                            <i class="fa fa-chalkboard-teacher fa-4x mb-3"></i>
                            <h3>For Teachers</h3>
                            <p>Create courses, manage students, upload materials, and conduct assessments.</p>
                            <a href="<?php echo $CFG->wwwroot; ?>/login/index.php" class="btn btn-primary btn-lg">
                                Teacher Login
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card student-card">
                        <div class="card-body text-center">
                            <i class="fa fa-graduation-cap fa-4x mb-3"></i>
                            <h3>For Students</h3>
                            <p>Access courses, submit assignments, participate in forums, and track progress.</p>
                            <a href="<?php echo $CFG->wwwroot; ?>/login/index.php" class="btn btn-success btn-lg">
                                Student Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="direct-login mt-5 text-center">
                <h4>Already have an account?</h4>
                <a href="<?php echo $CFG->wwwroot; ?>/login/index.php" class="btn btn-outline-light btn-lg">
                    Go to Login Page
                </a>
            </div>
        </div>
    </div>
    
    <footer class="landing-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> University Learning Portal. All Rights Reserved.</p>
        </div>
    </footer>
</div>

<style>
.landing-page {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
/* CHANGED BACKGROUND TO MOODLE ORANGE */
.hero-section {
    background: linear-gradient(135deg, #f98012 0%, #e65100 100%); /* Moodle Orange Gradient */
    color: white;
    padding: 80px 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    text-align: center;
    
}
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    transition: transform 0.3s;
    height: 100%;
}
.card:hover {
    transform: translateY(-10px);
}
.teacher-card {
    background: white;
    color: #333;
}
.student-card {
    background: white;
    color: #333;
}
.btn {
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: bold;
    margin-top: 15px;
    transition: all 0.3s;
}
.btn-primary {
    background: linear-gradient(to right, #1a237e, #283593); /* Dark blue */
    border: none;
}
.btn-primary:hover {
    background: linear-gradient(to right, #283593, #1a237e);
    transform: scale(1.05);
}
.btn-success {
    background: linear-gradient(to right, #1a237e, #283593); /* Moodle Orange */
    border: none;
}
.btn-success:hover {
    background: linear-gradient(to right, #283593, #1a237e);
    transform: scale(1.05);
}
.btn-outline-light {
    border: 2px solid white;
    background-color: transparent ;
    color: white;
}
.btn-outline-light:hover {
    background: white;
    color: #ff7b00cf;
    border-color: white;
}
.landing-footer {
    background: #0d1b2a; /* Dark blue */
    color: white;
    padding: 25px;
    text-align: center;
    font-size: 0.9rem;
}
/* Responsive adjustments */
@media (max-width: 768px) {
    .hero-section {
        padding: 50px 0;
    }
    h1 {
        font-size: 2.2rem;
    }
    .lead {
        font-size: 1.2rem;
    }
}
</style>

<?php
echo $OUTPUT->footer();
?>