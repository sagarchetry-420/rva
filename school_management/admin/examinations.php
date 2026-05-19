<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'manage';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = $_POST['action'];
    
    // Manage Exams Actions
    if ($a === 'add_exam') {
        $n = sanitize($conn, $_POST['exam_name']);
        $t = sanitize($conn, $_POST['exam_type']);
        $sd = sanitize($conn, $_POST['start_date']);
        $ed = sanitize($conn, $_POST['end_date']);
        $ay = sanitize($conn, $_POST['academic_year']);
        $dup = mysqli_query($conn, "SELECT exam_id FROM examinations WHERE exam_name='$n' AND academic_year='$ay'");
        if (mysqli_num_rows($dup) > 0) {
            setFlashMessage('error', "Examination '$n' for academic year '$ay' already exists.");
            header('Location: examinations.php?tab=manage'); exit();
        }
        mysqli_query($conn, "INSERT INTO examinations (exam_name,exam_type,start_date,end_date,academic_year) VALUES ('$n','$t','$sd','$ed','$ay')");
        setFlashMessage('success', "Examination created successfully.");
        header('Location: examinations.php?tab=manage'); exit();
    }
    if ($a === 'delete_exam') {
        mysqli_query($conn, "DELETE FROM examinations WHERE exam_id=".intval($_POST['exam_id']));
        setFlashMessage('success', 'Examination deleted.');
        header('Location: examinations.php?tab=manage'); exit();
    }
    if ($a === 'toggle_publish') {
        $eid = intval($_POST['exam_id']);
        $pub = intval($_POST['is_published']);
        mysqli_query($conn, "UPDATE examinations SET is_published=$pub WHERE exam_id=$eid");
        setFlashMessage('success', "Examination " . ($pub ? "published" : "unpublished") . " successfully.");
        header('Location: examinations.php?tab=manage'); exit();
    }
    
    // Exam Routines Actions
    if ($a === 'save_routine') {
        $eid = intval($_POST['exam_id']);
        $cid = intval($_POST['class_id']);
        
        $count = 0;
        foreach ($_POST['schedule'] as $subid => $data) {
            if (!empty($data['exam_date']) && !empty($data['start_time'])) {
                $dt = sanitize($conn, $data['exam_date']);
                $st = sanitize($conn, $data['start_time']);
                $et = sanitize($conn, $data['end_time']);
                $fm = floatval($data['full_marks']);
                $pm = floatval($data['pass_marks']);
                
                mysqli_query($conn, "INSERT INTO exam_schedules (exam_id, class_id, subject_id, exam_date, start_time, end_time, full_marks, pass_marks) 
                    VALUES ($eid, $cid, $subid, '$dt', '$st', '$et', $fm, $pm)
                    ON DUPLICATE KEY UPDATE exam_date='$dt', start_time='$st', end_time='$et', full_marks=$fm, pass_marks=$pm");
                $count++;
            }
        }
        setFlashMessage('success', "Exam routines saved for $count subjects.");
        header("Location: examinations.php?tab=routines&exam_id=$eid&class_id=$cid"); exit();
    }
    
    // Save Marks Action
    if ($a === 'save_marks') {
        $eid = intval($_POST['exam_id']);
        $subid = intval($_POST['subject_id']);
        
        $count = 0;
        foreach ($_POST['marks'] as $sid => $mdata) {
            $sid = intval($sid);
            $marks = !empty($mdata['obtained']) ? floatval($mdata['obtained']) : 0;
            $is_absent = isset($mdata['absent']) ? 1 : 0;
            
            // Basic grade calculation logic
            $grade = 'F';
            if ($is_absent) $grade = 'Ab';
            else {
                if ($marks >= 80) $grade = 'A+';
                elseif ($marks >= 70) $grade = 'A';
                elseif ($marks >= 60) $grade = 'B';
                elseif ($marks >= 50) $grade = 'C';
                elseif ($marks >= 40) $grade = 'D';
                elseif ($marks >= 30) $grade = 'E';
            }
            
            mysqli_query($conn, "INSERT INTO results (student_id, exam_id, subject_id, marks_obtained, is_absent, grade) 
                VALUES ($sid, $eid, $subid, $marks, $is_absent, '$grade')
                ON DUPLICATE KEY UPDATE marks_obtained=$marks, is_absent=$is_absent, grade='$grade'");
            $count++;
        }
        setFlashMessage('success', "Marks saved for $count students.");
        header("Location: examinations.php?tab=marks&exam_id=$eid&class_id=" . intval($_POST['class_id']) . "&subject_id=$subid"); exit();
    }
    
    // Export Results Action
    if ($a === 'export_csv') {
        $eid = intval($_POST['exam_id']);
        $cid = intval($_POST['class_id']);
        
        $exam_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT exam_name FROM examinations WHERE exam_id=$eid"));
        $class_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT class_name, section FROM classes WHERE class_id=$cid"));
        
        $q = "SELECT s.roll_number, s.first_name, s.last_name, sub.subject_name, r.marks_obtained, r.is_absent, r.grade, sch.full_marks, sch.pass_marks
              FROM results r 
              JOIN students s ON r.student_id=s.student_id 
              JOIN subjects sub ON r.subject_id=sub.subject_id
              LEFT JOIN exam_schedules sch ON r.exam_id=sch.exam_id AND s.class_id=sch.class_id AND r.subject_id=sch.subject_id
              WHERE r.exam_id=$eid AND s.class_id=$cid ORDER BY s.roll_number, sub.subject_name";
              
        $res = mysqli_query($conn, $q);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="result_'.$class_data['class_name'].'_'.$exam_data['exam_name'].'.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Roll No', 'Student Name', 'Subject', 'Full Marks', 'Pass Marks', 'Marks Obtained', 'Status', 'Grade']);
        while ($row = mysqli_fetch_assoc($res)) {
            $status = isset($row['is_absent']) && $row['is_absent'] ? 'Absent' : 'Present';
            $marks = isset($row['is_absent']) && $row['is_absent'] ? '—' : $row['marks_obtained'];
            fputcsv($output, [$row['roll_number'], $row['first_name'].' '.$row['last_name'], $row['subject_name'], $row['full_marks'], $row['pass_marks'], $marks, $status, $row['grade']]);
        }
        fclose($output);
        exit();
    }
}

// Fetch distinct academic years for the filter
$academic_years_q = mysqli_query($conn, "SELECT DISTINCT academic_year FROM examinations ORDER BY academic_year DESC");
$academic_years = [];
while ($ay = mysqli_fetch_assoc($academic_years_q)) {
    if (!empty($ay['academic_year'])) $academic_years[] = $ay['academic_year'];
}

$filter_year = isset($_GET['year']) ? sanitize($conn, $_GET['year']) : '';

// Fetch general data based on filter
$exam_query = "SELECT * FROM examinations ";
if ($filter_year) {
    $exam_query .= "WHERE academic_year = '$filter_year' ";
}
$exam_query .= "ORDER BY start_date DESC";

$exams = mysqli_query($conn, $exam_query);
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");

// Tab: Routines & Results specifics
$sel_exam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$sel_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examinations - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
            <div class="page-header">
                <div><h1><i class="fa-solid fa-file-invoice"></i> Examinations & Results</h1><p>Manage exams, routines, and view results</p></div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <form method="GET" style="margin:0;">
                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                        <?php if($sel_exam) echo '<input type="hidden" name="exam_id" value="'.$sel_exam.'">'; ?>
                        <?php if($sel_class) echo '<input type="hidden" name="class_id" value="'.$sel_class.'">'; ?>
                        <select name="year" onchange="this.form.submit()" class="form-control" style="width:150px; padding:8px; border-radius:6px; font-weight:500;">
                            <option value="">All Academic Years</option>
                            <?php foreach($academic_years as $y): ?>
                                <option value="<?php echo htmlspecialchars($y); ?>" <?php echo $filter_year===$y?'selected':''; ?>><?php echo htmlspecialchars($y); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php if ($tab === 'manage'): ?>
                    <button class="btn btn-primary" onclick="openModal('addExamModal')">+ Create Exam</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-nav">
                <a href="?tab=manage" class="tab-btn <?php echo $tab==='manage'?'active':''; ?>"><i class="fa-solid fa-list-check"></i> Manage Exams</a>
                <a href="?tab=routines" class="tab-btn <?php echo $tab==='routines'?'active':''; ?>"><i class="fa-solid fa-calendar-days"></i> Exam Routines</a>
                <a href="?tab=marks" class="tab-btn <?php echo $tab==='marks'?'active':''; ?>"><i class="fa-solid fa-pen-nib"></i> Enter Marks</a>
                <a href="?tab=results" class="tab-btn <?php echo $tab==='results'?'active':''; ?>"><i class="fa-solid fa-square-poll-vertical"></i> View Results</a>
            </div>

            <?php if ($tab === 'manage'): ?>
            <!-- ===================== MANAGE EXAMS ===================== -->
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Exam Name</th><th>Type</th><th>Start</th><th>End</th><th>Year</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php mysqli_data_seek($exams, 0); while ($e = mysqli_fetch_assoc($exams)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($e['exam_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($e['exam_type']); ?></td>
                        <td><?php echo $e['start_date'] ? date('M d, Y', strtotime($e['start_date'])) : '—'; ?></td>
                        <td><?php echo $e['end_date'] ? date('M d, Y', strtotime($e['end_date'])) : '—'; ?></td>
                        <td><?php echo htmlspecialchars($e['academic_year']); ?></td>
                        <td>
                            <?php 
                            $today = date('Y-m-d');
                            $start = $e['start_date'];
                            $end = $e['end_date'];
                            $exam_state = 'Upcoming';
                            $state_class = 'badge-info';
                            if ($end && $end < $today) {
                                $exam_state = 'Finished';
                                $state_class = 'badge-secondary';
                            } elseif ($start && $start <= $today && $end && $end >= $today) {
                                $exam_state = 'Ongoing';
                                $state_class = 'badge-success';
                            } elseif (!$start || !$end) {
                                $exam_state = 'No Dates';
                                $state_class = 'badge-warning';
                            }
                            ?>
                            <span class="badge <?php echo $state_class; ?>"><?php echo $exam_state; ?></span>
                            
                            <?php if(isset($e['is_published']) && $e['is_published']): ?>
                                <span class="badge badge-paid">Published</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="toggle_publish">
                                <input type="hidden" name="exam_id" value="<?php echo $e['exam_id']; ?>">
                                <?php if(isset($e['is_published']) && $e['is_published']): ?>
                                    <input type="hidden" name="is_published" value="0">
                                    <button class="btn btn-sm btn-secondary" title="Unpublish Results"><i class="fa-solid fa-eye-slash"></i> Unpublish</button>
                                <?php else: ?>
                                    <input type="hidden" name="is_published" value="1">
                                    <button class="btn btn-sm btn-success" title="Publish Results"><i class="fa-solid fa-eye"></i> Publish</button>
                                <?php endif; ?>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirmDelete()">
                                <input type="hidden" name="action" value="delete_exam"><input type="hidden" name="exam_id" value="<?php echo $e['exam_id']; ?>">
                                <button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php elseif ($tab === 'routines'): ?>
            <!-- ===================== EXAM ROUTINES ===================== -->
            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end">
                    <input type="hidden" name="tab" value="routines">
                    <div class="form-group"><label>Select Exam</label>
                        <select name="exam_id" required onchange="this.form.submit()">
                            <option value="">-- Select Exam --</option>
                            <?php mysqli_data_seek($exams, 0); while ($e = mysqli_fetch_assoc($exams)): ?>
                            <option value="<?php echo $e['exam_id']; ?>" <?php echo $sel_exam==$e['exam_id']?'selected':''; ?>><?php echo htmlspecialchars($e['exam_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Select Class</label>
                        <select name="class_id" required onchange="this.form.submit()">
                            <option value="">-- Select Class --</option>
                            <?php mysqli_data_seek($classes, 0); while ($c = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class==$c['class_id']?'selected':''; ?>><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if ($sel_exam > 0 && $sel_class > 0): 
                $class_subjects = mysqli_query($conn, "SELECT s.subject_id, s.subject_name FROM class_subjects cs JOIN subjects s ON cs.subject_id=s.subject_id WHERE cs.class_id=$sel_class ORDER BY s.subject_name");
                if (mysqli_num_rows($class_subjects) > 0):
                    // Fetch existing schedules
                    $existing = [];
                    // Using query suppression incase user hasn't run SQL yet so it doesn't crash fatally immediately.
                    $sq = @mysqli_query($conn, "SELECT * FROM exam_schedules WHERE exam_id=$sel_exam AND class_id=$sel_class");
                    if($sq) { while ($row = mysqli_fetch_assoc($sq)) $existing[$row['subject_id']] = $row; }
            ?>
            <div class="table-container">
                <div class="table-header"><h2>Schedule Subjects</h2></div>
                <form method="POST">
                    <input type="hidden" name="action" value="save_routine">
                    <input type="hidden" name="exam_id" value="<?php echo $sel_exam; ?>">
                    <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                    <table class="data-table">
                        <thead><tr><th>Subject</th><th>Exam Date</th><th>Start Time</th><th>End Time</th><th>Full Marks</th><th>Pass Marks</th></tr></thead>
                        <tbody>
                        <?php while ($sub = mysqli_fetch_assoc($class_subjects)): 
                            $sid = $sub['subject_id'];
                            $ex = isset($existing[$sid]) ? $existing[$sid] : null;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($sub['subject_name']); ?></strong></td>
                            <td><input type="date" name="schedule[<?php echo $sid; ?>][exam_date]" value="<?php echo $ex ? $ex['exam_date'] : ''; ?>" min="<?php echo date('Y-m-d'); ?>" class="form-control routine-input" style="width:140px" oninput="checkFormValidity()"></td>
                            <td><input type="time" name="schedule[<?php echo $sid; ?>][start_time]" id="start_<?php echo $sid; ?>" value="<?php echo $ex ? $ex['start_time'] : ''; ?>" class="form-control routine-input" style="width:120px" onchange="validateTime(<?php echo $sid; ?>); checkFormValidity()"></td>
                            <td><input type="time" name="schedule[<?php echo $sid; ?>][end_time]" id="end_<?php echo $sid; ?>" value="<?php echo $ex ? $ex['end_time'] : ''; ?>" class="form-control routine-input" style="width:120px" onchange="validateTime(<?php echo $sid; ?>); checkFormValidity()"></td>
                            <td><input type="number" name="schedule[<?php echo $sid; ?>][full_marks]" value="<?php echo $ex ? $ex['full_marks'] : '100'; ?>" step="0.5" min="1" class="form-control routine-input" style="width:80px" oninput="checkFormValidity()"></td>
                            <td><input type="number" name="schedule[<?php echo $sid; ?>][pass_marks]" value="<?php echo $ex ? $ex['pass_marks'] : '30'; ?>" step="0.5" min="0" class="form-control routine-input" style="width:80px" oninput="checkFormValidity()"></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="padding:15px;text-align:right;border-top:1px solid var(--border)">
                        <button type="submit" id="saveRoutineBtn" class="btn btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;"><i class="fa-solid fa-save"></i> Save Routine</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
                <div class="empty-state"><p>No subjects assigned to this class.</p></div>
            <?php endif; endif; ?>


            <?php elseif ($tab === 'marks'): ?>
            <!-- ===================== ENTER MARKS ===================== -->
            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;">
                    <input type="hidden" name="tab" value="marks">
                    <div class="form-group"><label>Exam</label>
                        <select name="exam_id" required onchange="this.form.submit()">
                            <option value="">-- Select Exam --</option>
                            <?php mysqli_data_seek($exams, 0); while ($e = mysqli_fetch_assoc($exams)): ?>
                            <option value="<?php echo $e['exam_id']; ?>" <?php echo $sel_exam==$e['exam_id']?'selected':''; ?>><?php echo htmlspecialchars($e['exam_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Class</label>
                        <select name="class_id" required onchange="this.form.submit()">
                            <option value="">-- Select Class --</option>
                            <?php mysqli_data_seek($classes, 0); while ($c = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class==$c['class_id']?'selected':''; ?>><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php if($sel_class > 0): 
                        $class_subjects = mysqli_query($conn, "SELECT s.subject_id, s.subject_name FROM class_subjects cs JOIN subjects s ON cs.subject_id=s.subject_id WHERE cs.class_id=$sel_class ORDER BY s.subject_name");
                        $sel_sub = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
                    ?>
                    <div class="form-group"><label>Subject</label>
                        <select name="subject_id" required onchange="this.form.submit()">
                            <option value="">-- Select Subject --</option>
                            <?php while($sub = mysqli_fetch_assoc($class_subjects)): ?>
                            <option value="<?php echo $sub['subject_id']; ?>" <?php echo $sel_sub==$sub['subject_id']?'selected':''; ?>><?php echo htmlspecialchars($sub['subject_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($sel_exam > 0 && $sel_class > 0 && isset($sel_sub) && $sel_sub > 0): 
                $students = mysqli_query($conn, "SELECT student_id, first_name, last_name, roll_number FROM students WHERE class_id=$sel_class ORDER BY roll_number");
                $existing_marks = [];
                $mq = mysqli_query($conn, "SELECT * FROM results WHERE exam_id=$sel_exam AND subject_id=$sel_sub");
                while($m = mysqli_fetch_assoc($mq)) $existing_marks[$m['student_id']] = $m;
                
                $schedule = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_marks, pass_marks FROM exam_schedules WHERE exam_id=$sel_exam AND class_id=$sel_class AND subject_id=$sel_sub"));
                $max = $schedule ? $schedule['full_marks'] : 100;
            ?>
            <div class="table-container">
                <div class="table-header">
                    <h2>Entering Marks: <?php echo htmlspecialchars($sel_sub_name ?? 'Subject'); ?></h2>
                    <span class="badge badge-info">Max Marks: <?php echo $max; ?></span>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="save_marks">
                    <input type="hidden" name="exam_id" value="<?php echo $sel_exam; ?>">
                    <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                    <input type="hidden" name="subject_id" value="<?php echo $sel_sub; ?>">
                    <table class="data-table">
                        <thead><tr><th>Roll No</th><th>Student Name</th><th>Marks Obtained (Max: <?php echo $max; ?>)</th><th>Absent</th></tr></thead>
                        <tbody>
                        <?php while ($s = mysqli_fetch_assoc($students)): 
                            $sid = $s['student_id'];
                            $em = isset($existing_marks[$sid]) ? $existing_marks[$sid] : null;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['roll_number']); ?></td>
                            <td><strong><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></strong></td>
                            <td>
                                <input type="number" name="marks[<?php echo $sid; ?>][obtained]" 
                                       value="<?php echo $em ? $em['marks_obtained'] : ''; ?>" 
                                       step="0.5" min="0" max="<?php echo $max; ?>" 
                                       class="form-control" style="width:120px"
                                       <?php echo ($em && $em['is_absent']) ? 'disabled' : ''; ?> id="m_<?php echo $sid; ?>">
                            </td>
                            <td>
                                <input type="checkbox" name="marks[<?php echo $sid; ?>][absent]" 
                                       onchange="document.getElementById('m_<?php echo $sid; ?>').disabled = this.checked"
                                       <?php echo ($em && $em['is_absent']) ? 'checked' : ''; ?>>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="padding:15px;text-align:right;">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save All Marks</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php elseif ($tab === 'results'): ?>
            <!-- ===================== VIEW RESULTS ===================== -->
            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end">
                    <input type="hidden" name="tab" value="results">
                    <div class="form-group"><label>Select Exam</label>
                        <select name="exam_id" required onchange="this.form.submit()">
                            <option value="">-- Select Exam --</option>
                            <?php mysqli_data_seek($exams, 0); while ($e = mysqli_fetch_assoc($exams)): ?>
                            <option value="<?php echo $e['exam_id']; ?>" <?php echo $sel_exam==$e['exam_id']?'selected':''; ?>><?php echo htmlspecialchars($e['exam_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Select Class</label>
                        <select name="class_id" required onchange="this.form.submit()">
                            <option value="">-- Select Class --</option>
                            <?php mysqli_data_seek($classes, 0); while ($c = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class==$c['class_id']?'selected':''; ?>><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
                <?php if($sel_exam > 0 && $sel_class > 0): ?>
                <form method="POST" style="margin-left:auto;display:flex;gap:8px;">
                    <input type="hidden" name="action" value="export_csv">
                    <input type="hidden" name="exam_id" value="<?php echo $sel_exam; ?>">
                    <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-file-csv"></i> CSV</button>
                    <button type="button" class="btn btn-danger" onclick="downloadReportPDF()"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                </form>
                <?php endif; ?>
            </div>

            <?php if ($sel_exam > 0 && $sel_class > 0): 
                $view_mode = isset($_GET['view']) ? $_GET['view'] : 'summary';
            ?>
            <div class="tab-sub-nav" style="margin-bottom:15px;">
                <a href="?tab=results&exam_id=<?php echo $sel_exam; ?>&class_id=<?php echo $sel_class; ?>&view=summary" class="btn btn-sm <?php echo $view_mode==='summary'?'btn-primary':'btn-outline'; ?>">Student Summary</a>
                <a href="?tab=results&exam_id=<?php echo $sel_exam; ?>&class_id=<?php echo $sel_class; ?>&view=detailed" class="btn btn-sm <?php echo $view_mode==='detailed'?'btn-primary':'btn-outline'; ?>">Detailed View</a>
            </div>

            <?php 
                if ($view_mode === 'detailed'):
                    $res_query = mysqli_query($conn, "SELECT s.roll_number, s.first_name, s.last_name, sub.subject_name, r.marks_obtained, r.is_absent, r.grade, sch.full_marks, sch.pass_marks
                        FROM results r 
                        JOIN students s ON r.student_id=s.student_id 
                        JOIN subjects sub ON r.subject_id=sub.subject_id
                        LEFT JOIN exam_schedules sch ON r.exam_id=sch.exam_id AND s.class_id=sch.class_id AND r.subject_id=sch.subject_id
                        WHERE r.exam_id=$sel_exam AND s.class_id=$sel_class ORDER BY s.roll_number, sub.subject_name");
            ?>
            <div class="table-container" id="reportTable">
                <table class="data-table">
                    <thead><tr><th>Roll No</th><th>Student Name</th><th>Subject</th><th>Total</th><th>Pass</th><th>Marks</th><th>Grade</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php while ($r = mysqli_fetch_assoc($res_query)): 
                        $status = ($r['is_absent']) ? 'Absent' : 'Present';
                        $marks = ($r['is_absent']) ? '—' : $r['marks_obtained'];
                        $is_fail = ($marks !== '—' && isset($r['pass_marks']) && $marks < $r['pass_marks']);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($r['first_name'].' '.$r['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['full_marks'] ?? '100'); ?></td>
                        <td><?php echo htmlspecialchars($r['pass_marks'] ?? '30'); ?></td>
                        <td <?php echo $is_fail ? 'style="color:var(--danger);font-weight:bold;"' : ''; ?>><?php echo $marks; ?></td>
                        <td><span class="badge badge-info"><?php echo $r['grade']; ?></span></td>
                        <td>
                            <?php if ($status === 'Absent'): ?>
                                <span class="badge badge-overdue">Absent</span>
                            <?php elseif ($is_fail): ?>
                                <span class="badge badge-danger">Failed</span>
                            <?php else: ?>
                                <span class="badge badge-success">Passed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: 
                // Summary View
                $summary_query = mysqli_query($conn, "
                    SELECT s.student_id, s.roll_number, s.first_name, s.last_name, 
                           SUM(r.marks_obtained) as total_marks,
                           COUNT(r.result_id) as subjects_count,
                           SUM(IF(r.marks_obtained < sch.pass_marks OR r.is_absent=1, 1, 0)) as failed_subjects
                    FROM students s
                    JOIN results r ON s.student_id = r.student_id
                    LEFT JOIN exam_schedules sch ON r.exam_id = sch.exam_id AND s.class_id = sch.class_id AND r.subject_id = sch.subject_id
                    WHERE r.exam_id = $sel_exam AND s.class_id = $sel_class
                    GROUP BY s.student_id
                    ORDER BY s.roll_number
                ");
            ?>
            <div class="table-container" id="reportTable">
                <table class="data-table">
                    <thead><tr><th>Roll No</th><th>Student Name</th><th>Subjects</th><th>Total Marks</th><th>Result</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php while ($r = mysqli_fetch_assoc($summary_query)): 
                        $res_status = ($r['failed_subjects'] > 0) ? 'FAILED' : 'PASSED';
                    ?>
                    <tr>
                        <td><?php echo $r['roll_number']; ?></td>
                        <td><strong><?php echo htmlspecialchars($r['first_name'].' '.$r['last_name']); ?></strong></td>
                        <td><?php echo $r['subjects_count']; ?></td>
                        <td><?php echo number_format($r['total_marks'], 1); ?></td>
                        <td>
                            <span class="badge badge-<?php echo ($res_status==='PASSED')?'success':'danger'; ?>">
                                <?php echo $res_status; ?> (<?php echo $r['failed_subjects']; ?> Back)
                            </span>
                        </td>
                        <td>
                            <a href="marksheet.php?student_id=<?php echo $r['student_id']; ?>&exam_id=<?php echo $sel_exam; ?>" 
                               target="_blank" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-file-invoice"></i> Marksheet
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <!-- Add Exam Modal -->
    <div id="addExamModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Create Examination</h2><span class="close" onclick="closeModal('addExamModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="add_exam"><div class="modal-body">
        <div class="form-group"><label>Exam Name *</label><input type="text" name="exam_name" placeholder="e.g. Mid-Term 2025" required></div>
        <div class="form-row">
            <div class="form-group"><label>Exam Type</label><select name="exam_type"><option value="Mid-Term">Mid-Term</option><option value="Final">Final</option><option value="Unit Test">Unit Test</option><option value="Quarterly">Quarterly</option></select></div>
            <div class="form-group"><label>Academic Year</label><input type="text" name="academic_year" value="<?php echo date('Y') . '-' . date('y', strtotime('+1 year')); ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Start Date</label><input type="date" name="start_date" id="exam_start_date" min="<?php echo date('Y-m-d'); ?>" onchange="document.getElementById('exam_end_date').min = this.value;"></div>
            <div class="form-group"><label>End Date</label><input type="date" name="end_date" id="exam_end_date" min="<?php echo date('Y-m-d'); ?>"></div>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addExamModal')">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div></form></div></div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>
    function validateExamForm() {
        const name = document.querySelector('input[name="exam_name"]').value;
        const type = document.querySelector('select[name="exam_type"]').value;
        const year = document.querySelector('input[name="academic_year"]').value;
        const start = document.getElementById('exam_start_date').value;
        const end = document.getElementById('exam_end_date').value;
        const btn = document.querySelector('#addExamModal button[type="submit"]');
        
        if (name.trim() && type && year && start && end) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
        }
    }

    function downloadReportPDF() {
        var element = document.getElementById('reportTable');
        var opt = {
            margin: 0.5,
            filename: 'exam_results.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }

    function validateTime(sid) {
        let startTime = document.getElementById('start_' + sid).value;
        let endTimeInput = document.getElementById('end_' + sid);
        if (startTime) {
            endTimeInput.min = startTime;
            if (endTimeInput.value && endTimeInput.value <= startTime) {
                endTimeInput.value = '';
                alert('End time must be logically after the start time.');
            }
        }
    }

    function checkFormValidity() {
        const rows = document.querySelectorAll('.data-table tbody tr');
        let isValid = true;
        let hasData = false;
        let selectedDates = [];
        let hasDuplicateDate = false;

        rows.forEach(row => {
            const inputs = row.querySelectorAll('.routine-input');
            if (inputs.length > 0) {
                let filledCount = 0;
                let rowDate = '';
                
                inputs.forEach(input => {
                    if (input.value.trim() !== '') {
                        filledCount++;
                        if(input.type === 'date') {
                            rowDate = input.value;
                        }
                    }
                });

                if (filledCount > 0) {
                    hasData = true;
                    // If partially filled, it's invalid
                    if (filledCount < inputs.length) {
                        isValid = false;
                    }
                    
                    // Check for duplicate dates
                    if (rowDate !== '') {
                        if (selectedDates.includes(rowDate)) {
                            hasDuplicateDate = true;
                        } else {
                            selectedDates.push(rowDate);
                        }
                    }
                }
            }
        });

        const btn = document.getElementById('saveRoutineBtn');
        if (btn) {
            if (hasData && isValid && !hasDuplicateDate) {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            } else {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
                
                if (hasDuplicateDate) {
                    btn.title = "Cannot schedule multiple subjects on the same date";
                } else {
                    btn.title = "";
                }
            }
        }
    }

    // Attach listeners for exam form
    document.querySelectorAll('#addExamModal input, #addExamModal select').forEach(el => {
        el.addEventListener('input', validateExamForm);
    });

    window.addEventListener('DOMContentLoaded', (event) => {
        checkFormValidity();
        validateExamForm();
    });
    </script>
</body>
</html>
