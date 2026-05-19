<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $cid = intval($_POST['class_id']);
        $day = sanitize($conn, $_POST['day_of_week']);
        $period = intval($_POST['period_number']);
        $subid = intval($_POST['subject_id']);
        $tid = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 'NULL';
        $st = sanitize($conn, $_POST['start_time']);
        $et = sanitize($conn, $_POST['end_time']);
        
        $chk = mysqli_query($conn, "SELECT timetable_id FROM timetable WHERE class_id=$cid AND day_of_week='$day' AND period_number=$period");
        if (mysqli_num_rows($chk) > 0) {
            mysqli_query($conn, "UPDATE timetable SET subject_id=$subid, teacher_id=$tid, start_time='$st', end_time='$et' WHERE class_id=$cid AND day_of_week='$day' AND period_number=$period");
        } else {
            mysqli_query($conn, "INSERT INTO timetable (class_id,subject_id,teacher_id,day_of_week,period_number,start_time,end_time) VALUES ($cid,$subid,$tid,'$day',$period,'$st','$et')");
        }
        setFlashMessage('success', 'Timetable entry has been saved successfully!');
        header('Location: timetable.php?class_id='.$cid); exit();
    }
    if ($_POST['action'] === 'delete_slot') {
        mysqli_query($conn, "DELETE FROM timetable WHERE timetable_id=".intval($_POST['timetable_id']));
        setFlashMessage('success', 'Timetable slot has been removed successfully.');
        header('Location: timetable.php?class_id='.$_POST['class_id']); exit();
    }
}

$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$sel_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$periods = 7;
$timetable = [];
$class_subjects = null;
$teachers_list = null;

if ($sel_class > 0) {
    $tt = mysqli_query($conn, "SELECT t.*, s.subject_name, CONCAT(tc.first_name,' ',tc.last_name) as teacher_name FROM timetable t JOIN subjects s ON t.subject_id=s.subject_id LEFT JOIN teachers tc ON t.teacher_id=tc.teacher_id WHERE t.class_id=$sel_class");
    while ($r = mysqli_fetch_assoc($tt)) {
        $timetable[$r['day_of_week']][$r['period_number']] = $r;
    }
    $class_subjects = mysqli_query($conn, "SELECT cs.subject_id, s.subject_name FROM class_subjects cs JOIN subjects s ON cs.subject_id=s.subject_id WHERE cs.class_id=$sel_class");
    $teachers_list = mysqli_query($conn, "SELECT teacher_id, CONCAT(first_name,' ',last_name) as name, subject_specialization FROM teachers ORDER BY first_name");
    
    $teacher_mapping = [];
    while($t = mysqli_fetch_assoc($teachers_list)) {
        $teacher_mapping[] = $t;
    }
}

