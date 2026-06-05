<?php
/**
 * Student View Result Detail
 * Variables: $student, $examName, $results, $sessionId
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-award"></i> <?php echo htmlspecialchars($examName ?: $pageTitle); ?></h1>
        <p>Detailed subject-wise results</p>
    </div>
    <a href="<?php echo moduleUrl('student', 'results'); ?>?session_id=<?php echo $sessionId; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Exams
    </a>
</div>

<?php if (empty($results)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-file-circle-xmark"></i></div>
        <p>No results found for this exam.</p>
    </div>
<?php else: ?>
    <style>
        /* Force traditional tabular layout on mobile but smaller */
        @media (max-width: 768px) {
            .view-result-table {
                display: table !important;
                width: 100% !important;
                min-width: 0 !important;
            }
            .view-result-table thead {
                display: table-header-group !important;
            }
            .view-result-table tbody {
                display: table-row-group !important;
            }
            .view-result-table tfoot {
                display: table-footer-group !important;
            }
            .view-result-table tr {
                display: table-row !important;
            }
            .view-result-table th, 
            .view-result-table td {
                display: table-cell !important;
            }
            .view-result-table th, 
            .view-result-table td {
                padding: 6px 3px !important;
                font-size: 11px !important;
                white-space: normal !important;
                word-wrap: break-word !important;
            }
            .view-result-table td::before {
                display: none !important; /* Hide the card labels */
            }
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
    <div class="table-container">
        <table class="data-table view-result-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Exam Date</th>
                    <th>Max Marks</th>
                    <th>Marks Obtained</th>
                    <th>Grade</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalMax = 0;
                $totalObtained = 0;
                foreach ($results as $r):
                    $totalMax += $r['max_marks'];
                    $totalObtained += $r['marks_obtained'];
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($r['subject_name']); ?></strong></td>
                        <td><?php echo date('d M Y', strtotime($r['exam_date'])); ?></td>
                        <td><?php echo htmlspecialchars($r['max_marks']); ?></td>
                        <td>
                            <?php if ($r['is_absent']): ?>
                                <span style="color: var(--danger); font-weight: bold;">Absent</span>
                            <?php else: ?>
                                <?php echo htmlspecialchars($r['marks_obtained']); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['grade']): ?>
                                <strong style="color: <?php
                                    $g = $r['grade'];
                                    if ($g === 'A+' || $g === 'A') echo 'var(--success)';
                                    elseif ($g === 'B+' || $g === 'B') echo '#2196F3';
                                    elseif ($g === 'C') echo 'var(--warning)';
                                    else echo 'var(--danger)';
                                ?>;"><?php echo htmlspecialchars($r['grade']); ?></strong>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($r['remarks'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td colspan="2" style="text-align: right;">Total:</td>
                    <td><?php echo $totalMax; ?></td>
                    <td><?php echo $totalObtained; ?></td>
                    <td colspan="2">
                        <?php 
                        $pct = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : 0;
                        $pctColor = $pct >= 80 ? 'var(--success)' : ($pct >= 50 ? '#2196F3' : 'var(--danger)');
                        ?>
                        <span style="color: <?php echo $pctColor; ?>;"><?php echo $pct; ?>%</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php endif; ?>
