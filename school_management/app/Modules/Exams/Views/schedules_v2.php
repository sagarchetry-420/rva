<?php
/**
 * Exam Schedules View (Admin) Matrix Version
 * Variables: $exam, $class, $schedules, $subjects, $pageTitle, $examSlots, $matrix, $dates
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <label style="font-weight: bold; margin: 0; color: var(--primary);">Scheduling for Class:</label>
            <select class="form-control" style="width: auto; display: inline-block; padding: 6px; font-weight: bold;" onchange="window.location.href="<?php echo moduleUrl('admin', 'schedules'); ?>?exam_id=<?php echo $exam['exam_id']; ?>&class_id='+this.value">
                <?php foreach ($exam['classes'] as $c): ?>
                    <option value="<?php echo $c['class_id']; ?>" <?php echo $c['class_id'] == $class['class_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <p>Define subject timetables and marks structure</p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="<?php echo moduleUrl('admin', 'examinations'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Exams</a>
        <form method="POST" action="<?php echo moduleUrl('admin', 'schedules'); ?>" style="display:inline;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
            <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
            <input type="hidden" name="action" value="download_exam_details">
            <button type="submit" class="btn btn-success"><i class="fas fa-file-excel"></i> Exam Details</button>
        </form>
        <form method="POST" action="<?php echo moduleUrl('admin', 'schedules'); ?>" style="display:inline;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="download_schedules_template">
            <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
            <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
            <button type="submit" class="btn btn-info"><i class="fas fa-download"></i> CSV Template</button>
        </form>
        <button type="button" class="btn btn-warning" onclick="openModal('importCsvModal')"><i class="fas fa-upload"></i> Import CSV</button>
        <button type="button" class="btn btn-primary" onclick="openAddSlotModal()"><i class="fas fa-plus"></i> Add Time Column</button>
        <button type="button" class="btn btn-info" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    </div>
</div>

<style>
@media print {
    .sidebar, .top-nav, .btn, .actions-cell, .modal, .delete-row-btn, .edit-slot-btn, .remove-slot-btn, #addDateRowBtn { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .page-header p { display: none; }
    input[type="date"], select { border: none; appearance: none; -webkit-appearance: none; -moz-appearance: none; background: transparent; }
}
</style>

<div class="table-container" style="overflow-x: auto;">
    <form method="POST" action="<?php echo moduleUrl('admin', 'schedules'); ?>" id="scheduleForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="update_schedules">
        <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
        <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
        
        <div style="margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" class="btn btn-secondary" id="addDateRowBtn" onclick="addDateRow()"><i class="fas fa-plus"></i> Add Date Row</button>
            <button type="submit" class="btn btn-success" id="saveScheduleBtn"><i class="fas fa-save"></i> Save Schedule</button>
        </div>


        <table class="data-table timetable-matrix" style="width: 100%; border-collapse: collapse; min-width: 800px;" id="matrixTable">
            <thead>
                <tr id="matrixHeader">
                    <th style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; width: 180px; text-align: center; position: sticky; left: 0; z-index: 2;">Date \ Slot</th>
                    <?php foreach ($examSlots as $slotKey => $slot): ?>
                        <th style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; text-align: center; white-space: nowrap;" data-slot="<?php echo htmlspecialchars($slotKey); ?>">
                            <strong><?php echo htmlspecialchars($slot['label']); ?></strong><br>
                            <div style="margin-top: 5px; display:flex; gap:5px; justify-content:center;">
                                <button type="button" class="btn btn-sm btn-info edit-slot-btn" style="padding: 2px 6px; font-size: 11px;" onclick="openEditSlotModal('<?php echo htmlspecialchars($slotKey); ?>', '<?php echo $slot['start_time']; ?>', '<?php echo $slot['end_time']; ?>')" title="Edit Slot"><i class="fas fa-edit"></i> Edit</button>
                                <button type="button" class="btn btn-sm btn-danger remove-slot-btn" style="padding: 2px 6px; font-size: 11px;" onclick="removeSlotColumn('<?php echo htmlspecialchars($slotKey); ?>', '<?php echo $slot['start_time']; ?>', '<?php echo $slot['end_time']; ?>')" title="Remove Slot"><i class="fas fa-trash"></i> Remove</button>
                            </div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="matrixBody">
                <?php $rowIndex = 0; ?>
                <?php foreach ($dates as $date): ?>
                    <tr id="row_<?php echo $rowIndex; ?>">
                        <td style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; text-align: center; position: sticky; left: 0; z-index: 1;">
                            <div style="display:flex; align-items:center; gap:5px;">
                                <input type="date" name="row_dates[<?php echo $rowIndex; ?>]" value="<?php echo htmlspecialchars($date); ?>" min="<?php echo $exam['start_date']; ?>" max="<?php echo $exam['end_date']; ?>" class="form-control" required style="width: 100%;">
                                <button type="button" class="btn btn-sm btn-danger delete-row-btn" onclick="removeDateRow('row_<?php echo $rowIndex; ?>')" title="Remove Row"><i class="fas fa-trash"></i></button>
                            </div>
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
                            ?>
                            <td style="border: 1px solid var(--border); padding: 10px; text-align: center; vertical-align: top; background: <?php echo $selectedSubjectId > 0 ? '#f0fdf4' : '#fff'; ?>; transition: background 0.3s;" data-slot-col="<?php echo htmlspecialchars($slotKey); ?>">
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <select name="schedules[<?php echo $rowIndex; ?>][<?php echo htmlspecialchars($slotKey); ?>][subject_id]" class="form-control" style="width: 100%; min-width: 220px; font-weight: <?php echo $selectedSubjectId > 0 ? '600' : 'normal'; ?>; border: 1px solid <?php echo $selectedSubjectId > 0 ? '#86efac' : '#cbd5e1'; ?>;" onchange="this.parentElement.parentElement.style.background = this.value !== '0' ? '#f0fdf4' : '#fff'; this.style.fontWeight = this.value !== '0' ? '600' : 'normal'; this.style.borderColor = this.value !== '0' ? '#86efac' : '#cbd5e1';">
                                        <option value="0">-- Unassigned --</option>
                                        <?php foreach ($subjects as $s): ?>
                                            <option value="<?php echo $s['subject_id']; ?>" <?php echo $selectedSubjectId == $s['subject_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($s['subject_name'] . ' (' . $s['subject_code'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div style="display:flex; gap:6px; align-items:center;">
                                        <div style="flex:1; display:flex; align-items:center; border:1px solid #e2e8f0; border-radius:4px; overflow:hidden; background:#fff;">
                                            <span style="font-size:10px; background:#f8fafc; padding:4px 6px; color:#64748b; border-right:1px solid #e2e8f0; font-weight:600;">Full</span>
                                            <input type="number" name="schedules[<?php echo $rowIndex; ?>][<?php echo htmlspecialchars($slotKey); ?>][full_marks]" class="mark-input" style="width:100%; border:none; padding:4px; font-size:11px; text-align:center; outline:none;" value="<?php echo $selectedFullMarks; ?>" min="0" step="0.01">
                                        </div>
                                        <div style="flex:1; display:flex; align-items:center; border:1px solid #e2e8f0; border-radius:4px; overflow:hidden; background:#fff;">
                                            <span style="font-size:10px; background:#f8fafc; padding:4px 6px; color:#64748b; border-right:1px solid #e2e8f0; font-weight:600;">Pass</span>
                                            <input type="number" name="schedules[<?php echo $rowIndex; ?>][<?php echo htmlspecialchars($slotKey); ?>][pass_marks]" class="mark-input" style="width:100%; border:none; padding:4px; font-size:11px; text-align:center; outline:none;" value="<?php echo $selectedPassMarks; ?>" min="0" step="0.01">
                                        </div>
                                    </div>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php $rowIndex++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($examSlots)): ?>
            <div id="emptyNotice" style="padding: 20px; text-align: center; color: var(--gray);">
                No exam slots defined yet. Click "Add Time Column" to start building the schedule.
            </div>
        <?php endif; ?>
    </form>
</div>

<!-- Add/Edit Exam Slot Modal -->
<div id="examSlotModal" class="modal">
    <div class="modal-content" style="max-width: 450px; border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
        <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; padding: 20px; border-bottom: none;">
            <h2 id="examSlotModalTitle" style="margin: 0; font-size: 1.25rem; display: flex; align-items: center; gap: 10px;">
                <div style="background: rgba(255,255,255,0.2); width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                    <i class="fas fa-clock"></i>
                </div>
                <span>Add Time Slot</span>
            </h2>
            <span class="close" onclick="closeModal('examSlotModal')" style="color: white; opacity: 0.8; text-shadow: none;">&times;</span>
        </div>
        <form id="slotForm" method="POST" action="<?php echo moduleUrl('admin', 'schedules'); ?>" onsubmit="return validateSlotForm(this);">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" id="slotAction" value="add_exam_slot">
            <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
            <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
            <input type="hidden" name="old_start_time" id="slotOldStartTime" value="">
            <input type="hidden" name="old_end_time" id="slotOldEndTime" value="">

            <div class="modal-body" style="padding: 25px;">
                <p style="color: var(--gray); font-size: 0.9rem; margin-bottom: 20px;">Define a new time column for your exam schedule. This slot will be applied across all dates.</p>
                <div class="row" style="display:flex; gap:20px; margin-bottom: 5px;">
                    <div class="form-group" style="flex:1;">
                        <label style="font-weight: 600; color: var(--text-color); margin-bottom: 8px;">Start Time *</label>
                        <div style="position: relative;">
                            <input type="time" name="start_time" id="slotStartTime" required class="form-control" value="09:00" style="padding-left: 35px; border-radius: 8px;">
                            <i class="fas fa-hourglass-start" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray);"></i>
                        </div>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label style="font-weight: 600; color: var(--text-color); margin-bottom: 8px;">End Time *</label>
                        <div style="position: relative;">
                            <input type="time" name="end_time" id="slotEndTime" required class="form-control" value="12:00" style="padding-left: 35px; border-radius: 8px;">
                            <i class="fas fa-hourglass-end" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray);"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 15px 25px; background: #f8f9fa; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('examSlotModal')" style="border-radius: 6px; padding: 10px 20px;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 6px; padding: 10px 20px;"><i class="fas fa-check-circle"></i> Save Slot</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Slot Form -->
<form id="deleteSlotForm" method="POST" action="<?php echo moduleUrl('admin', 'schedules'); ?>" style="display:none;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="delete_exam_slot">
    <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
    <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
    <input type="hidden" name="start_time" id="deleteSlotStartTime" value="">
    <input type="hidden" name="end_time" id="deleteSlotEndTime" value="">
</form>

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
const subjectsData = <?php echo json_encode($subjects ?? []) ?: '[]'; ?>;
let rowCounter = <?php echo $rowIndex ?? 0; ?>;

function addDateRow() {
    const tbody = document.getElementById('matrixBody');
    const tr = document.createElement('tr');
    tr.id = 'row_' + rowCounter;
    
    // Create Date Cell
    let html = `
        <td style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; text-align: center; position: sticky; left: 0; z-index: 1;">
            <div style="display:flex; align-items:center; gap:5px;">
                <input type="date" name="row_dates[${rowCounter}]" min="<?php echo $exam['start_date']; ?>" max="<?php echo $exam['end_date']; ?>" class="form-control" required style="width: 100%;">
                <button type="button" class="btn btn-sm btn-danger delete-row-btn" onclick="removeDateRow('row_${rowCounter}')" title="Remove Row"><i class="fas fa-trash"></i></button>
            </div>
        </td>
    `;
    
    // Create Select Cells for existing slots
    const headerRow = document.getElementById('matrixHeader');
    const ths = headerRow.querySelectorAll('th[data-slot]');
    
    ths.forEach(th => {
        const slotKey = th.getAttribute('data-slot');
        let selectHtml = `<select name="schedules[${rowCounter}][${slotKey}][subject_id]" class="form-control" style="width: 100%; min-width: 220px; font-weight: normal; border: 1px solid #cbd5e1;" onchange="this.parentElement.parentElement.style.background = this.value !== '0' ? '#f0fdf4' : '#fff'; this.style.fontWeight = this.value !== '0' ? '600' : 'normal'; this.style.borderColor = this.value !== '0' ? '#86efac' : '#cbd5e1';">
            <option value="0">-- Unassigned --</option>`;
        
        subjectsData.forEach(sub => {
            selectHtml += `<option value="${sub.subject_id}">${sub.subject_name} (${sub.subject_code})</option>`;
        });
        selectHtml += `</select>
        <div style="display:flex; gap:6px; align-items:center;">
            <div style="flex:1; display:flex; align-items:center; border:1px solid #e2e8f0; border-radius:4px; overflow:hidden; background:#fff;">
                <span style="font-size:10px; background:#f8fafc; padding:4px 6px; color:#64748b; border-right:1px solid #e2e8f0; font-weight:600;">Full</span>
                <input type="number" name="schedules[${rowCounter}][${slotKey}][full_marks]" class="mark-input" style="width:100%; border:none; padding:4px; font-size:11px; text-align:center; outline:none;" value="100" min="0" step="0.01">
            </div>
            <div style="flex:1; display:flex; align-items:center; border:1px solid #e2e8f0; border-radius:4px; overflow:hidden; background:#fff;">
                <span style="font-size:10px; background:#f8fafc; padding:4px 6px; color:#64748b; border-right:1px solid #e2e8f0; font-weight:600;">Pass</span>
                <input type="number" name="schedules[${rowCounter}][${slotKey}][pass_marks]" class="mark-input" style="width:100%; border:none; padding:4px; font-size:11px; text-align:center; outline:none;" value="35" min="0" step="0.01">
            </div>
        </div>`;
        
        html += `<td style="border: 1px solid var(--border); padding: 10px; text-align: center; vertical-align: top; background: #fff; transition: background 0.3s;" data-slot-col="${slotKey}">
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        ${selectHtml}
                    </div>
                 </td>`;
    });
    
    tr.innerHTML = html;
    tbody.appendChild(tr);
    rowCounter++;
    checkFormCompleteness();
}

function removeDateRow(rowId) {
    if (confirm("Remove this date row? Any subjects assigned on this date will be removed when saving.")) {
        const row = document.getElementById(rowId);
        if (row) row.remove();
        checkFormCompleteness();
    }
}

function validateSlotForm(form) {
    const newStart = form.start_time.value;
    const newEnd = form.end_time.value;
    const editingSlotKey = document.getElementById('slotAction').value === 'edit_exam_slot' 
        ? document.getElementById('slotOldStartTime').value.substring(0,5) + '|' + document.getElementById('slotOldEndTime').value.substring(0,5) 
        : null;

    if (newStart >= newEnd) {
        alert("Start Time must be before End Time.");
        return false;
    }

    // Check existing columns for overlap
    const headers = document.querySelectorAll('th[data-slot]');
    for (let i = 0; i < headers.length; i++) {
        const slotKey = headers[i].getAttribute('data-slot');
        // If editing, skip the slot being edited
        if (editingSlotKey && slotKey.startsWith(editingSlotKey)) {
            continue;
        }

        const parts = slotKey.split('|');
        if (parts.length >= 2) {
            const existStart = parts[0].substring(0,5);
            const existEnd = parts[1].substring(0,5);

            if (newStart < existEnd && newEnd > existStart) {
                alert("This time overlaps with an existing column: " + existStart + " - " + existEnd);
                return false;
            }
        }
    }
    return true;
}

function openAddSlotModal() {
    document.getElementById('examSlotModalTitle').innerText = 'Add Exam Slot';
    document.getElementById('slotAction').value = 'add_exam_slot';
    document.getElementById('slotOldStartTime').value = '';
    document.getElementById('slotOldEndTime').value = '';
    document.getElementById('slotStartTime').value = '09:00';
    document.getElementById('slotEndTime').value = '12:00';
    openModal('examSlotModal');
}

function openEditSlotModal(slotKey, startTime, endTime) {
    document.getElementById('examSlotModalTitle').innerText = 'Edit Exam Slot';
    document.getElementById('slotAction').value = 'edit_exam_slot';
    
    // Set hidden fields to track the old slot
    document.getElementById('slotOldStartTime').value = startTime;
    document.getElementById('slotOldEndTime').value = endTime;

    // Remove seconds from time for input type="time"
    document.getElementById('slotStartTime').value = startTime.substring(0, 5);
    document.getElementById('slotEndTime').value = endTime.substring(0, 5);
    openModal('examSlotModal');
}

function removeSlotColumn(slotKey, startTime, endTime) {
    if (confirm("Are you sure you want to remove this column? This will permanently delete the column and unassign any subjects in this slot.")) {
        document.getElementById('deleteSlotStartTime').value = startTime;
        document.getElementById('deleteSlotEndTime').value = endTime;
        document.getElementById('deleteSlotForm').submit();
    }
}

function checkFormCompleteness() {
    const saveBtn = document.getElementById('saveScheduleBtn');
    if (!saveBtn) return;
    
    let isComplete = true;
    
    // Check all date inputs
    const dateInputs = document.querySelectorAll('#matrixBody input[type="date"]');
    dateInputs.forEach(dateInput => {
        if (!dateInput.value) {
            isComplete = false;
        }
    });

    // Check all selects and their corresponding marks
    const selects = document.querySelectorAll('#matrixBody select');
    selects.forEach(select => {
        if (select.value === '0') {
            isComplete = false; // Cannot have unassigned subjects
        } else {
            const cell = select.closest('td');
            const marks = cell.querySelectorAll('.mark-input');
            marks.forEach(mark => {
                if (!mark.value || parseFloat(mark.value) <= 0) {
                    isComplete = false;
                }
            });
        }
    });

    saveBtn.disabled = !isComplete;
    if (!isComplete) {
        saveBtn.style.opacity = '0.5';
        saveBtn.style.cursor = 'not-allowed';
    } else {
        saveBtn.style.opacity = '1';
        saveBtn.style.cursor = 'pointer';
    }
}

// Bind the validation to form changes
document.getElementById('scheduleForm').addEventListener('input', checkFormCompleteness);
document.getElementById('scheduleForm').addEventListener('change', checkFormCompleteness);

// Run on page load
window.addEventListener('DOMContentLoaded', checkFormCompleteness);

</script>
