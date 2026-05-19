<?php
/**
 * ============================================================
 * Admin Login Page
 * ============================================================
 * Handles administrator authentication
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
    
    // Only allow admin user type
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password' AND user_type = 'admin'";
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
        $error = 'Invalid admin credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - School Management System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-login-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <div class="auth-logo"><i class="fa-solid fa-shield-halved"></i></div>
                <h1>Admin Portal</h1>
                <p>Secure Administrator Access</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" id="adminLoginForm">
                <div class="form-group">
                    <label for="username">Admin Username</label>
                    <div class="input-with-icon">
                        <span class="input-icon"><i class="fa-solid fa-user-shield"></i></span>
                        <input type="text" id="username" name="username" placeholder="Enter admin username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Secure Login <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
            
        </div>
    </div>
</body>
</html>
