<?php
require_once dirname(__DIR__) . '/config/database.php';
requireTeacher();

$uid = getUserId();
$teacher = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE user_id=$uid"));
$tid = $teacher['teacher_id'];

// Stats
$r = mysqli_query($conn, "SELECT COUNT(DISTINCT class_id) as c FROM class_subjects WHERE teacher_id=$tid");
$class_count = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(DISTINCT subject_id) as c FROM class_subjects WHERE teacher_id=$tid");
$subj_count = mysqli_fetch_assoc($r)['c'];

// Today's schedule
$today_day = date('l');
$schedule = mysqli_query($conn, "SELECT t.*, s.subject_name, c.class_name, c.section FROM timetable t JOIN subjects s ON t.subject_id=s.subject_id JOIN classes c ON t.class_id=c.class_id WHERE t.teacher_id=$tid AND t.day_of_week='$today_day' ORDER BY t.period_number");

// Notices for teachers
$notices = mysqli_query($conn, "SELECT * FROM notices WHERE target_audience IN ('All','Teachers') ORDER BY notice_date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1>🏠 Teacher Dashboard</h1><p>Welcome, <?php echo htmlspecialchars($teacher['first_name'].' '.$teacher['last_name']); ?>!</p></div></div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon classes-icon">📚</div><div class="stat-details"><h3><?php echo $class_count; ?></h3><p>My Classes</p></div></div>
                <div class="stat-card"><div class="stat-icon subjects-icon">📖</div><div class="stat-details"><h3><?php echo $subj_count; ?></h3><p>My Subjects</p></div></div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-section">
                    <h2>📅 Today's Schedule (<?php echo $today_day; ?>)</h2>
                    <div class="notices-list">
                    <?php if (mysqli_num_rows($schedule) > 0): ?>
                        <?php while ($s = mysqli_fetch_assoc($schedule)): ?>
                        <div class="notice-item">
                            <div class="notice-date">P<?php echo $s['period_number']; ?><br><small><?php echo substr($s['start_time'],0,5); ?></small></div>
                            <div class="notice-content"><h4><?php echo htmlspecialchars($s['subject_name']); ?></h4><p><?php echo htmlspecialchars($s['class_name'].' '.$s['section']); ?></p></div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state"><p>No classes scheduled today.</p></div>
                    <?php endif; ?>
                    </div>
                </div>
                <div class="dashboard-section">
                    <h2>📢 Notices</h2>
                    <div class="notices-list">
                    <?php while ($n = mysqli_fetch_assoc($notices)): ?>
                        <div class="notice-item">
                            <div class="notice-date"><?php echo date('M d', strtotime($n['notice_date'])); ?></div>
                            <div class="notice-content"><h4><?php echo htmlspecialchars($n['title']); ?></h4><p><?php echo htmlspecialchars(substr($n['description'],0,80)); ?></p></div>
                        </div>
                    <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
