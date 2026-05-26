<?php
/**
 * Student Dashboard View
 * Variables: $student, $academic, $session, $attendanceSummary, $totalDays
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-house"></i> Welcome, <?php echo htmlspecialchars($student['first_name'] ?? 'Student'); ?>!</h1>
        <p>Student Dashboard — <?php echo htmlspecialchars($session['session_name'] ?? 'No Active Session'); ?></p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="form-card" style="text-align: center; padding: 30px;">
        <div style="font-size: 2.5rem; color: var(--primary-color, #2c5f2d); margin-bottom: 10px;">
            <i class="fas fa-user-graduate"></i>
        </div>
        <h3 style="margin: 0 0 5px 0;">
            <?php echo $academic ? htmlspecialchars($academic['class_name'] . ' ' . $academic['section']) : 'Not Assigned'; ?>
        </h3>
        <p style="margin: 0; color: #666;">Current Class</p>
    </div>

    <div class="form-card" style="text-align: center; padding: 30px;">
        <div style="font-size: 2.5rem; color: #2196F3; margin-bottom: 10px;">
            <i class="fas fa-id-card"></i>
        </div>
        <h3 style="margin: 0 0 5px 0;">
            <?php 
                $rollNo = $academic['roll_number'] ?? $student['roll_number'] ?? '';
                echo htmlspecialchars($rollNo !== '' ? $rollNo : 'Not Assigned'); 
            ?>
        </h3>
        <p style="margin: 0; color: #666;">Roll Number</p>
    </div>

    <div class="form-card" style="text-align: center; padding: 30px;">
        <div style="font-size: 2.5rem; color: #FF9800; margin-bottom: 10px;">
            <i class="fas fa-chart-pie"></i>
        </div>
        <h3 style="margin: 0 0 5px 0;">
            <?php 
            $present = $attendanceSummary['Present'] ?? 0;
            echo $totalDays > 0 ? round(($present / $totalDays) * 100, 1) . '%' : '0%'; 
            ?>
        </h3>
        <p style="margin: 0; color: #666;">Attendance Rate</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
    <a href="<?php echo moduleUrl('student', 'profile'); ?>" class="btn btn-primary" style="text-align:center; padding: 15px;">
        <i class="fas fa-user"></i> My Profile
    </a>
    <a href="<?php echo moduleUrl('student', 'timetable'); ?>" class="btn btn-secondary" style="text-align:center; padding: 15px;">
        <i class="fas fa-clock"></i> Timetable
    </a>
    <a href="<?php echo moduleUrl('student', 'results'); ?>" class="btn btn-success" style="text-align:center; padding: 15px;">
        <i class="fas fa-file-pen"></i> My Results
    </a>
    <a href="<?php echo moduleUrl('student', 'fees'); ?>" class="btn btn-warning" style="text-align:center; padding: 15px; color: white;">
        <i class="fas fa-file-invoice-dollar"></i> Fee Invoices
    </a>
    <a href="<?php echo moduleUrl('student', 'notices'); ?>" class="btn btn-info" style="text-align:center; padding: 15px;">
        <i class="fas fa-bullhorn"></i> View Notices
    </a>
    <a href="<?php echo moduleUrl('student', 'id_card'); ?>" class="btn btn-primary" style="text-align:center; padding: 15px; background-color: #673ab7; border-color: #673ab7;">
        <i class="fas fa-address-card"></i> ID Card
    </a>
</div>
