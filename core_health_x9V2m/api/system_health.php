<?php
require_once __DIR__ . '/../includes/auth.php';
checkAuth();
header('Content-Type: application/json');

// Memory Info (Windows)
function getSystemMemory() {
    $free = 0; $total = 0;
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        @exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value', $output);
        foreach ($output as $line) {
            if (preg_match('/FreePhysicalMemory=(\d+)/', $line, $m)) $free = $m[1] * 1024;
            if (preg_match('/TotalVisibleMemorySize=(\d+)/', $line, $m)) $total = $m[1] * 1024;
        }
    }
    return ['free' => $free, 'total' => $total];
}

// DB Ping
function checkDB() {
    try {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_CHARSET'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->query("SELECT 1");
        return "Connected";
    } catch (Exception $e) {
        return "Failed: " . $e->getMessage();
    }
}

// Disk
$df = disk_free_space("C:");
$dt = disk_total_space("C:");

$mem = getSystemMemory();

$data = [
    'disk' => [
        'free' => $df,
        'total' => $dt,
        'used_percent' => $dt > 0 ? round((($dt - $df) / $dt) * 100, 2) : 0
    ],
    'memory' => [
        'free' => $mem['free'],
        'total' => $mem['total'],
        'used_percent' => $mem['total'] > 0 ? round((($mem['total'] - $mem['free']) / $mem['total']) * 100, 2) : 0
    ],
    'db_status' => checkDB(),
    'php_version' => phpversion(),
    'os' => php_uname('s') . ' ' . php_uname('r')
];

echo json_encode($data);
