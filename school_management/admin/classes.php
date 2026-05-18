<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'add') {
        $cn = sanitize($conn, $_POST['class_name']);
        $sec = sanitize($conn, $_POST['section']);
        $tid = !empty($_POST['class_teacher_id']) ? intval($_POST['class_teacher_id']) : 'NULL';
        $ay = sanitize($conn, $_POST['academic_year']);
        mysqli_query($conn, "INSERT INTO classes (class_name,section,class_teacher_id,academic_year) VALUES ('$cn','$sec',$tid,'$ay')");
        setFlashMessage('success', "Class '$cn $sec' created!");
        header('Location: classes.php'); exit();
    }
    if ($action === 'edit') {
        $cid = intval($_POST['class_id']);
        $cn = sanitize($conn, $_POST['class_name']);
        $sec = sanitize($conn, $_POST['section']);
        $tid = !empty($_POST['class_teacher_id']) ? intval($_POST['class_teacher_id']) : 'NULL';
        $ay = sanitize($conn, $_POST['academic_year']);
        mysqli_query($conn, "UPDATE classes SET class_name='$cn',section='$sec',class_teacher_id=$tid,academic_year='$ay' WHERE class_id=$cid");
        setFlashMessage('success', 'Class updated!');
        header('Location: classes.php'); exit();
    }
    if ($action === 'delete') {
        mysqli_query($conn, "DELETE FROM classes WHERE class_id=".intval($_POST['class_id']));
        setFlashMessage('success', 'Class deleted!');
        header('Location: classes.php'); exit();
    }
    if ($action === 'assign_subject') {
        $cid = intval($_POST['class_id']); $sid = intval($_POST['subject_id']);
        $tid = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 'NULL';
        $chk = mysqli_query($conn, "SELECT id FROM class_subjects WHERE class_id=$cid AND subject_id=$sid");
        if (mysqli_num_rows($chk) > 0) {
            mysqli_query($conn, "UPDATE class_subjects SET teacher_id=$tid WHERE class_id=$cid AND subject_id=$sid");
        } else {
            mysqli_query($conn, "INSERT INTO class_subjects (class_id,subject_id,teacher_id) VALUES ($cid,$sid,$tid)");
        }
        setFlashMessage('success', 'Subject assigned!');
        header('Location: classes.php'); exit();
    }
}

