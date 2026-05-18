<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = $_POST['action'];
    if ($a === 'add_exam') {
        $n = sanitize($conn, $_POST['exam_name']);
        $t = sanitize($conn, $_POST['exam_type']);
        $sd = sanitize($conn, $_POST['start_date']);
        $ed = sanitize($conn, $_POST['end_date']);
        $ay = sanitize($conn, $_POST['academic_year']);
        mysqli_query($conn, "INSERT INTO examinations (exam_name,exam_type,start_date,end_date,academic_year) VALUES ('$n','$t','$sd','$ed','$ay')");
        setFlashMessage('success', "Exam '$n' created!");
        header('Location: examinations.php'); exit();
    }
    if ($a === 'delete_exam') {
        mysqli_query($conn, "DELETE FROM examinations WHERE exam_id=".intval($_POST['exam_id']));
        setFlashMessage('success', 'Exam deleted!');
        header('Location: examinations.php'); exit();
    }
    if ($a === 'save_results') {
        $eid = intval($_POST['exam_id']);
        $subid = intval($_POST['subject_id']);
        $max = floatval($_POST['max_marks']);
        $cnt = 0;
        foreach ($_POST['marks'] as $sid => $marks) {
            $sid = intval($sid);
            $m = floatval($marks);
            $grade = calculateGrade($m, $max);
            $chk = mysqli_query($conn, "SELECT result_id FROM results WHERE student_id=$sid AND exam_id=$eid AND subject_id=$subid");
            if (mysqli_num_rows($chk) > 0) {
                mysqli_query($conn, "UPDATE results SET marks_obtained=$m, max_marks=$max, grade='$grade' WHERE student_id=$sid AND exam_id=$eid AND subject_id=$subid");
            } else {
                mysqli_query($conn, "INSERT INTO results (student_id,exam_id,subject_id,marks_obtained,max_marks,grade) VALUES ($sid,$eid,$subid,$m,$max,'$grade')");
            }
            $cnt++;
        }
        setFlashMessage('success', "Results saved for $cnt students!");
        header('Location: examinations.php?exam_id='.$eid.'&class_id='.$_POST['class_id'].'&subject_id='.$subid); exit();
    }
}

$exams = mysqli_query($conn, "SELECT * FROM examinations ORDER BY start_date DESC");
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");

$sel_exam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$sel_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$sel_subj = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

$students_list = null;
$existing_results = [];
$class_subjects = null;

