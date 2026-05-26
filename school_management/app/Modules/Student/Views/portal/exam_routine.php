<?php
/**
 * Student Exam Routine View
 * Variables: $student, $academic, $exams, $selectedExam, $schedules, $pageTitle
 */
?>
<div class="print-header" style="display: none;">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #ccc;">
        <img src="<?php echo asset('img/logo.png'); ?>" alt="Logo" style="height: 50px; width: 50px; border-radius: 50%; object-fit: cover;">
        <div>
            <h2 style="margin: 0; font-size: 22px; color: #333;">RVA</h2>
            <p style="margin: 0; font-size: 14px; color: #666;">Student Examination Routine</p>
        </div>
    </div>
</div>

<div class="page-header">
    <div>
        <h1><i class="fas fa-calendar-check"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Your upcoming examination schedule</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-info" onclick="window.print()"><i class="fas fa-file-pdf"></i> Download PDF</button>
    </div>
</div>

<style>
@media print {
    .sidebar, .top-nav, .main-header, .page-header, .btn, .exam-selector { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .print-header { display: block !important; }
}
</style>

<?php if (empty($academic)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-exclamation-circle"></i></div>
        <p>No academic record found. Please contact administration.</p>
    </div>
<?php elseif (empty($exams)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-calendar-times"></i></div>
        <p>No exam schedules have been published for your class yet.</p>
    </div>
<?php else: ?>
    
    <div class="exam-selector" style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <label style="font-weight: bold; margin: 0; color: var(--primary);">Select Exam:</label>
        <select class="form-control" style="width: auto; display: inline-block; padding: 8px; font-weight: bold;" onchange="window.location.href="<?php echo moduleUrl('student', 'exam_routine'); ?>?exam_id="+this.value">
            <?php foreach ($exams as $e): ?>
                <option value="<?php echo $e['exam_id']; ?>" <?php echo ($selectedExam && $selectedExam['exam_id'] == $e['exam_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($e['exam_name']); ?> (<?php echo date('M Y', strtotime($e['start_date'])); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (empty($schedules)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-calendar-minus"></i></div>
            <p>No schedule found for this exam.</p>
        </div>
    <?php else: 
        // Build matrix for selected exam
        $groupedData = [
            'matrix' => [],
            'slots' => []
        ];
        foreach ($schedules as $s) {
            $slotKey = $s['start_time'].'|'.$s['end_time'].'|'.(float)$s['full_marks'].'|'.(float)$s['pass_marks'];
            
            $groupedData['slots'][$slotKey] = [
                'start_time' => $s['start_time'],
                'end_time'   => $s['end_time'],
                'full_marks' => $s['full_marks'],
                'pass_marks' => $s['pass_marks'],
                'label'      => date('h:i A', strtotime($s['start_time'])) . ' - ' . date('h:i A', strtotime($s['end_time']))
            ];
            
            $groupedData['matrix'][$s['exam_date']][$slotKey] = $s;
        }

        $examSlots = $groupedData['slots'];
        uasort($examSlots, function($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });
        $matrix = $groupedData['matrix'];
        $dates = array_keys($matrix);
        sort($dates);
    ?>
        <div style="margin-bottom: 40px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 20px; color: var(--primary); border-bottom: 2px solid #eee; padding-bottom: 10px;">
                <i class="fas fa-file-signature"></i> <?php echo htmlspecialchars($selectedExam['exam_name']); ?> Routine
            </h3>
            <div class="table-container" style="overflow-x: auto;">
                <table class="data-table timetable-matrix" style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <thead>
                        <tr>
                            <th style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; width: 150px; text-align: center; position: sticky; left: 0; z-index: 2;">Date \ Time</th>
                            <?php foreach ($examSlots as $slotKey => $slot): ?>
                                <th style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; text-align: center; white-space: nowrap;">
                                    <strong><?php echo htmlspecialchars($slot['label']); ?></strong><br>
                                    <small style="color: #666; font-weight: normal;">Marks: <?php echo (float)$slot['full_marks']; ?> (Pass: <?php echo (float)$slot['pass_marks']; ?>)</small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dates as $date): ?>
                            <tr>
                                <td style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; text-align: center; position: sticky; left: 0; z-index: 1;">
                                    <strong><?php echo date('d M, Y', strtotime($date)); ?></strong><br>
                                    <small style="color: #666;"><?php echo date('l', strtotime($date)); ?></small>
                                </td>
                                <?php foreach ($examSlots as $slotKey => $slot): ?>
                                    <td style="border: 1px solid #ddd; padding: 12px; text-align: center; vertical-align: middle;">
                                        <?php if (isset($matrix[$date][$slotKey])): ?>
                                            <?php $s = $matrix[$date][$slotKey]; ?>
                                            <strong><?php echo htmlspecialchars($s['subject_name']); ?></strong><br>
                                            <small style="color: #666;">(<?php echo htmlspecialchars($s['subject_code']); ?>)</small>
                                        <?php else: ?>
                                            <span style="color: #ccc;">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
