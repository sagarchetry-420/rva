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
                    <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Search Name, Roll, Receipt, or Remarks..." style="width: 100%; border: 1px solid var(--border-color); background: #fff; padding: 10px 10px 10px 35px; border-radius: 4px;">
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border-radius: 4px;"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
            
            <div>
                <button class="btn btn-warning" onclick="openModal('bulkExamModal')" style="padding: 10px 20px; border-radius: 4px; font-weight: bold; box-shadow: 0 2px 4px rgba(255,152,0,0.3);">
                    <i class="fas fa-bolt"></i> Generate Exam Fees
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Generate Exam Fees Modal -->
    <div id="bulkExamModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Generate Exam Fees (Bulk)</h2>
                <span class="close" onclick="closeModal('bulkExamModal')">&times;</span>
            </div>
            <form method="POST" action="<?php echo moduleUrl('admin', 'fee_collection'); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="generate_exam_fees">
                
                <div class="modal-body">
                    <p style="margin-bottom:15px; color:var(--text-light);">This will generate a pending exam fee invoice for all active students in the selected class who haven't been billed yet.</p>
                    <div class="form-group">
                        <label>Select Class *</label>
                        <select name="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ' (Exam Fee: ₹' . $c['exam_fee'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Exam (Optional)</label>
                        <select name="exam_id">
                            <option value="">-- Select Exam --</option>
                            <?php if(!empty($exams)): ?>
                                <?php foreach ($exams as $e): ?>
                                    <option value="<?php echo $e['exam_id']; ?>"><?php echo htmlspecialchars($e['exam_name']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning" style="width:100%;"><i class="fas fa-bolt"></i> Generate Fees</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results / Student List -->
    <?php if (empty($selectedStudentId)): ?>
        <div class="table-container" style="padding: 0; overflow: hidden;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border-color); background: var(--bg-color);">
                <h3 style="margin: 0; font-size: 18px; color: var(--text-color);"><i class="fas fa-users"></i> Student List</h3>
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
                                <span class="badge" style="background:var(--danger); color: #fff; font-size:14px; padding:4px 8px;">₹ <?php echo number_format($s['pending_amount'], 2); ?></span>
                            <?php else: ?>
                                <span class="badge" style="background:var(--success); color: #fff; font-size:12px;">Clear</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo moduleUrl('admin', 'fee_collection'); ?>?student_id=<?php echo $s['student_id']; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-sm btn-info">View Fees</a>
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
    <?php endif; ?>

    <!-- Student Fees View -->
    <?php if ($selectedStudentId): ?>
        <div style="margin-bottom: 20px;">
            <a href="<?php echo moduleUrl('admin', 'fee_collection'); ?>?search=<?php echo urlencode($search ?? ''); ?>&filter_class_id=<?php echo $filterClassId ?? ''; ?>" class="btn btn-secondary" style="background: #fff; color: #333; border: 1px solid #ddd; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"><i class="fas fa-arrow-left"></i> Back to Student List</a>
        </div>

        <div class="table-container" style="padding: 0; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid var(--border-color); background: var(--bg-color);">
                <h3 style="margin: 0; font-size: 18px; color: var(--text-color);"><i class="fas fa-history"></i> Fee History</h3>
                <button class="btn btn-info" onclick="openModal('generateModal')" style="padding: 8px 16px; font-weight: bold;"><i class="fas fa-plus"></i> Generate Manual Fee</button>
            </div>

            <table class="data-table" style="margin: 0; width: 100%;">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Receipt</th>
                        <th class="actions-cell">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($studentFees)): ?>
                    <tr><td colspan="6"><div class="empty-state"><p>No fee records found for this session.</p></div></td></tr>
                <?php else: ?>
                    <?php foreach ($studentFees as $f): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($f['category_name']); ?></strong>
                                <?php if ($f['service_name']): ?>
                                    <br><small style="color:var(--gray);"><i class="fas fa-bus"></i> <?php echo htmlspecialchars($f['service_name']); ?></small>
                                <?php endif; ?>
                                <?php if ($f['remarks']): ?>
                                    <br><small style="color:var(--gray);"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($f['remarks']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatMoney($f['amount']); ?></td>
                            <td><?php echo formatDate($f['due_date']); ?></td>
                            <td>
                                <?php if ($f['payment_status'] === 'Paid'): ?>
                                    <span style="color:var(--success); font-weight:bold;"><i class="fas fa-check-circle"></i> Paid</span>
                                <?php else: ?>
                                    <span style="color:var(--danger); font-weight:bold;"><i class="fas fa-exclamation-circle"></i> <?php echo $f['payment_status']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($f['receipt_number']): ?>
                                    <a href="<?php echo moduleUrl('admin', 'receipt'); ?>?receipt_no=<?php echo $f['receipt_number']; ?>" target="_blank" style="color:var(--primary); font-weight:bold;">
                                        <i class="fas fa-print"></i> <?php echo $f['receipt_number']; ?>
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <?php if ($f['payment_status'] !== 'Paid'): ?>
                                    <button class="btn btn-sm btn-success" onclick='openPayModal(<?php echo htmlspecialchars(json_encode($f)); ?>)'><i class="fas fa-money-bill-wave"></i> Collect</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Collect Payment Modal -->
        <div id="payModal" class="modal">
            <div class="modal-content" style="max-width: 400px;">
                <div class="modal-header">
                    <h2>Collect Payment</h2>
                    <span class="close" onclick="closeModal('payModal')">&times;</span>
                </div>
                <form method="POST" action="<?php echo moduleUrl('admin', 'fee_collection'); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="collect">
                    <input type="hidden" name="student_id" value="<?php echo $selectedStudentId; ?>">
                    <input type="hidden" name="fee_id" id="pay_fee_id">
                    
                    <div class="modal-body">
                        <div style="background:var(--primary); color:white; padding:10px; border-radius:4px; margin-bottom:15px;">
                            <strong id="pay_category_name"></strong><br>
                            Amount Due: <span style="font-size:18px; font-weight:bold; color:white;" id="pay_amount"></span>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select name="payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="Online">Online / UPI</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Transaction ID / Remarks *</label>
                            <input type="text" name="remarks" placeholder="Required..." required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success" style="width:100%;"><i class="fas fa-check"></i> Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Generate Manual Fee Modal -->
        <div id="generateModal" class="modal">
            <div class="modal-content" style="max-width: 400px;">
                <div class="modal-header">
                    <h2>Generate Manual Fee</h2>
                    <span class="close" onclick="closeModal('generateModal')">&times;</span>
                </div>
                <form method="POST" action="<?php echo moduleUrl('admin', 'fee_collection'); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="generate">
                    <input type="hidden" name="student_id" value="<?php echo $selectedStudentId; ?>">
                    <input type="hidden" name="service_id" value=""> <!-- Simplicity -->
                    
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category_id" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['category_id']; ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount *</label>
                            <input type="number" name="amount" required step="0.01" min="1">
                        </div>
                        <div class="form-group">
                            <label>Due Date *</label>
                            <input type="date" name="due_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function openPayModal(fee) {
            document.getElementById('pay_fee_id').value = fee.fee_id;
            document.getElementById('pay_category_name').innerText = fee.category_name;
            document.getElementById('pay_amount').innerText = '₹' + parseFloat(fee.amount).toFixed(2);
            openModal('payModal');
        }
        </script>
    <?php endif; ?>
<?php endif; ?>
