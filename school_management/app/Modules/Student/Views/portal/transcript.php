<?php
/**
 * Student Academic Transcript View
 * Variables: $student, $academic, $transcriptData, $pageTitle
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-file-download"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Your complete academic history across all sessions</p>
    </div>

</div>

<!-- Student Info Card -->
<?php if ($student): ?>
<div class="form-card" style="margin-bottom: 25px; padding: 20px 25px;">
    <div style="display: flex; flex-wrap: wrap; gap: 20px 40px; align-items: center;">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:50px; height:50px; border-radius:50%; background:linear-gradient(135deg, #667eea, #764ba2); display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; font-weight:bold;">
                <?php echo strtoupper(substr($student['first_name'] ?? 'S', 0, 1)); ?>
            </div>
            <div>
                <h3 style="margin:0; font-size:16px;">
                    <?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?>
                </h3>
                <span style="color:#888; font-size:13px;">Admission No: <?php echo htmlspecialchars($student['admission_number'] ?? 'N/A'); ?></span>
            </div>
        </div>
        <?php if ($academic): ?>
        <div style="display:flex; gap:25px; flex-wrap:wrap;">
            <div>
                <span style="color:#888; font-size:12px; display:block;">Current Class</span>
                <strong><?php echo htmlspecialchars(($academic['class_name'] ?? '') . ' ' . ($academic['section'] ?? '')); ?></strong>
            </div>
            <div>
                <span style="color:#888; font-size:12px; display:block;">Roll Number</span>
                <strong><?php echo htmlspecialchars($academic['roll_number'] ?? 'N/A'); ?></strong>
            </div>
            <div>
                <span style="color:#888; font-size:12px; display:block;">Session</span>
                <strong><?php echo htmlspecialchars($academic['session_name'] ?? 'N/A'); ?></strong>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($transcriptData)): ?>
    <div class="form-card" style="text-align: center; padding: 50px;">
        <div style="font-size: 4rem; color: #ccc; margin-bottom: 20px;">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <h2 style="color: #333;">No Results Available</h2>
        <p style="color: #666; max-width: 500px; margin: 0 auto 30px auto; line-height: 1.6;">
            No published exam results were found for your account. Results will appear here once your teachers have entered marks and the admin has published the results.
        </p>
        <a href="<?php echo moduleUrl('student', 'results'); ?>" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> View Results Online
        </a>
    </div>
<?php else: ?>

    <!-- Grade Legend -->
    <div class="form-card" style="margin-bottom: 20px; padding: 12px 20px;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:15px; font-size:13px;">
            <strong style="color:#555;"><i class="fas fa-info-circle"></i> Grading Scale:</strong>
            <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#10b981;"></span> A+ (≥90%)</span>
            <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#22c55e;"></span> A (≥80%)</span>
            <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#3b82f6;"></span> B+ (≥70%)</span>
            <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#2196F3;"></span> B (≥60%)</span>
            <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#f59e0b;"></span> C (≥50%)</span>
            <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#f97316;"></span> D (≥40%)</span>
            <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#ef4444;"></span> F (<40%)</span>
        </div>
    </div>

    <?php foreach ($transcriptData as $tdIndex => $td): ?>
        <?php $sess = $td['session']; ?>
        <!-- Session Block -->
        <div class="form-card" style="margin-bottom: 25px; padding: 0; overflow: hidden;">
            <!-- Session Header -->
            <div style="background: linear-gradient(135deg, #2980b9, #3498db); color: #fff; padding: 14px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div>
                    <h3 style="margin:0; font-size:16px;"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($sess['session_name']); ?></h3>
                    <span style="font-size:13px; opacity:0.85;">
                        Class: <?php echo htmlspecialchars($sess['class_name'] . ' ' . $sess['section']); ?>
                        <?php if (!empty($sess['roll_number'])): ?>
                            &nbsp;|&nbsp; Roll No: <?php echo htmlspecialchars($sess['roll_number']); ?>
                        <?php endif; ?>
                    </span>
                </div>
                <span style="font-size:12px; opacity:0.7;">
                    <?php echo date('M Y', strtotime($sess['start_date'])); ?> – <?php echo date('M Y', strtotime($sess['end_date'])); ?>
                </span>
            </div>

            <?php foreach ($td['exams'] as $edIndex => $ed): ?>
                <?php $exam = $ed['exam']; ?>
                <!-- Exam Sub-header -->
                <div style="background: #f1f5f9; padding: 10px 20px; border-bottom: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                    <div>
                        <strong style="color:#334155;"><?php echo htmlspecialchars($exam['exam_name']); ?></strong>
                        <span style="color:#94a3b8; font-size:13px; margin-left:8px;">(<?php echo htmlspecialchars($exam['exam_type']); ?>)</span>
                    </div>
                    <div style="display:flex; gap:15px; align-items:center; font-size:13px;">
                        <?php
                        $pctColor = '#ef4444';
                        if ($ed['percentage'] >= 80) $pctColor = '#10b981';
                        elseif ($ed['percentage'] >= 60) $pctColor = '#3b82f6';
                        elseif ($ed['percentage'] >= 40) $pctColor = '#f59e0b';
                        ?>
                        <span style="background:<?php echo $pctColor; ?>; color:#fff; padding:3px 10px; border-radius:12px; font-weight:700; font-size:12px;">
                            <?php echo $ed['percentage']; ?>% | Grade: <?php echo $ed['overallGrade']; ?>
                        </span>
                    </div>
                </div>

                <!-- Results Table -->
                <style>
                    /* Force traditional tabular layout on mobile but smaller */
                    @media (max-width: 768px) {
                        .transcript-table {
                            display: table !important;
                            width: 100% !important;
                            min-width: 0 !important;
                        }
                        .transcript-table thead { display: table-header-group !important; }
                        .transcript-table tbody { display: table-row-group !important; }
                        .transcript-table tfoot { display: table-footer-group !important; }
                        .transcript-table tr { display: table-row !important; }
                        .transcript-table th, .transcript-table td {
                            display: table-cell !important;
                            padding: 6px 3px !important;
                            font-size: 11px !important;
                            white-space: normal !important;
                            word-wrap: break-word !important;
                        }
                        .transcript-table td::before { display: none !important; }
                    }
                </style>
                <div style="overflow-x: auto;">
                    <table class="data-table transcript-table" style="margin:0; border-radius:0;">
                        <thead>
                            <tr>
                                <th style="text-align:left; padding-left:20px;">Subject</th>
                                <th>Code</th>
                                <th>Max Marks</th>
                                <th>Marks Obtained</th>
                                <th>Grade</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ed['results'] as $r): ?>
                            <tr>
                                <td style="text-align:left; padding-left:20px; font-weight:500;">
                                    <?php echo htmlspecialchars($r['subject_name']); ?>
                                </td>
                                <td style="color:#94a3b8; font-size:13px;">
                                    <?php echo htmlspecialchars($r['subject_code'] ?? '-'); ?>
                                </td>
                                <td><?php echo $r['max_marks']; ?></td>
                                <td>
                                    <?php if ($r['is_absent']): ?>
                                        <span style="color: var(--danger); font-weight: bold;">Absent</span>
                                    <?php else: ?>
                                        <strong><?php echo $r['marks_obtained']; ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['grade']): ?>
                                        <?php
                                        $g = $r['grade'];
                                        if ($g === 'A+' || $g === 'A') $gc = '#10b981';
                                        elseif ($g === 'B+' || $g === 'B') $gc = '#3b82f6';
                                        elseif ($g === 'C') $gc = '#f59e0b';
                                        else $gc = '#ef4444';
                                        ?>
                                        <span style="color:<?php echo $gc; ?>; font-weight:700;"><?php echo htmlspecialchars($g); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:13px; color:#64748b;"><?php echo htmlspecialchars($r['remarks'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8fafc; font-weight: bold;">
                                <td colspan="2" style="text-align: right; padding-right: 15px;">Total:</td>
                                <td><?php echo $ed['totalMax']; ?></td>
                                <td><?php echo $ed['totalObtained']; ?></td>
                                <td>
                                    <span style="color:<?php echo $pctColor; ?>; font-weight:700;"><?php echo $ed['overallGrade']; ?></span>
                                </td>
                                <td>
                                    <span style="color:<?php echo $pctColor; ?>;"><?php echo $ed['percentage']; ?>%</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php if ($edIndex < count($td['exams']) - 1): ?>
                    <div style="border-top: 2px dashed #e2e8f0;"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <!-- Download Section -->
    <div class="form-card" style="text-align:center; padding:30px; background:linear-gradient(135deg, #f0fdf4, #ecfdf5); border:1px solid #bbf7d0;">
        <i class="fas fa-file-pdf" style="font-size:2.5rem; color:#10b981; margin-bottom:10px;"></i>
        <h3 style="margin:0 0 8px;">Download Full Transcript</h3>
        <p style="color:#666; margin:0 0 15px; font-size:14px;">Get a printable PDF version of your complete academic record.</p>
        <a href="<?php echo moduleUrl('student', 'download_transcript'); ?>" class="btn btn-success" style="display:inline-flex; align-items:center; gap:8px; padding:12px 30px; font-size:15px;">
            <i class="fas fa-download"></i> Download PDF Transcript
        </a>
    </div>
<?php endif; ?>
