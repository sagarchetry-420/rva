<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once dirname(__DIR__) . '/includes/PHPMailer/Exception.php';
require_once dirname(__DIR__) . '/includes/PHPMailer/PHPMailer.php';
require_once dirname(__DIR__) . '/includes/PHPMailer/SMTP.php';

// Helper function for sending credentials
function sendCredentialsEmail($toEmail, $firstName, $username, $plainPassword) {
    global $conn;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USER') ?: 'test@example.com';
        $mail->Password   = getenv('SMTP_PASS') ?: 'password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('SMTP_PORT') ?: 587;

        $mail->setFrom(getenv('SMTP_USER') ?: 'admin@school.com', APP_NAME);
        $mail->addAddress($toEmail, $firstName);

        $mail->isHTML(true);
        $mail->Subject = 'Your School Account Credentials';
        $mail->Body    = "Hello $firstName,<br><br>Your account has been created. Here are your login details:<br><b>Username:</b> $username<br><b>Password:</b> $plainPassword<br><br>Please log in and change your password.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $first_name = sanitize($conn, $_POST['first_name']);
        $last_name = sanitize($conn, $_POST['last_name']);
        $class_id = intval($_POST['class_id']);
        $gender = sanitize($conn, $_POST['gender']);
        $phone = sanitize($conn, $_POST['phone']);
        $parent_name = sanitize($conn, $_POST['parent_name']);
        $parent_phone = sanitize($conn, $_POST['parent_phone']);
        $dob = sanitize($conn, $_POST['date_of_birth']);
        $email = sanitize($conn, $_POST['email']);
        // Check if email already exists
        if (!empty($email)) {
            $email_check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
            if (mysqli_num_rows($email_check) > 0) {
                setFlashMessage('error', 'This email address is already registered. Please use a different email.');
                header('Location: students.php'); exit();
            }
        }
        
        $username = strtolower($first_name . '.' . $last_name . rand(100, 999));
        $plain_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$'), 0, 10);
        $password = md5($plain_password);
        
        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username'");
        if (mysqli_num_rows($check) > 0) {
            $username = $username . rand(1, 99);
        }
        
        $q = "INSERT INTO users (username, password, user_type, email) VALUES ('$username', '$password', 'student', '$email')";
        if (mysqli_query($conn, $q)) {
            $uid = mysqli_insert_id($conn);
            $roll = 'STD' . sprintf('%03d', $uid);
            $q2 = "INSERT INTO students (user_id, first_name, last_name, date_of_birth, gender, phone, email, parent_name, parent_phone, class_id, roll_number, admission_date) 
                   VALUES ($uid, '$first_name', '$last_name', '$dob', '$gender', '$phone', '$email', '$parent_name', '$parent_phone', $class_id, '$roll', CURDATE())";
            mysqli_query($conn, $q2);
            
            if (!empty($email)) {
                sendCredentialsEmail($email, $first_name, $username, $plain_password);
            }
            
            setFlashMessage('success', 'Student registered successfully!');
        } else {
            setFlashMessage('error', 'Failed to register the student. Please try again.');
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
        $email = sanitize($conn, $_POST['email']);
        
        // Get current data to check for changes and get user_id
        $current_res = mysqli_query($conn, "SELECT * FROM students WHERE student_id=$sid");
        $current = mysqli_fetch_assoc($current_res);
        $uid = $current['user_id'];
        
        // Check for duplicate email
        if (!empty($email) && $email !== $current['email']) {
            $email_check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' AND user_id != $uid");
            if (mysqli_num_rows($email_check) > 0) {
                setFlashMessage('error', 'This email address is already registered by another user.');
                header('Location: students.php'); exit();
            }
        }
        
        // Check if anything actually changed
        if ($first_name === $current['first_name'] && $last_name === $current['last_name'] && 
            $class_id == $current['class_id'] && $gender === $current['gender'] && 
            $phone === $current['phone'] && $parent_name === $current['parent_name'] && 
            $parent_phone === $current['parent_phone'] && $dob === $current['date_of_birth'] && 
            $email === $current['email']) {
            setFlashMessage('info', 'No changes were made.');
            header('Location: students.php'); exit();
        }
        
        $q = "UPDATE students SET first_name='$first_name', last_name='$last_name', date_of_birth='$dob', gender='$gender', phone='$phone', email='$email', parent_name='$parent_name', parent_phone='$parent_phone', class_id=$class_id WHERE student_id=$sid";
        mysqli_query($conn, $q);
        mysqli_query($conn, "UPDATE users SET email='$email' WHERE user_id=$uid");
        
        setFlashMessage('success', 'Student details updated successfully!');
        header('Location: students.php'); exit();
    }
    
    if ($action === 'delete') {
        $sid = intval($_POST['student_id']);
        $r = mysqli_query($conn, "SELECT user_id FROM students WHERE student_id=$sid");
        if ($row = mysqli_fetch_assoc($r)) {
            mysqli_query($conn, "DELETE FROM students WHERE student_id=$sid");
            mysqli_query($conn, "DELETE FROM users WHERE user_id=" . $row['user_id']);
        }
        setFlashMessage('success', 'Student record has been deleted successfully.');
        header('Location: students.php'); exit();
    }
    
    if ($action === 'export_csv') {
        $filter_class = isset($_POST['filter_class']) ? intval($_POST['filter_class']) : 0;
        $q = "SELECT s.roll_number, s.first_name, s.last_name, s.date_of_birth, s.gender, s.phone, s.email, s.parent_name, s.parent_phone, c.class_name, c.section, u.username 
              FROM students s 
              LEFT JOIN classes c ON s.class_id = c.class_id 
              LEFT JOIN users u ON s.user_id = u.user_id";
        if ($filter_class > 0) {
            $q .= " WHERE s.class_id = $filter_class";
        }
        $res = mysqli_query($conn, $q);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="students_export.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Roll No', 'First Name', 'Last Name', 'Username', 'Class', 'Section', 'DOB', 'Gender', 'Phone', 'Email', 'Parent Name', 'Parent Phone'));
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }
}

