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
        <button type="button" class="btn btn-warning" onclick="openScheduleModal('importCsvModal')"><i class="fas fa-upload"></i> Import CSV</button>
        <button type="button" class="btn btn-primary" onclick="openScheduleModal('addExamSlotModal')"><i class="fas fa-plus"></i> Add Exam Slot</button>
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
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Schedule</button>
        </div>


        <table class="data-table timetable-matrix" style="width: 100%; border-collapse: collapse; min-width: 800px;" id="matrixTable">
            <thead>
                <tr id="matrixHeader">
                    <th style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; width: 180px; text-align: center; position: sticky; left: 0; z-index: 2;">Date \ Slot</th>
                    <?php foreach ($examSlots as $slotKey => $slot): ?>
                        <th style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; text-align: center; white-space: nowrap;" data-slot="<?php echo htmlspecialchars($slotKey); ?>">
                            <strong><?php echo htmlspecialchars($slot['label']); ?></strong><br>
                            <?php if ($slot['full_marks'] > 0): ?>
                                <small>Marks: <?php echo (float)$slot['full_marks']; ?> (Pass: <?php echo (float)$slot['pass_marks']; ?>)</small><br>
                            <?php else: ?>
                                <small>No Marks</small><br>
                            <?php endif; ?>
                            <div style="margin-top: 5px; display:flex; gap:5px; justify-content:center;">
                                <button type="button" class="btn btn-sm btn-info edit-slot-btn" style="padding: 2px 6px; font-size: 11px;" onclick="openEditSlotModal('<?php echo htmlspecialchars($slotKey); ?>', '<?php echo $slot['start_time']; ?>', '<?php echo $slot['end_time']; ?>', '<?php echo $slot['full_marks']; ?>', '<?php echo $slot['pass_marks']; ?>')" title="Edit Slot"><i class="fas fa-edit"></i> Edit</button>
                                <button type="button" class="btn btn-sm btn-danger remove-slot-btn" style="padding: 2px 6px; font-size: 11px;" onclick="removeSlotColumn('<?php echo htmlspecialchars($slotKey); ?>')" title="Remove Slot"><i class="fas fa-trash"></i> Remove</button>
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
                            <?php $selectedSubjectId = ($date !== "" && isset($matrix[$date][$slotKey])) ? $matrix[$date][$slotKey] : 0; ?>
                            <td style="border: 1px solid #ddd; padding: 12px; text-align: center; vertical-align: middle;" data-slot-col="<?php echo htmlspecialchars($slotKey); ?>">
                                <select name="schedules[<?php echo $rowIndex; ?>][<?php echo htmlspecialchars($slotKey); ?>]" class="form-control" style="width: 100%; min-width: 140px;">
                                    <option value="0">-- Unassigned --</option>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?php echo $s['subject_id']; ?>" <?php echo $selectedSubjectId == $s['subject_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s['subject_name'] . ' (' . $s['subject_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php $rowIndex++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($examSlots)): ?>
            <div id="emptyNotice" style="padding: 20px; text-align: center; color: var(--gray);">
                No exam slots defined yet. Click "Add Exam Slot" to start building the schedule.
            </div>
        <?php endif; ?>
    </form>
</div>

