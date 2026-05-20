<?php
/**
 * Admission Settings - Admin Control Panel
 * Control when admission form is open, fees, deadlines, required documents
 */

require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

$message = null;
$message_type = null;

// Load current settings
$settings = [];
$result = mysqli_query($conn, "SELECT setting_name, setting_value FROM admission_settings");
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_name']] = $row['setting_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_open = isset($_POST['admission_form_open']) ? 'yes' : 'no';
    $fee_amount = intval($_POST['application_fee_amount'] ?? 0);
    $deadline = $_POST['application_deadline'] ?? '';
    $required_docs = trim($_POST['required_documents'] ?? '');
    $instructions = trim($_POST['instructions_for_applicants'] ?? '');
    $reapplication = isset($_POST['reapplication_allowed']) ? 'yes' : 'no';
    $contact_email = trim($_POST['school_email_for_contact'] ?? '');

    // Validate
    if ($fee_amount <= 0) {
        $message = "Application fee must be greater than 0";
        $message_type = "error";
    } elseif (empty($deadline)) {
        $message = "Application deadline is required";
        $message_type = "error";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address";
        $message_type = "error";
    } else {
        // Update all settings
        $updates = [
            ['admission_form_open', $form_open],
            ['application_fee_amount', (string)$fee_amount],
            ['application_deadline', $deadline],
            ['required_documents', $required_docs],
            ['instructions_for_applicants', $instructions],
            ['reapplication_allowed', $reapplication],
            ['school_email_for_contact', $contact_email]
        ];

        $error = false;
        foreach ($updates as $setting) {
            $query = "UPDATE admission_settings SET setting_value = ? WHERE setting_name = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                $error = true;
                break;
            }
            $stmt->bind_param('ss', $setting[0], $setting[1]);
            if (!$stmt->execute()) {
                $error = true;
                $stmt->close();
                break;
            }
            $stmt->close();
        }

        if (!$error) {
            // Reload settings
            $settings = [];
            $result = mysqli_query($conn, "SELECT setting_name, setting_value FROM admission_settings");
            while ($row = mysqli_fetch_assoc($result)) {
                $settings[$row['setting_name']] = $row['setting_value'];
            }

            $message = "✅ Admission settings updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating settings. Please try again.";
            $message_type = "error";
        }
    }
}

