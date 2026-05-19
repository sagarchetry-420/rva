<?php
/**
 * ============================================================
 * Database Configuration & Helper Functions
 * ============================================================
 * Central configuration file - included by all pages
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// ─── Load .env Variables ───
$env_path = APP_ROOT . '/.env';
if (file_exists($env_path)) {
    $env_lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// ─── Database Credentials ───
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_management');

// ─── App Settings ───
define('APP_NAME', 'Rose Valley Academy');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/rva/school_management');

// ─── Create Database Connection ───
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// ─── Start Session ───
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Authentication Helper Functions
// ============================================================

/**
 * Check if a user is currently logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get the current user's type (admin/teacher/student)
 */
function getUserType() {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
}

/**
 * Get the current user's ID
 */
function getUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get the current user's username
 */
function getUsername() {
    return isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
}

/**
 * Redirect if user is NOT logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

/**
 * Redirect if user is NOT an admin
 */
function requireAdmin() {
    requireLogin();
    if (getUserType() !== 'admin') {
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

/**
 * Redirect if user is NOT a teacher
 */
function requireTeacher() {
    requireLogin();
    if (getUserType() !== 'teacher') {
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

/**
 * Redirect if user is NOT a student
 */
function requireStudent() {
    requireLogin();
    if (getUserType() !== 'student') {
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

// ============================================================
// Utility Helper Functions
// ============================================================

/**
 * Sanitize user input
 */
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

/**
 * Set a flash message to display on next page load
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Calculate grade from marks
 */
function calculateGrade($marks, $maxMarks) {
    $percentage = ($marks / $maxMarks) * 100;
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B+';
    if ($percentage >= 60) return 'B';
    if ($percentage >= 50) return 'C';
    if ($percentage >= 40) return 'D';
    return 'F';
}

/**
 * Get the current page name from URL for sidebar highlighting
 */
function getCurrentPage() {
    $path = $_SERVER['PHP_SELF'];
    $parts = explode('/', $path);
    return end($parts);
}

/**
 * Get the current folder name (admin/teacher/student)
 */
function getCurrentFolder() {
    $path = $_SERVER['PHP_SELF'];
    $parts = explode('/', $path);
    // Return the folder before the filename
    return isset($parts[count($parts) - 2]) ? $parts[count($parts) - 2] : '';
}
?>
