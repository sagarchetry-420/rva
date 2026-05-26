<?php
/**
 * Timetable View (Admin)
 * Variables: $pageTitle, $classes, $selectedClassId, $timetable, $subjects, $days
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-clock"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Manage weekly class timetables</p>
    </div>
</div>

<div class="form-card" style="margin-bottom:20px;">
    <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
        <input type="hidden" name="module" value="admin">
        <input type="hidden" name="action" value="timetable">
        <div class="form-group" style="flex:1; min-width:200px;">
            <label>Select Class</label>
            <select name="class_id" class="form-control" onchange="this.form.submit()" required>
                <option value="">-- Select Class --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?php echo $c['class_id']; ?>" <?php echo $selectedClassId == $c['class_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($selectedClassId): ?>

    <div style="margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <button class="btn btn-primary" onclick="openModal('addColumnModal')"><i class="fas fa-plus"></i> Add Time Column</button>
        </div>
        <div style="display:flex; gap:10px;">
            <form method="POST" action="<?php echo moduleUrl('admin', 'timetable'); ?>" style="display:inline;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="download_template">
                <button type="submit" class="btn btn-info"><i class="fas fa-download"></i> Download CSV Template</button>
            </form>
            <button class="btn btn-warning" onclick="openModal('importCsvModal')"><i class="fas fa-upload"></i> Import CSV</button>
        </div>
    </div>

    <?php if (empty($timetable)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div>
            <p>No timetable entries for this class. <?php echo empty($subjects) ? 'Note: No subjects are assigned to this class yet, so you can only add breaks for now.' : 'Click "Add Time Column" to start.'; ?></p>
        </div>
    <?php else: ?>
        <?php
        // Extract and sort unique time slots
        $timeSlots = [];
        foreach ($timetable as $t) {
            $slotKey = $t['start_time'] . '-' . $t['end_time'];
            if (!isset($timeSlots[$slotKey])) {
                $timeSlots[$slotKey] = [
                    'start' => $t['start_time'],
                    'end' => $t['end_time'],
                    'is_break' => $t['is_break'],
                    'break_name' => $t['break_name'],
                    'time_label' => date('h:i A', strtotime($t['start_time'])) . ' - ' . date('h:i A', strtotime($t['end_time']))
                ];
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

        $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
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
        <?php foreach ($timeSlots as $ts): ?>
            <form id="delete_column_<?php echo md5($ts['start'].$ts['end']); ?>" method="POST" action="<?php echo moduleUrl('admin', 'timetable'); ?>" style="display:none;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="delete_column">
                <input type="hidden" name="class_id" value="<?php echo $selectedClassId; ?>">
                <input type="hidden" name="start_time" value="<?php echo htmlspecialchars($ts['start']); ?>">
                <input type="hidden" name="end_time" value="<?php echo htmlspecialchars($ts['end']); ?>">
            </form>
        <?php endforeach; ?>
        
        <div class="table-container" style="overflow-x: auto;">
            <form method="POST" action="<?php echo moduleUrl('admin', 'timetable'); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="update_timetable">
                <input type="hidden" name="class_id" value="<?php echo $selectedClassId; ?>">
                <div style="margin-bottom: 10px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Timetable</button>
                </div>
                <table class="data-table timetable-matrix" style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <thead>
                        <tr>
                            <th style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; width: 120px; text-align: center; position: sticky; left: 0; z-index: 2;">Day \ Time</th>
                            <?php foreach ($timeSlots as $ts): ?>
                                <th style="background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 12px; text-align: center; white-space: nowrap;">
                                    <strong><?php echo $ts['label']; ?></strong><br>
                                    <small><?php echo $ts['time_label']; ?></small><br>
                                    <div style="margin-top: 5px; display: flex; justify-content: center; gap: 4px;">
                                        <button type="button" class="btn btn-sm btn-warning" style="padding: 2px 6px; font-size: 11px; color: #000;" onclick="openEditColumnModal('<?php echo htmlspecialchars($ts['start']); ?>', '<?php echo htmlspecialchars($ts['end']); ?>', '<?php echo $ts['is_break'] ? 'break' : 'subject'; ?>', '<?php echo htmlspecialchars($ts['break_name'] ?? ''); ?>')" title="Edit Column"><i class="fas fa-edit"></i> Edit</button>
                                        <button type="button" class="btn btn-sm btn-danger" style="padding: 2px 6px; font-size: 11px;" onclick="if(confirm('Delete this entire time column?')) { document.getElementById('delete_column_<?php echo md5($ts['start'].$ts['end']); ?>').submit(); }" title="Delete Column"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
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
                                <?php foreach ($timeSlots as $ts): ?>
                                    <?php 
                                    $key = $ts['start'].'-'.$ts['end'];
                                    $t = $matrix[$day][$key] ?? null; 
                                    ?>
                                    <td style="border: 1px solid #ddd; padding: 12px; text-align: center; vertical-align: middle;">
                                        <?php if ($t): ?>
                                            <?php if (!empty($t['is_break'])): ?>
                                                <span style="display:block; font-weight:bold; color:#555; background:#f3f4f6; padding:6px; border-radius:4px;"><i class="fas fa-coffee" style="margin-right:4px;"></i><?php echo htmlspecialchars($t['break_name']); ?></span>
                                            <?php else: ?>
                                                <select name="timetable_subjects[<?php echo $t['timetable_id']; ?>]" class="form-control" style="width: 100%; min-width: 140px;">
                                                    <option value="0">-- Unassigned --</option>
                                                    <?php foreach ($subjects as $s): ?>
                                                        <option value="<?php echo $s['subject_id']; ?>" <?php echo $t['subject_id'] == $s['subject_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($s['subject_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #e5e7eb;">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
    <?php endif; ?>

    <!-- Add Time Column Modal -->
    <div id="addColumnModal" class="modal">
        <div class="modal-content" style="max-width:500px;">
            <div class="modal-header">
                <h2>Add Time Column (All Days)</h2>
                <span class="close" onclick="closeModal('addColumnModal')">&times;</span>
            </div>
            <form method="POST" action="<?php echo moduleUrl('admin', 'timetable'); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add_column">
                <input type="hidden" name="class_id" value="<?php echo $selectedClassId; ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Slot Type *</label>
                        <select name="slot_type" id="slotType" required class="form-control">
                            <?php if (empty($subjects)): ?>
                                <option value="break">Break</option>
                            <?php else: ?>
                                <option value="subject">Subject</option>
                                <option value="break">Break</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group" id="breakGroup" style="display:none;">
                        <label>Break Name *</label>
                        <input type="text" name="break_name" id="breakName" class="form-control" placeholder="e.g., Lunch Break">
                    </div>
                    <div class="row" style="display:flex; gap:15px;">
                        <div class="form-group" style="flex:1;">
                            <label>Start Time *</label>
                            <input type="time" name="start_time" id="ttStartTime" required class="form-control">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>End Time *</label>
                            <input type="time" name="end_time" id="ttEndTime" required class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addColumnModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Column</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Time Column Modal -->
    <div id="editColumnModal" class="modal">
        <div class="modal-content" style="max-width:500px;">
            <div class="modal-header">
                <h2>Edit Time Column</h2>
                <span class="close" onclick="closeModal('editColumnModal')">&times;</span>
            </div>
            <form method="POST" action="<?php echo moduleUrl('admin', 'timetable'); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="edit_column">
                <input type="hidden" name="class_id" value="<?php echo $selectedClassId; ?>">
                <input type="hidden" name="old_start_time" id="editOldStartTime" value="">
                <input type="hidden" name="old_end_time" id="editOldEndTime" value="">
                <div class="modal-body">
                    <div class="form-group" id="editBreakGroup" style="display:none;">
                        <label>Break Name *</label>
                        <input type="text" name="break_name" id="editBreakName" class="form-control" placeholder="e.g., Lunch Break">
                    </div>
                    <div class="row" style="display:flex; gap:15px;">
                        <div class="form-group" style="flex:1;">
                            <label>Start Time *</label>
                            <input type="time" name="start_time" id="editStartTime" required class="form-control">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>End Time *</label>
                            <input type="time" name="end_time" id="editEndTime" required class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editColumnModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<!-- Import CSV Modal -->
<div id="importCsvModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Import Timetable CSV</h2>
            <span class="close" onclick="closeModal('importCsvModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'timetable'); ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="import_csv">
            <input type="hidden" name="class_id" value="<?php echo $selectedClassId; ?>">
            <div class="modal-body">
                <p style="margin-bottom: 15px; font-size: 0.9em; color: #555;">
                    <i class="fas fa-info-circle text-info"></i> Uploading a CSV will <strong>completely replace</strong> the existing timetable for this class.
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
function openEditColumnModal(start, end, type, breakName) {
    document.getElementById('editOldStartTime').value = start;
    document.getElementById('editOldEndTime').value = end;
    document.getElementById('editStartTime').value = start;
    document.getElementById('editEndTime').value = end;
    
    var breakGroup = document.getElementById('editBreakGroup');
    var breakNameInput = document.getElementById('editBreakName');
    
    if (type === 'break') {
        breakGroup.style.display = 'block';
        breakNameInput.required = true;
        breakNameInput.value = breakName;
    } else {
        breakGroup.style.display = 'none';
        breakNameInput.required = false;
        breakNameInput.value = '';
    }
    
    openModal('editColumnModal');
}

document.addEventListener('DOMContentLoaded', function() {
    var startTime = document.getElementById('ttStartTime');
    var endTime = document.getElementById('ttEndTime');
    if (startTime && endTime) {
        startTime.addEventListener('change', function() {
            endTime.min = this.value;
            if (endTime.value && endTime.value <= this.value) {
                endTime.value = '';
            }
        });
        // Also validate on form submit
        var form = startTime.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (endTime.value && startTime.value && endTime.value <= startTime.value) {
                    e.preventDefault();
                    alert('End Time must be after Start Time.');
                    endTime.focus();
                }
            });
        }
    }

    var slotType = document.getElementById('slotType');
    if (slotType) {
        slotType.addEventListener('change', function() {
            var subjectGroup = document.getElementById('subjectGroup');
            var breakGroup = document.getElementById('breakGroup');
            var subjectId = document.getElementById('subjectId');
            var breakName = document.getElementById('breakName');

            if (this.value === 'break') {
                if (subjectGroup) subjectGroup.style.display = 'none';
                if (subjectId) subjectId.required = false;
                if (breakGroup) breakGroup.style.display = 'block';
                if (breakName) breakName.required = true;
            } else {
                if (subjectGroup) subjectGroup.style.display = 'block';
                if (subjectId) subjectId.required = true;
                if (breakGroup) breakGroup.style.display = 'none';
                if (breakName) breakName.required = false;
            }
        });
        
        // Trigger change to set initial required state
        slotType.dispatchEvent(new Event('change'));
    }
});
</script>
