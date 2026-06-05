<?php
/**
 * Master Exam Schedule View (Admin)
 * Variables: $exam, $matrix, $dates, $pageTitle
 */
?>
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <h1><i class="fas fa-table"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Consolidated view of the examination routine across all classes.</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="window.print()" style="margin-right: 10px;"><i class="fas fa-print"></i> Print Master Routine</button>
        <a href="<?php echo moduleUrl('admin', 'examinations'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Exams</a>
    </div>
</div>

<style>
@media print {
    @page {
        size: landscape;
        margin: 10mm;
    }
    body * {
        visibility: hidden;
    }
    #printableArea, #printableArea * {
        visibility: visible;
    }
    #printableArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
    .print-header {
        display: block !important;
        margin-bottom: 20px;
        border-bottom: 2px solid #ccc;
        padding-bottom: 10px;
    }
    .sidebar, .top-nav, .main-header, .page-header, .btn {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    table.data-table th, table.data-table td {
        padding: 8px !important;
        font-size: 11px !important;
    }
}
.timetable-matrix th {
    background-color: var(--primary) !important;
    color: white !important;
    text-align: center;
    vertical-align: middle;
}
.subject-cell {
    text-align: center;
    vertical-align: top;
    padding: 10px !important;
    border: 1px solid #e5e7eb;
}
.subject-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 8px;
    margin-bottom: 5px;
    text-align: left;
}
</style>

<div id="printableArea" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    
    <div class="print-header" style="display: none; text-align: center;">
        <h2 style="margin: 0 0 5px 0; font-size: 24px; color: #333;">Master Exam Routine</h2>
        <h3 style="margin: 0; font-size: 18px; color: #666;"><?php echo htmlspecialchars($exam['exam_name']); ?> (<?php echo date('M Y', strtotime($exam['start_date'])); ?>)</h3>
    </div>

    <?php if (empty($dates) || empty($exam['classes'])): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-calendar-times"></i></div>
            <p>No schedules have been configured for this exam yet.</p>
        </div>
    <?php else: ?>
        <div class="table-container" style="overflow-x: auto;">
            <table class="data-table timetable-matrix" style="width: 100%; border-collapse: collapse; min-width: 1000px;">
                <thead>
                    <tr>
                        <th style="width: 120px; position: sticky; left: 0; z-index: 2;">Date \ Class</th>
                        <?php foreach ($exam['classes'] as $c): ?>
                            <th style="min-width: 180px;">
                                <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dates as $date): ?>
                        <tr>
                            <td style="background: #f8f9fa; color: #333; font-weight: bold; border: 1px solid #ddd; text-align: center; position: sticky; left: 0; z-index: 1;">
                                <?php echo date('d M, Y', strtotime($date)); ?><br>
                                <small style="color: #666; font-weight: normal;"><?php echo date('l', strtotime($date)); ?></small>
                            </td>
                            
                            <?php foreach ($exam['classes'] as $c): ?>
                                <td class="subject-cell">
                                    <?php 
                                        $classId = $c['class_id'];
                                        if (isset($matrix[$date][$classId]) && !empty($matrix[$date][$classId])): 
                                            // Sort schedules for this class/date by start time
                                            $daySchedules = $matrix[$date][$classId];
                                            usort($daySchedules, function($a, $b) {
                                                return strcmp($a['start_time'], $b['start_time']);
                                            });
                                            
                                            foreach ($daySchedules as $s):
                                    ?>
                                        <div class="subject-item">
                                            <strong style="color: #374151; display: block; margin-bottom: 3px; font-size: 13px;">
                                                <?php echo htmlspecialchars($s['subject_name']); ?> 
                                                <span style="color:#9ca3af; font-size:11px; font-weight:normal;">(<?php echo htmlspecialchars($s['subject_code']); ?>)</span>
                                            </strong>
                                            <div style="font-size: 11px; color: var(--primary); margin-bottom: 2px;">
                                                <i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($s['start_time'])); ?> - <?php echo date('h:i A', strtotime($s['end_time'])); ?>
                                            </div>
                                            <div style="font-size: 10px; color: #64748b;">
                                                Marks: <?php echo (float)$s['full_marks']; ?> (Pass: <?php echo (float)$s['pass_marks']; ?>)
                                            </div>
                                        </div>
                                    <?php 
                                            endforeach;
                                        else: 
                                    ?>
                                        <span style="color: #cbd5e1; font-style: italic;">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
