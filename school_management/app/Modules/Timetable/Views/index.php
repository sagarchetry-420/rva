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

<style>
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

<?php
$mode = isset($_GET['mode']) && $_GET['mode'] === 'teacher' ? 'teacher' : 'class';
?>
<div class="tabs" style="margin-bottom: 20px; display: flex; gap: 2px; border-bottom: 2px solid #ddd;">
    <a href="?module=admin&action=timetable&mode=class" style="padding: 10px 20px; text-decoration: none; font-weight: 600; color: <?php echo $mode === 'class' ? '#245e54' : '#666'; ?>; border-bottom: <?php echo $mode === 'class' ? '3px solid #245e54' : '3px solid transparent'; ?>; margin-bottom: -2px;">
        <i class="fas fa-users-rectangle"></i> Class Timetable
    </a>
    <a href="?module=admin&action=timetable&mode=teacher" style="padding: 10px 20px; text-decoration: none; font-weight: 600; color: <?php echo $mode === 'teacher' ? '#245e54' : '#666'; ?>; border-bottom: <?php echo $mode === 'teacher' ? '3px solid #245e54' : '3px solid transparent'; ?>; margin-bottom: -2px;">
        <i class="fas fa-user-tie"></i> Teacher Timetable
    </a>
</div>

<?php if ($mode === 'class'): ?>

<div class="form-card" style="margin-bottom:20px;">
    <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
        <input type="hidden" name="module" value="admin">
        <input type="hidden" name="action" value="timetable">
        <input type="hidden" name="mode" value="class">
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

    <div style="margin-bottom:15px; display:flex; justify-content:flex-end; align-items:center;">
        <div style="display:flex; gap:10px;">
            <form method="POST" action="<?php echo moduleUrl('admin', 'timetable'); ?>" style="display:inline;" class="no-auto-validate" onsubmit="return confirm('Are you sure you want to clone the timetable from the previous session? This will REPLACE any existing timetable for this class in the current session.');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="clone_previous">
                <input type="hidden" name="class_id" value="<?php echo $selectedClassId; ?>">
                <button type="submit" class="btn btn-secondary" style="background-color: #245e54; border: none; color: white; padding: 8px 15px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-copy"></i> Clone from Previous Session</button>
            </form>
            <form method="POST" action="<?php echo moduleUrl('admin', 'timetable'); ?>" style="display:inline;" class="no-auto-validate">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="download_template">
                <input type="hidden" name="class_id" value="<?php echo $selectedClassId; ?>">
                <button type="submit" class="btn btn-info"><i class="fas fa-download"></i> Download CSV Template</button>
            </form>
            <button class="btn btn-warning" onclick="openModal('importCsvModal')"><i class="fas fa-upload"></i> Import CSV</button>
        </div>
    </div>

    <?php if (empty($timetable)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div>
            <p>No timetable entries for this class. Use "Import CSV" to set up the timetable.</p>
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
        <div class="table-container modern-timetable-wrapper" style="overflow-x: auto;">
            <table class="timetable-modern">
                <thead>
                    <tr>
                        <th style="width: 120px;">Time</th>
                        <?php foreach ($dayOrder as $day): ?>
                            <th><?php echo htmlspecialchars($day); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timeSlots as $index => $ts): ?>
                        <tr>
                            <td class="time-col">
                                <?php echo date('h:i A', strtotime($ts['start'])) . '<br>-<br>' . date('h:i A', strtotime($ts['end'])); ?>
                            </td>
                            <?php foreach ($dayOrder as $day): ?>
                                <?php if ($day === 'Sunday'): ?>
                                    <?php if ($index === 0): ?>
                                        <td rowspan="<?php echo count($timeSlots); ?>" style="background-color: #fdfbfb; color: #d1d5db; font-weight: 700; letter-spacing: 8px; vertical-align: middle; text-align: center; border-radius: 6px; border: 2px dashed #f3f4f6;">
                                            <div style="writing-mode: vertical-rl; transform: rotate(180deg); margin: auto; padding: 10px;">HOLIDAY</div>
                                        </td>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php 
                                    $key = $ts['start'].'-'.$ts['end'];
                                    $t = $matrix[$day][$key] ?? null; 
                                    ?>
                                    <td class="subject-cell">
                                        <?php if ($t): ?>
                                            <?php if (!empty($t['is_break'])): ?>
                                                <span style="color:#9ca3af;"><i class="fas fa-coffee"></i> <?php echo htmlspecialchars($t['break_name']); ?></span>
                                            <?php else: ?>
                                                <span style="color:#374151;">
                                                    <?php echo htmlspecialchars($t['subject_name'] ?? 'Unassigned'); ?>
                                                </span>
                                            <?php endif; ?>
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
<?php endif; ?>

