<?php
/**
 * OPcache Flush Script
 * Forcefully clears all cached PHP code from OPcache
 * Run via: http://localhost/rva/school_management/flush_opcache.php
 */

$isWeb = php_sapi_name() !== 'cli';
$html = $isWeb;

if ($html) {
    echo '<!DOCTYPE html>
<html>
<head>
    <title>OPcache Flush Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; margin: 20px 0; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>OPcache Management Tool</h1>';
}

// Check if OPcache is enabled
if (!extension_loaded('Zend OPcache')) {
    $msg = "WARNING: Zend OPcache extension is not loaded.";
    echo $html ? "<p class='error'>$msg</p>" : "ERROR: $msg\n";
} else {
    // Check if opcache_reset() function exists
    if (!function_exists('opcache_reset')) {
        $msg = "ERROR: opcache_reset() function is not available.";
        echo $html ? "<p class='error'>$msg</p>" : "$msg\n";
    } else {
        // Attempt to flush the cache
        if (opcache_reset()) {
            $msg = "SUCCESS: OPcache has been flushed successfully!";
            echo $html ? "<p class='success'>$msg</p>" : "$msg\n";
            echo $html ? "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>" : "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        } else {
            $msg = "ERROR: Failed to flush OPcache. Check server configuration.";
            echo $html ? "<p class='error'>$msg</p>" : "$msg\n";
        }
    }
}

// Get OPcache status
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    if ($html) {
        echo '<div class="info"><h2>OPcache Status</h2><table>
                <tr><th>Parameter</th><th>Value</th></tr>
                <tr><td>OPcache Enabled</td><td>' . ($status['opcache_enabled'] ? 'Yes' : 'No') . '</td></tr>
                <tr><td>Cached Scripts</td><td>' . ($status['opcache_statistics']['num_cached_scripts'] ?? 0) . '</td></tr>
                <tr><td>Used Memory</td><td>' . round(($status['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 2) . ' MB</td></tr>
                <tr><td>Free Memory</td><td>' . round(($status['memory_usage']['free_memory'] ?? 0) / 1024 / 1024, 2) . ' MB</td></tr>
            </table></div>';
    } else {
        echo "\n--- OPcache Status ---\n";
        echo "OPcache Enabled: " . ($status['opcache_enabled'] ? "Yes" : "No") . "\n";
        echo "Cached Scripts: " . ($status['opcache_statistics']['num_cached_scripts'] ?? 0) . "\n";
        echo "Used Memory: " . round(($status['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 2) . " MB\n";
        echo "Free Memory: " . round(($status['memory_usage']['free_memory'] ?? 0) / 1024 / 1024, 2) . " MB\n";
    }
}

if ($html) {
    echo '</body></html>';
}
?>
