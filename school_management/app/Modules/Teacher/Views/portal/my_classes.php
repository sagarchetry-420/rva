<?php
/**
 * My Classes View — Shows all classes and subjects assigned to this teacher
 * Variables: $teacher, $assignments, $session
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-book-open"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Classes and subjects assigned to you for <?php echo htmlspecialchars($session['session_name'] ?? 'N/A'); ?></p>
    </div>
</div>

<?php if (empty($assignments)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
        <p>No classes or subjects have been assigned to you for this session yet.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Subject Code</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($assignments as $a): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($a['class_name'] . ' ' . $a['section']); ?></td>
                        <td><?php echo htmlspecialchars($a['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($a['subject_code']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
