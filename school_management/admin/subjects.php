<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

// Auto-generate subject code from name
function generateSubjectCode($conn, $subjectName) {
    // Take first 3 characters, uppercase
    $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $subjectName), 0, 3));
    if (strlen($prefix) < 3) $prefix = str_pad($prefix, 3, 'X');
    
    // Find next available number
    $r = mysqli_query($conn, "SELECT subject_code FROM subjects WHERE subject_code LIKE '$prefix%' ORDER BY subject_code DESC LIMIT 1");
    if (mysqli_num_rows($r) > 0) {
        $last = mysqli_fetch_assoc($r)['subject_code'];
        $num = intval(substr($last, 3)) + 1;
    } else {
        $num = 1;
    }
    return $prefix . sprintf('%03d', $num);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = $_POST['action'];
    if ($a === 'add') {
        $n = sanitize($conn, $_POST['subject_name']);
        $d = sanitize($conn, $_POST['description']);
        
        

        // Auto-generate subject code
        $c = generateSubjectCode($conn, $n);
        
        mysqli_query($conn, "INSERT INTO subjects (subject_name,subject_code,description) VALUES ('$n','$c','$d')");
        setFlashMessage('success', "Subject '$n' (Code: $c) has been added successfully!");
        header('Location: subjects.php'); exit();
    }
    if ($a === 'edit') {
        $id = intval($_POST['subject_id']);
        $n = sanitize($conn, $_POST['subject_name']);
        $d = sanitize($conn, $_POST['description']);
        
        

        // Don't update subject_code — it's auto-generated and permanent
        mysqli_query($conn, "UPDATE subjects SET subject_name='$n',description='$d' WHERE subject_id=$id");
        setFlashMessage('success', 'Subject details updated successfully!');
        header('Location: subjects.php'); exit();
    }
    if ($a === 'delete') {
        $id = intval($_POST['subject_id']);
        // Check if subject is assigned to any class
        $chk = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM class_subjects WHERE subject_id=$id");
        $cnt = mysqli_fetch_assoc($chk)['cnt'];
        if ($cnt > 0) {
            setFlashMessage('error', "Cannot delete this subject. It is assigned to $cnt class(es).");
            header('Location: subjects.php'); exit();
        }
        mysqli_query($conn, "DELETE FROM subjects WHERE subject_id=$id");
        setFlashMessage('success', 'Subject has been deleted successfully.');
        header('Location: subjects.php'); exit();
    }
}
$subjects = mysqli_query($conn, "SELECT s.*, (SELECT COUNT(*) FROM class_subjects WHERE subject_id=s.subject_id) as class_count FROM subjects s ORDER BY s.subject_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div><h1><i class="fa-solid fa-book"></i> Subjects Management</h1><p>Manage all subjects — codes are auto-generated</p></div>
                <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Subject</button>
            </div>
            <div class="table-container">
                <table class="data-table" id="dataTable">
                    <thead><tr><th>Code</th><th>Subject Name</th><th>Description</th><th>Classes Using</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($s = mysqli_fetch_assoc($subjects)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($s['subject_code'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['subject_name']); ?></td>
                        <td><small><?php echo htmlspecialchars($s['description'] ?? ''); ?></small></td>
                        <td><span class="badge badge-paid"><?php echo $s['class_count']; ?></span></td>
                        <td class="actions-cell">
                            <button class="btn btn-sm btn-info" onclick="openEdit(<?php echo htmlspecialchars(json_encode($s)); ?>)"><i class="fa-solid fa-pen-to-square"></i></button>
                            <form method="POST" style="display:inline" onsubmit="return confirmDelete()"><input type="hidden" name="action" value="delete"><input type="hidden" name="subject_id" value="<?php echo $s['subject_id']; ?>"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash-can"></i></button></form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Add Modal — No subject_code field, it's auto-generated -->
    <div id="addModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Add Subject</h2><span class="close" onclick="closeModal('addModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="add"><div class="modal-body">
        <div class="form-group"><label>Subject Name *</label><input type="text" name="subject_name" required placeholder="e.g. Mathematics"></div>
        <p style="color:var(--gray);font-size:12px;margin:-10px 0 15px;"><i class="fa-solid fa-info-circle"></i> Subject code will be auto-generated (e.g. MAT001)</p>
        <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button><button type="submit" class="btn btn-primary">Add</button></div></form></div></div>
    
    <!-- Edit Modal — Subject code is shown read-only -->
    <div id="editModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Edit Subject</h2><span class="close" onclick="closeModal('editModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="subject_id" id="e_id"><div class="modal-body">
        <div class="form-row">
            <div class="form-group"><label>Subject Name</label><input type="text" name="subject_name" id="e_name" required></div>
            <div class="form-group"><label>Subject Code</label><input type="text" id="e_code" readonly style="background:#f3f4f6;cursor:not-allowed;color:var(--gray)"></div>
        </div>
        <div class="form-group"><label>Description</label><textarea name="description" id="e_desc" rows="3"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div></form></div></div>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>function openEdit(d){document.getElementById('e_id').value=d.subject_id;document.getElementById('e_name').value=d.subject_name;document.getElementById('e_code').value=d.subject_code;document.getElementById('e_desc').value=d.description||'';openModal('editModal');}</script>
</body>
</html>