// Fetch students
$filter_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$query = "SELECT s.*, c.class_name, c.section, u.username 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.class_id 
    LEFT JOIN users u ON s.user_id = u.user_id ";
if ($filter_class > 0) {
    $query .= " WHERE s.class_id = $filter_class ";
}
$query .= " ORDER BY s.student_id DESC";
$students_result = mysqli_query($conn, $query);

// Fetch classes for dropdown
$classes_result = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-user-graduate"></i> Students Management</h1>
                    <p>Manage all student records</p>
                </div>
                <div style="display:flex; gap:10px;">
                    <a href="bulk_upload_students.php" class="btn btn-info"><i class="fas fa-upload"></i> Bulk Upload</a>
                    <button class="btn btn-primary" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Student</button>
                </div>
            </div>
            
            <div class="filter-bar">
                <form method="GET" style="display:flex; gap:10px; align-items:flex-end; width:100%;">
                    <div class="filter-group">
                        <label>Filter by Class</label>
                        <select name="class_id" onchange="this.form.submit()">
                            <option value="">All Classes</option>
                            <?php mysqli_data_seek($classes_result, 0); while ($c = mysqli_fetch_assoc($classes_result)): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo $filter_class == $c['class_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
                <form method="POST" style="margin-left:auto;">
                    <input type="hidden" name="action" value="export_csv">
                    <input type="hidden" name="filter_class" value="<?php echo $filter_class; ?>">
                    <button type="submit" class="btn btn-success"><i class="fas fa-file-csv"></i> Download CSV</button>
                    <button type="button" class="btn btn-danger" onclick="downloadPDF()"><i class="fas fa-file-pdf"></i> Download PDF</button>
                </form>
            </div>
            
            <div class="table-container" id="printableTable">
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
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($students_result) > 0): ?>
                        <?php while ($s = mysqli_fetch_assoc($students_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['roll_number'] ?? ''); ?></td>
                            <td><strong><?php echo htmlspecialchars(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')); ?></strong><br><small style="color:var(--gray)">@<?php echo htmlspecialchars($s['username'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars(($s['class_name'] ?? '') . ' ' . ($s['section'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($s['gender'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($s['phone'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($s['parent_name'] ?? ''); ?></td>
                            <td class="actions-cell no-print">
                                <button class="btn btn-sm btn-info" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($s)); ?>)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-sm btn-secondary" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($s)); ?>)"><i class="fas fa-eye"></i> View</button>
                                <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this student?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id" value="<?php echo $s['student_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7"><div class="empty-state"><div class="empty-icon"><i class="fas fa-user-graduate"></i></div><p>No students found.</p></div></td></tr>
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
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" max="<?php echo date('Y-m-d'); ?>">
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
                            <input type="text" name="phone" pattern="[0-9]{10}" maxlength="10" title="Please enter exactly 10 digits">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email">
                        </div>
                        <div class="form-group">
                            <label>Parent Name</label>
                            <input type="text" name="parent_name">
                        </div>
                    </div>
                    <div class="form-row">
                    <div class="form-group">
                        <label>Parent Phone</label>
                        <input type="text" name="parent_phone" pattern="[0-9]{10}" maxlength="10" title="Please enter exactly 10 digits">
                    </div>
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
                            <input type="date" name="date_of_birth" id="edit_dob" max="<?php echo date('Y-m-d'); ?>">
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
                            <input type="text" name="phone" id="edit_phone" pattern="[0-9]{10}" maxlength="10" title="Please enter exactly 10 digits">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" id="edit_email">
                        </div>
                        <div class="form-group">
                            <label>Parent Name</label>
                            <input type="text" name="parent_name" id="edit_parent_name">
                        </div>
                    </div>
                    <div class="form-row">
                    <div class="form-group">
                        <label>Parent Phone</label>
                        <input type="text" name="parent_phone" id="edit_parent_phone" pattern="[0-9]{10}" maxlength="10" title="Please enter exactly 10 digits">
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content" id="studentDetailsPDF">
            <div class="modal-header no-print">
                <h2>Student Details</h2>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div class="modal-body profile-card">
                <div class="profile-header">
                    <div class="profile-avatar"><i class="fas fa-user-graduate"></i></div>
                    <h2 id="view_name">Name</h2>
                    <p id="view_username">@username</p>
                </div>
                <div class="profile-body profile-info-grid">
                    <div class="info-item"><label>Roll Number</label><span id="view_roll"></span></div>
                    <div class="info-item"><label>Class</label><span id="view_class"></span></div>
                    <div class="info-item"><label>Date of Birth</label><span id="view_dob"></span></div>
                    <div class="info-item"><label>Gender</label><span id="view_gender"></span></div>
                    <div class="info-item"><label>Phone</label><span id="view_phone"></span></div>
                    <div class="info-item"><label>Email</label><span id="view_email"></span></div>
                    <div class="info-item"><label>Parent Name</label><span id="view_parent_name"></span></div>
                    <div class="info-item"><label>Parent Phone</label><span id="view_parent_phone"></span></div>
                </div>
            </div>
            <div class="modal-footer no-print">
                <button type="button" class="btn btn-danger" onclick="downloadSinglePDF()"><i class="fas fa-file-pdf"></i> Download PDF</button>
                <button type="button" class="btn btn-success" onclick="downloadSingleCSV()"><i class="fas fa-file-csv"></i> Download CSV</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Close</button>
            </div>
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
        document.getElementById('edit_email').value = data.email || '';
        document.getElementById('edit_parent_name').value = data.parent_name || '';
        document.getElementById('edit_parent_phone').value = data.parent_phone || '';
        openModal('editModal');
    }
    
    let currentStudentData = null;

    function viewDetails(data) {
        currentStudentData = data;
        document.getElementById('view_name').innerText = data.first_name + ' ' + data.last_name;
        document.getElementById('view_username').innerText = '@' + data.username;
        document.getElementById('view_roll').innerText = data.roll_number;
        document.getElementById('view_class').innerText = data.class_name + ' ' + data.section;
        document.getElementById('view_dob').innerText = data.date_of_birth || 'N/A';
        document.getElementById('view_gender').innerText = data.gender || 'N/A';
        document.getElementById('view_phone').innerText = data.phone || 'N/A';
        document.getElementById('view_email').innerText = data.email || 'N/A';
        document.getElementById('view_parent_name').innerText = data.parent_name || 'N/A';
        document.getElementById('view_parent_phone').innerText = data.parent_phone || 'N/A';
        openModal('viewModal');
    }

    function downloadPDF() {
        var element = document.getElementById('printableTable');
        var opt = {
            margin:       0.5,
            filename:     'students_list.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
        };
        // Hide elements with 'no-print' class before generation
        const noPrintEls = element.querySelectorAll('.no-print');
        noPrintEls.forEach(el => el.style.display = 'none');
        
        html2pdf().set(opt).from(element).save().then(() => {
            // Restore visibility
            noPrintEls.forEach(el => el.style.display = '');
        });
    }

    function downloadSinglePDF() {
        var element = document.getElementById('studentDetailsPDF');
        var opt = {
            margin:       0.5,
            filename:     'student_details.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        
        const noPrintEls = element.querySelectorAll('.no-print');
        noPrintEls.forEach(el => el.style.display = 'none');
        
        html2pdf().set(opt).from(element).save().then(() => {
            noPrintEls.forEach(el => el.style.display = '');
        });
    }

    function downloadSingleCSV() {
        if (!currentStudentData) return;
        const d = currentStudentData;
        const csvContent = "data:text/csv;charset=utf-8," 
            + "Roll Number,First Name,Last Name,Username,Class,Section,DOB,Gender,Phone,Email,Parent Name,Parent Phone\n"
            + `"${d.roll_number}","${d.first_name}","${d.last_name}","${d.username}","${d.class_name}","${d.section}","${d.date_of_birth}","${d.gender}","${d.phone}","${d.email}","${d.parent_name}","${d.parent_phone}"\n`;
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "student_details.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    </script>
</body>
</html>
