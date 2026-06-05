<?php
/**
 * Teacher Results Entry View
 * Variables: $teacher, $exams, $currentExam, $examId, $classId, $schedules, $scheduleId, $schedule, $students, $session
 */
$canEdit = false;
if ($currentExam) {
    $canEdit = $currentExam['is_approved'] && date('Y-m-d') >= $currentExam['end_date'];
}

$hasResults = false;
if (!empty($students)) {
    foreach ($students as $s) {
        if (isset($s['marks_obtained']) || isset($s['result_id'])) {
            $hasResults = true;
            break;
        }
    }
}
if ($hasResults) {
    $canEdit = false;
}
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-file-pen"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Select an exam, class, and subject to enter marks</p>
    </div>
</div>

<div class="form-card" style="margin-bottom: 20px;">
    <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap: wrap;">
        <input type="hidden" name="module" value="teacher">
        <input type="hidden" name="action" value="results">
        
        <div class="form-group" style="flex:1; min-width: 200px;">
            <label>Exam</label>
            <select name="exam_id" class="form-control" required onchange="this.form.submit()">
                <option value="">-- Select Exam --</option>
                <?php foreach ($exams as $e): ?>
                    <option value="<?php echo $e['exam_id']; ?>" <?php echo $examId == $e['exam_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($e['exam_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($examId): ?>
        <div class="form-group" style="flex:1; min-width: 200px;">
            <label>Class</label>
            <select name="class_id" class="form-control" required onchange="this.form.submit()">
                <option value="">-- Select Class --</option>
                <?php 
                $selectedExam = null;
                foreach ($exams as $e) { if ($e['exam_id'] == $examId) { $selectedExam = $e; break; } }
                if ($selectedExam):
                    foreach ($selectedExam['classes'] as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $classId == $c['class_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                        </option>
                    <?php endforeach;
                endif; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if ($examId && $classId && !empty($schedules)): ?>
        <div class="form-group" style="flex:1; min-width: 200px;">
            <label>Subject</label>
            <select name="schedule_id" class="form-control" required onchange="this.form.submit()">
                <option value="">-- Select Subject --</option>
                <?php foreach ($schedules as $s): ?>
                    <option value="<?php echo $s['schedule_id']; ?>" <?php echo $scheduleId == $s['schedule_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['subject_name']); ?> (<?php echo date('d M', strtotime($s['exam_date'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($scheduleId && !empty($students)): ?>
    <div class="form-card">
        <h3>
            <i class="fas fa-edit"></i> Enter Marks — <?php echo htmlspecialchars($schedule['subject_name'] ?? ''); ?>
            <?php if ($schedule): ?>
                (Full Marks: <?php echo (float)($schedule['full_marks'] ?? 100); ?>)
            <?php endif; ?>
        </h3>
        
        <?php if (!$canEdit && $currentExam): ?>
            <div class="alert alert-warning" style="margin-top:10px;">
                <i class="fas fa-lock"></i> Marks entry is currently locked. 
                <?php if ($hasResults): ?>
                    Results have already been submitted for this subject and cannot be edited.
                <?php elseif (!$currentExam['is_approved']): ?>
                    This exam is pending administrator approval.
                <?php else: ?>
                    Marks can only be entered after the exam ends on <?php echo date('d M Y', strtotime($currentExam['end_date'])); ?>.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo moduleUrl('teacher', 'results'); ?>">

            <?php echo csrf_field(); ?>
            <input type="hidden" name="schedule_id" value="<?php echo $scheduleId; ?>">
            <input type="hidden" name="class_id" value="<?php echo $classId; ?>">
            <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th>Marks</th>
                            <th>Absent</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['roll_number']); ?></td>
                                <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                                <td style="width: 120px;">
                                    <input type="number" name="marks[<?php echo $s['student_id']; ?>]" 
                                           class="form-control" style="width: 100px;"
                                           value="<?php echo $s['marks_obtained'] ?? ''; ?>"
                                           min="0" max="<?php echo $schedule['full_marks'] ?? 100; ?>" step="0.5"
                                           <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                </td>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="absent[<?php echo $s['student_id']; ?>]" 
                                           <?php echo ($s['is_absent'] ?? 0) ? 'checked' : ''; ?>
                                           <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                </td>
                                <td>
                                    <input type="text" name="remarks[<?php echo $s['student_id']; ?>]" 
                                           class="form-control" style="width: 150px;"
                                           value="<?php echo htmlspecialchars($s['remarks'] ?? ''); ?>"
                                           <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 15px; text-align: right;">
                <button type="submit" class="btn btn-primary" <?php echo !$canEdit ? 'disabled' : ''; ?>><i class="fas fa-save"></i> Save Results</button>
            </div>
        </form>
    </div>
<?php elseif ($examId && $classId && empty($schedules)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div>
        <p>No exam schedules found for your subjects in this class. The admin needs to create exam schedules first.</p>
    </div>
<?php endif; ?>
