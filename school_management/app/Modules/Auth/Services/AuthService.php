<?php
namespace App\Modules\Auth\Services;

use App\Modules\Auth\Repositories\UserRepository;

/**
 * AuthService — Business logic for authentication
 */
class AuthService
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    /**
     * Authenticate user with email/username and password
     * Supports MD5 → bcrypt auto-migration
     */
    public function authenticate(string $identifier, string $password, string $ipAddress = ''): array
    {
        if ($ipAddress !== '') {
            $attempts = $this->userRepo->getLoginAttempts($ipAddress, $identifier);
            if ($attempts && $attempts['attempts'] >= 5) {
                $lastAttempt = strtotime($attempts['last_attempt']);
                $fifteenMinsAgo = strtotime('-15 minutes');
                if ($lastAttempt > $fifteenMinsAgo) {
                    return ['success' => false, 'message' => 'Too many failed attempts. Please try again in 15 minutes.'];
                } else {
                    $this->userRepo->clearLoginAttempts($ipAddress, $identifier);
                }
            }
        }

        $user = $this->userRepo->findByEmailOrUsername($identifier);

        if (!$user) {
            if ($ipAddress !== '') {
                $this->userRepo->incrementLoginAttempts($ipAddress, $identifier);
            }
            return ['success' => false, 'message' => 'No account found with that email or username.'];
        }

        // Check if account is active
        if (isset($user['is_active']) && !$user['is_active']) {
            return ['success' => false, 'message' => 'Your account has been deactivated. Contact admin.'];
        }

        // Additional check for teachers and students: if today is past their leaving date
        if ($user['user_type'] === 'teacher') {
            $db = \Database::getInstance();
            $teacher = $db->fetch("SELECT leaving_date FROM teachers WHERE user_id = ?", [$user['user_id']]);
            if ($teacher && !empty($teacher['leaving_date'])) {
                if (strtotime(date('Y-m-d')) > strtotime($teacher['leaving_date'])) {
                    return ['success' => false, 'message' => 'Your account is inactive since ' . date('d M, Y', strtotime($teacher['leaving_date'])) . '. Contact admin.'];
                }
            }
        } elseif ($user['user_type'] === 'student') {
            $db = \Database::getInstance();
            $student = $db->fetch("SELECT leaving_date FROM students WHERE user_id = ?", [$user['user_id']]);
            if ($student && !empty($student['leaving_date'])) {
                if (strtotime(date('Y-m-d')) > strtotime($student['leaving_date'])) {
                    return ['success' => false, 'message' => 'Your account is inactive since ' . date('d M, Y', strtotime($student['leaving_date'])) . '. Contact admin.'];
                }
            }
        }

        $storedHash = $user['password_hash'];
        $authenticated = false;

        // Try bcrypt first (new format)
        if (password_verify($password, $storedHash)) {
            $authenticated = true;
            // Rehash if algorithm has been updated
            if (password_needs_rehash($storedHash, PASSWORD_BCRYPT)) {
                $this->userRepo->updatePassword($user['user_id'], password_hash($password, PASSWORD_BCRYPT));
            }
        }
        // Fallback: check legacy MD5 hash and auto-migrate
        elseif (strlen($storedHash) === 32 && md5($password) === $storedHash) {
            $authenticated = true;
            // Upgrade MD5 → bcrypt
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $this->userRepo->updatePassword($user['user_id'], $newHash);
        }

        if (!$authenticated) {
            if ($ipAddress !== '') {
                $this->userRepo->incrementLoginAttempts($ipAddress, $identifier);
            }
            return ['success' => false, 'message' => 'Incorrect password. Please try again.'];
        }

        if ($ipAddress !== '') {
            $this->userRepo->clearLoginAttempts($ipAddress, $identifier);
        }

        // Create session
        $this->createSession($user);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Create session for authenticated user
     */
    private function createSession(array $user): void
    {
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['user_email'] = $user['email'];

        // Regenerate session ID for security
        session_regenerate_id(true);
    }

    /**
     * Destroy session (logout)
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Generate password reset token
     */
    public function generateResetToken(string $email): array
    {
        $user = $this->userRepo->findByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'No account found with that email address.'];
        }

        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->userRepo->updateResetToken($user['user_id'], $token, $expiry);

        return [
            'success' => true,
            'token'   => $token,
            'user'    => $user,
        ];
    }

    /**
     * Reset password using token
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $user = $this->userRepo->findByResetToken($token);
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired reset token.'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->userRepo->updatePassword($user['user_id'], $hashedPassword);
        $this->userRepo->updateResetToken($user['user_id'], null, null);

        return ['success' => true, 'message' => 'Password has been reset successfully.'];
    }

    /**
     * Hash a password using bcrypt
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Get the redirect URL based on user role
     */
    public function getRedirectUrl(string $role): string
    {
        return match($role) {
            'admin'   => '/admin/dashboard',
            'teacher' => '/teacher/dashboard',
            'student' => '/student/dashboard',
            default   => '/auth/login',
        };
    }
}