<?php elseif ($mode === 'teacher'): ?>

    <div class="form-card" style="margin-bottom:20px;">
        <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="module" value="admin">
            <input type="hidden" name="action" value="timetable">
            <input type="hidden" name="mode" value="teacher">
            <div class="form-group" style="flex:1; min-width:200px;">
                <label>Select Teacher</label>
                <select name="teacher_id" class="form-control" onchange="this.form.submit()" required>
                    <option value="">-- Select Teacher --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo $t['teacher_id']; ?>" <?php echo $selectedTeacherId == $t['teacher_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($selectedTeacherId): ?>
        <?php if (empty($teacherTimetable)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div>
                <p>No timetable entries found for this teacher.</p>
            </div>
        <?php else: ?>
            <?php
            $timeSlots = [];
            foreach ($teacherTimetable as $t) {
                $slotKey = $t['start_time'] . '-' . $t['end_time'];
                if (!isset($timeSlots[$slotKey])) {
                    $timeSlots[$slotKey] = [
                        'start' => $t['start_time'],
                        'end' => $t['end_time']
                    ];
                }
            }
            usort($timeSlots, function($a, $b) {
                return strcmp($a['start'], $b['start']);
            });

            $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $matrix = [];
            foreach ($dayOrder as $day) {
                $matrix[$day] = [];
                foreach ($teacherTimetable as $t) {
                    if ($t['day_of_week'] === $day) {
                        $matrix[$day][$t['start_time'] . '-' . $t['end_time']] = $t;
                    }
                }
            }
            ?>
            <div class="table-container modern-timetable-wrapper" style="overflow-x: auto;">
                <table class="timetable-modern">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Time</th>
                            <?php foreach ($dayOrder as $day): ?>
                                <th><?php echo htmlspecialchars($day); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeSlots as $ts): ?>
                            <tr>
                                <td class="time-col">
                                    <?php echo date('h:i A', strtotime($ts['start'])) . '<br>-<br>' . date('h:i A', strtotime($ts['end'])); ?>
                                </td>
                                <?php foreach ($dayOrder as $day): ?>
                                    <?php 
                                    $key = $ts['start'].'-'.$ts['end'];
                                    $t = $matrix[$day][$key] ?? null; 
                                    ?>
                                    <td class="subject-cell" style="<?php echo $t ? 'background-color: #e2f0e9;' : ''; ?>">
                                        <?php if ($t): ?>
                                            <span style="color:#245e54; font-weight: 700; display: block; margin-bottom: 4px;">
                                                <?php echo htmlspecialchars($t['subject_name']); ?>
                                            </span>
                                            <span style="color:#666; font-size: 12px; background: #fff; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd8cf;">
                                                <?php echo htmlspecialchars($t['class_name'] . ' ' . $t['section']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#ccc;">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>

<?php endif; ?>

<!-- Import CSV Modal -->
<div id="importCsvModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Import Timetable CSV</h2>
            <span class="close" onclick="closeModal('importCsvModal')">&times;</span>
        </div>
        <form id="importCsvForm" method="POST" action="<?php echo moduleUrl('admin', 'timetable'); ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="import_csv">
            <input type="hidden" name="class_id" value="<?php echo $selectedClassId; ?>">
            <div class="modal-body">
                <p style="margin-bottom: 15px; font-size: 0.9em; color: #555;">
                    <i class="fas fa-info-circle text-info"></i> Uploading a CSV will <strong>completely replace</strong> the existing timetable for this class.
                </p>
                <div class="form-group">
                    <label>Select CSV File *</label>
                    <input type="file" id="csv_file_input" name="csv_file" accept=".csv" required class="form-control" style="padding: 5px;">
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
// Import CSV modal functionality handles opening via standard openModal()

document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('importCsvForm');
    const fileInput = document.getElementById('csv_file_input');
    
    if (importForm && fileInput) {
        importForm.addEventListener('submit', function(e) {
            const file = fileInput.files[0];
            if (file) {
                // Validate Extension
                if (!file.name.toLowerCase().endsWith('.csv')) {
                    e.preventDefault();
                    alert('Security Error: Please select a valid .csv file.');
                    return false;
                }
                
                // Validate Size (2MB limit)
                if (file.size > 2 * 1024 * 1024) {
                    e.preventDefault();
                    alert('Error: File size must be less than 2MB.');
                    return false;
                }
            }
        });
    }
});
</script>
