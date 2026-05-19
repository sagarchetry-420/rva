<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark') {
    $class_id = intval($_POST['class_id']);
    $date = sanitize($conn, $_POST['attendance_date']);
    $marked_by = getUserId();
    $count = 0;
    
    foreach ($_POST['status'] as $student_id => $status) {
        $sid = intval($student_id);
        $st = sanitize($conn, $status);
        $rem = isset($_POST['remarks'][$student_id]) ? sanitize($conn, $_POST['remarks'][$student_id]) : '';
        
        $chk = mysqli_query($conn, "SELECT attendance_id FROM attendance WHERE student_id=$sid AND attendance_date='$date'");
        if (mysqli_num_rows($chk) > 0) {
            mysqli_query($conn, "UPDATE attendance SET status='$st', remarks='$rem', marked_by=$marked_by WHERE student_id=$sid AND attendance_date='$date'");
        } else {
            mysqli_query($conn, "INSERT INTO attendance (student_id,class_id,attendance_date,status,remarks,marked_by) VALUES ($sid,$class_id,'$date','$st','$rem',$marked_by)");
        }
        $count++;
    }
    setFlashMessage('success', "Attendance has been marked successfully for $count students!");
    header('Location: attendance.php?class_id=' . $class_id . '&date=' . $date); exit();
}

$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$sel_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$sel_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$students = null;
$existing = [];
if ($sel_class > 0) {
    $students = mysqli_query($conn, "SELECT s.student_id, s.first_name, s.last_name, s.roll_number FROM students s WHERE s.class_id=$sel_class ORDER BY s.roll_number");
    $att_r = mysqli_query($conn, "SELECT student_id, status, remarks FROM attendance WHERE class_id=$sel_class AND attendance_date='$sel_date'");
    while ($a = mysqli_fetch_assoc($att_r)) $existing[$a['student_id']] = $a;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1><i class="fa-solid fa-check-to-slot"></i> Attendance Management</h1><p>Mark and view daily attendance</p></div></div>
            
            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap">
                    <div class="filter-group">
                        <label>Select Class</label>
                        <select name="class_id" onchange="this.form.submit()">
                            <option value="">-- Choose Class --</option>
                            <?php mysqli_data_seek($classes, 0); while ($c = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class == $c['class_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Date</label>
                        <input type="date" name="date" value="<?php echo $sel_date; ?>" onchange="this.form.submit()">
                    </div>
                </form>
            </div>

            <?php if ($students && mysqli_num_rows($students) > 0): ?>
            <div class="table-container">
                <div class="table-header"><h2>Mark Attendance — <?php echo date('D, M d, Y', strtotime($sel_date)); ?></h2></div>
                <form method="POST">
                    <input type="hidden" name="action" value="mark">
                    <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                    <input type="hidden" name="attendance_date" value="<?php echo $sel_date; ?>">
                    <table class="attendance-grid">
                        <thead><tr><th>Roll No</th><th>Student Name</th><th>Status</th><th>Remarks</th></tr></thead>
                        <tbody>
                        <?php while ($s = mysqli_fetch_assoc($students)):
                            $ex = isset($existing[$s['student_id']]) ? $existing[$s['student_id']] : null;
                            $cur_status = $ex ? $ex['status'] : 'Present';
                            $cur_remarks = $ex ? $ex['remarks'] : '';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['roll_number']); ?></td>
                            <td style="text-align:left;padding-left:25px"><strong><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></strong></td>
                            <td>
                                <div class="attendance-radio-group">
                                    <label><input type="radio" name="status[<?php echo $s['student_id']; ?>]" value="Present" <?php echo $cur_status==='Present'?'checked':''; ?>> P</label>
                                    <label><input type="radio" name="status[<?php echo $s['student_id']; ?>]" value="Absent" <?php echo $cur_status==='Absent'?'checked':''; ?>> A</label>
                                    <label><input type="radio" name="status[<?php echo $s['student_id']; ?>]" value="Late" <?php echo $cur_status==='Late'?'checked':''; ?>> L</label>
                                    <label><input type="radio" name="status[<?php echo $s['student_id']; ?>]" value="Excused" <?php echo $cur_status==='Excused'?'checked':''; ?>> E</label>
                                </div>
                            </td>
                            <td><input type="text" name="remarks[<?php echo $s['student_id']; ?>]" value="<?php echo htmlspecialchars($cur_remarks); ?>" placeholder="Optional" style="padding:6px 10px;border:1px solid var(--border);border-radius:4px;width:140px"></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="padding:18px 22px;text-align:right;border-top:1px solid var(--border)">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Attendance</button>
                    </div>
                </form>
            </div>
            <?php elseif ($sel_class > 0): ?>
                <div class="dashboard-section"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-clipboard-list"></i></div><p>No students found in this class.</p></div></div>
            <?php else: ?>
                <div class="dashboard-section"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-clipboard-list"></i></div><p>Select a class and date to mark attendance.</p></div></div>
            <?php endif; ?>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>

