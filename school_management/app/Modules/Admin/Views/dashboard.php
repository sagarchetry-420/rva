<?php
/**
 * Admin Dashboard View
 * Variables: $stats, $recentStudents, $recentNotices
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-chart-line"></i> Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars(getUsername()); ?>! Here's your overview.</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <a href="<?php echo moduleUrl('admin', 'students'); ?>" style="text-decoration: none; color: inherit; display: flex;" class="stat-card">
        <div class="stat-icon students-icon"><i class="fa-solid fa-user-graduate"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['total_students']; ?></h3>
            <p>Total Students</p>
        </div>
    </a>
    <a href="<?php echo moduleUrl('admin', 'teachers'); ?>" style="text-decoration: none; color: inherit; display: flex;" class="stat-card">
        <div class="stat-icon teachers-icon"><i class="fa-solid fa-chalkboard-teacher"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['total_teachers']; ?></h3>
            <p>Total Teachers</p>
        </div>
    </a>
    <a href="<?php echo moduleUrl('admin', 'classes'); ?>" style="text-decoration: none; color: inherit; display: flex;" class="stat-card">
        <div class="stat-icon classes-icon"><i class="fa-solid fa-chalkboard"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['total_classes']; ?></h3>
            <p>Total Classes</p>
        </div>
    </a>
    <a href="<?php echo moduleUrl('admin', 'subjects'); ?>" style="text-decoration: none; color: inherit; display: flex;" class="stat-card">
        <div class="stat-icon subjects-icon"><i class="fa-solid fa-book"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['total_subjects']; ?></h3>
            <p>Total Subjects</p>
        </div>
    </a>
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
            <div class="notice-item" style="display: flex; gap: 20px; align-items: center; padding-bottom: 20px;">
                <div style="width: 120px; height: 120px; flex-shrink: 0;">
                    <canvas id="feesPieChart"></canvas>
                </div>
                <div class="notice-content" style="flex: 1;">
                    <h4>Fee Status Overview</h4>
                    <p style="margin: 5px 0; color: var(--danger); font-weight: bold;">
                        <i class="fa-solid fa-circle-exclamation"></i> Pending: <?php echo '₹' . number_format($stats['pending_fees'], 2); ?>
                    </p>
                    <p style="margin: 5px 0; color: var(--success); font-weight: bold;">
                        <i class="fa-solid fa-circle-check"></i> Paid: <?php echo '₹' . number_format($stats['paid_fees'], 2); ?>
                    </p>
                    <a href="<?php echo moduleUrl('admin', 'fee_collection'); ?>" style="font-size: 13px; color: var(--primary); text-decoration: underline;">Go to Fee Collection</a>
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
            <div class="notice-item">
                <div class="notice-date"><i class="fa-solid fa-magnifying-glass"></i></div>
                <div class="notice-content">
                    <h4>
                        <a href="<?php echo baseUrl('public/track_application'); ?>" target="_blank" style="text-decoration: none; color: inherit;">Track Application Status <i class="fa-solid fa-external-link-alt" style="font-size: 12px;"></i></a>
                        <button onclick="navigator.clipboard.writeText('<?php echo baseUrl('public/track_application'); ?>'); alert('Link copied to clipboard!');" style="margin-left: 10px; background: none; border: 1px solid var(--border); border-radius: 4px; padding: 2px 6px; cursor: pointer; color: var(--text); font-size: 12px;" title="Copy Shareable Link"><i class="fa-regular fa-copy"></i> Copy Link</button>
                    </h4>
                    <p>Shareable link for applicants to track their admission status</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Advanced Analytics CSS -->
<style>
.analytics-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-top: 20px;
}
@media (min-width: 1024px) {
    .analytics-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .chart-full { grid-column: 1 / -1; }
    .chart-wide { grid-column: span 2; }
}
</style>

<!-- Advanced Analytics Grid -->
<div class="analytics-grid">
    <!-- 1. Student Distribution -->
    <div class="dashboard-section chart-full" style="margin-bottom: 0;">
        <h2><i class="fa-solid fa-users"></i> Student Distribution by Class</h2>
        <div style="height: 350px; width: 100%;">
            <canvas id="studentDistributionChart"></canvas>
        </div>
    </div>

    <!-- 2. Daily Attendance Trend -->
    <div class="dashboard-section chart-wide" style="margin-bottom: 0;">
        <h2><i class="fa-solid fa-calendar-check"></i> Attendance Trend (Last 7 Days)</h2>
        <div style="height: 320px; width: 100%;">
            <canvas id="attendanceTrendChart"></canvas>
        </div>
    </div>
    
    <!-- 4. Gender Demographics -->
    <div class="dashboard-section" style="margin-bottom: 0;">
        <h2><i class="fa-solid fa-venus-mars"></i> Gender Demographics</h2>
        <div style="height: 320px; width: 100%;">
            <canvas id="genderChart"></canvas>
        </div>
    </div>



    <!-- 5. Examination Performance -->
    <div class="dashboard-section chart-full" style="margin-bottom: 0;">
        <h2><i class="fa-solid fa-award"></i> Average Performance by Class</h2>
        <div style="height: 350px; width: 100%;">
            <canvas id="examPerformanceChart"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js for all charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 0. Quick Summary Pie Chart
    const ctxPie = document.getElementById('feesPieChart');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Paid'],
                datasets: [{
                    data: [
                        <?php echo (float)$stats['pending_fees']; ?>,
                        <?php echo (float)$stats['paid_fees']; ?>
                    ],
                    backgroundColor: ['#ef4444', '#22c55e'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed !== null) {
                                    label += new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(context.parsed);
                                }
                                return label;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Colors
    const primaryColor = '#800000';
    const primaryLight = '#990000';
    const successColor = '#2e7d32';
    const dangerColor = '#c62828';
    const warningColor = '#f57f17';
    const infoColor = '#1565c0';

    // 1. Student Distribution by Class (Bar Chart)
    const studentDistData = <?php echo json_encode($studentDistribution); ?>;
    const ctxDist = document.getElementById('studentDistributionChart');
    if (ctxDist && studentDistData.length > 0) {
        new Chart(ctxDist, {
            type: 'bar',
            data: {
                labels: studentDistData.map(item => item.section ? item.class_name + ' ' + item.section : item.class_name),
                datasets: [{
                    label: 'Active Students',
                    data: studentDistData.map(item => item.student_count),
                    backgroundColor: infoColor,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // 2. Attendance Trend (Line Chart)
    const attData = <?php echo json_encode($attendanceTrend); ?>;
    const ctxAtt = document.getElementById('attendanceTrendChart');
    if (ctxAtt && attData.length > 0) {
        new Chart(ctxAtt, {
            type: 'line',
            data: {
                labels: attData.map(item => {
                    const d = new Date(item.attendance_date);
                    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
                }),
                datasets: [{
                    label: 'Attendance %',
                    data: attData.map(item => {
                        return item.total_count > 0 ? ((item.present_count / item.total_count) * 100).toFixed(1) : 0;
                    }),
                    borderColor: successColor,
                    backgroundColor: 'rgba(46, 125, 50, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100 }
                }
            }
        });
    }


    // 4. Gender Demographics (Doughnut Chart)
    const genderData = <?php echo json_encode($genderDemographics); ?>;
    const ctxGender = document.getElementById('genderChart');
    if (ctxGender && genderData.length > 0) {
        new Chart(ctxGender, {
            type: 'pie',
            data: {
                labels: genderData.map(item => item.gender),
                datasets: [{
                    data: genderData.map(item => item.count),
                    backgroundColor: [infoColor, '#ec4899', warningColor], // Blue for boys, pink for girls, yellow for other
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    }

    // 5. Examination Performance (Bar Chart)
    const examData = <?php echo json_encode($examPerformance); ?>;
    const ctxExam = document.getElementById('examPerformanceChart');
    if (ctxExam && examData.length > 0) {
        new Chart(ctxExam, {
            type: 'bar',
            data: {
                labels: examData.map(item => item.section ? item.class_name + ' ' + item.section : item.class_name),
                datasets: [{
                    label: 'Avg Score %',
                    data: examData.map(item => parseFloat(item.avg_percentage).toFixed(1)),
                    backgroundColor: primaryLight,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100 }
                }
            }
        });
    }
});
</script>