<!-- Add/Edit Exam Slot Modal -->
<div id="examSlotModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2 id="examSlotModalTitle">Add Exam Slot</h2>
            <span class="close" onclick="closeScheduleModal('examSlotModal')">&times;</span>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editingSlotKey" value="">
            <div class="row" style="display:flex; gap:15px;">
                <div class="form-group" style="flex:1;">
                    <label>Start Time *</label>
                    <input type="time" id="slotStartTime" required class="form-control" value="09:00">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>End Time *</label>
                    <input type="time" id="slotEndTime" required class="form-control" value="12:00">
                </div>
            </div>
            
            <div class="row" style="display:flex; gap:15px;">
                <div class="form-group" style="flex:1;">
                    <label>Full Marks (Optional)</label>
                    <input type="number" id="slotFullMarks" min="0" max="1000" step="0.01" class="form-control">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Pass Marks (Optional)</label>
                    <input type="number" id="slotPassMarks" min="0" max="1000" step="0.01" class="form-control">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeScheduleModal('examSlotModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveExamSlot()"><i class="fas fa-save"></i> Save Column</button>
        </div>
    </div>
</div>

<!-- Import CSV Modal -->
<div id="importCsvModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Import Schedules CSV</h2>
            <span class="close" onclick="closeScheduleModal('importCsvModal')">&times;</span>
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
                <button type="button" class="btn btn-secondary" onclick="closeScheduleModal('importCsvModal')">Cancel</button>
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
        let selectHtml = `<select name="schedules[${rowCounter}][${slotKey}]" class="form-control" style="width: 100%; min-width: 140px;">
            <option value="0">-- Unassigned --</option>`;
        
        subjectsData.forEach(sub => {
            selectHtml += `<option value="${sub.subject_id}">${sub.subject_name} (${sub.subject_code})</option>`;
        });
        selectHtml += `</select>`;
        
        html += `<td style="border: 1px solid #ddd; padding: 12px; text-align: center; vertical-align: middle;" data-slot-col="${slotKey}">
                    ${selectHtml}
                 </td>`;
    });
    
    tr.innerHTML = html;
    tbody.appendChild(tr);
    rowCounter++;
}

function removeDateRow(rowId) {
    if (confirm("Remove this date row? Any subjects assigned on this date will be removed when saving.")) {
        const row = document.getElementById(rowId);
        if (row) row.remove();
    }
}

function openScheduleModal(id) {
    if (id === 'addExamSlotModal') {
        document.getElementById('examSlotModalTitle').innerText = 'Add Exam Slot';
        document.getElementById('editingSlotKey').value = '';
        document.getElementById('slotStartTime').value = '09:00';
        document.getElementById('slotEndTime').value = '12:00';
        document.getElementById('slotFullMarks').value = '';
        document.getElementById('slotPassMarks').value = '';
        const modal = document.getElementById('examSlotModal');
        if (modal) modal.style.display = 'flex';
    } else {
        const modal = document.getElementById(id);
        if (modal) modal.style.display = 'flex';
    }
}

function closeScheduleModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'none';
}

function openEditSlotModal(slotKey, startTime, endTime, fullMarks, passMarks) {
    document.getElementById('examSlotModalTitle').innerText = 'Edit Exam Slot';
    document.getElementById('editingSlotKey').value = slotKey;
    
    // Remove seconds from time for input type="time"
    document.getElementById('slotStartTime').value = startTime.substring(0, 5);
    document.getElementById('slotEndTime').value = endTime.substring(0, 5);
    document.getElementById('slotFullMarks').value = (parseFloat(fullMarks) > 0) ? parseFloat(fullMarks) : '';
    document.getElementById('slotPassMarks').value = (parseFloat(passMarks) > 0) ? parseFloat(passMarks) : '';
    document.getElementById('examSlotModal').style.display = 'flex';
}

