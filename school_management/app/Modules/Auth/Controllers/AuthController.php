<?php
namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Validators\LoginValidator;

/**
 * AuthController — Handles login, logout, password reset
 */
class AuthController extends \Controller
{
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    /**
     * Show login form
     */
    public function showLoginForm(): void
    {
        // Already logged in? Redirect to dashboard
        if (isLoggedIn()) {
            $url = $this->authService->getRedirectUrl(getUserType());
            $this->redirect($url);
            return;
        }

        $this->render('Modules/Auth/Views/login', [
            'pageTitle' => 'Login',
        ], 'auth');
    }

    /**
     * Process login
     */
    public function login(): void
    {
        // Validate CSRF
        $this->validateCsrf();

        // Validate input
        $validator = new LoginValidator();
        $data = $this->allInput();

        if (!$validator->validate($data)) {
            $this->flash('error', $validator->firstError());
            setOldInput($data);
            $this->redirect(moduleUrl('auth', 'login'));
            return;
        }

        // Authenticate
        $result = $this->authService->authenticate(
            trim($data['email']),
            $data['password']
        );

        if (!$result['success']) {
            $this->flash('error', $result['message']);
            setOldInput(['email' => $data['email']]);
            $this->redirect(moduleUrl('auth', 'login'));
            return;
        }

        // Success — redirect based on role
        $this->flash('success', 'Welcome back, ' . getUsername() . '!');
        $redirectUrl = $this->authService->getRedirectUrl(getUserType());
        $this->redirect($redirectUrl);
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        $this->authService->logout();
        $this->flash('success', 'You have been logged out successfully.');
        $this->redirect(moduleUrl('auth', 'login'));
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword(): void
    {
        $this->render('Modules/Auth/Views/forgot_password', [
            'pageTitle' => 'Forgot Password',
        ], 'auth');
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(): void
    {
        $this->validateCsrf();
        $email = trim($this->input('email', ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Please enter a valid email address.');
            $this->redirect(moduleUrl('auth', 'forgot-password'));
            return;
        }

        $result = $this->authService->generateResetToken($email);

        if ($result['success']) {
            // Send email with reset link
            $mailService = new \App\Modules\Auth\Services\AuthMailService();
            $token = $result['token'];
            $user = $result['user'];
            $name = $user['username'] ?? 'User';
            
            // Build the absolute reset link
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $domainName = $_SERVER['HTTP_HOST'];
            $baseDir = dirname($_SERVER['PHP_SELF']);
            $resetLink = $protocol . $domainName . $baseDir . "/auth/reset-password?token=" . $token;

            $mailService->sendPasswordResetEmail($user['email'], $name, $resetLink);
            
            $this->flash('success', 'If an account exists with that email, a reset link has been sent.');
        } else {
            // Don't reveal whether email exists (security)
            $this->flash('success', 'If an account exists with that email, a reset link has been sent.');
        }

        $this->redirect(moduleUrl('auth', 'login'));
    }

    /**
     * Show reset password form
     */
    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            $this->flash('error', 'Invalid reset link.');
            $this->redirect(moduleUrl('auth', 'login'));
            return;
        }

        $this->render('Modules/Auth/Views/reset_password', [
            'pageTitle' => 'Reset Password',
            'token'     => $token,
        ], 'auth');
    }

    /**
     * Process password reset
     */
    public function resetPassword(): void
    {
        $this->validateCsrf();
        $token = $this->input('token', '');
        $password = $this->input('password', '');
        $confirm = $this->input('password_confirmation', '');

        if (empty($password) || strlen($password) < 6) {
            $this->flash('error', 'Password must be at least 6 characters.');
            $this->redirect('/auth/reset-password?token=' . $token);
            return;
        }

        if ($password !== $confirm) {
            $this->flash('error', 'Passwords do not match.');
            $this->redirect('/auth/reset-password?token=' . $token);
            return;
        }

        $result = $this->authService->resetPassword($token, $password);

        if ($result['success']) {
            $this->flash('success', $result['message']);
            $this->redirect(moduleUrl('auth', 'login'));
        } else {
            $this->flash('error', $result['message']);
            $this->redirect(moduleUrl('auth', 'forgot-password'));
        }
    }
}
