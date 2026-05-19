<?php
/**
 * ============================================================
 * Reset Password Page
 * ============================================================
 * Handles the actual password reset using a secure token
 */
require_once dirname(__DIR__) . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$error = '';
$success = '';
$token = isset($_GET['token']) ? sanitize($conn, $_GET['token']) : '';
$userId = null;

if (empty($token)) {
    $error = 'Invalid or missing reset token.';
} else {
    // Check if token exists and is not expired
    $query = "SELECT user_id FROM users WHERE reset_token = '$token' AND reset_expires > NOW()";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $userId = $user['user_id'];
    } else {
        $error = 'The reset token is invalid or has expired. Please request a new one.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId !== null) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $hashed_password = md5($password);
        
        // Update password and clear token
        $updateQuery = "UPDATE users SET password = '$hashed_password', reset_token = NULL, reset_expires = NULL WHERE user_id = $userId";
        if (mysqli_query($conn, $updateQuery)) {
            $success = 'Your password has been successfully reset. You can now login.';
            $userId = null; // Hide the form
        } else {
            $error = 'An error occurred while resetting your password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - School Management System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="forgot-password-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <div class="auth-logo"><i class="fa-solid fa-lock"></i></div>
                <h1>Reset Password</h1>
                <p>Create a new password for your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
                <a href="login.php" class="btn btn-primary btn-block" style="text-decoration: none;">Go to Login</a>
            <?php endif; ?>
            
            <?php if ($userId !== null): ?>
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="input-with-icon">
                        <span class="input-icon"><i class="fa-solid fa-key"></i></span>
                        <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="6">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-with-icon">
                        <span class="input-icon"><i class="fa-solid fa-check-double"></i></span>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="6">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Update Password <i class="fa-solid fa-save"></i>
                </button>
            </form>
            <?php endif; ?>
            
            <?php if ($userId === null && !$success): ?>
                <a href="forgot_password.php" class="btn btn-primary btn-block" style="text-decoration: none; margin-top: 20px;">Request New Link</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
