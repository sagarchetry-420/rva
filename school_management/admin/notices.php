<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = $_POST['action'];
    if ($a === 'add') {
        $title = sanitize($conn, $_POST['title']);
        $desc = sanitize($conn, $_POST['description']);
        $date = sanitize($conn, $_POST['notice_date']);
        $target = sanitize($conn, $_POST['target_audience']);
        $uid = getUserId();
        mysqli_query($conn, "INSERT INTO notices (title,description,notice_date,target_audience,posted_by) VALUES ('$title','$desc','$date','$target',$uid)");
        setFlashMessage('success', 'Notice published!');
        header('Location: notices.php'); exit();
    }
    if ($a === 'edit') {
        $nid = intval($_POST['notice_id']);
        $title = sanitize($conn, $_POST['title']);
        $desc = sanitize($conn, $_POST['description']);
        $target = sanitize($conn, $_POST['target_audience']);
        mysqli_query($conn, "UPDATE notices SET title='$title', description='$desc', target_audience='$target' WHERE notice_id=$nid");
        setFlashMessage('success', 'Notice updated!');
        header('Location: notices.php'); exit();
    }
    if ($a === 'delete') {
        mysqli_query($conn, "DELETE FROM notices WHERE notice_id=".intval($_POST['notice_id']));
        setFlashMessage('success', 'Notice deleted!');
        header('Location: notices.php'); exit();
    }
}

$notices = mysqli_query($conn, "SELECT n.*, u.username as posted_by_name FROM notices n LEFT JOIN users u ON n.posted_by=u.user_id ORDER BY n.notice_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notices - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div><h1>📢 Notice Board</h1><p>Create and manage notices</p></div>
                <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Notice</button>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Title</th><th>Description</th><th>Audience</th><th>Posted By</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($n = mysqli_fetch_assoc($notices)): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($n['notice_date'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($n['title']); ?></strong></td>
                        <td><small><?php echo htmlspecialchars(substr($n['description'],0,80)); ?>...</small></td>
                        <td><span class="badge badge-paid"><?php echo $n['target_audience']; ?></span></td>
                        <td><?php echo htmlspecialchars($n['posted_by_name']); ?></td>
                        <td class="actions-cell">
                            <button class="btn btn-sm btn-info" onclick='openEdit(<?php echo json_encode($n); ?>)'>✏️</button>
                            <form method="POST" style="display:inline" onsubmit="return confirmDelete()"><input type="hidden" name="action" value="delete"><input type="hidden" name="notice_id" value="<?php echo $n['notice_id']; ?>"><button class="btn btn-sm btn-danger">🗑️</button></form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Add Notice Modal -->
    <div id="addModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Add Notice</h2><span class="close" onclick="closeModal('addModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="add"><div class="modal-body">
        <div class="form-group"><label>Title *</label><input type="text" name="title" required></div>
        <div class="form-group"><label>Description *</label><textarea name="description" rows="4" required></textarea></div>
        <div class="form-row">
            <div class="form-group"><label>Date *</label><input type="date" name="notice_date" value="<?php echo date('Y-m-d'); ?>" required></div>
            <div class="form-group"><label>Target Audience</label><select name="target_audience"><option value="All">All</option><option value="Students">Students</option><option value="Teachers">Teachers</option><option value="Parents">Parents</option></select></div>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button><button type="submit" class="btn btn-primary">Publish</button></div></form></div></div>
    <!-- Edit Notice Modal -->
    <div id="editModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Edit Notice</h2><span class="close" onclick="closeModal('editModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="notice_id" id="e_nid"><div class="modal-body">
        <div class="form-group"><label>Title</label><input type="text" name="title" id="e_title" required></div>
        <div class="form-group"><label>Description</label><textarea name="description" id="e_desc" rows="4" required></textarea></div>
        <div class="form-group"><label>Target Audience</label><select name="target_audience" id="e_target"><option value="All">All</option><option value="Students">Students</option><option value="Teachers">Teachers</option><option value="Parents">Parents</option></select></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div></form></div></div>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>function openEdit(d){document.getElementById('e_nid').value=d.notice_id;document.getElementById('e_title').value=d.title;document.getElementById('e_desc').value=d.description;document.getElementById('e_target').value=d.target_audience;openModal('editModal');}</script>
</body>
</html>