$period_times = [
    1 => ['09:00','09:45'], 2 => ['09:45','10:30'], 3 => ['10:45','11:30'],
    4 => ['11:30','12:15'], 5 => ['13:00','13:45'], 6 => ['13:45','14:30'], 7 => ['14:30','15:15']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .timetable-table .break-cell {
        background: #f8f9fa !important;
        color: #6c757d;
        font-weight: 600;
        font-size: 11px;
        text-align: center;
        vertical-align: middle;
        border-left: 2px solid #dee2e6;
        border-right: 2px solid #dee2e6;
        width: 40px;
    }
    .break-text {
        writing-mode: vertical-rl;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .btn-delete-slot {
        position: absolute;
        top: 2px;
        right: 2px;
        background: none;
        border: none;
        color: #dc3545;
        cursor: pointer;
        font-size: 12px;
        padding: 0;
        opacity: 0;
        transition: opacity 0.2s;
    }
    .timetable-cell:hover .btn-delete-slot {
        opacity: 1;
    }
    .timetable-cell {
        position: relative;
    }
</style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div><h1><i class="fa-solid fa-clock"></i> Timetable Management</h1><p>Create and manage class timetables</p></div>
                <?php if ($sel_class > 0): ?>
                <button class="btn btn-primary" onclick="openModal('addSlotModal')">+ Add Period</button>
                <?php endif; ?>
            </div>

            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end">
                    <div class="filter-group"><label>Select Class</label>
                        <select name="class_id" onchange="this.form.submit()"><option value="">-- Choose --</option>
                        <?php mysqli_data_seek($classes,0); while($c=mysqli_fetch_assoc($classes)): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $sel_class==$c['class_id']?'selected':''; ?>><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                        <?php endwhile; ?></select>
                    </div>
                </form>
            </div>

            <?php if ($sel_class > 0): ?>
            <div class="table-container" style="overflow-x:auto">
                <table class="timetable-table">
                    <thead>
                        <tr><th>Day / Period</th>
                        <?php for ($p=1; $p<=$periods; $p++): ?>
                            <th>Period <?php echo $p; ?><br><small><?php echo $period_times[$p][0].'-'.$period_times[$p][1]; ?></small></th>
                            <?php if($p == 2): ?>
                                <th class="break-cell">SHORT<br>BREAK</th>
                            <?php elseif($p == 4): ?>
                                <th class="break-cell">LUNCH<br>BREAK</th>
                            <?php endif; ?>
                        <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($days as $day): ?>
                    <tr>
                        <td style="font-weight:700;background:var(--light)"><?php echo $day; ?></td>
                        <?php for ($p=1; $p<=$periods; $p++): ?>
                        <td>
                            <?php if (isset($timetable[$day][$p])): $slot = $timetable[$day][$p]; ?>
                            <div class="timetable-cell">
                                <div class="subject-name"><?php echo htmlspecialchars($slot['subject_name']); ?></div>
                                <div class="teacher-name"><?php echo htmlspecialchars($slot['teacher_name']); ?></div>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Remove this slot?')">
                                    <input type="hidden" name="action" value="delete_slot">
                                    <input type="hidden" name="timetable_id" value="<?php echo $slot['timetable_id']; ?>">
                                    <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                                    <button class="btn-delete-slot" title="Delete"><i class="fa-solid fa-xmark"></i></button>
                                </form>
                            </div>
                            <?php else: ?>
                            <small style="color:var(--gray)">—</small>
                            <?php endif; ?>
                        </td>
                        <?php if($p == 2): ?>
                            <td class="break-cell"><div class="break-text">RECESS</div></td>
                        <?php elseif($p == 4): ?>
                            <td class="break-cell"><div class="break-text">LUNCH</div></td>
                        <?php endif; ?>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="dashboard-section"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-clipboard-list"></i></div><p>Select a class to view/edit timetable.</p></div></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($sel_class > 0): ?>
    <div id="addSlotModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Add Timetable Entry</h2><span class="close" onclick="closeModal('addSlotModal')">&times;</span></div>
    <form method="POST" id="timetableForm"><input type="hidden" name="action" value="save"><input type="hidden" name="class_id" value="<?php echo $sel_class; ?>"><div class="modal-body">
        <div class="form-row">
            <div class="form-group"><label>Day *</label><select name="day_of_week" required><?php foreach($days as $d): ?><option value="<?php echo $d; ?>"><?php echo $d; ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Period *</label>
                <select name="period_number" id="periodSelect" required onchange="updateTimes(this.value)">
                    <?php for($p=1;$p<=$periods;$p++): ?>
                    <option value="<?php echo $p; ?>">Period <?php echo $p; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Subject *</label>
            <select name="subject_id" id="subjectSelect" required onchange="filterTeachers(this.options[this.selectedIndex].text)">
                <option value="">Select Subject</option>
                <?php if($class_subjects): mysqli_data_seek($class_subjects,0); while($s=mysqli_fetch_assoc($class_subjects)): ?>
                <option value="<?php echo $s['subject_id'];?>"><?php echo htmlspecialchars($s['subject_name']);?></option>
                <?php endwhile; endif; ?>
            </select>
        </div>
        <div class="form-group"><label>Teacher (Suggested by Specialization)</label>
            <select name="teacher_id" id="teacherSelect">
                <option value="">Select Teacher</option>
                <?php foreach($teacher_mapping as $t): ?>
                <option value="<?php echo $t['teacher_id'];?>" data-spec="<?php echo htmlspecialchars($t['subject_specialization']);?>">
                    <?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['subject_specialization'] ?: 'General'); ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Start Time</label><input type="time" name="start_time" id="start_time" value="09:00" onchange="validateTimetableForm()"></div>
            <div class="form-group"><label>End Time</label><input type="time" name="end_time" id="end_time" value="09:45" onchange="validateTimetableForm()"></div>
        </div>
        <div id="timeError" style="color:#dc3545; font-size:12px; margin-top:-10px; display:none; margin-bottom:10px;">End time must be after start time!</div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addSlotModal')">Cancel</button><button type="submit" class="btn btn-primary" id="saveSlotBtn">Save</button></div></form></div></div>
    
    <script>
    const periodTimes = <?php echo json_encode($period_times); ?>;
    
    function updateTimes(p) {
        if(periodTimes[p]) {
            document.getElementById('start_time').value = periodTimes[p][0];
            document.getElementById('end_time').value = periodTimes[p][1];
        }
        validateTimetableForm();
    }

    function filterTeachers(subjectName) {
        const teacherSelect = document.getElementById('teacherSelect');
        const options = teacherSelect.options;
        subjectName = subjectName.trim();
        
        for (let i = 1; i < options.length; i++) {
            const spec = options[i].getAttribute('data-spec');
            if (!subjectName || subjectName === 'Select Subject' || !spec || spec.toLowerCase().includes(subjectName.toLowerCase()) || subjectName.toLowerCase().includes(spec.toLowerCase())) {
                options[i].style.display = '';
            } else {
                options[i].style.display = 'none';
            }
        }
        if(teacherSelect.selectedIndex > 0 && options[teacherSelect.selectedIndex].style.display === 'none') {
            teacherSelect.value = '';
        }
        validateTimetableForm();
    }
    
    function validateTimetableForm() {
        const sub = document.getElementById('subjectSelect').value;
        const start = document.getElementById('start_time').value;
        const end = document.getElementById('end_time').value;
        const btn = document.getElementById('saveSlotBtn');
        const errorDiv = document.getElementById('timeError');
        
        let isValid = true;
        
        if (!sub || !start || !end) {
            isValid = false;
        } else {
            if (start >= end) {
                isValid = false;
                errorDiv.style.display = 'block';
            } else {
                errorDiv.style.display = 'none';
            }
        }
        
        if (isValid) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
        }
    }
    
    document.querySelectorAll('#timetableForm select, #timetableForm input').forEach(el => {
        el.addEventListener('change', validateTimetableForm);
        el.addEventListener('input', validateTimetableForm);
    });
    
    window.addEventListener('DOMContentLoaded', validateTimetableForm);
    </script>
    <?php endif; ?>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>

