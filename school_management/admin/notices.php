<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = $_POST['action'];
    if ($a === 'add' || $a === 'edit') {
        $title = sanitize($conn, $_POST['title']);
        $desc = sanitize($conn, $_POST['description']);
        $target = sanitize($conn, $_POST['target_audience']);
        $link = sanitize($conn, $_POST['link_url']);
        $uid = getUserId();
        
        $file_name = '';
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(__DIR__) . '/uploads/notices/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            if ($file_ext === 'pdf') {
                $file_name = 'notice_' . time() . '_' . uniqid() . '.pdf';
                move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $file_name);
            }
        }

        if ($a === 'add') {
            $date = date('Y-m-d');
            mysqli_query($conn, "INSERT INTO notices (title,description,notice_date,target_audience,file_attachment,link_url,posted_by) VALUES ('$title','$desc','$date','$target','$file_name','$link',$uid)");
            setFlashMessage('success', 'Notice has been published successfully!');
        } else {
            $nid = intval($_POST['notice_id']);
            $q = "UPDATE notices SET title='$title', description='$desc', target_audience='$target', link_url='$link'";
            if ($file_name) $q .= ", file_attachment='$file_name'";
            $q .= " WHERE notice_id=$nid";
            mysqli_query($conn, $q);
            setFlashMessage('success', 'Notice updated successfully!');
        }
        header('Location: notices.php'); exit();
    }
    if ($a === 'delete') {
        mysqli_query($conn, "DELETE FROM notices WHERE notice_id=".intval($_POST['notice_id']));
        setFlashMessage('success', 'Notice has been deleted successfully.');
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div><h1><i class="fa-solid fa-bullhorn"></i> Notice Board</h1><p>Create and manage notices</p></div>
                <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Notice</button>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Title</th><th>Audience</th><th>Attachments</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($n = mysqli_fetch_assoc($notices)): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($n['notice_date'])); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($n['title']); ?></strong><br>
                            <small style="color:#666;"><?php echo htmlspecialchars(substr($n['description'],0,100)); ?>...</small>
                        </td>
                        <td><span class="badge badge-paid"><?php echo $n['target_audience']; ?></span></td>
                        <td>
                            <?php if ($n['file_attachment']): ?>
                                <a href="<?php echo BASE_URL; ?>/uploads/notices/<?php echo $n['file_attachment']; ?>" target="_blank" class="btn btn-sm btn-secondary" style="background:#dc3545; color:white; border:none;" title="View PDF"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                            <?php endif; ?>
                            <?php if ($n['link_url']): ?>
                                <a href="<?php echo $n['link_url']; ?>" target="_blank" class="btn btn-sm btn-secondary" style="background:#17a2b8; color:white; border:none; margin-left:5px;" title="Visit Link"><i class="fa-solid fa-link"></i> Link</a>
                            <?php endif; ?>
                            <?php if (!$n['file_attachment'] && !$n['link_url']): ?>
                                <span style="color:#999; font-size:12px;">No attachments</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <button class="btn btn-sm btn-info" onclick="openEdit(<?php echo htmlspecialchars(json_encode($n)); ?>)"><i class="fa-solid fa-pen-to-square"></i></button>
                            <form method="POST" style="display:inline" onsubmit="return confirmDelete()"><input type="hidden" name="action" value="delete"><input type="hidden" name="notice_id" value="<?php echo $n['notice_id']; ?>"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash-can"></i></button></form>
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
    <form method="POST" enctype="multipart/form-data" id="addNoticeForm"><input type="hidden" name="action" value="add"><div class="modal-body">
        <div class="form-group"><label>Title *</label><input type="text" name="title" id="title" oninput="validateNoticeForm()" required></div>
        <div class="form-group"><label>Description *</label><textarea name="description" id="description" rows="4" oninput="validateNoticeForm()" required></textarea></div>
        <div class="form-row">
            <div class="form-group"><label>Target Audience</label><select name="target_audience" style="width:100%"><option value="All">All</option><option value="Students">Students</option><option value="Teachers">Teachers</option><option value="Parents">Parents</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Attach PDF (Optional)</label><input type="file" name="attachment" accept=".pdf"></div>
            <div class="form-group"><label>Attach Link (Optional)</label><input type="url" name="link_url" placeholder="https://example.com"></div>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button><button type="submit" id="publishBtn" class="btn btn-primary" disabled style="opacity:0.5; cursor:not-allowed;">Publish</button></div></form></div></div>

    <!-- Edit Notice Modal -->
    <div id="editModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Edit Notice</h2><span class="close" onclick="closeModal('editModal')">&times;</span></div>
    <form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="edit"><input type="hidden" name="notice_id" id="e_nid"><div class="modal-body">
        <div class="form-group"><label>Title</label><input type="text" name="title" id="e_title" required></div>
        <div class="form-group"><label>Description</label><textarea name="description" id="e_desc" rows="4" required></textarea></div>
        <div class="form-group"><label>Target Audience</label><select name="target_audience" id="e_target"><option value="All">All</option><option value="Students">Students</option><option value="Teachers">Teachers</option><option value="Parents">Parents</option></select></div>
        <div class="form-row">
            <div class="form-group"><label>Update PDF Attachment</label><input type="file" name="attachment" accept=".pdf"></div>
            <div class="form-group"><label>Update Link URL</label><input type="url" name="link_url" id="e_link"></div>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div></form></div></div>

    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>
    function openEdit(d){
        document.getElementById('e_nid').value=d.notice_id;
        document.getElementById('e_title').value=d.title;
        document.getElementById('e_desc').value=d.description;
        document.getElementById('e_target').value=d.target_audience;
        document.getElementById('e_link').value=d.link_url || '';
        openModal('editModal');
    }

    function validateNoticeForm() {
        const title = document.getElementById('title');
        const desc = document.getElementById('description');
        const btn = document.getElementById('publishBtn');
        
        if (!title || !desc || !btn) return;

        const isTitleValid = title.value.trim().length > 0;
        const isDescValid = desc.value.trim().length > 0;
        
        if (isTitleValid && isDescValid) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            btn.classList.remove('btn-disabled'); // Just in case
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
        }
    }

    // Call on load and attach to multiple events
    document.addEventListener('DOMContentLoaded', () => {
        const title = document.getElementById('title');
        const desc = document.getElementById('description');
        if(title) title.addEventListener('input', validateNoticeForm);
        if(desc) desc.addEventListener('input', validateNoticeForm);
        
        // Also call it periodically or on modal open if possible
        // Since we use openModal global function, we can wrap it
        const originalOpenModal = window.openModal;
        window.openModal = function(id) {
            if(originalOpenModal) originalOpenModal(id);
            if(id === 'addModal') validateNoticeForm();
        }
    });
    </script>
</body>
</html>

