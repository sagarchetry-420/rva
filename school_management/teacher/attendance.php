<?php
require_once dirname(__DIR__) . '/config/database.php';
requireTeacher();
$uid = getUserId();
$teacher = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE user_id=$uid"));
$tid = $teacher['teacher_id'];

// Handle POST — mark attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark') {
    $class_id = intval($_POST['class_id']);
    $date = sanitize($conn, $_POST['attendance_date']);
    
    // LOCK CHECK: Has any teacher already marked attendance for this class+date?
    $lock_check = mysqli_query($conn, "SELECT attendance_id FROM attendance WHERE class_id=$class_id AND attendance_date='$date' LIMIT 1");
    if (mysqli_num_rows($lock_check) > 0) {
        setFlashMessage('error', 'Attendance for this class has already been marked for this date. It is now locked.');
        header('Location: attendance.php?class_id='.$class_id.'&date='.$date); exit();
    }
    
    $count = 0;
    foreach ($_POST['status'] as $sid => $status) {
        $sid = intval($sid); $st = sanitize($conn, $status);
        mysqli_query($conn, "INSERT INTO attendance (student_id,class_id,attendance_date,status,marked_by) VALUES ($sid,$class_id,'$date','$st',$uid)
            ON DUPLICATE KEY UPDATE status='$st', marked_by=$uid");
        $count++;
    }
    setFlashMessage('success', "Attendance saved for $count students! It is now locked for today.");
    header('Location: attendance.php?class_id='.$class_id.'&date='.$date); exit();
}

// Get classes this teacher teaches
$my_classes = mysqli_query($conn, "SELECT DISTINCT c.class_id, c.class_name, c.section FROM class_subjects cs JOIN classes c ON cs.class_id=c.class_id WHERE cs.teacher_id=$tid ORDER BY c.class_name");
$sel_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$sel_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$students = null; $existing = []; $is_locked = false; $marked_by_name = '';
if ($sel_class > 0) {
    $students = mysqli_query($conn, "SELECT student_id, first_name, last_name, roll_number FROM students WHERE class_id=$sel_class ORDER BY roll_number");
    $att = mysqli_query($conn, "SELECT a.student_id, a.status, u.username as marked_by_user FROM attendance a LEFT JOIN users u ON a.marked_by=u.user_id WHERE a.class_id=$sel_class AND a.attendance_date='$sel_date'");
    while ($a = mysqli_fetch_assoc($att)) {
        $existing[$a['student_id']] = $a['status'];
        if (!empty($a['marked_by_user'])) $marked_by_name = $a['marked_by_user'];
    }
    // Check lock: if any attendance exists for this class+date, it's locked
    if (!empty($existing)) {
        $is_locked = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/teacher.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1><i class="fa-solid fa-check-to-slot"></i> Mark Attendance</h1><p>Mark attendance for your classes</p></div></div>
            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap">
                    <div class="filter-group"><label>My Class</label>
                        <select name="class_id" onchange="this.form.submit()"><option value="">Select</option>
                        <?php while($c=mysqli_fetch_assoc($my_classes)):?><option value="<?php echo $c['class_id'];?>" <?php echo $sel_class==$c['class_id']?'selected':'';?>><?php echo htmlspecialchars($c['class_name'].' '.$c['section']);?></option><?php endwhile;?>
                        </select></div>
                    <div class="filter-group"><label>Date</label><input type="date" name="date" value="<?php echo $sel_date;?>" max="<?php echo date('Y-m-d'); ?>" onchange="this.form.submit()"></div>
                </form>
            </div>

            <?php if ($students && mysqli_num_rows($students) > 0): ?>
            <div class="table-container">
                <?php if ($is_locked): ?>
                    <!-- LOCKED: Show read-only view -->
                    <div style="padding:16px 22px;background:#fef3c7;border-bottom:1px solid #fcd34d;display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-lock" style="color:#d97706;font-size:18px;"></i>
                        <div>
                            <strong style="color:#92400e;">Attendance Locked</strong>
                            <p style="margin:2px 0 0;font-size:13px;color:#78350f;">Attendance for this class on <?php echo date('M d, Y', strtotime($sel_date)); ?> has already been marked<?php echo $marked_by_name ? " by @$marked_by_name" : ''; ?>. Only admin can modify it.</p>
                        </div>
                    </div>
                    <table class="attendance-grid">
                        <thead><tr><th>Roll</th><th>Student</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php while($s=mysqli_fetch_assoc($students)): $cur = isset($existing[$s['student_id']]) ? $existing[$s['student_id']] : 'N/A'; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['roll_number']);?></td>
                            <td style="text-align:left;padding-left:25px"><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']);?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-paid';
                                if ($cur === 'Absent') $badgeClass = 'badge-pending';
                                elseif ($cur === 'Late') $badgeClass = 'badge-overdue';
                                elseif ($cur === 'Excused') $badgeClass = 'badge-info';
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $cur; ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <!-- UNLOCKED: Show marking form -->
                    <form method="POST"><input type="hidden" name="action" value="mark"><input type="hidden" name="class_id" value="<?php echo $sel_class;?>"><input type="hidden" name="attendance_date" value="<?php echo $sel_date;?>">
                    <div class="table-header"><h2>Mark Attendance — <?php echo date('D, M d, Y', strtotime($sel_date)); ?></h2></div>
                    <table class="attendance-grid">
                        <thead><tr><th>Roll</th><th>Student</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php while($s=mysqli_fetch_assoc($students)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['roll_number']);?></td>
                            <td style="text-align:left;padding-left:25px"><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']);?></td>
                            <td><div class="attendance-radio-group">
                                <label><input type="radio" name="status[<?php echo $s['student_id'];?>]" value="Present" checked> P</label>
                                <label><input type="radio" name="status[<?php echo $s['student_id'];?>]" value="Absent"> A</label>
                                <label><input type="radio" name="status[<?php echo $s['student_id'];?>]" value="Late"> L</label>
                            </div></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="padding:18px 22px;text-align:right;border-top:1px solid var(--border)"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Attendance</button></div>
                    </form>
                <?php endif; ?>
            </div>
            <?php elseif ($sel_class > 0): ?>
            <div class="dashboard-section"><div class="empty-state"><p>No students in this class.</p></div></div>
            <?php else: ?>
            <div class="dashboard-section"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-clipboard-list"></i></div><p>Select a class to mark attendance.</p></div></div>
            <?php endif; ?>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
