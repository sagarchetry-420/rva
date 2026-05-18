<?php
/**
 * Logout - Destroys session and redirects to login
 */
require_once dirname(__DIR__) . '/config/database.php';
session_destroy();
header('Location: ' . BASE_URL . '/auth/login.php');
exit();
?>
