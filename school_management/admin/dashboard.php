<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

// Get statistics
$stats = [];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM students"); $stats['students'] = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM teachers"); $stats['teachers'] = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM classes"); $stats['classes'] = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM subjects"); $stats['subjects'] = mysqli_fetch_assoc($r)['c'];

// Recent notices
$notices_result = mysqli_query($conn, "SELECT * FROM notices ORDER BY notice_date DESC LIMIT 5");

// Today's attendance summary
$today = date('Y-m-d');
$att = mysqli_query($conn, "SELECT status, COUNT(*) as c FROM attendance WHERE attendance_date='$today' GROUP BY status");
$attendance_today = [];
while ($row = mysqli_fetch_assoc($att)) { $attendance_today[$row['status']] = $row['c']; }

// Pending fees
$r = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM fees WHERE payment_status='Pending'");
$pending_fees = mysqli_fetch_assoc($r)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <div>
                    <h1>📊 Admin Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars(getUsername()); ?>! Here's your overview.</p>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon students-icon">👨‍🎓</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['students']; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon teachers-icon">👨‍🏫</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['teachers']; ?></h3>
                        <p>Total Teachers</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon classes-icon">📚</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['classes']; ?></h3>
                        <p>Total Classes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon subjects-icon">📖</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['subjects']; ?></h3>
                        <p>Total Subjects</p>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Recent Notices -->
                <div class="dashboard-section">
                    <h2>📢 Recent Notices</h2>
                    <div class="notices-list">
                        <?php if (mysqli_num_rows($notices_result) > 0): ?>
                            <?php while ($notice = mysqli_fetch_assoc($notices_result)): ?>
                                <div class="notice-item">
                                    <div class="notice-date"><?php echo date('M d', strtotime($notice['notice_date'])); ?></div>
                                    <div class="notice-content">
                                        <h4><?php echo htmlspecialchars($notice['title']); ?></h4>
                                        <p><?php echo htmlspecialchars(substr($notice['description'], 0, 80)); ?>...</p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state"><p>No notices yet.</p></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Info -->
                <div class="dashboard-section">
                    <h2>📋 Quick Summary</h2>
                    <div class="notices-list">
                        <div class="notice-item">
                            <div class="notice-date">Today</div>
                            <div class="notice-content">
                                <h4>Attendance Marked</h4>
                                <p>Present: <?php echo $attendance_today['Present'] ?? 0; ?> | Absent: <?php echo $attendance_today['Absent'] ?? 0; ?></p>
                            </div>
                        </div>
                        <div class="notice-item">
                            <div class="notice-date">💰</div>
                            <div class="notice-content">
                                <h4>Pending Fees</h4>
                                <p>₹<?php echo number_format($pending_fees, 2); ?> total pending</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
