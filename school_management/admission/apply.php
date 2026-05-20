<?php
/**
 * Student Admission Application Form
 * Public-facing form for students to apply for admission
 */

require_once dirname(__DIR__) . '/config/database.php';

// Load admission settings
$settings = [];
$result = mysqli_query($conn, "SELECT setting_name, setting_value FROM admission_settings");
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_name']] = $row['setting_value'];
}

$form_open = isset($settings['admission_form_open']) && $settings['admission_form_open'] === 'yes';
$form_deadline = $settings['application_deadline'] ?? '';
$required_docs = isset($settings['required_documents']) ? explode(',', $settings['required_documents']) : [];
$instructions = $settings['instructions_for_applicants'] ?? '';
$application_fee = $settings['application_fee_amount'] ?? '0';

// Check deadline
$form_expired = false;
if ($form_deadline && strtotime($form_deadline) < time()) {
    $form_open = false;
    $form_expired = true;
}

$message = null;
$message_type = null;
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $form_open && !$form_expired) {
    // Get form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $parent_email = trim($_POST['parent_email'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $class_id = intval($_POST['class_id'] ?? 0);
    $session_id = intval($_POST['session_id'] ?? 0);
    $previous_school = trim($_POST['previous_school'] ?? '');

    // Validate
    $errors = [];

    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($dob)) $errors[] = "Date of birth is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if ($class_id <= 0) $errors[] = "Please select a class";
    if ($session_id <= 0) $errors[] = "Please select a session";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";

    // Check for duplicate application
    if (empty($errors)) {
        $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email FROM admission_applications WHERE email = '$email' AND application_status != 'Rejected'"));
        if ($check) {
            $errors[] = "An application with this email already exists. Cannot reapply with same email.";
        }
    }

    // Handle file uploads
    $uploaded_files = [];
    $upload_dir = dirname(__DIR__) . '/uploads/admission_documents/';

    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (empty($errors)) {
        // Process file uploads
        $file_fields = ['birth_certificate', 'transfer_certificate', 'address_proof'];

        foreach ($file_fields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$field];
                $file_name = basename($file['name']);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                // Validate file type
                $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($file_ext, $allowed_ext)) {
                    $errors[] = "Invalid file type for " . str_replace('_', ' ', $field) . ". Only PDF, JPG, PNG allowed.";
                    continue;
                }

                // Validate file size (max 5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    $errors[] = "File size exceeds 5MB limit for " . str_replace('_', ' ', $field);
                    continue;
                }

                // Generate unique filename
                $unique_name = time() . '_' . $field . '.' . $file_ext;
                $file_path = $upload_dir . $unique_name;

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $uploaded_files[$field] = 'uploads/admission_documents/' . $unique_name;
                } else {
                    $errors[] = "Failed to upload " . str_replace('_', ' ', $field);
                }
            }
        }
    }

    // Optional file: Previous mark sheet (for upper classes)
    if (isset($_FILES['mark_sheet']) && $_FILES['mark_sheet']['error'] === UPLOAD_ERR_OK && empty($errors)) {
        $file = $_FILES['mark_sheet'];
        $file_ext = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));

        if (in_array($file_ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $unique_name = time() . '_mark_sheet.' . $file_ext;
            $file_path = $upload_dir . $unique_name;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $uploaded_files['mark_sheet'] = 'uploads/admission_documents/' . $unique_name;
            }
        }
    }

    // Insert into database
    if (empty($errors)) {
        $documents_json = json_encode($uploaded_files);
        $session_id_safe = $session_id;
        $class_id_safe = $class_id;

        $query = "INSERT INTO admission_applications
                  (first_name, last_name, email, phone, date_of_birth, gender,
                   father_name, mother_name, parent_email, parent_phone,
                   address, city, class_id, session_id, previous_school,
                   documents_submitted, application_status)
                  VALUES
                  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param(
                'sssssssssssssiss',
                $first_name, $last_name, $email, $phone, $dob, $gender,
                $father_name, $mother_name, $parent_email, $parent_phone,
                $address, $city, $class_id_safe, $session_id_safe, $previous_school,
                $documents_json
            );

            if ($stmt->execute()) {
                $application_id = $stmt->insert_id;
                $stmt->close();

                // Send confirmation email
                sendApplicationConfirmationEmail($email, $first_name, $last_name, $application_id);

                $message = "✅ Application submitted successfully!<br>Confirmation email has been sent to <strong>$email</strong>.<br><br>Application ID: <strong>$application_id</strong><br>We will review your application and contact you within 3-5 business days.";
                $message_type = "success";
                $success = true;
            } else {
                $errors[] = "Failed to submit application: " . $stmt->error;
                $stmt->close();
            }
        }
    }

    if (!empty($errors)) {
        $message = "❌ " . implode("<br>❌ ", $errors);
        $message_type = "error";
    }
}

