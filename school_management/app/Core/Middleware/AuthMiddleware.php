<?php
/**
 * AuthMiddleware — Checks if user is logged in
 */

class AuthMiddleware
{
    public function handle(array $route): void
    {
        // Skip middleware for auth routes, public routes, and api routes
        if (strpos($route['path'], 'auth/') === 0 || strpos($route['path'], 'public/') === 0 || strpos($route['path'], 'api/') === 0) return;

        if (!isLoggedIn()) {
            setFlashMessage('error', 'Please log in to continue.');
            redirect(moduleUrl('auth', 'login'));
        }
    }
}
