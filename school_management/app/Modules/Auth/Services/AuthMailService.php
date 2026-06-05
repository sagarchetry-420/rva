<?php
namespace App\Modules\Auth\Services;

class AuthMailService
{
    public function sendWelcomeEmail(string $toEmail, string $name, string $username, string $password, string $portalName = 'Portal'): bool
    {
        $subject = 'Welcome to Rose Valley Academy - ' . $portalName;
        
        $body = "
        <div style='font-family:Arial,sans-serif;max-width:500px;margin:0 auto;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
            <div style='background:#1e3a5f;color:#fff;padding:20px;text-align:center;'>
                <h2 style='margin:0;'>Rose Valley Academy</h2>
                <p style='margin:5px 0 0;font-size:13px;opacity:0.8;'>Account Credentials</p>
            </div>
            <div style='padding:24px;'>
                <p>Hello <strong>{$name}</strong>,</p>
                <p style='color:#6b7280;'>Your account has been created. Below are your login credentials:</p>
                <table style='width:100%;border-collapse:collapse;margin-bottom:16px;'>
                    <tr><td style='padding:10px 12px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:600;width:120px;'>Email</td><td style='padding:10px 12px;border:1px solid #e5e7eb;'>{$toEmail}</td></tr>
                    <tr><td style='padding:10px 12px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:600;'>Username</td><td style='padding:10px 12px;border:1px solid #e5e7eb;'>{$username}</td></tr>
                    <tr><td style='padding:10px 12px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:600;'>Password</td><td style='padding:10px 12px;border:1px solid #e5e7eb;'>{$password}</td></tr>
                </table>
                <p style='color:#6b7280;font-size:13px;'>Please log in and change your password at your earliest convenience.</p>
            </div>
            <div style='background:#f9fafb;padding:12px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;'>
                &copy; " . date('Y') . " Rose Valley Academy. All rights reserved.
            </div>
        </div>";

        return $this->sendEmail($toEmail, $name, $subject, $body);
    }

    public function sendPasswordResetEmail(string $toEmail, string $name, string $resetLink, string $portalName = 'Portal'): bool
    {
        $subject = 'Password Reset Request - ' . $portalName;
        
        $body = "
        <div style='font-family:Arial,sans-serif;max-width:500px;margin:0 auto;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
            <div style='background:#1e3a5f;color:#fff;padding:20px;text-align:center;'>
                <h2 style='margin:0;'>Rose Valley Academy</h2>
                <p style='margin:5px 0 0;font-size:13px;opacity:0.8;'>Password Reset</p>
            </div>
            <div style='padding:24px;'>
                <p>Hello <strong>{$name}</strong>,</p>
                <p style='color:#6b7280;'>We received a request to reset your password. Click the button below to choose a new one:</p>
                <div style='text-align:center;margin:20px 0;'>
                    <a href='{$resetLink}' style='background-color:#2563eb;color:#fff;text-decoration:none;padding:12px 24px;border-radius:4px;font-weight:bold;display:inline-block;'>Reset Password</a>
                </div>
                <p style='color:#6b7280;font-size:13px;'>If you did not request a password reset, you can safely ignore this email. This link will expire in 1 hour.</p>
            </div>
            <div style='background:#f9fafb;padding:12px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;'>
                &copy; " . date('Y') . " Rose Valley Academy. All rights reserved.
            </div>
        </div>";

        return $this->sendEmail($toEmail, $name, $subject, $body);
    }

    public function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $phpmailerPath = APP_ROOT . '/includes/PHPMailer/PHPMailer.php';
        
        if (file_exists($phpmailerPath)) {
            require_once APP_ROOT . '/includes/PHPMailer/Exception.php';
            require_once $phpmailerPath;
            require_once APP_ROOT . '/includes/PHPMailer/SMTP.php';
            
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = \App::env('SMTP_HOST', 'smtp.gmail.com');
                $mail->SMTPAuth   = true;
                $mail->Username   = \App::env('SMTP_USER', 'test@example.com');
                $mail->Password   = \App::env('SMTP_PASS', 'password');
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = (int)\App::env('SMTP_PORT', 587);

                $mail->setFrom(\App::env('SMTP_USER', 'admin@school.com'), APP_NAME);
                $mail->addAddress($toEmail, $toName);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;

                $mail->send();
                return true;
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                error_log("PHPMailer Error: " . $errorMsg);
                
                // Log strictly to mail_error.log for Super Admin dashboard
                $mailLogPath = APP_ROOT . '/app/Core/mail_error.log';
                $logEntry = date('Y-m-d H:i:s') . " | TO: $toEmail | SUB: $subject | ERROR: " . str_replace(["\r", "\n"], " ", $errorMsg) . "\n";
                @file_put_contents($mailLogPath, $logEntry, FILE_APPEND);
                
                // Do not return false here, let it fall through to the file logging fallback
            }
        }

        // Fallback to PHP native mail()
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . APP_NAME . " <admin@school.com>\r\n";
        
        // Log the email to file in case sendmail is not configured on local WAMP
        $logPath = APP_ROOT . '/uploads/emails.log';
        $logEntry = "\n[" . date('Y-m-d H:i:s') . "] TO: $toEmail | SUB: $subject\nBODY:\n$htmlBody\n-------------------------\n";
        @file_put_contents($logPath, $logEntry, FILE_APPEND);

        @mail($toEmail, $subject, $htmlBody, $headers);
        return true;
    }
}
