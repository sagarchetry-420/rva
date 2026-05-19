<?php
/**
 * ============================================================
 * Forgot Password Page
 * ============================================================
 * Handles sending a password reset link to the user's email
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/PHPMailer/Exception.php';
require_once dirname(__DIR__) . '/includes/PHPMailer/PHPMailer.php';
require_once dirname(__DIR__) . '/includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($conn, $_POST['email']);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Find user by email across all tables (users, students, teachers)
        $query = "SELECT u.user_id, u.username, u.user_type FROM users u 
                  LEFT JOIN students s ON u.user_id = s.user_id 
                  LEFT JOIN teachers t ON u.user_id = t.user_id 
                  WHERE u.email = '$email' OR s.email = '$email' OR t.email = '$email' LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $userId = $user['user_id'];
            $resetToken = bin2hex(random_bytes(32));
            $resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Update the users table with the reset token
            $updateQuery = "UPDATE users SET reset_token = '$resetToken', reset_expires = '$resetExpires' WHERE user_id = $userId";
            mysqli_query($conn, $updateQuery);
            
            // Send the email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = getenv('SMTP_USER') ?: 'rvasupport@gmail.com';
                $mail->Password   = getenv('SMTP_PASS');
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = getenv('SMTP_PORT') ?: 587;

                // Recipients
                $mail->setFrom(getenv('SMTP_USER') ?: 'rvasupport@gmail.com', APP_NAME);
                $mail->addAddress($email);

                // Content
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/auth/reset_password.php?token=" . $resetToken;
                
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "
                    <h2>Password Reset Request</h2>
                    <p>Hello,</p>
                    <p>We received a request to reset your password for Rose Valley Academy.</p>
                    <p>Please click the link below to reset your password. This link is valid for 1 hour.</p>
                    <p><a href='{$resetLink}' style='display:inline-block;padding:10px 20px;background:#1e3c72;color:white;text-decoration:none;border-radius:5px;'>Reset Password</a></p>
                    <p>If you did not request this, please ignore this email.</p>
                    <br>
                    <p>Regards,<br>Rose Valley Academy Support</p>
                ";
                $mail->AltBody = "Please copy and paste this link to reset your password: {$resetLink}";

                $mail->send();
                $success = 'If an account with that email exists, a reset link has been sent.';
            } catch (Exception $e) {
                $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            // For security reasons, still say success even if email not found
            $success = 'If an account with that email exists, a reset link has been sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - School Management System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="forgot-password-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <div class="auth-logo"><i class="fa-solid fa-key"></i></div>
                <h1>Forgot Password</h1>
                <p>Enter your email to receive a reset link</p>
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
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="email">Registered Email Address</label>
                    <div class="input-with-icon">
                        <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Send Reset Link <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
            
            <a href="login.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>
