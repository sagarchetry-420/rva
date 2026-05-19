<?php
require_once dirname(__DIR__) . '/config/database.php';
requireStudent();
$uid = getUserId();
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE user_id=$uid"));
$sid = $student['student_id'];

// Summary
$att = mysqli_query($conn, "SELECT status, COUNT(*) as c FROM attendance WHERE student_id=$sid GROUP BY status");
$summary = []; $total = 0;
while ($a = mysqli_fetch_assoc($att)) { $summary[$a['status']] = $a['c']; $total += $a['c']; }

// Monthly filter
$sel_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$records = mysqli_query($conn, "SELECT attendance_date, status, remarks FROM attendance WHERE student_id=$sid AND DATE_FORMAT(attendance_date,'%Y-%m')='$sel_month' ORDER BY attendance_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/student.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1>✅ My Attendance</h1><p>View your attendance records</p></div></div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon classes-icon">✅</div><div class="stat-details"><h3><?php echo $summary['Present']??0;?></h3><p>Present</p></div></div>
                <div class="stat-card"><div class="stat-icon fees-icon">❌</div><div class="stat-details"><h3><?php echo $summary['Absent']??0;?></h3><p>Absent</p></div></div>
                <div class="stat-card"><div class="stat-icon teachers-icon">⏰</div><div class="stat-details"><h3><?php echo $summary['Late']??0;?></h3><p>Late</p></div></div>
                <div class="stat-card"><div class="stat-icon subjects-icon">📊</div><div class="stat-details"><h3><?php echo $total>0?round((($summary['Present']??0)/$total)*100,1):0;?>%</h3><p>Overall</p></div></div>
            </div>

            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end">
                    <div class="filter-group"><label>Month</label><input type="month" name="month" value="<?php echo $sel_month;?>" onchange="this.form.submit()"></div>
                </form>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Day</th><th>Status</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php if(mysqli_num_rows($records)>0):?>
                    <?php while($r=mysqli_fetch_assoc($records)):?>
                    <tr>
                        <td><?php echo date('M d, Y',strtotime($r['attendance_date']));?></td>
                        <td><?php echo date('l',strtotime($r['attendance_date']));?></td>
                        <td><span class="badge badge-<?php echo strtolower($r['status']);?>"><?php echo $r['status'];?></span></td>
                        <td><?php echo htmlspecialchars($r['remarks'] ?? '')?:'-';?></td>
                    </tr>
                    <?php endwhile;?>
                    <?php else:?>
                    <tr><td colspan="4"><div class="empty-state"><p>No attendance records for this month.</p></div></td></tr>
                    <?php endif;?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
