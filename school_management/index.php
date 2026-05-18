<?php
/**
 * ============================================================
 * School Management System - Main Entry Point / Router
 * ============================================================
 * Redirects users to their role-based dashboard
 */

require_once __DIR__ . '/config/database.php';

// If not logged in, go to login page
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}

// Route to the correct dashboard based on user type
$userType = getUserType();

switch ($userType) {
    case 'admin':
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        break;
    case 'teacher':
        header('Location: ' . BASE_URL . '/teacher/dashboard.php');
        break;
    case 'student':
        header('Location: ' . BASE_URL . '/student/dashboard.php');
        break;
    default:
        // Unknown role — destroy session and redirect to login
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login.php');
        break;
}
exit();
?>
