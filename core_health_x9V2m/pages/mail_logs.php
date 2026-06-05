<?php
require_once __DIR__ . '/../includes/auth.php';
checkAuth();
require_once __DIR__ . '/../components/header.php';
?>

<header class="topbar">
    <h1>Email Delivery Failures</h1>
</header>

<div class="logs-container">
    <table class="logs-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Recipient</th>
                <th>Subject</th>
                <th>Error Reason</th>
            </tr>
        </thead>
        <tbody id="mailLogsTableBody">
            <tr><td colspan="4">Loading mail logs...</td></tr>
        </tbody>
    </table>
    <div class="pagination" id="mailPaginationControls">
        <button id="mailPrevPage" class="btn" disabled>Previous</button>
        <span id="mailPageInfo">Page 1 of 1</span>
        <button id="mailNextPage" class="btn" disabled>Next</button>
    </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
