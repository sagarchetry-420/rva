<?php
/**
 * Exam Schedules View (Admin) Read-Only Version
 * Variables: $exam, $class, $schedules, $subjects, $pageTitle, $examSlots, $matrix, $dates
 */
?>
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <h1><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <label style="font-weight: bold; margin: 0; color: var(--primary);">Scheduling for Class:</label>
            <select class="form-control" style="width: auto; display: inline-block; padding: 6px; font-weight: bold;" onchange="window.location.href='<?php echo moduleUrl('admin', 'schedules'); ?>?exam_id=<?php echo $exam['exam_id']; ?>&class_id='+this.value">
                <?php foreach ($exam['classes'] as $c): ?>
                    <option value="<?php echo $c['class_id']; ?>" <?php echo $c['class_id'] == $class['class_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <p>View exam timetables and marks structure</p>
    </div>
    <div>
        <a href="<?php echo moduleUrl('admin', 'examinations'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Exams</a>
    </div>
</div>

<div class="action-buttons-container" style="display: flex; gap: 10px; margin-bottom: 20px;">
    <form method="POST" action="<?php echo moduleUrl('admin', 'schedules'); ?>" style="display:inline;" class="no-auto-validate">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="download_schedules_template">
        <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
        <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
        <button type="submit" class="btn btn-info" style="width: 160px; text-align: center;"><i class="fas fa-download"></i> CSV Template</button>
    </form>
    <button type="button" class="btn btn-warning" onclick="openModal('importCsvModal')" style="width: 160px; text-align: center;"><i class="fas fa-upload"></i> Import CSV</button>
    <button type="button" class="btn btn-primary" onclick="window.print()" style="width: 160px; text-align: center;"><i class="fas fa-print"></i> Print</button>
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
        text-align: center;
        margin-bottom: 20px;
    }
    .print-header h2 { margin: 0; color: #000; font-size: 24px; }
    .print-header h3 { margin: 5px 0 0 0; color: #555; font-size: 18px; }
    
    .modern-timetable-wrapper {
        background: none !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        overflow: visible !important;
    }
    .timetable-modern {
        background-color: transparent !important;
        border-spacing: 0 !important;
        border-collapse: collapse !important;
        width: 100% !important;
        min-width: 100% !important;
    }
    .timetable-modern th {
        border: 1px solid #000 !important;
        color: #000 !important;
        padding: 10px !important;
        background-color: #f5f5f5 !important;
        -webkit-print-color-adjust: exact;
    }
    .timetable-modern td {
        border: 1px solid #000 !important;
        color: #000 !important;
        padding: 10px !important;
        height: auto !important;
    }
    .timetable-modern td.time-col {
        color: #000 !important;
        font-weight: bold;
    }
}

.print-header {
    display: none;
}

.modern-timetable-wrapper {
    background-color: #faf9f7;
    background-image: 
        radial-gradient(circle at 5% 5%, #fff2ec 0%, transparent 30%), 
        radial-gradient(circle at 95% 95%, #fff2ec 0%, transparent 30%);
    padding: 20px;
    border-radius: 16px;
    border: 1px solid #f0eee9;
}
.timetable-modern {
    background-color: #cbd8cf;
    border-radius: 12px;
    padding: 8px;
    width: 100%;
    border-collapse: separate;
    border-spacing: 10px;
    min-width: 800px;
}
.timetable-modern th {
    color: #245e54;
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    padding: 5px 10px 10px 10px;
    border: none;
}
.timetable-modern td {
    background-color: #ffffff;
    border-radius: 6px;
    padding: 12px 10px;
    text-align: center;
    vertical-align: middle;
    border: none;
    height: 50px;
}
.timetable-modern td.time-col {
    color: #e08b76;
    font-weight: 600;
    white-space: nowrap;
    font-size: 14px;
}
.timetable-modern td.subject-cell {
    color: #444;
    font-size: 14px;
    font-weight: 500;
}
</style>

<div id="printableArea">
    <div class="print-header">
        <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
        <h3>Class: <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?></h3>
    </div>

    <?php if (empty($examSlots) || empty($dates)): ?>
        <div class="empty-state" style="padding: 40px; text-align: center; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
            <div class="empty-icon" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"><i class="fas fa-calendar-xmark"></i></div>
            <p style="color: #64748b; font-size: 16px;">No schedules for this class. Use "Import CSV" to set up the schedules.</p>
        </div>
    <?php else: ?>
        <div class="table-container modern-timetable-wrapper" style="overflow-x: auto; margin-top: 20px;">
        <table class="timetable-modern">
            <thead>
                <tr>
                    <th style="width: 120px;">Date \ Slot</th>
                    <?php foreach ($examSlots as $slotKey => $slot): ?>
                        <th>
                            <strong><?php echo htmlspecialchars($slot['label']); ?></strong>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dates as $date): ?>
                    <tr>
                        <td class="time-col">
                            <?php echo date('d M Y', strtotime($date)); ?><br>
                            <span style="font-size: 12px; font-weight: normal; color: #666;"><?php echo date('l', strtotime($date)); ?></span>
                        </td>
                        <?php foreach ($examSlots as $slotKey => $slot): ?>
                            <?php 
                                $selectedSubjectId = 0;
                                $selectedFullMarks = 100;
                                $selectedPassMarks = 35;
                                if ($date !== "" && isset($matrix[$date][$slotKey])) {
                                    $selectedSubjectId = $matrix[$date][$slotKey]['subject_id'];
                                    $selectedFullMarks = $matrix[$date][$slotKey]['full_marks'];
                                    $selectedPassMarks = $matrix[$date][$slotKey]['pass_marks'];
                                }
                                
                                $subjectName = '--';
                                if ($selectedSubjectId > 0) {
                                    foreach ($subjects as $s) {
                                        if ($s['subject_id'] == $selectedSubjectId) {
                                            $subjectName = $s['subject_name'] . ' (' . $s['subject_code'] . ')';
                                            break;
                                        }
                                    }
                                }
                            ?>
                            <td class="subject-cell">
                                <?php if ($selectedSubjectId > 0): ?>
                                    <span style="color:#374151; font-weight: 600; display: block; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($subjectName); ?>
                                    </span>
                                    <span style="font-size:12px; font-weight:normal; color:#245e54;">Marks: <?php echo (float)$selectedFullMarks; ?> (Pass: <?php echo (float)$selectedPassMarks; ?>)</span>
                                <?php else: ?>
                                    <span style="color:#9ca3af; font-style: italic; font-weight: normal;">--</span>
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

<!-- Import CSV Modal -->
<div id="importCsvModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Import Schedules CSV</h2>
            <span class="close" onclick="closeModal('importCsvModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'schedules'); ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="import_schedules_csv">
            <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
            <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
            <div class="modal-body">
                <p style="margin-bottom: 15px; font-size: 0.9em; color: #555;">
                    <i class="fas fa-info-circle text-info"></i> Uploading a CSV will <strong>completely replace</strong> the existing schedule for this class in this exam.
                </p>
                <div class="form-group">
                    <label>Select CSV File *</label>
                    <input type="file" name="csv_file" accept=".csv" required class="form-control" style="padding: 5px;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('importCsvModal')">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-upload"></i> Upload & Replace</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'flex';
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'none';
}

window.onclick = function(event) {
    const importModal = document.getElementById('importCsvModal');
    if (event.target == importModal) {
        importModal.style.display = "none";
    }
}
</script>
