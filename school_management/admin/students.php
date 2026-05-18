<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $username = sanitize($conn, $_POST['username']);
        $password = md5($_POST['password']);
        $first_name = sanitize($conn, $_POST['first_name']);
        $last_name = sanitize($conn, $_POST['last_name']);
        $class_id = intval($_POST['class_id']);
        $gender = sanitize($conn, $_POST['gender']);
        $phone = sanitize($conn, $_POST['phone']);
        $parent_name = sanitize($conn, $_POST['parent_name']);
        $parent_phone = sanitize($conn, $_POST['parent_phone']);
        $dob = sanitize($conn, $_POST['date_of_birth']);
        
        // Check if username exists
        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username'");
        if (mysqli_num_rows($check) > 0) {
            setFlashMessage('error', 'Username already exists!');
        } else {
            $q = "INSERT INTO users (username, password, user_type, email) VALUES ('$username', '$password', 'student', '$username@school.com')";
            if (mysqli_query($conn, $q)) {
                $uid = mysqli_insert_id($conn);
                $roll = 'STD' . sprintf('%03d', $uid);
                $q2 = "INSERT INTO students (user_id, first_name, last_name, date_of_birth, gender, phone, parent_name, parent_phone, class_id, roll_number, admission_date) 
                       VALUES ($uid, '$first_name', '$last_name', '$dob', '$gender', '$phone', '$parent_name', '$parent_phone', $class_id, '$roll', CURDATE())";
                mysqli_query($conn, $q2);
                setFlashMessage('success', "Student '$first_name $last_name' added successfully!");
            } else {
                setFlashMessage('error', 'Failed to add student.');
            }
        }
        header('Location: students.php'); exit();
    }
    
    if ($action === 'edit') {
        $sid = intval($_POST['student_id']);
        $first_name = sanitize($conn, $_POST['first_name']);
        $last_name = sanitize($conn, $_POST['last_name']);
        $class_id = intval($_POST['class_id']);
        $gender = sanitize($conn, $_POST['gender']);
        $phone = sanitize($conn, $_POST['phone']);
        $parent_name = sanitize($conn, $_POST['parent_name']);
        $parent_phone = sanitize($conn, $_POST['parent_phone']);
        $dob = sanitize($conn, $_POST['date_of_birth']);
        
        $q = "UPDATE students SET first_name='$first_name', last_name='$last_name', date_of_birth='$dob', gender='$gender', phone='$phone', parent_name='$parent_name', parent_phone='$parent_phone', class_id=$class_id WHERE student_id=$sid";
        mysqli_query($conn, $q);
        setFlashMessage('success', 'Student updated successfully!');
        header('Location: students.php'); exit();
    }
    
    if ($action === 'delete') {
        $sid = intval($_POST['student_id']);
        // Get user_id first
        $r = mysqli_query($conn, "SELECT user_id FROM students WHERE student_id=$sid");
        if ($row = mysqli_fetch_assoc($r)) {
            mysqli_query($conn, "DELETE FROM users WHERE user_id=" . $row['user_id']);
        }
        setFlashMessage('success', 'Student deleted successfully!');
        header('Location: students.php'); exit();
    }
}

// Fetch students
$students_result = mysqli_query($conn, "SELECT s.*, c.class_name, c.section, u.username 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.class_id 
    LEFT JOIN users u ON s.user_id = u.user_id 
    ORDER BY s.student_id DESC");

// Fetch classes for dropdown
$classes_result = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div>
                    <h1>👨‍🎓 Students Management</h1>
                    <p>Manage all student records</p>
                </div>
                <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Student</button>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h2>All Students (<?php echo mysqli_num_rows($students_result); ?>)</h2>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search students..." onkeyup="searchTable('searchInput','dataTable')">
                    </div>
                </div>
                <table class="data-table" id="dataTable">
                    <thead>
                        <tr>
                            <th>Roll No</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Gender</th>
                            <th>Phone</th>
                            <th>Parent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($students_result) > 0): ?>
                        <?php while ($s = mysqli_fetch_assoc($students_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['roll_number']); ?></td>
                            <td><strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong><br><small style="color:var(--gray)">@<?php echo htmlspecialchars($s['username']); ?></small></td>
                            <td><?php echo htmlspecialchars($s['class_name'] . ' ' . $s['section']); ?></td>
                            <td><?php echo htmlspecialchars($s['gender']); ?></td>
                            <td><?php echo htmlspecialchars($s['phone']); ?></td>
                            <td><?php echo htmlspecialchars($s['parent_name']); ?></td>
                            <td class="actions-cell">
                                <button class="btn btn-sm btn-info" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($s)); ?>)">✏️ Edit</button>
                                <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this student?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id" value="<?php echo $s['student_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7"><div class="empty-state"><div class="empty-icon">👨‍🎓</div><p>No students found. Click "Add Student" to get started.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Student</h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth">
                        </div>
                        <div class="form-group">
                            <label>Gender *</label>
                            <select name="gender" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Class *</label>
                        <select name="class_id" required>
                            <option value="">Select Class</option>
                            <?php mysqli_data_seek($classes_result, 0); while ($c = mysqli_fetch_assoc($classes_result)): ?>
                            <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone">
                        </div>
                        <div class="form-group">
                            <label>Parent Name</label>
                            <input type="text" name="parent_name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Parent Phone</label>
                        <input type="text" name="parent_phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Student</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="student_id" id="edit_student_id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" id="edit_first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" id="edit_last_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" id="edit_dob">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" id="edit_gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Class *</label>
                        <select name="class_id" id="edit_class_id" required>
                            <?php mysqli_data_seek($classes_result, 0); while ($c = mysqli_fetch_assoc($classes_result)): ?>
                            <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" id="edit_phone">
                        </div>
                        <div class="form-group">
                            <label>Parent Name</label>
                            <input type="text" name="parent_name" id="edit_parent_name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Parent Phone</label>
                        <input type="text" name="parent_phone" id="edit_parent_phone">
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
    function openEditModal(data) {
        document.getElementById('edit_student_id').value = data.student_id;
        document.getElementById('edit_first_name').value = data.first_name;
        document.getElementById('edit_last_name').value = data.last_name;
        document.getElementById('edit_dob').value = data.date_of_birth || '';
        document.getElementById('edit_gender').value = data.gender || 'Male';
        document.getElementById('edit_class_id').value = data.class_id;
        document.getElementById('edit_phone').value = data.phone || '';
        document.getElementById('edit_parent_name').value = data.parent_name || '';
        document.getElementById('edit_parent_phone').value = data.parent_phone || '';
        openModal('editModal');
    }
    </script>
</body>
</html>
