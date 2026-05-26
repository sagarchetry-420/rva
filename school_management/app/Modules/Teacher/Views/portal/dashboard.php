<?php
/**
 * Teacher Dashboard View
 * Variables: $teacher, $classesTeaching, $subjectsTeaching, $classTeacherOf, $upcomingExams, $session
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-house"></i> Welcome, <?php echo htmlspecialchars($teacher['first_name'] ?? 'Teacher'); ?>!</h1>
        <p>Teacher Dashboard — <?php echo htmlspecialchars($session['session_name'] ?? 'No Active Session'); ?></p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="form-card" style="text-align: center; padding: 30px;">
        <div style="font-size: 2.5rem; color: var(--primary-color, #2c5f2d); margin-bottom: 10px;">
            <i class="fas fa-chalkboard"></i>
        </div>
        <h3 style="margin: 0 0 5px 0;"><?php echo $classesTeaching; ?></h3>
        <p style="margin: 0; color: #666;">Classes Assigned</p>
    </div>

    <div class="form-card" style="text-align: center; padding: 30px;">
        <div style="font-size: 2.5rem; color: #2196F3; margin-bottom: 10px;">
            <i class="fas fa-book"></i>
        </div>
        <h3 style="margin: 0 0 5px 0;"><?php echo $subjectsTeaching; ?></h3>
        <p style="margin: 0; color: #666;">Subjects Teaching</p>
    </div>

    <div class="form-card" style="text-align: center; padding: 30px;">
        <div style="font-size: 2.5rem; color: #FF9800; margin-bottom: 10px;">
            <i class="fas fa-user-tie"></i>
        </div>
        <h3 style="margin: 0 0 5px 0;">
            <?php echo $classTeacherOf ? htmlspecialchars($classTeacherOf['class_name'] . ' ' . $classTeacherOf['section']) : 'N/A'; ?>
        </h3>
        <p style="margin: 0; color: #666;">Class Teacher Of</p>
    </div>
</div>

<?php if (!empty($upcomingExams)): ?>
<div class="form-card">
    <h3><i class="fas fa-calendar-check"></i> Upcoming Examinations</h3>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Exam Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcomingExams as $exam): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($exam['start_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($exam['end_date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
    <a href="<?php echo moduleUrl('teacher', 'attendance'); ?>" class="btn btn-primary" style="text-align:center; padding: 15px;">
        <i class="fas fa-check-to-slot"></i> Mark Attendance
    </a>
    <a href="<?php echo moduleUrl('teacher', 'my-classes'); ?>" class="btn btn-secondary" style="text-align:center; padding: 15px;">
        <i class="fas fa-book-open"></i> My Classes
    </a>
    <a href="<?php echo moduleUrl('teacher', 'results'); ?>" class="btn btn-success" style="text-align:center; padding: 15px;">
        <i class="fas fa-file-pen"></i> Enter Results
    </a>
    <a href="<?php echo moduleUrl('teacher', 'notices'); ?>" class="btn btn-info" style="text-align:center; padding: 15px;">
        <i class="fas fa-bullhorn"></i> View Notices
    </a>
    <a href="<?php echo moduleUrl('teacher', 'id_card'); ?>" class="btn btn-primary" style="text-align:center; padding: 15px; background-color: #673ab7; border-color: #673ab7;">
        <i class="fas fa-address-card"></i> ID Card
    </a>
</div>
