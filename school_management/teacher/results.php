<?php
require_once dirname(__DIR__) . '/config/database.php';
requireTeacher();
$uid = getUserId();
$teacher = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE user_id=$uid"));
$tid = $teacher['teacher_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'save_results') {
    $eid = intval($_POST['exam_id']); $subid = intval($_POST['subject_id']); $max = floatval($_POST['max_marks']);
    foreach ($_POST['marks'] as $sid => $marks) {
        $sid = intval($sid); $m = floatval($marks); $grade = calculateGrade($m, $max);
        $chk = mysqli_query($conn, "SELECT result_id FROM results WHERE student_id=$sid AND exam_id=$eid AND subject_id=$subid");
        if (mysqli_num_rows($chk) > 0) { mysqli_query($conn, "UPDATE results SET marks_obtained=$m, max_marks=$max, grade='$grade' WHERE student_id=$sid AND exam_id=$eid AND subject_id=$subid");
        } else { mysqli_query($conn, "INSERT INTO results (student_id,exam_id,subject_id,marks_obtained,max_marks,grade) VALUES ($sid,$eid,$subid,$m,$max,'$grade')"); }
    }
    setFlashMessage('success', 'Results saved!');
    header('Location: results.php?exam_id='.$eid.'&class_id='.$_POST['class_id'].'&subject_id='.$subid); exit();
}

$exams = mysqli_query($conn, "SELECT * FROM examinations ORDER BY start_date DESC");
$my_classes = mysqli_query($conn, "SELECT DISTINCT cs.class_id, cs.subject_id, c.class_name, c.section, s.subject_name FROM class_subjects cs JOIN classes c ON cs.class_id=c.class_id JOIN subjects s ON cs.subject_id=s.subject_id WHERE cs.teacher_id=$tid ORDER BY c.class_name");
$sel_exam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$sel_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$sel_subj = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

$students_list = null; $existing_results = [];
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
    <title>Results - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1>📝 Enter Results</h1><p>Enter marks for your subjects</p></div></div>
            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap">
                    <div class="filter-group"><label>Exam</label><select name="exam_id" onchange="this.form.submit()"><option value="">Select Exam</option>
                    <?php while($e=mysqli_fetch_assoc($exams)):?><option value="<?php echo $e['exam_id'];?>" <?php echo $sel_exam==$e['exam_id']?'selected':'';?>><?php echo htmlspecialchars($e['exam_name']);?></option><?php endwhile;?></select></div>
                    <div class="filter-group"><label>Class & Subject</label><select name="class_id" onchange="updateSubject(this)">
                        <option value="">Select</option>
                        <?php mysqli_data_seek($my_classes,0); while($mc=mysqli_fetch_assoc($my_classes)):?>
                        <option value="<?php echo $mc['class_id'];?>" data-subj="<?php echo $mc['subject_id'];?>" <?php echo ($sel_class==$mc['class_id']&&$sel_subj==$mc['subject_id'])?'selected':'';?>><?php echo htmlspecialchars($mc['class_name'].' '.$mc['section'].' — '.$mc['subject_name']);?></option>
                        <?php endwhile;?></select></div>
                    <input type="hidden" name="subject_id" id="subject_id_field" value="<?php echo $sel_subj;?>">
                    <button type="submit" class="btn btn-primary btn-sm">Load</button>
                </form>
            </div>
            <?php if ($students_list && mysqli_num_rows($students_list) > 0): ?>
            <div class="table-container">
                <form method="POST"><input type="hidden" name="action" value="save_results"><input type="hidden" name="exam_id" value="<?php echo $sel_exam;?>"><input type="hidden" name="class_id" value="<?php echo $sel_class;?>"><input type="hidden" name="subject_id" value="<?php echo $sel_subj;?>">
                <div style="padding:15px 22px"><label style="font-weight:600">Max Marks: </label><input type="number" name="max_marks" value="100" style="width:80px;padding:6px 10px;border:1px solid var(--border);border-radius:4px"></div>
                <table class="data-table">
                    <thead><tr><th>Roll</th><th>Student</th><th>Marks</th><th>Grade</th></tr></thead>
                    <tbody>
                    <?php while($st=mysqli_fetch_assoc($students_list)): $ex=isset($existing_results[$st['student_id']])?$existing_results[$st['student_id']]:null; ?>
                    <tr><td><?php echo htmlspecialchars($st['roll_number']);?></td><td><?php echo htmlspecialchars($st['first_name'].' '.$st['last_name']);?></td>
                    <td><input type="number" name="marks[<?php echo $st['student_id'];?>]" value="<?php echo $ex?$ex['marks_obtained']:'';?>" min="0" max="100" step="0.5" style="width:80px;padding:6px;border:1px solid var(--border);border-radius:4px" required></td>
                    <td><?php echo $ex?'<span class="badge badge-paid">'.$ex['grade'].'</span>':'—';?></td></tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <div style="padding:18px 22px;text-align:right;border-top:1px solid var(--border)"><button type="submit" class="btn btn-primary">💾 Save Results</button></div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>function updateSubject(sel){var opt=sel.options[sel.selectedIndex];document.getElementById('subject_id_field').value=opt.getAttribute('data-subj')||'';}</script>
</body>
</html>
