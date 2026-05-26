<?php
// Load .env
$envFile = dirname(__FILE__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}

define('DB_HOST', DB_HOST ?? 'localhost');
define('DB_USER', DB_USER ?? 'root');
define('DB_PASS', DB_PASS ?? '');
define('DB_NAME', DB_NAME ?? 'RVA');

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($db->connect_error) {
        echo "Connection failed: " . $db->connect_error;
        exit;
    }

    // Check if attachment_path column exists
    $result = $db->query("SHOW COLUMNS FROM notices LIKE 'attachment_path'");

    if ($result->num_rows > 0) {
        echo "<p style='color: green;'><strong>✓ SUCCESS:</strong> attachment_path column exists in database</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ ERROR:</strong> attachment_path column NOT found in database</p>";
        echo "<p>You need to run the migration first:</p>";
        echo "<p><a href='/rva/school_management/database/run_attachment_migration.php' target='_blank'>Run Attachment Migration</a></p>";
    }

    // Check if uploads directory exists
    $uploadsDir = dirname(__FILE__) . '/public/uploads/notices/';
    if (is_dir($uploadsDir) && is_writable($uploadsDir)) {
        echo "<p style='color: green;'><strong>✓ SUCCESS:</strong> Uploads directory exists and is writable</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ ERROR:</strong> Uploads directory issue</p>";
    }

    $db->close();
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
