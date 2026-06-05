<?php
require_once __DIR__ . '/../includes/auth.php';
checkAuth();
require_once __DIR__ . '/../components/header.php';
?>

<header class="topbar">
    <h1>System Health Overview</h1>
    <div class="status-badge" id="globalStatus">Checking...</div>
</header>

<div class="metrics-grid">
    <div class="metric-card">
        <h3>Database Status</h3>
        <p class="value" id="dbStatus">Loading...</p>
    </div>
    <div class="metric-card">
        <h3>Disk Space</h3>
        <p class="value" id="diskStatus">Loading...</p>
        <div class="progress"><div class="progress-bar" id="diskBar"></div></div>
    </div>
    <div class="metric-card">
        <h3>Server Memory</h3>
        <p class="value" id="memStatus">Loading...</p>
        <div class="progress"><div class="progress-bar" id="memBar"></div></div>
    </div>
    <div class="metric-card">
        <h3>API Rate Blocks</h3>
        <p class="value" id="apiBlocks">Loading...</p>
    </div>
</div>

<div class="charts-container">
    <div class="chart-box">
        <h3>Error Timeline (7 Days)</h3>
        <canvas id="lineChart"></canvas>
    </div>
    <div class="chart-box">
        <h3>Logic Failure Types</h3>
        <canvas id="pieChart"></canvas>
    </div>
</div>

<div class="logs-container">
    <h3>Recent System Errors</h3>
    <table class="logs-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Type</th>
                <th>Error Message</th>
                <th>SQL Query (If DB Error)</th>
            </tr>
        </thead>
        <tbody id="logsTableBody">
            <tr><td colspan="4">Loading logs...</td></tr>
        </tbody>
    </table>
    <div class="pagination" id="paginationControls">
        <button id="prevPage" class="btn" disabled>Previous</button>
        <span id="pageInfo">Page 1 of 1</span>
        <button id="nextPage" class="btn" disabled>Next</button>
    </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
