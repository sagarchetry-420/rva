<?php
/**
 * Student Timetable View
 * Variables: $student, $academic, $timetable, $session
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-clock"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Class schedule for <?php echo $academic ? htmlspecialchars($academic['class_name'] . ' ' . $academic['section']) : 'N/A'; ?></p>
    </div>
</div>

<?php if (empty($timetable)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div>
        <p>No timetable has been published for your class yet.</p>
    </div>
<?php else: ?>
    <?php
    // Extract and sort unique time slots
    $timeSlots = [];
    foreach ($timetable as $t) {
        $slotKey = $t['start_time'] . '-' . $t['end_time'];
        
        // If this is a holiday record, don't use it to define the column's break status
        $isBreak = (!empty($t['is_break']) && strtolower($t['break_name']) !== 'holiday') ? 1 : 0;
        $breakName = $isBreak ? $t['break_name'] : '';

        if (!isset($timeSlots[$slotKey])) {
            $timeSlots[$slotKey] = [
                'start' => $t['start_time'],
                'end' => $t['end_time'],
                'is_break' => $isBreak,
                'break_name' => $breakName,
                'time_label' => date('h:i A', strtotime($t['start_time'])) . ' - ' . date('h:i A', strtotime($t['end_time']))
            ];
        } else {
            // If we previously saved it as not a break, but we just found a real break for this timeslot, update it
            if ($isBreak && !$timeSlots[$slotKey]['is_break']) {
                $timeSlots[$slotKey]['is_break'] = 1;
                $timeSlots[$slotKey]['break_name'] = $breakName;
            }
        }
    }
    usort($timeSlots, function($a, $b) {
        return strcmp($a['start'], $b['start']);
    });

    $periodCount = 1;
    foreach ($timeSlots as &$ts) {
        if (!empty($ts['is_break'])) {
            $ts['label'] = htmlspecialchars($ts['break_name']);
        } else {
            $ts['label'] = 'Period ' . $periodCount++;
        }
    }
    unset($ts);

    $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $matrix = [];
    foreach ($dayOrder as $day) {
        $matrix[$day] = [];
        foreach ($timetable as $t) {
            if ($t['day_of_week'] === $day) {
                $matrix[$day][$t['start_time'] . '-' . $t['end_time']] = $t;
            }
        }
    }
    ?>
    
    <div class="table-container" style="overflow-x: auto;">
        <table class="data-table timetable-matrix" style="width: 100%; border-collapse: collapse; min-width: 800px;">
            <thead>
                <tr>
                    <th style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; width: 120px; text-align: center; position: sticky; left: 0; z-index: 2;">Day \ Time</th>
                    <?php foreach ($timeSlots as $ts): ?>
                        <th style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; text-align: center; white-space: nowrap;">
                            <strong><?php echo $ts['label']; ?></strong><br>
                            <small><?php echo $ts['time_label']; ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dayOrder as $day): ?>
                    <tr>
                        <td style="font-weight: bold; background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; text-align: center; position: sticky; left: 0; z-index: 1;">
                            <?php echo htmlspecialchars($day); ?>
                        </td>
                        <?php foreach ($timeSlots as $index => $ts): ?>
                            <?php if ($day === 'Sunday'): ?>
                                <?php if ($index === 0): ?>
                                    <td colspan="<?php echo count($timeSlots); ?>" style="background-color: #fdfbfb; color: #d1d5db; font-weight: 700; letter-spacing: 8px; text-align: center; vertical-align: middle; border: 2px dashed #f3f4f6;">
                                        HOLIDAY
                                    </td>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php 
                                $key = $ts['start'].'-'.$ts['end'];
                                $t = $matrix[$day][$key] ?? null; 
                                ?>
                                <td style="border: 1px solid #ddd; padding: 12px; text-align: center; vertical-align: middle;">
                                    <?php if ($t): ?>
                                        <?php if (!empty($t['is_break'])): ?>
                                            <span style="display:block; font-weight:bold; color:#555; background:#f3f4f6; padding:6px; border-radius:4px;"><i class="fas fa-coffee" style="margin-right:4px;"></i><?php echo htmlspecialchars($t['break_name']); ?></span>
                                        <?php else: ?>
                                            <strong style="display:block; color:#0d6efd; margin-bottom:4px;"><?php echo htmlspecialchars($t['subject_name'] ?? 'N/A'); ?></strong>
                                            <?php if (!empty($t['teacher_first'])): ?>
                                                <small style="color:#666; display:block;"><i class="fas fa-user" style="font-size:10px;"></i> <?php echo htmlspecialchars($t['teacher_first'] . ' ' . $t['teacher_last']); ?></small>
                                            <?php else: ?>
                                                <small style="color:#999; font-style:italic; display:block;">Not Assigned</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #e5e7eb;">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
