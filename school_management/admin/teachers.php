<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once dirname(__DIR__) . '/includes/PHPMailer/Exception.php';
require_once dirname(__DIR__) . '/includes/PHPMailer/PHPMailer.php';
require_once dirname(__DIR__) . '/includes/PHPMailer/SMTP.php';

function sendCredentialsEmail($toEmail, $firstName, $username, $plainPassword, $phone = '') {
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
        $mail->Subject = 'Your Rose Valley Academy Account Credentials';
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:500px;margin:0 auto;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
                <div style='background:#1e3a5f;color:#fff;padding:20px;text-align:center;'>
                    <h2 style='margin:0;'>Rose Valley Academy</h2>
                    <p style='margin:5px 0 0;font-size:13px;opacity:0.8;'>Account Credentials</p>
                </div>
                <div style='padding:24px;'>
                    <p style='margin:0 0 16px;'>Hello <strong>$firstName</strong>,</p>
                    <p style='margin:0 0 16px;color:#6b7280;'>Your account has been created successfully. Below are your login credentials:</p>
                    <table style='width:100%;border-collapse:collapse;margin-bottom:16px;'>
                        <tr><td style='padding:10px 12px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:600;width:120px;'>Username</td><td style='padding:10px 12px;border:1px solid #e5e7eb;'>$username</td></tr>
                        <tr><td style='padding:10px 12px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:600;'>Password</td><td style='padding:10px 12px;border:1px solid #e5e7eb;'>$plainPassword</td></tr>
                        <tr><td style='padding:10px 12px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:600;'>Email</td><td style='padding:10px 12px;border:1px solid #e5e7eb;'>$toEmail</td></tr>
                        " . (!empty($phone) ? "<tr><td style='padding:10px 12px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:600;'>Phone</td><td style='padding:10px 12px;border:1px solid #e5e7eb;'>$phone</td></tr>" : "") . "
                    </table>
                    <p style='margin:0 0 8px;color:#6b7280;font-size:13px;'>Please log in and change your password at your earliest convenience.</p>
                </div>
                <div style='background:#f9fafb;padding:12px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;'>
                    &copy; " . date('Y') . " Rose Valley Academy. All rights reserved.
                </div>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $first_name = sanitize($conn, $_POST['first_name']);
        $last_name = sanitize($conn, $_POST['last_name']);
        $gender = sanitize($conn, $_POST['gender']);
        $phone = sanitize($conn, $_POST['phone']);
        $email = sanitize($conn, $_POST['email']);
        $qualification = sanitize($conn, $_POST['qualification']);
        $specialization = sanitize($conn, $_POST['subject_specialization']);
        $dob = sanitize($conn, $_POST['date_of_birth']);
        
        // Check if email already exists
        if (!empty($email)) {
            $email_check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
            if (mysqli_num_rows($email_check) > 0) {
                setFlashMessage('error', 'This email address is already registered. Please use a different email.');
                header('Location: teachers.php'); exit();
            }
        }
        
        $username = strtolower($first_name . '.' . $last_name . rand(100, 999));
        $plain_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$'), 0, 10);
        $password = md5($plain_password);
        
        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username'");
        if (mysqli_num_rows($check) > 0) {
            $username = $username . rand(1, 99);
        }

        $q = "INSERT INTO users (username, password, user_type, email) VALUES ('$username', '$password', 'teacher', '$email')";
        if (mysqli_query($conn, $q)) {
            $uid = mysqli_insert_id($conn);
            $q2 = "INSERT INTO teachers (user_id, first_name, last_name, date_of_birth, gender, phone, email, qualification, subject_specialization, joining_date) 
                   VALUES ($uid, '$first_name', '$last_name', '$dob', '$gender', '$phone', '$email', '$qualification', '$specialization', CURDATE())";
            mysqli_query($conn, $q2);
            
            if (!empty($email)) {
                sendCredentialsEmail($email, $first_name, $username, $plain_password, $phone);
            }
            
            setFlashMessage('success', 'Teacher registered successfully!');
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
        
        // Get current data to check for changes and get user_id
        $current_res = mysqli_query($conn, "SELECT * FROM teachers WHERE teacher_id=$tid");
        $current = mysqli_fetch_assoc($current_res);
        $uid = $current['user_id'];
        
        // Check for duplicate email
        if (!empty($email) && $email !== $current['email']) {
            $email_check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' AND user_id != $uid");
            if (mysqli_num_rows($email_check) > 0) {
                setFlashMessage('error', 'This email address is already registered by another user.');
                header('Location: teachers.php'); exit();
            }
        }
        
        // Check if anything actually changed
        if ($first_name === $current['first_name'] && $last_name === $current['last_name'] && 
            $gender === $current['gender'] && $phone === $current['phone'] && 
            $email === $current['email'] && $qualification === $current['qualification'] && 
            $specialization === $current['subject_specialization'] && $dob === $current['date_of_birth']) {
            setFlashMessage('info', 'No changes were made.');
            header('Location: teachers.php'); exit();
        }
        
        mysqli_query($conn, "UPDATE teachers SET first_name='$first_name', last_name='$last_name', date_of_birth='$dob', gender='$gender', phone='$phone', email='$email', qualification='$qualification', subject_specialization='$specialization' WHERE teacher_id=$tid");
        mysqli_query($conn, "UPDATE users SET email='$email' WHERE user_id=$uid");
        
        setFlashMessage('success', 'Teacher details updated successfully!');
        header('Location: teachers.php'); exit();
    }
    
    if ($action === 'delete') {
        $tid = intval($_POST['teacher_id']);
        $r = mysqli_query($conn, "SELECT user_id FROM teachers WHERE teacher_id=$tid");
        if ($row = mysqli_fetch_assoc($r)) {
            mysqli_query($conn, "DELETE FROM teachers WHERE teacher_id=$tid");
            mysqli_query($conn, "DELETE FROM users WHERE user_id=" . $row['user_id']);
        }
        setFlashMessage('success', 'Teacher record has been deleted successfully.');
        header('Location: teachers.php'); exit();
    }
    
    if ($action === 'export_csv') {
        $q = "SELECT t.first_name, t.last_name, t.date_of_birth, t.gender, t.phone, t.email, t.qualification, t.subject_specialization, u.username 
              FROM teachers t 
              LEFT JOIN users u ON t.user_id = u.user_id ORDER BY t.first_name";
        $res = mysqli_query($conn, $q);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="teachers_export.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('First Name', 'Last Name', 'Username', 'DOB', 'Gender', 'Phone', 'Email', 'Qualification', 'Specialization'));
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
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
                <div><h1><i class="fas fa-chalkboard-teacher"></i> Teachers Management</h1><p>Manage all teacher records</p></div>
                <div style="display:flex; gap:10px;">
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="export_csv">
                        <button type="submit" class="btn btn-success"><i class="fas fa-file-csv"></i> Download CSV</button>
                        <button type="button" class="btn btn-danger" onclick="downloadPDF()"><i class="fas fa-file-pdf"></i> Download PDF</button>
                    </form>
                    <button class="btn btn-primary" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Teacher</button>
                </div>
            </div>
            
            <div class="table-container" id="printableTable">
                <div class="table-header">
                    <h2>All Teachers (<?php echo mysqli_num_rows($teachers_result); ?>)</h2>
                    <div class="search-box no-print">
                        <input type="text" id="searchInput" placeholder="Search teachers..." onkeyup="searchTable('searchInput','dataTable')">
                    </div>
                </div>
                <table class="data-table" id="dataTable">
                    <thead>
                        <tr><th>Name</th><th>Username</th><th>Specialization</th><th>Qualification</th><th>Phone</th><th>Email</th><th class="no-print">Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($teachers_result) > 0): ?>
                        <?php while ($t = mysqli_fetch_assoc($teachers_result)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? '')); ?></strong></td>
                            <td>@<?php echo htmlspecialchars($t['username'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($t['subject_specialization'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($t['qualification'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($t['phone'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($t['email'] ?? ''); ?></td>
                            <td class="actions-cell no-print">
                                <button class="btn btn-sm btn-info" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($t)); ?>)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-sm btn-secondary" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($t)); ?>)"><i class="fas fa-eye"></i> View</button>
                                <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this teacher?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="teacher_id" value="<?php echo $t['teacher_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7"><div class="empty-state"><div class="empty-icon"><i class="fas fa-chalkboard-teacher"></i></div><p>No teachers found.</p></div></td></tr>
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
                        <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"></div>
                        <div class="form-group"><label>Gender *</label>
                            <select name="gender" required><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="phone" pattern="[0-9]{10}" maxlength="10" title="Please enter exactly 10 digits"></div>
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
            <form method="POST" id="edit_teacher_form">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="teacher_id" id="edit_teacher_id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group"><label>First Name *</label><input type="text" name="first_name" id="edit_first_name" required></div>
                        <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" id="edit_last_name" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" id="edit_dob" max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"></div>
                        <div class="form-group"><label>Gender</label>
                            <select name="gender" id="edit_gender"><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email"></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone" pattern="[0-9]{10}" maxlength="10" title="Please enter exactly 10 digits"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Qualification</label><input type="text" name="qualification" id="edit_qualification"></div>
                        <div class="form-group"><label>Specialization</label><input type="text" name="subject_specialization" id="edit_specialization"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit_submit_btn" disabled>Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content" id="teacherDetailsPDF">
            <div class="modal-header no-print">
                <h2>Teacher Details</h2>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div class="modal-body profile-card">
                <div class="profile-header">
                    <div class="profile-avatar"><i class="fas fa-chalkboard-teacher"></i></div>
                    <h2 id="view_name">Name</h2>
                    <p id="view_username">@username</p>
                </div>
                <div class="profile-body profile-info-grid">
                    <div class="info-item"><label>Specialization</label><span id="view_specialization"></span></div>
                    <div class="info-item"><label>Qualification</label><span id="view_qualification"></span></div>
                    <div class="info-item"><label>Date of Birth</label><span id="view_dob"></span></div>
                    <div class="info-item"><label>Gender</label><span id="view_gender"></span></div>
                    <div class="info-item"><label>Phone</label><span id="view_phone"></span></div>
                    <div class="info-item"><label>Email</label><span id="view_email"></span></div>
                </div>
            </div>
            <div class="modal-footer no-print">
                <button type="button" class="btn btn-danger" onclick="downloadSinglePDF()"><i class="fas fa-file-pdf"></i> Download PDF</button>
                <!-- Simple client-side CSV download hack for single teacher -->
                <button type="button" class="btn btn-success" onclick="downloadSingleCSV()"><i class="fas fa-file-csv"></i> Download CSV</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>
    let currentTeacherData = null;

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
        
        // Reset button state
        const submitBtn = document.getElementById('edit_submit_btn');
        submitBtn.disabled = true;
        
        // Store initial form data
        const form = document.getElementById('edit_teacher_form');
        form.dataset.initialData = JSON.stringify(Object.fromEntries(new FormData(form)));
        
        openModal('editModal');
    }

    // Add change listener to edit form
    document.getElementById('edit_teacher_form').addEventListener('input', function() {
        const initialData = JSON.parse(this.dataset.initialData);
        const currentData = Object.fromEntries(new FormData(this));
        const hasChanged = JSON.stringify(initialData) !== JSON.stringify(currentData);
        document.getElementById('edit_submit_btn').disabled = !hasChanged;
    });

    document.getElementById('edit_teacher_form').addEventListener('change', function() {
        const initialData = JSON.parse(this.dataset.initialData);
        const currentData = Object.fromEntries(new FormData(this));
        const hasChanged = JSON.stringify(initialData) !== JSON.stringify(currentData);
        document.getElementById('edit_submit_btn').disabled = !hasChanged;
    });
    
    function viewDetails(data) {
        currentTeacherData = data;
        document.getElementById('view_name').innerText = data.first_name + ' ' + data.last_name;
        document.getElementById('view_username').innerText = '@' + data.username;
        document.getElementById('view_specialization').innerText = data.subject_specialization || 'N/A';
        document.getElementById('view_qualification').innerText = data.qualification || 'N/A';
        document.getElementById('view_dob').innerText = data.date_of_birth || 'N/A';
        document.getElementById('view_gender').innerText = data.gender || 'N/A';
        document.getElementById('view_phone').innerText = data.phone || 'N/A';
        document.getElementById('view_email').innerText = data.email || 'N/A';
        openModal('viewModal');
    }

    function downloadPDF() {
        var element = document.getElementById('printableTable');
        var opt = {
            margin:       0.5,
            filename:     'teachers_list.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
        };
        const noPrintEls = element.querySelectorAll('.no-print');
        noPrintEls.forEach(el => el.style.display = 'none');
        
        html2pdf().set(opt).from(element).save().then(() => {
            noPrintEls.forEach(el => el.style.display = '');
        });
    }

    function downloadSinglePDF() {
        var element = document.getElementById('teacherDetailsPDF');
        var opt = {
            margin:       0.5,
            filename:     'teacher_details.pdf',
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
        if (!currentTeacherData) return;
        const d = currentTeacherData;
        const csvContent = "data:text/csv;charset=utf-8," 
            + "First Name,Last Name,Username,Specialization,Qualification,DOB,Gender,Phone,Email\n"
            + `"${d.first_name}","${d.last_name}","${d.username}","${d.subject_specialization}","${d.qualification}","${d.date_of_birth}","${d.gender}","${d.phone}","${d.email}"\n`;
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "teacher_details.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    </script>
</body>
</html>
