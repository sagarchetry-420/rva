<?php
/**
 * RoleMiddleware — Checks if user has the required role
 */

class RoleMiddleware
{
    public function handle(array $route): void
    {
        // Skip if no role requirement
        if (empty($route['role'])) return;

        if (!isLoggedIn()) {
            setFlashMessage('error', 'Please log in to continue.');
            redirect(moduleUrl('auth', 'login'));
        }

        $userRole = getUserType();
        $requiredRole = $route['role'];

        if ($userRole !== $requiredRole) {
            setFlashMessage('error', 'You do not have permission to access this page.');

            // Redirect to their own dashboard
            switch ($userRole) {
                case 'admin':   redirect(moduleUrl('admin', 'dashboard')); break;
                case 'teacher': redirect(moduleUrl('teacher', 'dashboard')); break;
                case 'student': redirect(moduleUrl('student', 'dashboard')); break;
                default:        redirect(moduleUrl('auth', 'login'));
            }
        }
    }
}