// Helper function to get setting value
function getSetting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? $settings[$key] : $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Settings - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .settings-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 1.3em;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 1em;
            font-family: inherit;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .form-group.checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group.checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .form-group.checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
            user-select: none;
        }
        .form-help-text {
            font-size: 0.85em;
            color: var(--gray);
            margin-top: 5px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .btn-save {
            background: var(--primary);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            flex: 1;
        }
        .btn-save:hover {
            background: var(--primary-dark);
        }
        .btn-reset {
            background: var(--gray-light);
            color: #333;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
        }
        .btn-reset:hover {
            background: #ddd;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
        }
        .status-open {
            background: #d1fae5;
            color: #065f46;
        }
        .status-closed {
            background: #fee2e2;
            color: #991b1b;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            color: #0d47a1;
        }
        .info-box strong {
            color: #0d47a1;
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div>
                    <h1><i class="fa-solid fa-sliders"></i> Admission Settings</h1>
                    <p>Control admission form visibility, fees, deadlines, and requirements</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fa-solid fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="settings-container">
                <!-- Current Status Display -->
                <div class="settings-section">
                    <div class="section-title">
                        <i class="fa-solid fa-circle-info"></i> Current Status
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div>
                            <strong>Admission Form Status:</strong><br><br>
                            <span class="status-badge <?php echo getSetting('admission_form_open') === 'yes' ? 'status-open' : 'status-closed'; ?>">
                                <?php echo getSetting('admission_form_open') === 'yes' ? '✓ OPEN' : '✗ CLOSED'; ?>
                            </span>
                        </div>
                        <div>
                            <strong>Application Fee:</strong><br>
                            Rs. <?php echo htmlspecialchars(getSetting('application_fee_amount', '0')); ?>
                        </div>
                        <div>
                            <strong>Deadline:</strong><br>
                            <?php
                            $deadline = getSetting('application_deadline');
                            echo $deadline ? date('d M Y', strtotime($deadline)) : 'Not set';
                            ?>
                        </div>
                        <div>
                            <strong>Applications:</strong><br>
                            <?php
                            $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admission_applications"));
                            echo $count['cnt'] . " total";
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Settings Form -->
                <form method="POST" class="settings-section">
                    <!-- Admission Form Status -->
                    <div class="section-title">
                        <i class="fa-solid fa-door-open"></i> Form Access Control
                    </div>

                    <div class="info-box">
                        <strong>ℹ️</strong> Control whether the public admission form is accessible to applicants. When closed, the form will show "Admissions are currently closed".
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="admission_form_open" name="admission_form_open"
                               value="yes" <?php echo getSetting('admission_form_open') === 'yes' ? 'checked' : ''; ?>>
                        <label for="admission_form_open">
                            <strong>Open Admission Form for New Applications</strong>
                        </label>
                    </div>
                    <div class="form-help-text">
                        ✓ Checked = Form is OPEN | ✗ Unchecked = Form is CLOSED
                    </div>

                    <!-- Application Deadline -->
                    <div style="margin-top: 30px;">
                        <div class="section-title">
                            <i class="fa-solid fa-calendar"></i> Application Deadline
                        </div>
                        <div class="form-group">
                            <label for="application_deadline">Last Date for Applications *</label>
                            <input type="date" id="application_deadline" name="application_deadline"
                                   value="<?php echo getSetting('application_deadline'); ?>" required>
                            <div class="form-help-text">
                                Applications received after this date will not be accepted
                            </div>
                        </div>
                    </div>

                    <!-- Fees Section -->
                    <div style="margin-top: 30px;">
                        <div class="section-title">
                            <i class="fa-solid fa-money-bill"></i> Application Fee
                        </div>
                        <div class="form-group">
                            <label for="application_fee_amount">Application Fee Amount (Rs.) *</label>
                            <input type="number" id="application_fee_amount" name="application_fee_amount"
                                   value="<?php echo getSetting('application_fee_amount'); ?>"
                                   min="0" step="1" required>
                            <div class="form-help-text">
                                Fee amount applicants must pay after approval (in rupees)
                            </div>
                        </div>
                    </div>

                    <!-- Requirements Section -->
                    <div style="margin-top: 30px;">
                        <div class="section-title">
                            <i class="fa-solid fa-file-circle-check"></i> Required Documents
                        </div>
                        <div class="form-group">
                            <label for="required_documents">Required Documents (comma-separated) *</label>
                            <textarea id="required_documents" name="required_documents"
                                      placeholder="Birth Certificate, Transfer Certificate, Address Proof"><?php echo htmlspecialchars(getSetting('required_documents')); ?></textarea>
                            <div class="form-help-text">
                                List documents applicants must submit. Separate with commas.
                            </div>
                        </div>
                    </div>

                    <!-- Instructions Section -->
                    <div style="margin-top: 30px;">
                        <div class="section-title">
                            <i class="fa-solid fa-book"></i> Instructions for Applicants
                        </div>
                        <div class="form-group">
                            <label for="instructions_for_applicants">Application Instructions</label>
                            <textarea id="instructions_for_applicants" name="instructions_for_applicants"
                                      placeholder="Enter instructions that will be shown to applicants..."><?php echo htmlspecialchars(getSetting('instructions_for_applicants')); ?></textarea>
                            <div class="form-help-text">
                                This text will be displayed on the application form. Use it to provide guidance to applicants.
                            </div>
                        </div>
                    </div>

                    <!-- Reapplication Policy -->
                    <div style="margin-top: 30px;">
                        <div class="section-title">
                            <i class="fa-solid fa-rotate-right"></i> Reapplication Policy
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="reapplication_allowed" name="reapplication_allowed"
                                   value="yes" <?php echo getSetting('reapplication_allowed') === 'yes' ? 'checked' : ''; ?>>
                            <label for="reapplication_allowed">
                                <strong>Allow Rejected Applicants to Reapply in Same Season</strong>
                            </label>
                        </div>
                        <div class="form-help-text">
                            ✓ Checked = Can reapply anytime | ✗ Unchecked = No reapplication allowed
                        </div>
                    </div>

                    <!-- Contact Section -->
                    <div style="margin-top: 30px;">
                        <div class="section-title">
                            <i class="fa-solid fa-envelope"></i> Contact Information
                        </div>
                        <div class="form-group">
                            <label for="school_email_for_contact">School Email for Inquiries *</label>
                            <input type="email" id="school_email_for_contact" name="school_email_for_contact"
                                   value="<?php echo htmlspecialchars(getSetting('school_email_for_contact')); ?>" required>
                            <div class="form-help-text">
                                Email address applicants can use to contact regarding their application
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="button-group">
                        <button type="submit" class="btn-save">
                            <i class="fa-solid fa-save"></i> Save All Settings
                        </button>
                        <button type="reset" class="btn-reset">
                            <i class="fa-solid fa-undo"></i> Reset Form
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
