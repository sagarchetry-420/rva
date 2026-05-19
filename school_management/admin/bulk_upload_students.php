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
    if ($_POST['action'] === 'download_template') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="student_bulk_template.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('First Name', 'Last Name', 'Date of Birth (YYYY-MM-DD)', 'Gender (Male/Female/Other)', 'Phone', 'Email', 'Parent Name', 'Parent Phone'));
        fputcsv($output, array('John', 'Doe', '2005-08-15', 'Male', '1234567890', 'john.doe@example.com', 'Mr. Doe', '0987654321'));
        fclose($output);
        exit();
    }
    
    if ($_POST['action'] === 'upload_csv') {
        $class_id = intval($_POST['class_id']);
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
            $filename = $_FILES['csv_file']['tmp_name'];
            $file = fopen($filename, "r");
            $count = 0;
            $errors = [];
            
            // Skip header
            fgetcsv($file);
            
            while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
                if (count($data) < 8) continue;
                
                $first_name = sanitize($conn, $data[0]);
                $last_name = sanitize($conn, $data[1]);
                $dob = sanitize($conn, $data[2]);
                $gender = sanitize($conn, $data[3]);
                $phone = sanitize($conn, $data[4]);
                $email = sanitize($conn, $data[5]);
                $parent_name = sanitize($conn, $data[6]);
                $parent_phone = sanitize($conn, $data[7]);
                
                if (empty($first_name) || empty($last_name)) continue;
                
                $username = strtolower($first_name . '.' . $last_name . rand(100, 999));
                $plain_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$'), 0, 10);
                $password = md5($plain_password);
                
                $check = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username'");
                if (mysqli_num_rows($check) > 0) {
                    $username = $username . rand(1, 99);
                }
                
                // Check if email already exists
                if (!empty($email)) {
                    $email_check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
                    if (mysqli_num_rows($email_check) > 0) {
                        $errors[] = "Email $email is already registered. Skipped $first_name $last_name.";
                        continue;
                    }
                }
                
                $q = "INSERT INTO users (username, password, user_type, email) VALUES ('$username', '$password', 'student', '$email')";
                if (mysqli_query($conn, $q)) {
                    $uid = mysqli_insert_id($conn);
                    $roll = 'STD' . sprintf('%03d', $uid);
                    $q2 = "INSERT INTO students (user_id, first_name, last_name, date_of_birth, gender, phone, email, parent_name, parent_phone, class_id, roll_number, admission_date) 
                           VALUES ($uid, '$first_name', '$last_name', '$dob', '$gender', '$phone', '$email', '$parent_name', '$parent_phone', $class_id, '$roll', CURDATE())";
                    if (mysqli_query($conn, $q2)) {
                        $count++;
                        if (!empty($email)) {
                            sendCredentialsEmail($email, $first_name, $username, $plain_password, $phone);
                        }
                    } else {
                        $errors[] = "Failed to insert student info for $first_name $last_name.";
                        mysqli_query($conn, "DELETE FROM users WHERE user_id=$uid");
                    }
                } else {
                    $errors[] = "Failed to create user for $first_name $last_name.";
                }
            }
            fclose($file);
            
            if (empty($errors)) {
                setFlashMessage('success', "$count students have been imported successfully!");
            } else {
                setFlashMessage('warning', "Imported $count students with some errors: " . implode(' ', $errors));
            }
            header('Location: students.php');
            exit();
        } else {
            setFlashMessage('error', 'Please upload a valid CSV file. Only .csv format is accepted.');
        }
    }
}

$classes_result = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Students - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-upload"></i> Bulk Upload Students</h1>
                    <p>Upload a CSV file to add multiple students at once.</p>
                </div>
                <a href="students.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Students</a>
            </div>
            
            <div class="dashboard-section" style="max-width: 600px;">
                <form method="POST" style="margin-bottom: 20px;">
                    <input type="hidden" name="action" value="download_template">
                    <p>First, download the template CSV file to see the required format.</p>
                    <button type="submit" class="btn btn-info" style="margin-top: 10px;"><i class="fas fa-file-download"></i> Download Template</button>
                </form>
                
                <hr style="margin: 20px 0; border: none; border-top: 1px solid var(--border);">
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_csv">
                    <div class="form-group">
                        <label>Target Class *</label>
                        <select name="class_id" required>
                            <option value="">Select Class</option>
                            <?php while ($c = mysqli_fetch_assoc($classes_result)): ?>
                            <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>CSV File *</label>
                        <input type="file" name="csv_file" accept=".csv" required style="padding: 8px;">
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload & Import</button>
                </form>
            </div>
        </div>
    </div>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