function saveExamSlot() {
    try {
        const startTime = document.getElementById('slotStartTime').value;
        const endTime = document.getElementById('slotEndTime').value;
        const fullMarks = document.getElementById('slotFullMarks').value || 0;
        const passMarks = document.getElementById('slotPassMarks').value || 0;
        const editingSlotKey = document.getElementById('editingSlotKey').value;

    if (!startTime || !endTime) {
        alert("Please fill Start Time and End Time.");
        return;
    }

    if (startTime >= endTime) {
        alert("End Time must be after Start Time.");
        return;
    }

    const tStart = new Date('1970-01-01T' + startTime + 'Z').toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit', timeZone: 'UTC'});
    const tEnd = new Date('1970-01-01T' + endTime + 'Z').toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit', timeZone: 'UTC'});
    const newSlotKey = startTime + ':00|' + endTime + ':00|' + fullMarks + '|' + passMarks;

    // Check if slot exists (and we aren't editing it)
    if (editingSlotKey !== newSlotKey && document.querySelector(`th[data-slot="${newSlotKey}"]`)) {
        alert("An identical exam slot already exists.");
        return;
    }

    const marksText = (fullMarks > 0) ? `<small>Marks: ${fullMarks} (Pass: ${passMarks})</small><br>` : `<small>No Marks</small><br>`;
    const headerHtml = `
        <strong>${tStart} - ${tEnd}</strong><br>
        ${marksText}
        <div style="margin-top: 5px; display:flex; gap:5px; justify-content:center;">
            <button type="button" class="btn btn-sm btn-info edit-slot-btn" style="padding: 2px 6px; font-size: 11px;" onclick="openEditSlotModal('${newSlotKey}', '${startTime}:00', '${endTime}:00', '${fullMarks}', '${passMarks}')" title="Edit Slot"><i class="fas fa-edit"></i> Edit</button>
            <button type="button" class="btn btn-sm btn-danger remove-slot-btn" style="padding: 2px 6px; font-size: 11px;" onclick="removeSlotColumn('${newSlotKey}')" title="Remove Slot"><i class="fas fa-trash"></i> Remove</button>
        </div>
    `;

    if (editingSlotKey) {
        // Edit existing column
        const th = document.querySelector(`th[data-slot="${editingSlotKey}"]`);
        th.setAttribute('data-slot', newSlotKey);
        th.innerHTML = headerHtml;

        // Update all cells in this column
        const tds = document.querySelectorAll(`td[data-slot-col="${editingSlotKey}"]`);
        tds.forEach(td => {
            td.setAttribute('data-slot-col', newSlotKey);
            const select = td.querySelector('select');
            // name format: schedules[rowIndex][slotKey]
            const currentName = select.getAttribute('name');
            const newName = currentName.replace(`[${editingSlotKey}]`, `[${newSlotKey}]`);
            select.setAttribute('name', newName);
        });
    } else {
        const notice = document.getElementById('emptyNotice');
        if (notice) notice.style.display = 'none';

        const headerRow = document.getElementById('matrixHeader');
        const th = document.createElement('th');
        th.style.cssText = "background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; text-align: center; white-space: nowrap;";
        th.setAttribute('data-slot', newSlotKey);
        th.innerHTML = headerHtml;
        headerRow.appendChild(th);

        const tbody = document.querySelector('#matrixTable tbody');
        Array.from(tbody.children).forEach(tr => {
            const rId = tr.id.replace('row_', '');
            const td = document.createElement('td');
            td.style.cssText = "border: 1px solid #ddd; padding: 12px; text-align: center; vertical-align: middle;";
            td.setAttribute('data-slot-col', newSlotKey);
            
            let selectHtml = `<select name="schedules[${rId}][${newSlotKey}]" class="form-control" style="width: 100%; min-width: 140px;">
                <option value="0">-- Unassigned --</option>`;
            
            subjectsData.forEach(sub => {
                selectHtml += `<option value="${sub.subject_id}">${sub.subject_name} (${sub.subject_code})</option>`;
            });
            selectHtml += `</select>`;
            td.innerHTML = selectHtml;
            tr.appendChild(td);
        });
    }

        closeScheduleModal('examSlotModal');
    } catch (e) {
        alert("An error occurred while adding the column: " + e.message);
    }
}

function removeSlotColumn(slotKey) {
    if (confirm("Are you sure you want to remove this column? This will unassign any subjects in this slot.")) {
        const th = document.querySelector(`th[data-slot="${slotKey}"]`);
        if (th) th.remove();
        const tds = document.querySelectorAll(`td[data-slot-col="${slotKey}"]`);
        tds.forEach(td => td.remove());
    }
}
</script>
