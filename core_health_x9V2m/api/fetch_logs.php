<?php
require_once __DIR__ . '/../includes/auth.php';
checkAuth();
header('Content-Type: application/json');

$dbLogFile = __DIR__ . '/../../school_management/app/Core/db_error.log';
$apiRateFile = __DIR__ . '/../../rossie/rossie_rate_limits.json';
$mailLogFile = __DIR__ . '/../../school_management/app/Core/mail_error.log';

$logs = [];
$mailLogs = [];
$errorTypes = [];
$timeline = [];

if (file_exists($dbLogFile)) {
    $lines = file($dbLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode(' | ', $line);
        if (count($parts) >= 3) {
            $time = trim($parts[0]);
            $sql = str_replace('SQL: ', '', trim($parts[1]));
            $error = str_replace('Error: ', '', trim($parts[2]));
            
            $type = 'Unknown';
            if (preg_match('/(SQLSTATE\[\w+\])/', $error, $m)) {
                $type = $m[1];
            } else if (preg_match('/Column not found/i', $error)) {
                $type = 'Column Error';
            } else if (preg_match('/Syntax/i', $error)) {
                $type = 'Syntax Error';
            }
            
            $dateOnly = substr($time, 0, 10);
            
            if (!isset($errorTypes[$type])) $errorTypes[$type] = 0;
            $errorTypes[$type]++;
            
            if (!isset($timeline[$dateOnly])) $timeline[$dateOnly] = 0;
            $timeline[$dateOnly]++;
            
            $logs[] = [
                'time' => $time,
                'sql' => htmlspecialchars($sql),
                'error' => htmlspecialchars($error),
                'type' => $type
            ];
        }
    }
}

$logs = array_reverse($logs);

if (file_exists($mailLogFile)) {
    $lines = file($mailLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode(' | ', $line);
        if (count($parts) >= 4) {
            $time = trim($parts[0]);
            $to = str_replace('TO: ', '', trim($parts[1]));
            $sub = str_replace('SUB: ', '', trim($parts[2]));
            $err = str_replace('ERROR: ', '', trim($parts[3]));
            
            $mailLogs[] = [
                'time' => $time,
                'to' => htmlspecialchars($to),
                'subject' => htmlspecialchars($sub),
                'error' => htmlspecialchars($err)
            ];
        }
    }
}
$mailLogs = array_reverse($mailLogs);

$blockedCount = 0;
if (file_exists($apiRateFile)) {
    $rateData = json_decode(file_get_contents($apiRateFile), true) ?: [];
    foreach ($rateData as $hash => $times) {
        if (count($times) >= 12) $blockedCount++;
    }
}

echo json_encode([
    'logs' => $logs,
    'mail_logs' => $mailLogs,
    'chart_pie' => [
        'labels' => array_keys($errorTypes),
        'data' => array_values($errorTypes)
    ],
    'chart_line' => [
        'labels' => array_keys($timeline),
        'data' => array_values($timeline)
    ],
    'api_blocks' => $blockedCount
]);