// Function to send confirmation email
function sendApplicationConfirmationEmail($email, $first_name, $last_name, $app_id) {
    global $conn, $settings;

    require_once dirname(__DIR__) . '/includes/PHPMailer/PHPMailer.php';
    require_once dirname(__DIR__) . '/includes/PHPMailer/SMTP.php';
    require_once dirname(__DIR__) . '/includes/PHPMailer/Exception.php';

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rvasupport@gmail.com';
        $mail->Password = 'pyza hpyp dlhz puzz';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('rvasupport@gmail.com', APP_NAME);
        $mail->addReplyTo($settings['school_email_for_contact'] ?? 'noreply@school.com', 'Admissions');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Application Confirmation - " . APP_NAME;

        $school_name = APP_NAME;
        $school_email = $settings['school_email_for_contact'] ?? 'admissions@school.com';

        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>
                    <h2 style='color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px;'>
                        Application Received - " . htmlspecialchars($school_name) . "
                    </h2>

                    <p>Dear <strong>" . htmlspecialchars($first_name . ' ' . $last_name) . "</strong>,</p>

                    <p>Thank you for submitting your admission application to " . htmlspecialchars($school_name) . ".</p>

                    <div style='background: #ecf0f1; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p><strong>Application Details:</strong></p>
                        <p style='margin: 5px 0;'>
                            <strong>Application ID:</strong> <span style='color: #e74c3c;'>#" . htmlspecialchars($app_id) . "</span>
                        </p>
                        <p style='margin: 5px 0;'>
                            <strong>Submitted on:</strong> " . date('d M Y, H:i A') . "
                        </p>
                        <p style='margin: 5px 0;'>
                            <strong>Email:</strong> " . htmlspecialchars($email) . "
                        </p>
                    </div>

                    <h3 style='color: #2c3e50;'>Next Steps:</h3>
                    <ol>
                        <li>We will review your application within 3-5 business days</li>
                        <li>You will receive an email notification about the status of your application</li>
                        <li>If approved, you will be informed about the admission fee and payment process</li>
                        <li>Once fee is paid, admission will be confirmed</li>
                    </ol>

                    <p style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;'>
                        If you have any questions, please contact us at
                        <strong>" . htmlspecialchars($school_email) . "</strong>
                    </p>

                    <p style='color: #7f8c8d; font-size: 0.9em;'>
                        This is an automated message. Please do not reply to this email.
                    </p>
                </div>
            </body>
            </html>
        ";

        $mail->send();
    } catch (Exception $e) {
        // Log email error but don't fail the application
        error_log("Email error: " . $e->getMessage());
    }
}

