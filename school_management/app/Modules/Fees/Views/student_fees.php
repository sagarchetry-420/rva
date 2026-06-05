<?php
/**
 * Student Fees View (Admin)
 * Variables: $session, $search, $selectedStudentId, $studentFees, $categories, $pageTitle
 */
?>
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1><i class="fas fa-history"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>View and manage fee history for student</p>
    </div>
    <div>
        <a href="<?php echo moduleUrl('admin', 'fee_collection'); ?>?search=<?php echo urlencode($search ?? ''); ?>&filter_class_id=<?php echo $filterClassId ?? ''; ?>" class="btn" style="background: var(--gray); color: #fff; border: none; padding: 10px 15px; border-radius: 4px; text-decoration: none;"><i class="fas fa-arrow-left"></i> Back to Student List</a>
    </div>
</div>

<?php if (!$session): ?>
    <div class="alert alert-danger">No active academic session found.</div>
<?php else: ?>

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
            <!-- Submit back to fee_collection or student_fees? Let's use fee_collection to handleAction and redirect_to -->
            <form method="POST" action="<?php echo moduleUrl('admin', 'fee_collection'); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="collect">
                <input type="hidden" name="student_id" value="<?php echo $selectedStudentId; ?>">
                <input type="hidden" name="redirect_to" value="student_fees">
                <input type="hidden" name="fee_id" id="pay_fee_id">
                
                <div class="modal-body">
                    <div style="background:var(--primary); color:white; padding:10px; border-radius:4px; margin-bottom:15px;">
                        <strong id="pay_category_name"></strong><br>
                        Amount Due: <span style="font-size:18px; font-weight:bold; color:white;" id="pay_amount"></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Amount (₹) *</label>
                        <input type="number" name="amount" id="pay_amount_input" readonly required step="0.01">
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
                        <input type="text" name="remarks" placeholder="Required..." required maxlength="100" pattern="^[a-zA-Z0-9\s\-_]+$" title="Only letters, numbers, spaces, hyphens, and underscores are allowed.">
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
            <form method="POST" action="<?php echo moduleUrl('admin', 'fee_collection'); ?>" onsubmit="return validateManualFee(event)">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="generate">
                <input type="hidden" name="student_id" value="<?php echo $selectedStudentId; ?>">
                <input type="hidden" name="service_id" value="">
                <input type="hidden" name="redirect_to" value="student_fees">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['category_id']; ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--gray); display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> To add or edit categories, go to <a href="<?php echo moduleUrl('admin', 'fee_config'); ?>" style="color: var(--primary); text-decoration: underline;">Fee Config</a>.
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Amount *</label>
                        <input type="number" name="amount" required step="0.01" min="1">
                    </div>
                    <div class="form-group">
                        <label>Due Date *</label>
                        <input type="date" name="due_date" min="<?php echo date('Y-m-d'); ?>" required>
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
        
        let amtInput = document.getElementById('pay_amount_input');
        if (amtInput) {
            amtInput.value = parseFloat(fee.amount).toFixed(2);
        }
        
        openModal('payModal');
    }

    const existingFees = <?php echo json_encode(array_map(function($f) {
        return [
            'category_id' => $f['category_id'],
            'due_date'    => $f['due_date']
        ];
    }, $studentFees ?: [])); ?>;

    function validateManualFee(event) {
        let form = event.target;
        let catSelect = form.querySelector('select[name="category_id"]');
        let dueInput = form.querySelector('input[name="due_date"]');
        
        let selectedCat = catSelect.value;
        let selectedDue = dueInput.value;

        for (let i = 0; i < existingFees.length; i++) {
            if (existingFees[i].category_id == selectedCat && existingFees[i].due_date == selectedDue) {
                alert("A fee for this category and due date already exists for this student!");
                event.preventDefault();
                return false;
            }
        }
        return true;
    }
    </script>
<?php endif; ?>
