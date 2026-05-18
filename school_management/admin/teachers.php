<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $username = sanitize($conn, $_POST['username']);
        $password = md5($_POST['password']);
        $first_name = sanitize($conn, $_POST['first_name']);
        $last_name = sanitize($conn, $_POST['last_name']);
        $gender = sanitize($conn, $_POST['gender']);
        $phone = sanitize($conn, $_POST['phone']);
        $email = sanitize($conn, $_POST['email']);
        $qualification = sanitize($conn, $_POST['qualification']);
        $specialization = sanitize($conn, $_POST['subject_specialization']);
        $dob = sanitize($conn, $_POST['date_of_birth']);
        
        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username'");
        if (mysqli_num_rows($check) > 0) {
            setFlashMessage('error', 'Username already exists!');
        } else {
            $q = "INSERT INTO users (username, password, user_type, email) VALUES ('$username', '$password', 'teacher', '$email')";
            if (mysqli_query($conn, $q)) {
                $uid = mysqli_insert_id($conn);
                $q2 = "INSERT INTO teachers (user_id, first_name, last_name, date_of_birth, gender, phone, email, qualification, subject_specialization, joining_date) 
                       VALUES ($uid, '$first_name', '$last_name', '$dob', '$gender', '$phone', '$email', '$qualification', '$specialization', CURDATE())";
                mysqli_query($conn, $q2);
                setFlashMessage('success', "Teacher '$first_name $last_name' added successfully!");
            }
        }
        header('Location: teachers.php'); exit();
    }
    
    if ($action === 'edit') {
        $tid = intval($_POST['teacher_id']);
        $first_name = sanitize($conn, $_POST['first_name']);
        $last_name = sanitize($conn, $_POST['last_name']);
        $gender = sanitize($conn, $_POST['gender']);
        $phone = sanitize($conn, $_POST['phone']);
        $email = sanitize($conn, $_POST['email']);
        $qualification = sanitize($conn, $_POST['qualification']);
        $specialization = sanitize($conn, $_POST['subject_specialization']);
        $dob = sanitize($conn, $_POST['date_of_birth']);
        
        mysqli_query($conn, "UPDATE teachers SET first_name='$first_name', last_name='$last_name', date_of_birth='$dob', gender='$gender', phone='$phone', email='$email', qualification='$qualification', subject_specialization='$specialization' WHERE teacher_id=$tid");
        setFlashMessage('success', 'Teacher updated successfully!');
        header('Location: teachers.php'); exit();
    }
    
    if ($action === 'delete') {
        $tid = intval($_POST['teacher_id']);
        $r = mysqli_query($conn, "SELECT user_id FROM teachers WHERE teacher_id=$tid");
        if ($row = mysqli_fetch_assoc($r)) {
            mysqli_query($conn, "DELETE FROM users WHERE user_id=" . $row['user_id']);
        }
        setFlashMessage('success', 'Teacher deleted successfully!');
        header('Location: teachers.php'); exit();
    }
}

$teachers_result = mysqli_query($conn, "SELECT t.*, u.username FROM teachers t LEFT JOIN users u ON t.user_id = u.user_id ORDER BY t.teacher_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div><h1>👨‍🏫 Teachers Management</h1><p>Manage all teacher records</p></div>
                <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Teacher</button>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h2>All Teachers (<?php echo mysqli_num_rows($teachers_result); ?>)</h2>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search teachers..." onkeyup="searchTable('searchInput','dataTable')">
                    </div>
                </div>
                <table class="data-table" id="dataTable">
                    <thead>
                        <tr><th>Name</th><th>Username</th><th>Specialization</th><th>Qualification</th><th>Phone</th><th>Email</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($teachers_result) > 0): ?>
                        <?php while ($t = mysqli_fetch_assoc($teachers_result)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></strong></td>
                            <td>@<?php echo htmlspecialchars($t['username']); ?></td>
                            <td><?php echo htmlspecialchars($t['subject_specialization']); ?></td>
                            <td><?php echo htmlspecialchars($t['qualification']); ?></td>
                            <td><?php echo htmlspecialchars($t['phone']); ?></td>
                            <td><?php echo htmlspecialchars($t['email']); ?></td>
                            <td class="actions-cell">
                                <button class="btn btn-sm btn-info" onclick='openEditModal(<?php echo json_encode($t); ?>)'>✏️ Edit</button>
                                <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this teacher?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="teacher_id" value="<?php echo $t['teacher_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7"><div class="empty-state"><div class="empty-icon">👨‍🏫</div><p>No teachers found.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2>Add New Teacher</h2><span class="close" onclick="closeModal('addModal')">&times;</span></div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group"><label>First Name *</label><input type="text" name="first_name" required></div>
                        <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Username *</label><input type="text" name="username" required></div>
                        <div class="form-group"><label>Password *</label><input type="password" name="password" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth"></div>
                        <div class="form-group"><label>Gender *</label>
                            <select name="gender" required><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Qualification</label><input type="text" name="qualification" placeholder="e.g. M.Sc. Mathematics"></div>
                        <div class="form-group"><label>Subject Specialization</label><input type="text" name="subject_specialization" placeholder="e.g. Mathematics"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Teacher</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2>Edit Teacher</h2><span class="close" onclick="closeModal('editModal')">&times;</span></div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="teacher_id" id="edit_teacher_id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group"><label>First Name *</label><input type="text" name="first_name" id="edit_first_name" required></div>
                        <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" id="edit_last_name" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" id="edit_dob"></div>
                        <div class="form-group"><label>Gender</label>
                            <select name="gender" id="edit_gender"><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email"></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Qualification</label><input type="text" name="qualification" id="edit_qualification"></div>
                        <div class="form-group"><label>Specialization</label><input type="text" name="subject_specialization" id="edit_specialization"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>
    function openEditModal(d) {
        document.getElementById('edit_teacher_id').value = d.teacher_id;
        document.getElementById('edit_first_name').value = d.first_name;
        document.getElementById('edit_last_name').value = d.last_name;
        document.getElementById('edit_dob').value = d.date_of_birth || '';
        document.getElementById('edit_gender').value = d.gender || 'Male';
        document.getElementById('edit_email').value = d.email || '';
        document.getElementById('edit_phone').value = d.phone || '';
        document.getElementById('edit_qualification').value = d.qualification || '';
        document.getElementById('edit_specialization').value = d.subject_specialization || '';
        openModal('editModal');
    }
    </script>
</body>
</html>
