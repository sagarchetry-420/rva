<?php
/**
 * Student Profile View
 * Variables: $student, $academic
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-user"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Your personal and academic details</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
    <div class="form-card">
        <h3><i class="fas fa-address-card"></i> Personal Information</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee; width: 40%;"><strong>Full Name</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Date of Birth</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo date('d M Y', strtotime($student['date_of_birth'])); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Gender</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['gender']); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Phone</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Email</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Address</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo nl2br(htmlspecialchars($student['address'] ?? 'N/A')); ?></td>
            </tr>
        </table>
    </div>

    <div class="form-card">
        <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee; width: 40%;"><strong>Current Class</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo $academic ? htmlspecialchars($academic['class_name'] . ' ' . $academic['section']) : 'N/A'; ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Roll Number</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($academic['roll_number'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Admission Date</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo !empty($student['admission_date']) ? date('d M Y', strtotime($student['admission_date'])) : 'N/A'; ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Parent/Guardian</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['parent_name'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Parent Phone</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['parent_phone'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Parent Email</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($student['parent_email'] ?? 'N/A'); ?></td>
            </tr>
        </table>
    </div>
</div>
