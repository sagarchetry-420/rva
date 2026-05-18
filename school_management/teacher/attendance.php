<?php
require_once dirname(__DIR__) . '/config/database.php';
requireTeacher();
$uid = getUserId();
$teacher = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE user_id=$uid"));
$tid = $teacher['teacher_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'mark') {
    $class_id = intval($_POST['class_id']);
    $date = sanitize($conn, $_POST['attendance_date']);
    foreach ($_POST['status'] as $sid => $status) {
        $sid = intval($sid); $st = sanitize($conn, $status);
        $chk = mysqli_query($conn, "SELECT attendance_id FROM attendance WHERE student_id=$sid AND attendance_date='$date'");
        if (mysqli_num_rows($chk) > 0) {
            mysqli_query($conn, "UPDATE attendance SET status='$st', marked_by=$uid WHERE student_id=$sid AND attendance_date='$date'");
        } else {
            mysqli_query($conn, "INSERT INTO attendance (student_id,class_id,attendance_date,status,marked_by) VALUES ($sid,$class_id,'$date','$st',$uid)");
        }
    }
    setFlashMessage('success', 'Attendance saved!');
    header('Location: attendance.php?class_id='.$class_id.'&date='.$date); exit();
}

$my_classes = mysqli_query($conn, "SELECT DISTINCT c.class_id, c.class_name, c.section FROM class_subjects cs JOIN classes c ON cs.class_id=c.class_id WHERE cs.teacher_id=$tid ORDER BY c.class_name");
$sel_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$sel_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$students = null; $existing = [];
if ($sel_class > 0) {
    $students = mysqli_query($conn, "SELECT student_id, first_name, last_name, roll_number FROM students WHERE class_id=$sel_class ORDER BY roll_number");
    $att = mysqli_query($conn, "SELECT student_id, status FROM attendance WHERE class_id=$sel_class AND attendance_date='$sel_date'");
    while ($a = mysqli_fetch_assoc($att)) $existing[$a['student_id']] = $a['status'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1>✅ Mark Attendance</h1><p>Mark attendance for your classes</p></div></div>
            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap">
                    <div class="filter-group"><label>My Class</label>
                        <select name="class_id" onchange="this.form.submit()"><option value="">Select</option>
                        <?php while($c=mysqli_fetch_assoc($my_classes)):?><option value="<?php echo $c['class_id'];?>" <?php echo $sel_class==$c['class_id']?'selected':'';?>><?php echo htmlspecialchars($c['class_name'].' '.$c['section']);?></option><?php endwhile;?>
                        </select></div>
                    <div class="filter-group"><label>Date</label><input type="date" name="date" value="<?php echo $sel_date;?>" onchange="this.form.submit()"></div>
                </form>
            </div>
            <?php if ($students && mysqli_num_rows($students) > 0): ?>
            <div class="table-container">
                <form method="POST"><input type="hidden" name="action" value="mark"><input type="hidden" name="class_id" value="<?php echo $sel_class;?>"><input type="hidden" name="attendance_date" value="<?php echo $sel_date;?>">
                <table class="attendance-grid">
                    <thead><tr><th>Roll</th><th>Student</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php while($s=mysqli_fetch_assoc($students)): $cur = isset($existing[$s['student_id']]) ? $existing[$s['student_id']] : 'Present'; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['roll_number']);?></td>
                        <td style="text-align:left;padding-left:25px"><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']);?></td>
                        <td><div class="attendance-radio-group">
                            <label><input type="radio" name="status[<?php echo $s['student_id'];?>]" value="Present" <?php echo $cur==='Present'?'checked':'';?>> P</label>
                            <label><input type="radio" name="status[<?php echo $s['student_id'];?>]" value="Absent" <?php echo $cur==='Absent'?'checked':'';?>> A</label>
                            <label><input type="radio" name="status[<?php echo $s['student_id'];?>]" value="Late" <?php echo $cur==='Late'?'checked':'';?>> L</label>
                        </div></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <div style="padding:18px 22px;text-align:right;border-top:1px solid var(--border)"><button type="submit" class="btn btn-primary">💾 Save Attendance</button></div>
                </form>
            </div>
            <?php elseif ($sel_class > 0): ?>
            <div class="dashboard-section"><div class="empty-state"><p>No students in this class.</p></div></div>
            <?php else: ?>
            <div class="dashboard-section"><div class="empty-state"><div class="empty-icon">📋</div><p>Select a class to mark attendance.</p></div></div>
            <?php endif; ?>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
