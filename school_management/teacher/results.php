<?php
require_once dirname(__DIR__) . '/config/database.php';
requireTeacher();
$uid = getUserId();
$teacher = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE user_id=$uid"));
$tid = $teacher['teacher_id'];

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'schedule';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'save_results') {
    $eid = intval($_POST['exam_id']); 
    $subid = intval($_POST['subject_id']); 
    $cid = intval($_POST['class_id']);

    // Get max marks from schedule
    $sch = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_marks, pass_marks FROM exam_schedules WHERE exam_id=$eid AND class_id=$cid AND subject_id=$subid"));
    $max = $sch ? $sch['full_marks'] : 100.00;

    $count = 0;
    foreach ($_POST['marks'] as $sid => $m_data) {
        $sid = intval($sid); 
        $is_absent = isset($m_data['absent']) ? 1 : 0;
        
        if ($is_absent) {
            $m = 0;
            $grade = 'F'; // Fail grade for absent
        } else {
            $m = floatval($m_data['obtained']); 
            $grade = calculateGrade($m, $max);
        }
        
        $chk = mysqli_query($conn, "SELECT result_id FROM results WHERE student_id=$sid AND exam_id=$eid AND subject_id=$subid");
        if (mysqli_num_rows($chk) > 0) { 
            mysqli_query($conn, "UPDATE results SET marks_obtained=$m, is_absent=$is_absent, grade='$grade' WHERE student_id=$sid AND exam_id=$eid AND subject_id=$subid");
        } else { 
            mysqli_query($conn, "INSERT INTO results (student_id,exam_id,subject_id,marks_obtained,is_absent,grade) VALUES ($sid,$eid,$subid,$m,$is_absent,'$grade')"); 
        }
        $count++;
    }
    setFlashMessage('success', "Results saved for $count students!");
    header('Location: results.php?tab=marks&exam_id='.$eid.'&class_id='.$cid.'&subject_id='.$subid); exit();
}

$exams = mysqli_query($conn, "SELECT * FROM examinations ORDER BY start_date DESC");
$my_classes = mysqli_query($conn, "SELECT DISTINCT cs.class_id, cs.subject_id, c.class_name, c.section, s.subject_name FROM class_subjects cs JOIN classes c ON cs.class_id=c.class_id JOIN subjects s ON cs.subject_id=s.subject_id WHERE cs.teacher_id=$tid ORDER BY c.class_name");

$sel_exam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$sel_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$sel_subj = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

