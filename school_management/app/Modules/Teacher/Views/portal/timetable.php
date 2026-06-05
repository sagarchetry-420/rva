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
    
    <style>
        .day-group:hover td.day-cell {
            background-color: #e2f0e9 !important;
            color: #245e54;
            transition: all 0.2s ease;
        }
        .data-table tr:hover td:not(.day-cell) {
            background-color: #f8fafc;
        }
        /* Add a bold line to visually separate different days */
        .day-group + .day-group tr:first-child td {
            border-top: 3px solid #cbd5e1 !important;
        }
    </style>
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
            <?php foreach ($dayOrder as $day): ?>
                <?php if (isset($byDay[$day])): ?>
                    <tbody class="day-group">
                        <?php $first = true; foreach ($byDay[$day] as $entry): ?>
                            <tr>
                                <?php if ($first): ?>
                                    <td class="day-cell hide-on-mobile" rowspan="<?php echo count($byDay[$day]); ?>" style="vertical-align: middle; font-weight: bold; background: #fdfbfb;">
                                        <?php echo htmlspecialchars($day); ?>
                                    </td>
                                <?php $first = false; endif; ?>
                                <td class="day-cell show-on-mobile" style="display:none;">
                                    <?php echo htmlspecialchars($day); ?>
                                </td>
                                <td class="td-time">
                                    <?php echo date('h:i A', strtotime($entry['start_time'])); ?> - 
                                    <?php echo date('h:i A', strtotime($entry['end_time'])); ?>
                                </td>
                                <td class="td-subject"><?php echo htmlspecialchars($entry['subject_name']); ?></td>
                                <td class="td-class"><?php echo htmlspecialchars($entry['class_name'] . ' ' . $entry['section']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
