<?php
/**
 * Admin Dashboard View
 * Variables: $stats, $recentStudents, $recentNotices
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-chart-line"></i> Admin Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars(getUsername()); ?>! Here's your overview.</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon students-icon"><i class="fa-solid fa-user-graduate"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['total_students']; ?></h3>
            <p>Total Students</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teachers-icon"><i class="fa-solid fa-chalkboard-teacher"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['total_teachers']; ?></h3>
            <p>Total Teachers</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon classes-icon"><i class="fa-solid fa-chalkboard"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['total_classes']; ?></h3>
            <p>Total Classes</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon subjects-icon"><i class="fa-solid fa-book"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['total_subjects']; ?></h3>
            <p>Total Subjects</p>
        </div>
    </div>
</div>

<!-- Dashboard Grid -->
<div class="dashboard-grid">
    <!-- Recent Notices -->
    <div class="dashboard-section">
        <h2><i class="fa-solid fa-bullhorn"></i> Recent Notices</h2>
        <div class="notices-list">
            <?php if (!empty($recentNotices)): ?>
                <?php foreach ($recentNotices as $notice): ?>
                    <div class="notice-item">
                        <div class="notice-date"><?php echo formatDate($notice['created_at'] ?? $notice['notice_date'] ?? '', 'M d'); ?></div>
                        <div class="notice-content">
                            <h4><?php echo htmlspecialchars($notice['title']); ?></h4>
                            <p><?php echo htmlspecialchars(substr($notice['description'] ?? $notice['content'] ?? '', 0, 80)); ?>...</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><p>No notices yet.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Summary -->
    <div class="dashboard-section">
        <h2><i class="fa-solid fa-clipboard-list"></i> Quick Summary</h2>
        <div class="notices-list">
            <div class="notice-item">
                <div class="notice-date"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                <div class="notice-content">
                    <h4>Pending Fees: <?php echo '₹' . number_format($stats['pending_fees'], 2); ?></h4>
                    <p>Manage and collect student fees</p>
                </div>
            </div>
            <div class="notice-item">
                <div class="notice-date"><i class="fa-solid fa-bullhorn"></i></div>
                <div class="notice-content">
                    <h4>Active Notices</h4>
                    <p><?php echo $stats['total_notices']; ?> notices posted</p>
                </div>
            </div>
            <div class="notice-item">
                <div class="notice-date"><i class="fa-solid fa-square-poll-vertical"></i></div>
                <div class="notice-content">
                    <h4>
                        <a href="<?php echo baseUrl('public/check-result'); ?>" target="_blank" style="text-decoration: none; color: inherit;">Public Result Portal <i class="fa-solid fa-external-link-alt" style="font-size: 12px;"></i></a>
                        <button onclick="navigator.clipboard.writeText('<?php echo baseUrl('public/check-result'); ?>'); alert('Link copied to clipboard!');" style="margin-left: 10px; background: none; border: 1px solid var(--border); border-radius: 4px; padding: 2px 6px; cursor: pointer; color: var(--text); font-size: 12px;" title="Copy Shareable Link"><i class="fa-regular fa-copy"></i> Copy Link</button>
                    </h4>
                    <p>Shareable link to view results without login</p>
                </div>
            </div>
        </div>
    </div>
</div>
