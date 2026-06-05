<?php
/**
 * Student Profile View
 * Variables: $student, $academic
 */
?>
<style>
    .profile-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }
    .profile-info-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .profile-info-table td {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    .profile-info-table td:first-child {
        width: 40%;
    }
    @media (max-width: 768px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
        .profile-info-table tr {
            display: flex;
            flex-direction: column;
            border-bottom: 1px solid #eee;
        }
        .profile-info-table td {
            border-bottom: none !important;
            width: 100% !important;
        }
        .profile-info-table td:first-child {
            padding-bottom: 0 !important;
            color: #666;
        }
        .profile-info-table td:last-child {
            padding-top: 5px !important;
        }
    }
</style>

<div class="page-header">
    <div>
        <h1><i class="fas fa-user"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Your personal and academic details</p>
    </div>
</div>

<div class="profile-container">
    <div class="form-card">
        <h3><i class="fas fa-address-card"></i> Personal Information</h3>
        <table class="profile-info-table">
            <tr>
                <td><strong>Full Name</strong></td>
                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
            </tr>
            <tr>
                <td><strong>Date of Birth</strong></td>
                <td><?php echo date('d M Y', strtotime($student['date_of_birth'])); ?></td>
            </tr>
            <tr>
                <td><strong>Gender</strong></td>
                <td><?php echo htmlspecialchars($student['gender']); ?></td>
            </tr>
            <tr>
                <td><strong>Phone</strong></td>
                <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <td><strong>Email</strong></td>
                <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
            </tr>
        </table>
    </div>

    <div class="form-card">
        <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
        <table class="profile-info-table">
            <tr>
                <td><strong>Current Class</strong></td>
                <td><?php echo $academic ? htmlspecialchars($academic['class_name'] . ' ' . $academic['section']) : 'N/A'; ?></td>
            </tr>
            <tr>
                <td><strong>Roll Number</strong></td>
                <td><?php echo htmlspecialchars($academic['roll_number'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <td><strong>Admission Date</strong></td>
                <td><?php echo !empty($student['admission_date']) ? date('d M Y', strtotime($student['admission_date'])) : 'N/A'; ?></td>
            </tr>
            <tr>
                <td><strong>Parent/Guardian</strong></td>
                <td><?php echo htmlspecialchars($student['parent_name'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <td><strong>Parent Phone</strong></td>
                <td><?php echo htmlspecialchars($student['parent_phone'] ?? 'N/A'); ?></td>
            </tr>
        </table>
    </div>
</div>
