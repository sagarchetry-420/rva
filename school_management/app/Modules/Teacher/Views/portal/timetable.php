<?php
/**
 * Teacher Timetable View — Shows this teacher's schedule
 * Variables: $teacher, $timetable, $session
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-clock"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Your weekly schedule for <?php echo htmlspecialchars($session['session_name'] ?? 'N/A'); ?></p>
    </div>
</div>

<?php if (empty($timetable)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div>
        <p>No timetable has been assigned to you for this session yet.</p>
    </div>
<?php else: ?>
    <?php
    // Group by day
    $byDay = [];
    foreach ($timetable as $entry) {
        $byDay[$entry['day_of_week']][] = $entry;
    }
    $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    ?>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Subject</th>
                    <th>Class</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dayOrder as $day): ?>
                    <?php if (isset($byDay[$day])): ?>
                        <?php $first = true; foreach ($byDay[$day] as $entry): ?>
                            <tr>
                                <?php if ($first): ?>
                                    <td rowspan="<?php echo count($byDay[$day]); ?>" style="vertical-align: middle; font-weight: bold; background: #f8f9fa;">
                                        <?php echo htmlspecialchars($day); ?>
                                    </td>
                                <?php $first = false; endif; ?>
                                <td>
                                    <?php echo date('h:i A', strtotime($entry['start_time'])); ?> - 
                                    <?php echo date('h:i A', strtotime($entry['end_time'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($entry['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($entry['class_name'] . ' ' . $entry['section']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
