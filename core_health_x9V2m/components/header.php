<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin | RVA System Health</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">
                <div class="logo">⚡</div>
                <h2>Super Admin</h2>
            </div>
            <nav>
                <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">System Health</a>
                <a href="mail_logs.php" class="<?= $currentPage === 'mail_logs.php' ? 'active' : '' ?>">Mail Logs</a>
                <a href="rossie_logs.php" class="<?= $currentPage === 'rossie_logs.php' ? 'active' : '' ?>">Rossie Logs</a>
                <a href="logout.php" class="logout">Secure Logout</a>
            </nav>
        </aside>
        <main class="content">