if ($sel_class > 0) {
    $class_subjects = mysqli_query($conn, "SELECT cs.subject_id, s.subject_name FROM class_subjects cs JOIN subjects s ON cs.subject_id=s.subject_id WHERE cs.class_id=$sel_class");
}
if ($sel_exam > 0 && $sel_class > 0 && $sel_subj > 0) {
    $students_list = mysqli_query($conn, "SELECT student_id, first_name, last_name, roll_number FROM students WHERE class_id=$sel_class ORDER BY roll_number");
    $rr = mysqli_query($conn, "SELECT student_id, marks_obtained, grade FROM results WHERE exam_id=$sel_exam AND subject_id=$sel_subj");
    while ($r = mysqli_fetch_assoc($rr)) $existing_results[$r['student_id']] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examinations - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div><h1>📝 Examinations & Results</h1><p>Create exams and enter results</p></div>
                <button class="btn btn-primary" onclick="openModal('addExamModal')">+ Create Exam</button>
            </div>

            <!-- Exams List -->
            <div class="table-container" style="margin-bottom:25px">
                <div class="table-header"><h2>All Examinations</h2></div>
                <table class="data-table">
                    <thead><tr><th>Exam Name</th><th>Type</th><th>Start</th><th>End</th><th>Year</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($e = mysqli_fetch_assoc($exams)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($e['exam_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($e['exam_type']); ?></td>
                        <td><?php echo $e['start_date'] ? date('M d, Y', strtotime($e['start_date'])) : '—'; ?></td>
                        <td><?php echo $e['end_date'] ? date('M d, Y', strtotime($e['end_date'])) : '—'; ?></td>
                        <td><?php echo htmlspecialchars($e['academic_year']); ?></td>
                        <td class="actions-cell">
                            <a href="?exam_id=<?php echo $e['exam_id']; ?>" class="btn btn-sm btn-info">📝 Enter Results</a>
                            <form method="POST" style="display:inline" onsubmit="return confirmDelete()"><input type="hidden" name="action" value="delete_exam"><input type="hidden" name="exam_id" value="<?php echo $e['exam_id']; ?>"><button class="btn btn-sm btn-danger">🗑️</button></form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($sel_exam > 0): ?>
            <!-- Enter Results Section -->
            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap">
                    <input type="hidden" name="exam_id" value="<?php echo $sel_exam; ?>">
                    <div class="filter-group"><label>Class</label>
                        <select name="class_id" onchange="this.form.submit()">
                            <option value="">Select Class</option>
                            <?php mysqli_data_seek($classes,0); while($c=mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class==$c['class_id']?'selected':''; ?>><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php if ($class_subjects && mysqli_num_rows($class_subjects) > 0): ?>
                    <div class="filter-group"><label>Subject</label>
                        <select name="subject_id" onchange="this.form.submit()">
                            <option value="">Select Subject</option>
                            <?php while($s=mysqli_fetch_assoc($class_subjects)): ?>
                            <option value="<?php echo $s['subject_id']; ?>" <?php echo $sel_subj==$s['subject_id']?'selected':''; ?>><?php echo htmlspecialchars($s['subject_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($students_list && mysqli_num_rows($students_list) > 0): ?>
            <div class="table-container">
                <div class="table-header"><h2>Enter Marks</h2></div>
                <form method="POST">
                    <input type="hidden" name="action" value="save_results">
                    <input type="hidden" name="exam_id" value="<?php echo $sel_exam; ?>">
                    <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                    <input type="hidden" name="subject_id" value="<?php echo $sel_subj; ?>">
                    <div style="padding:15px 22px"><label style="font-weight:600">Max Marks: </label><input type="number" name="max_marks" value="100" style="width:80px;padding:6px 10px;border:1px solid var(--border);border-radius:4px" required></div>
                    <table class="data-table">
                        <thead><tr><th>Roll</th><th>Student</th><th>Marks Obtained</th><th>Grade</th></tr></thead>
                        <tbody>
                        <?php while($st=mysqli_fetch_assoc($students_list)):
                            $ex = isset($existing_results[$st['student_id']]) ? $existing_results[$st['student_id']] : null;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($st['roll_number']); ?></td>
                            <td><strong><?php echo htmlspecialchars($st['first_name'].' '.$st['last_name']); ?></strong></td>
                            <td><input type="number" name="marks[<?php echo $st['student_id']; ?>]" value="<?php echo $ex ? $ex['marks_obtained'] : ''; ?>" min="0" max="100" step="0.5" style="width:80px;padding:6px 10px;border:1px solid var(--border);border-radius:4px" required></td>
                            <td><?php echo $ex ? '<span class="badge badge-paid">'.$ex['grade'].'</span>' : '—'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="padding:18px 22px;text-align:right;border-top:1px solid var(--border)"><button type="submit" class="btn btn-primary">💾 Save Results</button></div>
                </form>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Exam Modal -->
    <div id="addExamModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Create Examination</h2><span class="close" onclick="closeModal('addExamModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="add_exam"><div class="modal-body">
        <div class="form-group"><label>Exam Name *</label><input type="text" name="exam_name" placeholder="e.g. Mid-Term 2025" required></div>
        <div class="form-row">
            <div class="form-group"><label>Exam Type</label><select name="exam_type"><option value="Mid-Term">Mid-Term</option><option value="Final">Final</option><option value="Unit Test">Unit Test</option><option value="Quarterly">Quarterly</option></select></div>
            <div class="form-group"><label>Academic Year</label><input type="text" name="academic_year" value="2025-26"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
            <div class="form-group"><label>End Date</label><input type="date" name="end_date"></div>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addExamModal')">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div></form></div></div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
