<?php
require_once dirname(__DIR__) . '/config/database.php';
requireStudent();
$uid = getUserId();
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON s.class_id=c.class_id WHERE s.user_id=$uid"));

// Attendance summary
$att = mysqli_query($conn, "SELECT status, COUNT(*) as c FROM attendance WHERE student_id=".$student['student_id']." GROUP BY status");
$att_summary = []; $att_total = 0;
while ($a = mysqli_fetch_assoc($att)) { $att_summary[$a['status']] = $a['c']; $att_total += $a['c']; }
$att_percent = $att_total > 0 ? round((($att_summary['Present'] ?? 0) / $att_total) * 100, 1) : 0;

// Pending fees
$r = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as t FROM fees WHERE student_id=".$student['student_id']." AND payment_status='Pending'");
$pending = mysqli_fetch_assoc($r)['t'];

// Notices
$notices = mysqli_query($conn, "SELECT * FROM notices WHERE target_audience IN ('All','Students') ORDER BY notice_date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1>🏠 My Dashboard</h1><p>Welcome, <?php echo htmlspecialchars($student['first_name'].' '.$student['last_name']); ?>! — <?php echo htmlspecialchars($student['class_name'].' '.$student['section']); ?></p></div></div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon classes-icon">✅</div><div class="stat-details"><h3><?php echo $att_percent;?>%</h3><p>Attendance</p></div></div>
                <div class="stat-card"><div class="stat-icon students-icon">📊</div><div class="stat-details"><h3><?php echo ($att_summary['Present']??0).'/'.($att_total);?></h3><p>Days Present</p></div></div>
                <div class="stat-card"><div class="stat-icon fees-icon">💰</div><div class="stat-details"><h3>₹<?php echo number_format($pending);?></h3><p>Pending Fees</p></div></div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-section">
                    <h2>📢 Recent Notices</h2>
                    <div class="notices-list">
                    <?php while($n=mysqli_fetch_assoc($notices)):?>
                        <div class="notice-item">
                            <div class="notice-date"><?php echo date('M d',strtotime($n['notice_date']));?></div>
                            <div class="notice-content"><h4><?php echo htmlspecialchars($n['title']);?></h4><p><?php echo htmlspecialchars(substr($n['description'],0,80));?></p></div>
                        </div>
                    <?php endwhile;?>
                    </div>
                </div>
                <div class="dashboard-section">
                    <h2>👤 Quick Info</h2>
                    <div class="notices-list">
                        <div class="notice-item"><div class="notice-date">🎓</div><div class="notice-content"><h4>Class</h4><p><?php echo htmlspecialchars($student['class_name'].' '.$student['section']);?></p></div></div>
                        <div class="notice-item"><div class="notice-date">🔢</div><div class="notice-content"><h4>Roll Number</h4><p><?php echo htmlspecialchars($student['roll_number']);?></p></div></div>
                        <div class="notice-item"><div class="notice-date">📅</div><div class="notice-content"><h4>Admission Date</h4><p><?php echo $student['admission_date']?date('M d, Y',strtotime($student['admission_date'])):'—';?></p></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
