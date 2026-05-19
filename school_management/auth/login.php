<?php
/**
 * ============================================================
 * Login Page
 * ============================================================
 * Handles user authentication and role-based redirection
 */
require_once dirname(__DIR__) . '/config/database.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = sanitize($conn, $_POST['mobile']);
    $password = md5($_POST['password']);
    
    $query = "SELECT DISTINCT u.* FROM users u LEFT JOIN students s ON u.user_id = s.user_id LEFT JOIN teachers t ON u.user_id = t.user_id WHERE (s.phone = '$mobile' OR s.parent_phone = '$mobile' OR t.phone = '$mobile') AND u.password = '$password' AND u.user_type IN ('student', 'teacher')";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Set session variables
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        
        // Redirect to main router
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    } else {
        $error = 'Invalid mobile number or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Management System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .login-box h1 {
        color: white;
    }
    .input-with-icon input {
        padding-left: 45px !important;
    }
    .input-with-icon {
        position: relative;
    }
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #666;
        z-index: 10;
    }
    .input-with-icon input[type="password"],
    .input-with-icon input#password {
        padding-right: 45px !important;
    }
    .forgot-password-link {
        text-align: right;
        margin-top: 8px;
    }
    .forgot-password-link a {
        color: #1e3c72;
        font-size: 14px;
        text-decoration: none;
    }
    .forgot-password-link a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-logo"><i class="fa-solid fa-university"></i></div>
                <h1>School Management System</h1>
                <p>Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error login-alert">
                    <i class="fa-solid fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="mobile">Mobile Number</label>
                    <div class="input-with-icon">
                        <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                        <input type="text" id="mobile" name="mobile" placeholder="Enter mobile number" required 
                               value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility()">
                            <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                        </span>
                    </div>
                    <div class="forgot-password-link">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-login">
                    Sign In <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
            
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const submitBtn = loginForm.querySelector('.btn-login');
            
            loginForm.addEventListener('submit', function(e) {
                // Prevent multiple submissions
                if (loginForm.checkValidity()) {
                    submitBtn.innerHTML = 'Signing In <i class="fa-solid fa-spinner fa-spin"></i>';
                    submitBtn.classList.add('loading');
                    submitBtn.style.opacity = '0.8';
                    submitBtn.style.cursor = 'not-allowed';
                    
                    // Allow the form to submit normally, but disable button after a brief delay
                    // so the submission still goes through
                    setTimeout(() => {
                        submitBtn.disabled = true;
                    }, 50);
                }
            });
            
            // Toggle password visibility
            window.togglePasswordVisibility = function() {
                const passwordInput = document.getElementById('password');
                const toggleIcon = document.getElementById('togglePasswordIcon');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            };

            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>
