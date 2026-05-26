<?php
/**
 * Student Results View — Exam Listing
 * Variables: $student, $sessions, $exams, $results, $selectedSessionId, $selectedExamId
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-file-pen"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Your examination results</p>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="index.php" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; width: 100%;">
        <input type="hidden" name="module" value="student">
        <input type="hidden" name="action" value="results">
        
        <div class="filter-group" style="flex: 1; min-width: 200px;">
            <label for="session_id">Academic Session</label>
            <select name="session_id" id="session_id" class="form-control" onchange="this.form.submit()">
                <?php if (empty($sessions)): ?>
                    <option value="">No Sessions Available</option>
                <?php else: ?>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?php echo $s['session_id']; ?>" <?php echo $selectedSessionId == $s['session_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['session_name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </form>
</div>

<?php if (empty($exams)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-file-circle-xmark"></i></div>
        <p>No results have been published yet for this session.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Exam Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($exams as $e): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><strong><i class="fas fa-award" style="color: var(--primary-color); margin-right: 5px;"></i><?php echo htmlspecialchars($e['exam_name']); ?></strong></td>
                        <td>
                            <a href="<?php echo moduleUrl('student', 'view_result'); ?>?exam_id=<?php echo $e['exam_id']; ?>&session_id=<?php echo $selectedSessionId; ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View Result
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
