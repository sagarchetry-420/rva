<?php
/**
 * Fee Collection View (Admin)
 * Variables: $session, $search, $students, $selectedStudentId, $studentFees, $categories, $pageTitle
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-money-check-alt"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Collect fees and view student dues</p>
    </div>
</div>

<?php if (!$session): ?>
    <div class="alert alert-danger">No active academic session found.</div>
<?php else: ?>
    <!-- Search Bar & Bulk Actions -->
    <div style="margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <form method="GET" style="display: flex; gap: 15px; flex: 1; flex-wrap: wrap; align-items: center;">
                <input type="hidden" name="module" value="admin">
                <input type="hidden" name="action" value="fee_collection">
                
                <div style="flex: 0 1 250px;">
                    <select name="filter_class_id" class="form-control" style="width: 100%; border: 1px solid var(--border-color); background: #fff; padding: 10px; border-radius: 4px;">
                        <option value="">-- All Classes --</option>
                        <?php foreach ($classes ?? [] as $c): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo (isset($filterClassId) && $filterClassId == $c['class_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="flex: 1; min-width: 250px; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 12px; color: var(--gray);"></i>
                    <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Search Name, Roll, Receipt, or Remarks..." style="width: 100%; border: 1px solid var(--border-color); background: #fff; padding: 10px 10px 10px 35px; border-radius: 4px;" maxlength="100" pattern="^[a-zA-Z0-9\s\-_@.]*$" title="Only letters, numbers, spaces, and basic punctuation allowed.">
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border-radius: 4px;"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
            
        </div>
    </div>

    <!-- Search Results / Student List -->
    <div class="table-container" style="padding: 0; overflow: hidden;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border-color); background: var(--bg-color);">
                <h3 style="margin: 0; font-size: 18px; color: var(--text-color);">
                    <i class="fas fa-users"></i> Student List 
                    <?php if (isset($totalPendingCount) && $totalPendingCount > 0): ?>
                        <span style="font-size: 14px; color: var(--danger); font-weight: normal; margin-left: 10px;">(<?php echo $totalPendingCount; ?> with pending dues)</span>
                    <?php endif; ?>
                </h3>
            </div>
            <table class="data-table" style="margin: 0; width: 100%;">
                <thead>
                    <tr><th>Roll No</th><th>Name</th><th>Class</th><th>Pending Dues</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($s['class_name'] . ' ' . $s['section']); ?></td>
                        <td>
                            <?php if ($s['pending_amount'] > 0): ?>
                                <span style="color:var(--danger); font-size:14px; font-weight:bold;">₹ <?php echo number_format($s['pending_amount'], 2); ?></span>
                            <?php else: ?>
                                <span style="color:var(--success); font-size:14px; font-weight:bold;">Clear</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo moduleUrl('admin', 'student_fees'); ?>?student_id=<?php echo $s['student_id']; ?>&search=<?php echo urlencode($search ?? ''); ?>" class="btn btn-sm btn-info">View Fees</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (isset($pagination)): ?>
                <div class="no-print" data-html2canvas-ignore="true" style="padding: 10px;">
                    <?php echo renderPagination($pagination); ?>
                    <div style="text-align: center; margin-top: 10px; color: var(--gray); font-size: 13px;">
                        Showing page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['pages']; ?> (Total: <?php echo $pagination['total']; ?> students)
                    </div>
                </div>
            <?php endif; ?>
        </div>
        </div>
<?php endif; ?>
