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
    $username = sanitize($conn, $_POST['username']);
    $password = md5($_POST['password']);
    
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Set session variables
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        
        // Redirect to main router
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    } else {
        $error = 'Invalid username or password. Please try again.';
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
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-login">
                    Sign In <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="login-footer">
                <p><strong>Default Login Credentials:</strong></p>
                <div class="credentials-grid">
                    <div class="credential-item">
                        <span class="credential-role admin">Admin</span>
                        <span>admin / admin123</span>
                    </div>
                    <div class="credential-item">
                        <span class="credential-role teacher">Teacher</span>
                        <span>teacher1 / teacher123</span>
                    </div>
                    <div class="credential-item">
                        <span class="credential-role student">Student</span>
                        <span>student1 / student123</span>
                    </div>
                </div>
            </div>
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