$classes_r = mysqli_query($conn, "SELECT c.*, CONCAT(t.first_name,' ',t.last_name) as teacher_name, (SELECT COUNT(*) FROM students WHERE class_id=c.class_id) as student_count FROM classes c LEFT JOIN teachers t ON c.class_teacher_id=t.teacher_id ORDER BY c.class_name");
$teachers_r = mysqli_query($conn, "SELECT teacher_id, CONCAT(first_name,' ',last_name) as name FROM teachers ORDER BY first_name");
$subjects_r = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div><h1>📚 Classes Management</h1><p>Manage classes and assign subjects</p></div>
                <div style="display:flex;gap:10px">
                    <button class="btn btn-success" onclick="openModal('assignModal')">📖 Assign Subject</button>
                    <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Class</button>
                </div>
            </div>
            <div class="table-container">
                <table class="data-table" id="dataTable">
                    <thead><tr><th>Class</th><th>Section</th><th>Class Teacher</th><th>Year</th><th>Students</th><th>Subjects</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($c = mysqli_fetch_assoc($classes_r)):
                        $sr = mysqli_query($conn, "SELECT s.subject_name FROM class_subjects cs JOIN subjects s ON cs.subject_id=s.subject_id WHERE cs.class_id=".$c['class_id']);
                        $subs = []; while ($x = mysqli_fetch_assoc($sr)) $subs[] = $x['subject_name'];
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($c['class_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($c['section']); ?></td>
                        <td><?php echo $c['teacher_name'] ?: '<span style="color:var(--gray)">—</span>'; ?></td>
                        <td><?php echo htmlspecialchars($c['academic_year']); ?></td>
                        <td><span class="badge badge-paid"><?php echo $c['student_count']; ?></span></td>
                        <td><small><?php echo $subs ? implode(', ',$subs) : '—'; ?></small></td>
                        <td class="actions-cell">
                            <button class="btn btn-sm btn-info" onclick='openEditClass(<?php echo json_encode($c); ?>)'>✏️</button>
                            <form method="POST" style="display:inline" onsubmit="return confirmDelete()"><input type="hidden" name="action" value="delete"><input type="hidden" name="class_id" value="<?php echo $c['class_id']; ?>"><button class="btn btn-sm btn-danger">🗑️</button></form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Add Modal -->
    <div id="addModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Add Class</h2><span class="close" onclick="closeModal('addModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="add"><div class="modal-body">
        <div class="form-row"><div class="form-group"><label>Class Name *</label><input type="text" name="class_name" required></div><div class="form-group"><label>Section *</label><input type="text" name="section" value="A" required></div></div>
        <div class="form-row"><div class="form-group"><label>Class Teacher</label><select name="class_teacher_id"><option value="">Select</option><?php mysqli_data_seek($teachers_r,0); while($t=mysqli_fetch_assoc($teachers_r)): ?><option value="<?php echo $t['teacher_id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option><?php endwhile; ?></select></div>
        <div class="form-group"><label>Academic Year</label><input type="text" name="academic_year" value="2025-26" required></div></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div></form></div></div>
    <!-- Edit Modal -->
    <div id="editModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Edit Class</h2><span class="close" onclick="closeModal('editModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="class_id" id="e_cid"><div class="modal-body">
        <div class="form-row"><div class="form-group"><label>Class Name</label><input type="text" name="class_name" id="e_cn" required></div><div class="form-group"><label>Section</label><input type="text" name="section" id="e_sec" required></div></div>
        <div class="form-row"><div class="form-group"><label>Class Teacher</label><select name="class_teacher_id" id="e_tid"><option value="">Select</option><?php mysqli_data_seek($teachers_r,0); while($t=mysqli_fetch_assoc($teachers_r)): ?><option value="<?php echo $t['teacher_id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option><?php endwhile; ?></select></div>
        <div class="form-group"><label>Academic Year</label><input type="text" name="academic_year" id="e_ay"></div></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div></form></div></div>
    <!-- Assign Subject Modal -->
    <div id="assignModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Assign Subject</h2><span class="close" onclick="closeModal('assignModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="assign_subject"><div class="modal-body">
        <div class="form-group"><label>Class *</label><select name="class_id" required><option value="">Select</option><?php $cl=mysqli_query($conn,"SELECT * FROM classes ORDER BY class_name"); while($c=mysqli_fetch_assoc($cl)): ?><option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option><?php endwhile; ?></select></div>
        <div class="form-group"><label>Subject *</label><select name="subject_id" required><option value="">Select</option><?php mysqli_data_seek($subjects_r,0); while($s=mysqli_fetch_assoc($subjects_r)): ?><option value="<?php echo $s['subject_id']; ?>"><?php echo htmlspecialchars($s['subject_name']); ?></option><?php endwhile; ?></select></div>
        <div class="form-group"><label>Teacher</label><select name="teacher_id"><option value="">Select</option><?php mysqli_data_seek($teachers_r,0); while($t=mysqli_fetch_assoc($teachers_r)): ?><option value="<?php echo $t['teacher_id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option><?php endwhile; ?></select></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('assignModal')">Cancel</button><button type="submit" class="btn btn-success">Assign</button></div></form></div></div>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>function openEditClass(d){document.getElementById('e_cid').value=d.class_id;document.getElementById('e_cn').value=d.class_name;document.getElementById('e_sec').value=d.section;document.getElementById('e_tid').value=d.class_teacher_id||'';document.getElementById('e_ay').value=d.academic_year||'';openModal('editModal');}</script>
</body>
</html>
