<?php
/**
 * Migration: Add broadcast_date column to notices table
 * Run this script to add the broadcast_date column required for notice auto-hide feature
 */

// Load environment variables from .env file
$envFile = dirname(__DIR__) . '/.env';
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

// Define database constants if not already defined
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'RVA');

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($db->connect_error) {
        die('Connection failed: ' . $db->connect_error);
    }

    echo "<h2>Running Migration: Add broadcast_date column</h2>";

    // Check if column already exists
    $checkColumn = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='notices' AND COLUMN_NAME='broadcast_date'";
    $result = $db->query($checkColumn);

    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'><strong>Notice:</strong> Column 'broadcast_date' already exists in the notices table.</p>";
    } else {
        // Add the column
        $sql1 = "ALTER TABLE notices ADD COLUMN broadcast_date DATETIME NULL DEFAULT NULL AFTER is_broadcasted";

        if ($db->query($sql1)) {
            echo "<p style='color: green;'><strong>✓ Success:</strong> Added 'broadcast_date' column to notices table</p>";
        } else {
            throw new Exception("Failed to add column: " . $db->error);
        }

        // Create index for better performance
        $sql2 = "CREATE INDEX idx_broadcast_date ON notices(broadcast_date)";

        if ($db->query($sql2)) {
            echo "<p style='color: green;'><strong>✓ Success:</strong> Created index on 'broadcast_date' column</p>";
        } else {
            echo "<p style='color: orange;'><strong>Note:</strong> Index creation skipped (may already exist): " . $db->error . "</p>";
        }
    }

    echo "<h3>Migration completed successfully!</h3>";
    echo "<p><a href='javascript:history.back()'>Go back</a></p>";

    $db->close();

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='javascript:history.back()'>Go back</a></p>";
}
?>
