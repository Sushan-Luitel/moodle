<?php
// theme/uniproject/layout/login.php - FULL SCREEN MOODLE ORANGE DESIGN
echo $OUTPUT->doctype();
?>
<html>
<head>
    <title>University Learning Portal - Login</title>
    <style>
        /* FULL SCREEN DESIGN */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body.pagelayout-login {
            background: linear-gradient(135deg, #f98012 0%, #e65100 100%); /* Moodle Orange Gradient */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            margin: 0;
        }
        
        /* LEFT SIDE - Branding Section */
        .login-branding {
            flex: 1;
            background: rgba(0, 0, 0, 0.1);
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .branding-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.2);
            z-index: 1;
        }
        
        .branding-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
        }
        
        .university-logo {
            font-size: 72px;
            margin-bottom: 30px;
            color: white;
        }
        
        .university-name {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .university-tagline {
            font-size: 20px;
            line-height: 1.6;
            opacity: 0.9;
            margin-bottom: 40px;
        }
        
        .features-list {
            list-style: none;
            margin-top: 40px;
        }
        
        .features-list li {
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
        }
        
        .features-list li:before {
            content: "✓";
            color: #4CAF50;
            font-weight: bold;
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* RIGHT SIDE - Login Form */
        .login-container {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        
        .login-form-wrapper {
            width: 100%;
            max-width: 450px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-title {
            color: #333;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: #666;
            font-size: 16px;
        }
        
        /* FORM STYLING */
        .moodle-login-form {
            width: 100%;
        }
        
        /* Form Groups */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            color: #555;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Text Inputs */
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus {
            outline: none;
            border-color: #f98012;
            background: white;
            box-shadow: 0 0 0 3px rgba(249, 128, 18, 0.1);
        }
        
        input[type="text"]:hover,
        input[type="password"]:hover,
        input[type="email"]:hover {
            border-color: #b0b0b0;
        }
        
        /* Buttons */
        .btn {
            display: block;
            width: 100%;
            padding: 18px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            margin-bottom: 15px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #f98012 0%, #e65100 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(249, 128, 18, 0.3);
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #e65100 0%, #f98012 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(249, 128, 18, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-guest {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e0e0e0;
        }
        
        .btn-guest:hover {
            background: #e9ecef;
            border-color: #b0b0b0;
        }
        
        .btn-forgot {
            background: transparent;
            color: #f98012;
            border: 2px solid transparent;
            padding: 12px;
        }
        
        .btn-forgot:hover {
            background: rgba(249, 128, 18, 0.1);
            border-color: rgba(249, 128, 18, 0.2);
        }
        
        /* Checkbox */
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .remember-me input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            accent-color: #f98012;
        }
        
        .remember-me label {
            color: #666;
            font-size: 14px;
            cursor: pointer;
        }
        
        /* Links */
        .login-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .login-links a {
            color: #f98012;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            margin: 0 10px;
        }
        
        .login-links a:hover {
            text-decoration: underline;
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 40px;
            color: #888;
            font-size: 13px;
            line-height: 1.5;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            body.pagelayout-login {
                flex-direction: column;
            }
            
            .login-branding {
                padding: 40px 20px;
                text-align: center;
            }
            
            .university-name {
                font-size: 32px;
            }
            
            .login-container {
                padding: 30px 20px;
            }
        }
        
        /* Moodle Form Specific Overrides */
        #login-form input[type="submit"] {
            display: none; /* Hide default Moodle submit button */
        }
        
        /* Custom placeholders for Moodle form elements */
        #username,
        #password {
            background-position: 95% center;
            background-repeat: no-repeat;
            padding-left: 20px;
            padding-right: 50px;
        }
        
        #username {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%23999" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>');
        }
        
        #password {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%23999" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>');
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-form-wrapper {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body class="pagelayout-login">
    <!-- LEFT SIDE - Branding -->
    <div class="login-branding">
        <div class="branding-overlay"></div>
        <div class="branding-content">
            <div class="university-logo">🎓</div>
            <h1 class="university-name">University Learning Portal</h1>
            <p class="university-tagline">
                A comprehensive digital learning environment designed to enhance teaching 
                and learning experiences for educators and students alike.
            </p>
            
            <ul class="features-list">
                <li>Access courses anytime, anywhere</li>
                <li>Interactive learning materials</li>
                <li>Real-time grade tracking</li>
                <li>Collaborative discussion forums</li>
                <li>Secure and reliable platform</li>
            </ul>
        </div>
    </div>
    
    <!-- RIGHT SIDE - Login Form -->
    <div class="login-container">
        <div class="login-form-wrapper">
            <div class="login-header">
                <h2 class="login-title">Welcome Back</h2>
                <p class="login-subtitle">Sign in to access your dashboard</p>
            </div>
            
            <!-- Moodle login form - THIS LINE IS CRITICAL -->
            <div id="login-form">
                <?php echo $OUTPUT->main_content(); ?>
            </div>
            
            <div class="login-links">
                <a href="<?php echo $CFG->wwwroot; ?>/login/forgot_password.php">Forgot Password?</a>
                <a href="<?php echo $CFG->wwwroot; ?>/login/signup.php">Create New Account</a>
                <a href="<?php echo $CFG->wwwroot; ?>/help.php">Help Center</a>
            </div>
            
            <div class="login-footer">
                <p>© <?php echo date('Y'); ?> University Name. All rights reserved.</p>
                <p>v2.0 | Secure Login System</p>
            </div>
        </div>
    </div>

    <script>
        // Add some interactivity to Moodle's form
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for Moodle form to load
            setTimeout(function() {
                // Style the username field
                const usernameInput = document.querySelector('input[name="username"]');
                if (usernameInput) {
                    usernameInput.placeholder = "Enter your username";
                    usernameInput.className = "";
                    usernameInput.style.cssText = 'width: 100%; padding: 16px 20px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; background: #f8f9fa;';
                    
                    // Wrap in styled container
                    const usernameContainer = document.createElement('div');
                    usernameContainer.className = 'form-group';
                    usernameInput.parentNode.insertBefore(usernameContainer, usernameInput);
                    usernameContainer.appendChild(usernameInput);
                    
                    // Add label
                    const usernameLabel = document.createElement('label');
                    usernameLabel.className = 'form-label';
                    //usernameLabel.textContent = 'Username';
                    usernameContainer.insertBefore(usernameLabel, usernameInput);
                }
                
                // Style the password field
                const passwordInput = document.querySelector('input[name="password"]');
                if (passwordInput) {
                    passwordInput.placeholder = "Enter your password";
                    passwordInput.className = "";
                    passwordInput.style.cssText = 'width: 100%; padding: 16px 20px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; background: #f8f9fa;';
                    
                    // Wrap in styled container
                    const passwordContainer = document.createElement('div');
                    passwordContainer.className = 'form-group';
                    passwordInput.parentNode.insertBefore(passwordContainer, passwordInput);
                    passwordContainer.appendChild(passwordInput);
                    
                    // Add label
                    const passwordLabel = document.createElement('label');
                    passwordLabel.className = 'form-label';
                    //passwordLabel.textContent = 'Password';
                    passwordContainer.insertBefore(passwordLabel, passwordInput);
                }
                
                // Style the submit button
                const submitButton = document.querySelector('input[type="submit"], button[type="submit"]');
                if (submitButton) {
                    submitButton.className = 'btn btn-login';
                    submitButton.value = 'Sign In';
                    submitButton.style.cssText = 'display: block; width: 100%; padding: 18px; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; background: linear-gradient(135deg, #f98012 0%, #e65100 100%); color: white; cursor: pointer;';
                }
                
                // Style the remember me checkbox
                const rememberCheckbox = document.querySelector('input[name="rememberusername"]');
                if (rememberCheckbox) {
                    const rememberContainer = document.createElement('div');
                    rememberContainer.className = 'remember-me';
                    rememberCheckbox.parentNode.insertBefore(rememberContainer, rememberCheckbox);
                    rememberContainer.appendChild(rememberCheckbox);
                    
                    const rememberLabel = document.querySelector('label[for="rememberusername"]');
                    if (rememberLabel) {
                        rememberContainer.appendChild(rememberLabel);
                    }
                }
                
            }, 100); // Small delay to ensure Moodle form loads
        });
    </script>
</body>
</html>