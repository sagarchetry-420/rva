<?php
/**
 * Student Promotions View
 * Variables: $pageTitle, $session, $classes, $students, $selectedClassId
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-arrow-up"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Promote students from one class to the next</p>
    </div>
</div>

<?php if (!$session): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> No active academic session found.</div>
<?php else: ?>

<div class="form-card" style="margin-bottom:20px;">
    <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
        <input type="hidden" name="module" value="admin">
        <input type="hidden" name="action" value="promotions">
        <div class="form-group" style="flex:1; min-width:200px;">
            <label>Select Source Class</label>
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

<?php 
$selectedClassName = '';
$selectedSection = '';
foreach ($classes as $c) {
    if ($c['class_id'] == $selectedClassId) {
        $selectedClassName = $c['class_name'];
        $selectedSection = isset($c['section']) ? trim($c['section']) : '';
        break;
    }
}
$isHighestClass = strpos(strtolower($selectedClassName), '12') !== false;
?>

<?php if ($selectedClassId && !empty($students)): ?>
    <div class="form-card">
        <form method="POST" action="<?php echo moduleUrl('admin', 'promotions'); ?>">
            <?php echo csrf_field(); ?>
            <?php if ($isHighestClass): ?>
                <input type="hidden" name="action" value="pass_out">
            <?php else: ?>
                <input type="hidden" name="action" value="promote">
            <?php endif; ?>
            <input type="hidden" name="source_class_id" value="<?php echo $selectedClassId; ?>">

            <?php if (!$isHighestClass): ?>
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Target Class (Promote To) *</label>
                    <select name="target_class_id" required class="form-control" style="max-width:300px;">
                        <option value="">-- Select Target Class --</option>
                        <?php 
                        $currentLevel = 0;
                        if (preg_match('/\d+/', $selectedClassName, $matches)) {
                            $currentLevel = (int)$matches[0];
                        }
                        $nextLevel = $currentLevel + 1;
                        
                        // Helper to extract stream keywords (like 'arts', 'science', 'commerce') 
                        // ignoring 'class', numbers, and single letters ('A', 'B', etc.)
                        $getStreamKeywords = function($className, $section) {
                            $text = strtolower($className . ' ' . $section);
                            $words = preg_split('/[\s\W]+/', $text);
                            $keywords = [];
                            foreach ($words as $w) {
                                if (!is_numeric($w) && $w !== 'class' && $w !== '' && strlen($w) > 1) {
                                    $keywords[] = $w;
                                }
                            }
                            return $keywords;
                        };
                        $sourceKeywords = $getStreamKeywords($selectedClassName, $selectedSection);
                        ?>
                        <?php foreach ($classes as $c): ?>
                            <?php 
                            $cLevel = 0;
                            if (preg_match('/\d+/', $c['class_name'], $matches)) {
                                $cLevel = (int)$matches[0];
                            }
                            
                            $isValidTarget = false;
                            if ($currentLevel > 0) {
                                $isValidTarget = ($cLevel === $nextLevel);
                                
                                // Enforce stream matching when promoting from Class 11 to 12
                                if ($currentLevel == 11 && $isValidTarget) {
                                    $targetSection = isset($c['section']) ? trim($c['section']) : '';
                                    $targetKeywords = $getStreamKeywords($c['class_name'], $targetSection);
                                    
                                    // If the source has a stream (like 'arts'), the target MUST have the same stream
                                    if (!empty($sourceKeywords)) {
                                        $hasMatchingStream = false;
                                        foreach ($sourceKeywords as $sk) {
                                            if (in_array($sk, $targetKeywords)) {
                                                $hasMatchingStream = true;
                                                break;
                                            }
                                        }
                                        if (!$hasMatchingStream) {
                                            $isValidTarget = false;
                                        }
                                    }
                                }
                            } else {
                                $isValidTarget = ($c['class_id'] != $selectedClassId);
                            }
                            
                            if ($isValidTarget): 
                            ?>
                                <option value="<?php echo $c['class_id']; ?>">
                                    <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="alert alert-info" style="margin-bottom:15px; max-width:500px;">
                    <i class="fas fa-info-circle"></i> This is the highest class. Students will be marked as "Passed Out".
                </div>
            <?php endif; ?>

            <label style="display:block; margin-bottom:10px; font-weight:bold; color:var(--primary); cursor:pointer;">
                <input type="checkbox" id="selectAllStudents" onclick="document.querySelectorAll('.student-checkbox:not([disabled])').forEach(cb => cb.checked = this.checked)"> Select All Students
            </label>

            <div class="table-container">
                <div class="table-header" style="padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="display: flex; align-items: center; margin: 0; font-size: 18px; color: var(--text-dark);">Students <span style="background-color: #800000; color: white; border-radius: 16px; min-width: 28px; height: 28px; padding: 0 8px; font-size: 15px; font-weight: 600; margin-left: 10px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(128,0,0,0.25);"><?php echo $pagination['total'] ?? count($students); ?></span></h2>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" onclick="document.querySelectorAll('.student-checkbox:not([disabled])').forEach(cb => cb.checked = this.checked)"></th>
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Final Exam Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><input type="checkbox" class="student-checkbox" name="student_ids[]" value="<?php echo $s['student_id']; ?>" <?php echo empty($s['can_promote']) ? 'disabled title="Cannot be promoted until passing Final Exam"' : ''; ?>></td>
                                <td><?php echo htmlspecialchars($s['roll_number']); ?></td>
                                <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($s['class_name'] . ' ' . $s['section']); ?></td>
                                <td>
                                    <?php if (!empty($s['can_promote'])): ?>
                                        <span class="badge" style="background:var(--success); color:white; padding:3px 8px; border-radius:4px; font-size:0.85em;"><?php echo htmlspecialchars($s['final_status']); ?></span>
                                    <?php else: ?>
                                        <span class="badge" style="background:var(--danger); color:white; padding:3px 8px; border-radius:4px; font-size:0.85em;"><?php echo htmlspecialchars($s['final_status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (isset($pagination) && $pagination['total'] > 0): ?>
                <div style="margin-top:15px;" class="no-print" data-html2canvas-ignore="true">
                    <?php echo renderPagination($pagination); ?>
                    <div style="text-align: center; margin-top: 10px; color: var(--gray); font-size: 13px;">
                        Showing page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['pages']; ?> (Total: <?php echo $pagination['total']; ?> students)
                    </div>
                </div>
            <?php endif; ?>

            <div style="margin-top:20px; text-align:right;">
                <?php if ($isHighestClass): ?>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to pass out the selected students?')">
                        <i class="fas fa-graduation-cap"></i> Pass Out Selected Students
                    </button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to promote the selected students?')">
                        <i class="fas fa-level-up-alt"></i> Promote Selected Students
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
<?php elseif ($selectedClassId): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-users-slash"></i></div>
        <p>No students found in this class for the current session.</p>
    </div>
<?php endif; ?>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sourceSelect = document.querySelector('select[name="class_id"]');
    const targetSelect = document.querySelector('select[name="target_class_id"]');
    
    if (sourceSelect && targetSelect && sourceSelect.value !== "") {
        const sourceName = sourceSelect.options[sourceSelect.selectedIndex].text.trim();
        const numMatch = sourceName.match(/\d+/);
        
        if (numMatch) {
            const currentLevel = parseInt(numMatch[0]);
            const nextLevel = currentLevel + 1;
            const targetPattern = new RegExp("\\b" + nextLevel + "\\b");
            const sectionMatch = sourceName.match(/\b([A-Z])\b/);
            const section = sectionMatch ? sectionMatch[1] : "";
            
            for (let i = 0; i < targetSelect.options.length; i++) {
                let optText = targetSelect.options[i].text;
                if (targetPattern.test(optText)) {
                    if (section && optText.includes(section)) {
                        targetSelect.selectedIndex = i;
                        break;
                    } else if (!section) {
                        targetSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        }
    }
});
</script>
