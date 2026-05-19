<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = $_POST['action'];
    if ($a === 'add') {
        $n = sanitize($conn, $_POST['subject_name']);
        $c = sanitize($conn, $_POST['subject_code']);
        $d = sanitize($conn, $_POST['description']);
        mysqli_query($conn, "INSERT INTO subjects (subject_name,subject_code,description) VALUES ('$n','$c','$d')");
        setFlashMessage('success', "Subject '$n' has been added successfully!");
        header('Location: subjects.php'); exit();
    }
    if ($a === 'edit') {
        $id = intval($_POST['subject_id']);
        $n = sanitize($conn, $_POST['subject_name']);
        $c = sanitize($conn, $_POST['subject_code']);
        $d = sanitize($conn, $_POST['description']);
        mysqli_query($conn, "UPDATE subjects SET subject_name='$n',subject_code='$c',description='$d' WHERE subject_id=$id");
        setFlashMessage('success', 'Subject details updated successfully!');
        header('Location: subjects.php'); exit();
    }
    if ($a === 'delete') {
        mysqli_query($conn, "DELETE FROM subjects WHERE subject_id=".intval($_POST['subject_id']));
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
                <div><h1><i class="fa-solid fa-book"></i> Subjects Management</h1><p>Manage all subjects</p></div>
                <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Subject</button>
            </div>
            <div class="table-container">
                <table class="data-table" id="dataTable">
                    <thead><tr><th>Code</th><th>Subject Name</th><th>Description</th><th>Classes Using</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($s = mysqli_fetch_assoc($subjects)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($s['subject_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['subject_name']); ?></td>
                        <td><small><?php echo htmlspecialchars($s['description']); ?></small></td>
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
    <div id="addModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Add Subject</h2><span class="close" onclick="closeModal('addModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="add"><div class="modal-body">
        <div class="form-row"><div class="form-group"><label>Subject Name *</label><input type="text" name="subject_name" required></div><div class="form-group"><label>Subject Code *</label><input type="text" name="subject_code" required></div></div>
        <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button><button type="submit" class="btn btn-primary">Add</button></div></form></div></div>
    <div id="editModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Edit Subject</h2><span class="close" onclick="closeModal('editModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="subject_id" id="e_id"><div class="modal-body">
        <div class="form-row"><div class="form-group"><label>Subject Name</label><input type="text" name="subject_name" id="e_name" required></div><div class="form-group"><label>Code</label><input type="text" name="subject_code" id="e_code" required></div></div>
        <div class="form-group"><label>Description</label><textarea name="description" id="e_desc" rows="3"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div></form></div></div>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>function openEdit(d){document.getElementById('e_id').value=d.subject_id;document.getElementById('e_name').value=d.subject_name;document.getElementById('e_code').value=d.subject_code;document.getElementById('e_desc').value=d.description||'';openModal('editModal');}</script>
</body>
</html>

