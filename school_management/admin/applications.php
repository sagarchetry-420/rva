<?php
/**
 * Admin Applications Dashboard
 * Review and manage student applications - Approve, Reject, or Admit students
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/PHPMailer/PHPMailer.php';
require_once dirname(__DIR__) . '/includes/PHPMailer/SMTP.php';
require_once dirname(__DIR__) . '/includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

requireAdmin();

$uid = getUserId();
$message = null;
$message_type = null;

// Load settings
$settings = [];
$result = mysqli_query($conn, "SELECT setting_name, setting_value FROM admission_settings");
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_name']] = $row['setting_value'];
}

// Handle actions (Approve, Reject, Admit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $app_id = intval($_POST['application_id'] ?? 0);

    // Get application details
    $app = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM admission_applications WHERE admission_application_id = $app_id"));

    if (!$app) {
        $message = "❌ Application not found";
        $message_type = "error";
    } elseif ($action === 'approve') {
        // Approve application
        $remarks = trim($_POST['admin_remarks'] ?? '');

        $query = "UPDATE admission_applications
                  SET application_status = 'Approved', approved_on = NOW(), approved_by = $uid, admin_remarks = ?
                  WHERE admission_application_id = $app_id";

        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param('s', $remarks);
            if ($stmt->execute()) {
                // Create fee record
                $fee_amount = intval($settings['application_fee_amount'] ?? 500);
                $deadline = $settings['application_deadline'] ?? date('Y-m-d');

                $fee_query = "INSERT INTO fees (admission_application_id, amount, due_date, fee_type, payment_status, student_id)
                             VALUES ($app_id, $fee_amount, '$deadline', 'Admission Fee', 'Pending', NULL)";

                if (mysqli_query($conn, $fee_query)) {
                    // Send approval email
                    sendApprovalEmail($app['email'], $app['first_name'], $app_id, $fee_amount, $settings);

                    $message = "✅ Application #$app_id approved! Approval email sent to " . htmlspecialchars($app['email']);
                    $message_type = "success";
                } else {
                    $message = "✅ Application approved, but fee record creation failed";
                    $message_type = "warning";
                }
            } else {
                $message = "❌ Error updating application: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }

    } elseif ($action === 'reject') {
        // Reject application
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');

        if (empty($rejection_reason)) {
            $message = "❌ Please provide a rejection reason";
            $message_type = "error";
        } else {
            $query = "UPDATE admission_applications
                      SET application_status = 'Rejected', rejection_reason = ?
                      WHERE admission_application_id = $app_id";

            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param('s', $rejection_reason);
                if ($stmt->execute()) {
                    // Send rejection email
                    sendRejectionEmail($app['email'], $app['first_name'], $rejection_reason);

                    $message = "❌ Application #$app_id rejected. Notification sent to " . htmlspecialchars($app['email']);
                    $message_type = "success";
                } else {
                    $message = "❌ Error updating application: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }

    } elseif ($action === 'admit') {
        // Admit student (create student account)
        // First check if fee is paid
        $fee_check = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM fees WHERE admission_application_id = $app_id"));

        if (!$fee_check) {
            $message = "❌ No fee record found. Create fee record first.";
            $message_type = "error";
        } elseif ($fee_check['payment_status'] !== 'Paid') {
            $message = "❌ Admission fee not paid yet. Payment status: " . htmlspecialchars($fee_check['payment_status']);
            $message_type = "error";
        } else {
            // Fee is paid - create student account
            mysqli_query($conn, "START TRANSACTION");

            try {
                // Create user account
                $username = strtolower($app['first_name']) . '.' . strtolower($app['last_name']) . '.' . rand(100, 999);
                $temp_password = generatePassword();
                $password_md5 = md5($temp_password);

                // Check for duplicate username
                $user_check = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT user_id FROM users WHERE username = '$username'"));

                if ($user_check) {
                    $username = strtolower($app['first_name']) . '.' . strtolower($app['last_name']) . '.' . time();
                }

                $user_query = "INSERT INTO users (username, password, user_type, email, created_at)
                              VALUES ('$username', '$password_md5', 'student', '{$app['email']}', NOW())";

                if (!mysqli_query($conn, $user_query)) {
                    throw new Exception("Failed to create user account");
                }

                $user_id = mysqli_insert_id($conn);

                // Create student record
                $roll_number = generateRollNumber($app['class_id'], $app['session_id'], $conn);

                $student_query = "INSERT INTO students
                                 (user_id, first_name, last_name, email, phone, date_of_birth, gender,
                                  class_id, roll_number, admission_date, address, admission_application_id)
                                 VALUES
                                 ($user_id, '{$app['first_name']}', '{$app['last_name']}', '{$app['email']}',
                                  '{$app['phone']}', '{$app['date_of_birth']}', '{$app['gender']}',
                                  {$app['class_id']}, '$roll_number', NOW(), '{$app['address']}', $app_id)";

                if (!mysqli_query($conn, $student_query)) {
                    throw new Exception("Failed to create student record");
                }

                $student_id = mysqli_insert_id($conn);

                // Create student_academics record if using promotion system
                $session_query = "INSERT INTO student_academics
                                 (student_id, session_id, class_id, roll_number, admission_status, promotion_status)
                                 VALUES ($student_id, {$app['session_id']}, {$app['class_id']}, '$roll_number', 'Active', 'Promoted_Pending')";

                @mysqli_query($conn, $session_query); // Optional - might fail if table doesn't exist

                // Update application status
                $update_query = "UPDATE admission_applications
                                SET application_status = 'Admitted'
                                WHERE admission_application_id = $app_id";

                if (!mysqli_query($conn, $update_query)) {
                    throw new Exception("Failed to update application status");
                }

                // Update fee record with student_id
                $fee_update = "UPDATE fees SET student_id = $student_id WHERE admission_application_id = $app_id";
                mysqli_query($conn, $fee_update);

                // Send credentials email
                sendCredentialsEmail($app['email'], $app['first_name'], $app['last_name'], $username, $temp_password);

                mysqli_query($conn, "COMMIT");

                $message = "✅ Student #$student_id created successfully!<br>Roll Number: $roll_number<br>Credentials email sent to " . htmlspecialchars($app['email']);
                $message_type = "success";

            } catch (Exception $e) {
                mysqli_query($conn, "ROLLBACK");
                $message = "❌ Error: " . htmlspecialchars($e->getMessage());
                $message_type = "error";
            }
        }
    }
}

// Get statistics
$total_apps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admission_applications"))['cnt'];
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admission_applications WHERE application_status = 'Pending'"))['cnt'];
$approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admission_applications WHERE application_status = 'Approved'"))['cnt'];
$rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admission_applications WHERE application_status = 'Rejected'"))['cnt'];
$admitted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admission_applications WHERE application_status = 'Admitted'"))['cnt'];

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$class_filter = intval($_GET['class'] ?? 0);
$search = trim($_GET['search'] ?? '');

// Build query with filters
$where = "1=1";
if ($status_filter) $where .= " AND application_status = '$status_filter'";
if ($class_filter) $where .= " AND class_id = $class_filter";
if ($search) $where .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";

$applications = mysqli_query($conn, "
    SELECT aa.*, cl.class_name, ac.session_name
    FROM admission_applications aa
    JOIN classes cl ON aa.class_id = cl.class_id
    JOIN academic_sessions ac ON aa.session_id = ac.session_id
    WHERE $where
    ORDER BY aa.applied_on DESC
");

// Helper function to generate password
function generatePassword() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^';
    $password = '';
    for ($i = 0; $i < 10; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// Helper function to generate roll number
function generateRollNumber($class_id, $session_id, $conn) {
    $count = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as cnt FROM students WHERE class_id = $class_id"))['cnt'] + 1;
    return "C$class_id-" . date('Y') . "-" . str_pad($count, 3, "0", STR_PAD_LEFT);
}

// Email functions
function sendApprovalEmail($email, $name, $app_id, $fee, $settings) {
    try {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rvasupport@gmail.com';
        $mail->Password = 'pyza hpyp dlhz puzz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('rvasupport@gmail.com', APP_NAME);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Admission Application Approved - " . APP_NAME;

        $school_email = $settings['school_email_for_contact'] ?? 'admissions@school.com';

        $mail->Body = "
            <html><body style='font-family: Arial, sans-serif;'>
            <h2>🎉 Congratulations! Your Application is Approved</h2>
            <p>Dear <strong>$name</strong>,</p>
            <p>We are pleased to inform you that your application for admission to " . APP_NAME . " has been <strong>APPROVED</strong>.</p>

            <div style='background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0;'>
            <p><strong>Application ID:</strong> #$app_id</p>
            <p><strong>Admission Fee:</strong> Rs. $fee</p>
            <p><strong>Due by:</strong> " . date('d M Y', strtotime($settings['application_deadline'] ?? date('Y-m-d'))) . "</p>
            </div>

            <h3>Next Steps:</h3>
            <ol>
            <li>Visit our office with the fee amount</li>
            <li>Submit the fee to complete your admission</li>
            <li>You will receive login credentials via email</li>
            <li>Use your credentials to access the student portal</li>
            </ol>

            <p>If you have any questions, contact us at <strong>$school_email</strong></p>
            <p style='color: #999; font-size: 0.9em;'>This is an automated message. Do not reply to this email.</p>
            </body></html>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
    }
}

function sendRejectionEmail($email, $name, $reason) {
    try {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rvasupport@gmail.com';
        $mail->Password = 'pyza hpyp dlhz puzz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('rvasupport@gmail.com', APP_NAME);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Application Status - " . APP_NAME;

        $mail->Body = "
            <html><body style='font-family: Arial, sans-serif;'>
            <h2>Your Application Status</h2>
            <p>Dear <strong>$name</strong>,</p>
            <p>Thank you for your interest in " . APP_NAME . ". After careful review, your application has not been approved at this time.</p>

            <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>
            <p><strong>Reason:</strong></p>
            <p>" . htmlspecialchars($reason) . "</p>
            </div>

            <p>You are welcome to reapply in the next admission cycle. If you have any questions, please contact our admissions office.</p>
            <p style='color: #999; font-size: 0.9em;'>This is an automated message.</p>
            </body></html>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
    }
}

function sendCredentialsEmail($email, $first_name, $last_name, $username, $password) {
    try {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rvasupport@gmail.com';
        $mail->Password = 'pyza hpyp dlhz puzz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('rvasupport@gmail.com', APP_NAME);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Welcome to " . APP_NAME . " - Your Login Credentials";

        $mail->Body = "
            <html><body style='font-family: Arial, sans-serif;'>
            <h2>Welcome to " . APP_NAME . "! 🎓</h2>
            <p>Dear <strong>$first_name $last_name</strong>,</p>
            <p>Congratulations on your admission! Here are your login credentials for the student portal.</p>

            <div style='background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0; font-family: monospace;'>
            <p><strong>Username:</strong> <span style='color: #1976d2;'>$username</span></p>
            <p><strong>Password:</strong> <span style='color: #1976d2;'>$password</span></p>
            <p style='font-size: 0.9em; color: #666;'>🔒 Keep these credentials safe and change your password after first login.</p>
            </div>

            <h3>How to Login:</h3>
            <ol>
            <li>Go to the student login page</li>
            <li>Enter your username and password</li>
            <li>You can view attendance, fees, results, and more</li>
            </ol>

            <p>Welcome aboard! If you have any issues logging in, contact the admissions office.</p>
            </body></html>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Applications - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: white; padding: 15px; border-radius: 6px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-value { font-size: 2em; font-weight: bold; color: var(--primary); }
        .stat-label { color: #666; font-size: 0.9em; margin-top: 5px; }
        .applications-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 6px; overflow: hidden; }
        .applications-table th, .applications-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .applications-table th { background: #f9f6f0; font-weight: 600; }
        .applications-table tr:hover { background: #fafafa; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.85em; font-weight: 600; }
        .status-pending { background: #ffeaa7; color: #856404; }
        .status-approved { background: #a1d3ff; color: #004085; }
        .status-rejected { background: #ff7675; color: #721c24; }
        .status-admitted { background: #81c784; color: #1b5e20; }
        .action-buttons { display: flex; gap: 5px; }
        .btn-small { padding: 6px 12px; font-size: 0.85em; border: none; border-radius: 4px; cursor: pointer; }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-admit { background: #007bff; color: white; }
        .btn-view { background: #6c757d; color: white; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); }
        .modal-content { background: white; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 600px; }
        .modal-header { font-size: 1.5em; font-weight: bold; margin-bottom: 20px; }
        .modal-buttons { display: flex; gap: 10px; margin-top: 20px; }
        .btn-modal-submit { flex: 1; background: var(--primary); color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-modal-cancel { flex: 1; background: #ccc; color: #333; padding: 10px; border: none; border-radius: 4px; cursor: pointer; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #dc3545; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div>
                    <h1><i class="fa-solid fa-pen-to-square"></i> Student Applications</h1>
                    <p>Review and manage admission applications</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_apps; ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #ffc107;"><?php echo $pending; ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #17a2b8;"><?php echo $approved; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #dc3545;"><?php echo $rejected; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #28a745;"><?php echo $admitted; ?></div>
                    <div class="stat-label">Admitted</div>
                </div>
            </div>

            <!-- Filters -->
            <div style="background: white; padding: 20px; border-radius: 6px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: flex-end;">
                    <div>
                        <label style="font-weight: 600; margin-bottom: 5px; display: block;">Status</label>
                        <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Status</option>
                            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo $status_filter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="Admitted" <?php echo $status_filter === 'Admitted' ? 'selected' : ''; ?>>Admitted</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight: 600; margin-bottom: 5px; display: block;">Class</label>
                        <select name="class" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Classes</option>
                            <?php
                            $classes = mysqli_query($conn, "SELECT DISTINCT class_id, class_name FROM admission_applications JOIN classes USING(class_id) ORDER BY class_id");
                            while ($cls = mysqli_fetch_assoc($classes)):
                            ?>
                                <option value="<?php echo $cls['class_id']; ?>" <?php echo $class_filter === $cls['class_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cls['class_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight: 600; margin-bottom: 5px; display: block;">Search</label>
                        <input type="text" name="search" placeholder="Name or Email" value="<?php echo htmlspecialchars($search); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <button type="submit" style="background: var(--primary); color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fa-solid fa-search"></i> Filter
                    </button>
                </form>
            </div>

            <!-- Applications Table -->
            <table class="applications-table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Class</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($applications) === 0):
                    ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: #999;">
                                No applications found
                            </td>
                        </tr>
                    <?php
                    else:
                        while ($app = mysqli_fetch_assoc($applications)):
                    ?>
                        <tr>
                            <td>#<?php echo $app['admission_application_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></strong><br>
                                <small style="color: #999;"><?php echo htmlspecialchars($app['phone']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($app['email']); ?></td>
                            <td><?php echo htmlspecialchars($app['class_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($app['applied_on'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($app['application_status']); ?>">
                                    <?php echo $app['application_status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-small btn-view" onclick="viewApplication(<?php echo $app['admission_application_id']; ?>)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <?php if ($app['application_status'] === 'Pending'): ?>
                                        <button class="btn-small btn-approve" onclick="approveModal(<?php echo $app['admission_application_id']; ?>)">
                                            ✓
                                        </button>
                                        <button class="btn-small btn-reject" onclick="rejectModal(<?php echo $app['admission_application_id']; ?>)">
                                            ✕
                                        </button>
                                    <?php elseif ($app['application_status'] === 'Approved'): ?>
                                        <button class="btn-small btn-admit" onclick="admitModal(<?php echo $app['admission_application_id']; ?>)">
                                            Admit
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Approve Application</div>
            <form method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" id="appIdApprove" name="application_id">
                <div style="margin-bottom: 15px;">
                    <label style="font-weight: 600;">Admin Remarks (Optional)</label>
                    <textarea name="admin_remarks" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-modal-submit">Approve</button>
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('approveModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Reject Application</div>
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" id="appIdReject" name="application_id">
                <div style="margin-bottom: 15px;">
                    <label style="font-weight: 600;">Rejection Reason *</label>
                    <textarea name="rejection_reason" rows="4" required placeholder="Please provide a reason for rejection..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-modal-submit">Reject</button>
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('rejectModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admit Modal -->
    <div id="admitModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Admit Student</div>
            <p>Please ensure the admission fee has been paid before admitting the student.</p>
            <form method="POST">
                <input type="hidden" name="action" value="admit">
                <input type="hidden" id="appIdAdmit" name="application_id">
                <div class="modal-buttons">
                    <button type="submit" class="btn-modal-submit">Confirm & Create Student</button>
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('admitModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function approveModal(appId) {
            document.getElementById('appIdApprove').value = appId;
            document.getElementById('approveModal').style.display = 'block';
        }
        function rejectModal(appId) {
            document.getElementById('appIdReject').value = appId;
            document.getElementById('rejectModal').style.display = 'block';
        }
        function admitModal(appId) {
            document.getElementById('appIdAdmit').value = appId;
            document.getElementById('admitModal').style.display = 'block';
        }
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        window.onclick = function(event) {
            ['approveModal', 'rejectModal', 'admitModal'].forEach(id => {
                let modal = document.getElementById(id);
                if (event.target === modal) modal.style.display = 'none';
            });
        }
        function viewApplication(appId) {
            alert('View details: ' + appId + ' - Full application viewer coming soon!');
        }
    </script>
</body>
</html>