$students_list = null; $existing_results = []; $sch_details = null;
if ($sel_exam > 0 && $sel_class > 0 && $sel_subj > 0) {
    $students_list = mysqli_query($conn, "SELECT student_id, first_name, last_name, roll_number FROM students WHERE class_id=$sel_class ORDER BY roll_number");
    $rr = mysqli_query($conn, "SELECT student_id, marks_obtained, is_absent, grade FROM results WHERE exam_id=$sel_exam AND subject_id=$sel_subj");
    if($rr) { while ($r = mysqli_fetch_assoc($rr)) $existing_results[$r['student_id']] = $r; }
    
    $sch_q = @mysqli_query($conn, "SELECT * FROM exam_schedules WHERE exam_id=$sel_exam AND class_id=$sel_class AND subject_id=$sel_subj");
    if($sch_q) { $sch_details = mysqli_fetch_assoc($sch_q); }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examinations & Results - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/teacher.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-nav { display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:20px; }
        .tab-btn { padding:12px 24px; background:none; border:none; font-size:14px; font-weight:600; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; color:var(--gray); transition:all 0.2s; }
        .tab-btn:hover { color:var(--primary); }
        .tab-btn.active { color:var(--primary); border-bottom-color:var(--primary); }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1><i class="fa-solid fa-file-pen"></i> Examinations & Results</h1><p>View routines and enter marks for your subjects</p></div></div>
            
            <!-- Tab Navigation -->
            <div class="tab-nav">
                <a href="?tab=schedule" class="tab-btn <?php echo $tab==='schedule'?'active':''; ?>"><i class="fa-solid fa-calendar-days"></i> My Exam Schedules</a>
                <a href="?tab=marks" class="tab-btn <?php echo $tab==='marks'?'active':''; ?>"><i class="fa-solid fa-check-double"></i> Enter Marks</a>
            </div>

            <?php if ($tab === 'schedule'): ?>
            <div class="table-container">
                <div class="table-header"><h2>Upcoming & Recent Exam Routines</h2></div>
                <?php 
                $my_schedules = @mysqli_query($conn, "SELECT e.exam_name, c.class_name, c.section, s.subject_name, sch.exam_date, sch.start_time, sch.end_time, sch.full_marks 
                    FROM exam_schedules sch 
                    JOIN examinations e ON sch.exam_id = e.exam_id 
                    JOIN classes c ON sch.class_id = c.class_id 
                    JOIN subjects s ON sch.subject_id = s.subject_id 
                    JOIN class_subjects cs ON sch.class_id = cs.class_id AND sch.subject_id = cs.subject_id
                    WHERE cs.teacher_id = $tid ORDER BY sch.exam_date DESC LIMIT 50");
                
                if ($my_schedules && mysqli_num_rows($my_schedules) > 0):
                ?>
                <table class="data-table">
                    <thead><tr><th>Exam</th><th>Class</th><th>Subject</th><th>Date</th><th>Time</th><th>Full Marks</th></tr></thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($my_schedules)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['exam_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['class_name'].' '.$row['section']); ?></td>
                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo date('M d, Y', strtotime($row['exam_date'])); ?></span></td>
                        <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                        <td><?php echo $row['full_marks']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state"><p>No upcoming exam routines scheduled for your assigned subjects yet.</p></div>
                <?php endif; ?>
            </div>

            <?php elseif ($tab === 'marks'): ?>
            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap">
                    <input type="hidden" name="tab" value="marks">
                    <div class="filter-group"><label>Exam</label>
                        <select name="exam_id" onchange="this.form.submit()">
                            <option value="">Select Exam</option>
                            <?php mysqli_data_seek($exams, 0); while($e=mysqli_fetch_assoc($exams)):?>
                            <option value="<?php echo $e['exam_id'];?>" <?php echo $sel_exam==$e['exam_id']?'selected':'';?>><?php echo htmlspecialchars($e['exam_name']);?></option>
                            <?php endwhile;?>
                        </select>
                    </div>
                    <div class="filter-group"><label>Class & Subject</label>
                        <select name="class_id" onchange="updateSubject(this)">
                            <option value="">Select</option>
                            <?php mysqli_data_seek($my_classes,0); while($mc=mysqli_fetch_assoc($my_classes)):?>
                            <option value="<?php echo $mc['class_id'];?>" data-subj="<?php echo $mc['subject_id'];?>" <?php echo ($sel_class==$mc['class_id']&&$sel_subj==$mc['subject_id'])?'selected':'';?>><?php echo htmlspecialchars($mc['class_name'].' '.$mc['section'].' — '.$mc['subject_name']);?></option>
                            <?php endwhile;?>
                        </select>
                    </div>
                    <input type="hidden" name="subject_id" id="subject_id_field" value="<?php echo $sel_subj;?>">
                    <button type="submit" class="btn btn-primary btn-sm">Load Students</button>
                </form>
            </div>

            <?php if ($students_list && mysqli_num_rows($students_list) > 0): ?>
            <div class="table-container">
                <form method="POST">
                    <input type="hidden" name="action" value="save_results">
                    <input type="hidden" name="exam_id" value="<?php echo $sel_exam;?>">
                    <input type="hidden" name="class_id" value="<?php echo $sel_class;?>">
                    <input type="hidden" name="subject_id" value="<?php echo $sel_subj;?>">
                    
                    <div style="padding:15px 22px; background:var(--bg-color); border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span style="font-weight:600; margin-right:15px;">Full Marks: <?php echo $sch_details ? $sch_details['full_marks'] : 'Not Scheduled (Default: 100)'; ?></span>
                            <span style="font-weight:600;">Pass Marks: <?php echo $sch_details ? $sch_details['pass_marks'] : 'Not Scheduled (Default: 30)'; ?></span>
                        </div>
                        <?php if(!$sch_details): ?>
                            <span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i> Warning: Exam routine not scheduled by Admin for this subject yet. Defaults will be used.</span>
                        <?php endif; ?>
                    </div>

                    <table class="data-table">
                        <thead><tr><th>Roll</th><th>Student</th><th>Absent?</th><th>Marks Obtained</th><th>Grade</th></tr></thead>
                        <tbody>
                        <?php while($st=mysqli_fetch_assoc($students_list)): 
                            $ex=isset($existing_results[$st['student_id']]) ? $existing_results[$st['student_id']] : null; 
                            $is_abs = $ex && isset($ex['is_absent']) && $ex['is_absent'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($st['roll_number']);?></td>
                            <td><?php echo htmlspecialchars($st['first_name'].' '.$st['last_name']);?></td>
                            <td style="text-align:center;">
                                <input type="checkbox" name="marks[<?php echo $st['student_id'];?>][absent]" value="1" <?php echo $is_abs ? 'checked' : ''; ?> onchange="toggleMarks(this, <?php echo $st['student_id'];?>)">
                            </td>
                            <td>
                                <input type="number" id="marks_input_<?php echo $st['student_id'];?>" name="marks[<?php echo $st['student_id'];?>][obtained]" value="<?php echo ($ex && !$is_abs)?$ex['marks_obtained']:'';?>" min="0" max="<?php echo $sch_details ? $sch_details['full_marks'] : 100; ?>" step="0.5" style="width:100px;padding:6px;border:1px solid var(--border);border-radius:4px" <?php echo $is_abs ? 'disabled required=false' : 'required'; ?>>
                            </td>
                            <td><?php echo $ex?'<span class="badge badge-paid">'.$ex['grade'].'</span>':'—';?></td>
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
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>
    function updateSubject(sel){
        var opt=sel.options[sel.selectedIndex];
        document.getElementById('subject_id_field').value=opt.getAttribute('data-subj')||'';
    }
    
    function toggleMarks(checkbox, studentId) {
        const marksInput = document.getElementById('marks_input_' + studentId);
        if (checkbox.checked) {
            marksInput.disabled = true;
            marksInput.value = '';
            marksInput.removeAttribute('required');
        } else {
            marksInput.disabled = false;
            marksInput.setAttribute('required', 'required');
        }
    }
    </script>
</body>
</html>
