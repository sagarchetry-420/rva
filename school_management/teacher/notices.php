<?php
require_once dirname(__DIR__) . '/config/database.php';
requireTeacher();
$notices = mysqli_query($conn, "SELECT n.*, u.username as posted_by_name FROM notices n LEFT JOIN users u ON n.posted_by=u.user_id WHERE n.target_audience IN ('All','Teachers') ORDER BY n.notice_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notices - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/teacher.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1>📢 Notices</h1><p>School announcements</p></div></div>
            <?php while ($n = mysqli_fetch_assoc($notices)): ?>
            <div class="dashboard-section">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <h2 style="margin-bottom:0"><?php echo htmlspecialchars($n['title']); ?></h2>
                    <span class="badge badge-paid"><?php echo $n['target_audience']; ?></span>
                </div>
                <p style="color:var(--gray);font-size:13px;margin-bottom:10px">📅 <?php echo date('M d, Y', strtotime($n['notice_date'])); ?> | Posted by: <?php echo htmlspecialchars($n['posted_by_name']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($n['description'])); ?></p>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