// Get list of classes and sessions for dropdown
$classes = mysqli_query($conn, "SELECT class_id, class_name FROM classes ORDER BY class_id");
$sessions = mysqli_query($conn, "SELECT session_id, session_name FROM academic_sessions WHERE is_current = 1 OR session_id = (SELECT MAX(session_id) FROM academic_sessions) ORDER BY session_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Admission - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-header { background: white; padding: 30px; border-radius: 8px 8px 0 0; text-align: center; border-bottom: 4px solid #667eea; }
        .form-header h1 { color: #333; font-size: 2em; margin-bottom: 10px; }
        .form-header p { color: #666; font-size: 0.95em; }
        .form-wrapper { background: white; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .closed-message { background: white; padding: 40px; border-radius: 8px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .closed-message h2 { color: #e74c3c; margin-bottom: 15px; }
        .closed-message p { color: #666; margin: 10px 0; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid; }
        .alert-success { background: #d4edda; color: #155724; border-color: #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1em; font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-row.full { grid-template-columns: 1fr; }
        .required { color: #e74c3c; }
        .file-upload { border: 2px dashed #ddd; padding: 20px; border-radius: 6px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .file-upload:hover { border-color: #667eea; background: #f8f9ff; }
        .file-upload input { display: none; }
        .file-upload-label { cursor: pointer; }
        .instruction-box { background: #f8f9ff; border-left: 4px solid #667eea; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .instruction-box h3 { color: #667eea; margin-bottom: 10px; font-size: 1em; }
        .instruction-box ul { margin-left: 20px; color: #555; }
        .instruction-box li { margin: 5px 0; }
        .submit-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 40px; border: none; border-radius: 6px; font-size: 1.1em; font-weight: 600; cursor: pointer; width: 100%; transition: transform 0.2s; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); }
        .success-icon { font-size: 3em; color: #28a745; margin-bottom: 20px; }
        .deadline-info { background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 4px; margin-bottom: 20px; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$form_open && !$success): ?>
            <!-- Form Closed Message -->
            <div class="closed-message">
                <h2>
                    <i class="fa-solid fa-door-closed" style="color: #e74c3c;"></i> Admissions Currently Closed
                </h2>
                <p style="font-size: 1.1em; margin-top: 20px;">
                    <?php if ($form_expired): ?>
                        The admission application deadline has passed.
                    <?php else: ?>
                        Admissions are currently closed. Please check back later.
                    <?php endif; ?>
                </p>
                <?php if ($form_deadline): ?>
                    <p style="margin-top: 15px; color: #555;">
                        <strong>Application Deadline:</strong> <?php echo date('d M Y', strtotime($form_deadline)); ?>
                    </p>
                <?php endif; ?>
                <p style="margin-top: 20px;">
                    <a href="<?php echo BASE_URL; ?>" style="color: #667eea; text-decoration: none; font-weight: 600;">
                        ← Back to Home
                    </a>
                </p>
            </div>
        <?php elseif ($success): ?>
            <!-- Success Message -->
            <div style="background: white; padding: 40px; border-radius: 8px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div class="success-icon">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <h1 style="color: #28a745; margin-bottom: 15px;">Application Submitted Successfully!</h1>
                <div class="alert alert-success" style="text-align: left;">
                    <?php echo $message; ?>
                </div>
                <p style="color: #666; margin-top: 20px;">
                    You can close this window or <a href="<?php echo BASE_URL; ?>" style="color: #667eea; text-decoration: none; font-weight: 600;">return to home</a>.
                </p>
            </div>
        <?php else: ?>
            <!-- Application Form -->
            <div class="form-header">
                <h1><i class="fa-solid fa-graduation-cap"></i> Apply for Admission</h1>
                <p>Join <?php echo APP_NAME; ?> - Admission Application Form</p>
            </div>

            <div class="form-wrapper">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($form_deadline): ?>
                    <div class="deadline-info">
                        <i class="fa-solid fa-calendar-xmark"></i>
                        <strong>Application Deadline:</strong> <?php echo date('d M Y', strtotime($form_deadline)); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($instructions)): ?>
                    <div class="instruction-box">
                        <h3><i class="fa-solid fa-info-circle"></i> Instructions</h3>
                        <p><?php echo htmlspecialchars($instructions); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Personal Information Section -->
                    <h3 style="color: #333; margin: 25px 0 15px 0; font-size: 1.1em; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                        <i class="fa-solid fa-user"></i> Personal Information
                    </h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name <span class="required">*</span></label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name <span class="required">*</span></label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number <span class="required">*</span></label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth <span class="required">*</span></label>
                            <input type="date" name="date_of_birth" value="<?php echo $_POST['date_of_birth'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Gender <span class="required">*</span></label>
                            <select name="gender" required>
                                <option value="">-- Select Gender --</option>
                                <option value="Male" <?php echo ($_POST['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($_POST['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($_POST['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <h3 style="color: #333; margin: 25px 0 15px 0; font-size: 1.1em; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                        <i class="fa-solid fa-map-pin"></i> Address
                    </h3>

                    <div class="form-group form-row full">
                        <label>Address <span class="required">*</span></label>
                        <textarea name="address" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Previous School (if any)</label>
                            <input type="text" name="previous_school" value="<?php echo htmlspecialchars($_POST['previous_school'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Parent Information -->
                    <h3 style="color: #333; margin: 25px 0 15px 0; font-size: 1.1em; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                        <i class="fa-solid fa-users"></i> Parent/Guardian Information
                    </h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" name="father_name" value="<?php echo htmlspecialchars($_POST['father_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Mother's Name</label>
                            <input type="text" name="mother_name" value="<?php echo htmlspecialchars($_POST['mother_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Parent/Guardian Email</label>
                            <input type="email" name="parent_email" value="<?php echo htmlspecialchars($_POST['parent_email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Parent/Guardian Phone</label>
                            <input type="tel" name="parent_phone" value="<?php echo htmlspecialchars($_POST['parent_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Class & Session Selection -->
                    <h3 style="color: #333; margin: 25px 0 15px 0; font-size: 1.1em; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                        <i class="fa-solid fa-chalkboard"></i> Class Selection
                    </h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Select Class <span class="required">*</span></label>
                            <select name="class_id" required>
                                <option value="">-- Select Class --</option>
                                <?php
                                mysqli_data_seek($classes, 0);
                                while ($cls = mysqli_fetch_assoc($classes)):
                                ?>
                                    <option value="<?php echo $cls['class_id']; ?>" <?php echo ($_POST['class_id'] ?? '') == $cls['class_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cls['class_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Academic Session <span class="required">*</span></label>
                            <select name="session_id" required>
                                <option value="">-- Select Session --</option>
                                <?php
                                mysqli_data_seek($sessions, 0);
                                while ($sess = mysqli_fetch_assoc($sessions)):
                                ?>
                                    <option value="<?php echo $sess['session_id']; ?>" <?php echo ($_POST['session_id'] ?? '') == $sess['session_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sess['session_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Document Upload -->
                    <h3 style="color: #333; margin: 25px 0 15px 0; font-size: 1.1em; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                        <i class="fa-solid fa-file-upload"></i> Required Documents
                    </h3>

                    <p style="color: #666; font-size: 0.9em; margin-bottom: 15px;">
                        <strong>Required:</strong> Birth Certificate, Transfer Certificate, Address Proof<br>
                        <strong>Optional:</strong> Previous Mark Sheet (for upper classes)
                    </p>

                    <div class="form-group">
                        <label>Birth Certificate <span class="required">*</span></label>
                        <div class="file-upload">
                            <label class="file-upload-label">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2em; color: #667eea;"></i>
                                <p style="margin-top: 10px;">Click to upload or drag & drop</p>
                                <p style="font-size: 0.85em; color: #999;">PDF, JPG, PNG up to 5MB</p>
                                <input type="file" name="birth_certificate" accept=".pdf,.jpg,.jpeg,.png" required>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Transfer Certificate <span class="required">*</span></label>
                        <div class="file-upload">
                            <label class="file-upload-label">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2em; color: #667eea;"></i>
                                <p style="margin-top: 10px;">Click to upload or drag & drop</p>
                                <p style="font-size: 0.85em; color: #999;">PDF, JPG, PNG up to 5MB</p>
                                <input type="file" name="transfer_certificate" accept=".pdf,.jpg,.jpeg,.png" required>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address Proof (ID/Utility Bill) <span class="required">*</span></label>
                        <div class="file-upload">
                            <label class="file-upload-label">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2em; color: #667eea;"></i>
                                <p style="margin-top: 10px;">Click to upload or drag & drop</p>
                                <p style="font-size: 0.85em; color: #999;">PDF, JPG, PNG up to 5MB</p>
                                <input type="file" name="address_proof" accept=".pdf,.jpg,.jpeg,.png" required>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Previous Mark Sheet (Optional)</label>
                        <div class="file-upload">
                            <label class="file-upload-label">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2em; color: #667eea;"></i>
                                <p style="margin-top: 10px;">Click to upload or drag & drop</p>
                                <p style="font-size: 0.85em; color: #999;">PDF, JPG, PNG up to 5MB</p>
                                <input type="file" name="mark_sheet" accept=".pdf,.jpg,.jpeg,.png">
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn" style="margin-top: 30px;">
                        <i class="fa-solid fa-paper-plane"></i> Submit Application
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
