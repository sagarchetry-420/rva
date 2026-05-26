<?php
/**
 * Results Entry View (Teachers/Admins)
 * Variables: $exams, $classes, $filterExam, $filterClass, $filterSchedule, $scheduleDetails, $students, $pageTitle
 */

// Since schedules depend on Exam and Class, we typically load them dynamically via JS.
// For this static version, we simulate the flow: 
// 1. Select Exam & Class -> Submit -> Get Schedules dropdown.
// 2. Select Schedule -> Submit -> Show Results Grid.
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-edit"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Enter marks for students based on exam schedules</p>
    </div>
</div>

<div class="form-card" style="margin-bottom: 20px;">
    <form method="GET" id="filterForm">
        <input type="hidden" name="module" value="<?php echo htmlspecialchars($_GET['module'] ?? 'exams'); ?>">
        <input type="hidden" name="action" value="results">
        
        <div class="row" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
            <div class="form-group" style="flex:1; min-width:200px;">
                <label>Exam</label>
                <select name="exam_id" class="form-control" onchange="document.getElementById('filterForm').submit()" required>
                    <option value="">-- Select Exam --</option>
                    <?php foreach ($exams as $e): ?>
                        <option value="<?php echo $e['exam_id']; ?>" <?php echo $filterExam == $e['exam_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($e['exam_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="flex:1; min-width:200px;">
                <label>Class</label>
                <select name="class_id" class="form-control" onchange="document.getElementById('filterForm').submit()" required>
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $filterClass == $c['class_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($filterExam && $filterClass): ?>
                <?php 
                    // Fetch schedules inline for the dropdown
                    $schedRepo = new \App\Modules\Exams\Repositories\ScheduleRepository();
                    $schedulesList = $schedRepo->findByExamAndClass($filterExam, $filterClass);
                ?>
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Subject Schedule</label>
                    <select name="schedule_id" class="form-control" onchange="document.getElementById('filterForm').submit()" required>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($schedulesList as $sl): ?>
                            <option value="<?php echo $sl['schedule_id']; ?>" <?php echo $filterSchedule == $sl['schedule_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sl['subject_name'] . ' (' . date('d M', strtotime($sl['exam_date'])) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($filterSchedule && $scheduleDetails): ?>
    <div style="background:var(--primary-light); padding:15px; border-radius:8px; margin-bottom:20px; border-left:4px solid var(--primary);">
        <h3 style="margin-top:0; color:var(--primary-dark);"><i class="fas fa-info-circle"></i> Exam Configuration</h3>
        <p style="margin:5px 0;">
            <strong>Subject:</strong> <?php echo htmlspecialchars($scheduleDetails['subject_name']); ?> | 
            <strong>Date:</strong> <?php echo formatDate($scheduleDetails['exam_date']); ?> | 
            <strong>Full Marks:</strong> <span style="font-weight:bold; color:var(--danger);"><?php echo (float)$scheduleDetails['full_marks']; ?></span> | 
            <strong>Pass Marks:</strong> <span style="font-weight:bold; color:var(--success);"><?php echo (float)$scheduleDetails['pass_marks']; ?></span>
        </p>
    </div>

    <?php if (empty($students)): ?>
        <div class="empty-state"><p>No active students found in this class.</p></div>
    <?php else: ?>
        <form method="POST" action="<?php echo moduleUrl($_GET['module'] ?? 'exams', 'results'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="save_results">
            <input type="hidden" name="exam_id" value="<?php echo $filterExam; ?>">
            <input type="hidden" name="class_id" value="<?php echo $filterClass; ?>">
            <input type="hidden" name="schedule_id" value="<?php echo $filterSchedule; ?>">

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:10%;">Roll No</th>
                            <th style="width:30%;">Student Name</th>
                            <th style="width:10%;">Absent?</th>
                            <th style="width:20%;">Marks Obtained</th>
                            <th style="width:30%;">Remarks (Optional)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($s['roll_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                            <td style="text-align:center;">
                                <input type="checkbox" name="results[<?php echo $s['student_id']; ?>][is_absent]" value="1" <?php echo $s['is_absent'] ? 'checked' : ''; ?> onchange="toggleMarksInput(this, <?php echo $s['student_id']; ?>)">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" max="<?php echo $scheduleDetails['full_marks']; ?>" 
                                       id="marks_<?php echo $s['student_id']; ?>"
                                       name="results[<?php echo $s['student_id']; ?>][marks_obtained]" 
                                       value="<?php echo htmlspecialchars($s['marks_obtained'] ?? ''); ?>" 
                                       <?php echo $s['is_absent'] ? 'disabled' : ''; ?>
                                       class="form-control" style="width:100%;">
                            </td>
                            <td>
                                <input type="text" name="results[<?php echo $s['student_id']; ?>][remarks]" value="<?php echo htmlspecialchars($s['remarks'] ?? ''); ?>" class="form-control" style="width:100%;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top:20px; text-align:right;">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save All Results</button>
            </div>
        </form>

        <script>
        function toggleMarksInput(checkbox, studentId) {
            const marksInput = document.getElementById('marks_' + studentId);
            if (checkbox.checked) {
                marksInput.disabled = true;
                marksInput.value = ''; // clear value if absent
            } else {
                marksInput.disabled = false;
            }
        }
        </script>
    <?php endif; ?>
<?php endif; ?>
