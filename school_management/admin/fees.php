<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = $_POST['action'];
    if ($a === 'assign') {
        $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
        $type = sanitize($conn, $_POST['fee_type']);
        $amt = floatval($_POST['amount']);
        $due = sanitize($conn, $_POST['due_date']);
        
        if (empty($student_ids)) {
            setFlashMessage('error', "Please select at least one student.");
            header('Location: fees.php'); exit();
        }

        $count = 0;
        foreach ($student_ids as $sid) {
            $sid = intval($sid);
            // Check duplicate: same student + fee_type + due_date
            $dup = mysqli_query($conn, "SELECT fee_id FROM fees WHERE student_id=$sid AND fee_type='$type' AND due_date='$due'");
            if (mysqli_num_rows($dup) === 0) {
                mysqli_query($conn, "INSERT INTO fees (student_id,fee_type,amount,due_date,payment_status) VALUES ($sid,'$type',$amt,'$due','Pending')");
                $count++;
            }
        }
        
        if ($count > 0) {
            setFlashMessage('success', "Fee has been assigned to $count student(s) successfully!");
        } else {
            setFlashMessage('warning', "No new fees were assigned. They might already have this fee assigned for the same due date.");
        }
        header('Location: fees.php'); exit();
    }
    if ($a === 'pay') {
        $fid = intval($_POST['fee_id']);
        $method = sanitize($conn, $_POST['payment_method']);
        $receipt = sanitize($conn, $_POST['receipt_number']);
        $proof_name = '';

        // Handle File Upload for Payment Proof
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(__DIR__) . '/uploads/payments/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $proof_name = 'proof_' . $fid . '_' . time() . '.' . $file_ext;
            move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_dir . $proof_name);
        }

        $q = "UPDATE fees SET payment_status='Paid', payment_date=CURDATE(), payment_method='$method', receipt_number='$receipt'";
        if (!empty($proof_name)) {
            $q .= ", payment_proof='$proof_name'";
        }
        $q .= " WHERE fee_id=$fid";
        
        mysqli_query($conn, $q);
        setFlashMessage('success', 'Payment has been recorded successfully!');
        header('Location: fees.php'); exit();
    }
    if ($a === 'bulk_pay') {
        $method = sanitize($conn, $_POST['payment_method']);
        $fee_ids = $_POST['fee_ids'];
        $receipt_numbers = $_POST['receipt_numbers'];
        
        foreach ($fee_ids as $index => $fid) {
            $fid = intval($fid);
            $receipt = sanitize($conn, $receipt_numbers[$index]);
            mysqli_query($conn, "UPDATE fees SET payment_status='Paid', payment_date=CURDATE(), payment_method='$method', receipt_number='$receipt' WHERE fee_id=$fid");
        }
        
        setFlashMessage('success', count($fee_ids) . ' payments have been recorded successfully!');
        header('Location: fees.php'); exit();
    }
    if ($a === 'delete') {
        mysqli_query($conn, "DELETE FROM fees WHERE fee_id=".intval($_POST['fee_id']));
        setFlashMessage('success', 'Fee record has been deleted successfully.');
        header('Location: fees.php'); exit();
    }
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

$where = "WHERE 1=1";
if ($filter === 'pending') $where .= " AND f.payment_status='Pending'";
elseif ($filter === 'paid') $where .= " AND f.payment_status='Paid'";
elseif ($filter === 'overdue') $where .= " AND f.payment_status='Pending' AND f.due_date < CURDATE()";

if ($class_id > 0) $where .= " AND s.class_id=$class_id";

$fees = mysqli_query($conn, "SELECT f.*, CONCAT(s.first_name,' ',s.last_name) as student_name, s.roll_number, c.class_name, c.section 
    FROM fees f JOIN students s ON f.student_id=s.student_id LEFT JOIN classes c ON s.class_id=c.class_id $where ORDER BY f.fee_id DESC");

$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$students_raw = mysqli_query($conn, "SELECT student_id, class_id, CONCAT(first_name,' ',last_name,' (',roll_number,')') as label FROM students ORDER BY class_id, first_name");
$all_students = [];
while($s = mysqli_fetch_assoc($students_raw)) {
    $all_students[] = $s;
}

// Stats
$r = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as t FROM fees WHERE payment_status='Paid'"); $total_collected = mysqli_fetch_assoc($r)['t'];
$r = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as t FROM fees WHERE payment_status='Pending'"); $total_pending = mysqli_fetch_assoc($r)['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div><h1><i class="fa-solid fa-money-bill-wave"></i> Fee Management</h1><p>Assign and collect fees</p></div>
                <button class="btn btn-primary" onclick="openModal('assignModal')">+ Assign Fee</button>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon classes-icon"><i class="fa-solid fa-chalkboard"></i></div><div class="stat-details"><h3>₹<?php echo number_format($total_collected); ?></h3><p>Total Collected</p></div></div>
                <div class="stat-card"><div class="stat-icon fees-icon"><i class="fa-solid fa-hourglass-half"></i></div><div class="stat-details"><h3>₹<?php echo number_format($total_pending); ?></h3><p>Total Pending</p></div></div>
            </div>

            <div class="filter-bar" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <a href="?filter=all&class_id=<?php echo $class_id; ?>" class="btn <?php echo $filter==='all'?'btn-primary':'btn-secondary'; ?> btn-sm">All</a>
                    <a href="?filter=pending&class_id=<?php echo $class_id; ?>" class="btn <?php echo $filter==='pending'?'btn-warning':'btn-secondary'; ?> btn-sm">Pending</a>
                    <a href="?filter=paid&class_id=<?php echo $class_id; ?>" class="btn <?php echo $filter==='paid'?'btn-success':'btn-secondary'; ?> btn-sm">Paid</a>
                    <a href="?filter=overdue&class_id=<?php echo $class_id; ?>" class="btn <?php echo $filter==='overdue'?'btn-danger':'btn-secondary'; ?> btn-sm">Overdue</a>
                    
                    <select onchange="location.href='?filter=<?php echo $filter; ?>&class_id='+this.value" class="btn btn-secondary btn-sm" style="margin-left:10px; padding:5px; border-radius:4px; border:1px solid #ccc;">
                        <option value="0">-- All Classes --</option>
                        <?php mysqli_data_seek($classes, 0); while($c=mysqli_fetch_assoc($classes)): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $class_id==$c['class_id']?'selected':''; ?>><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <button id="bulkPayBtn" class="btn btn-success btn-sm" style="display:none; margin-right:5px;" onclick="openBulkPayModal()"><i class="fa-solid fa-file-invoice-dollar"></i> Bulk Mark Paid</button>
                    <a href="export_fees.php?action=csv&filter=<?php echo $filter; ?>&class_id=<?php echo $class_id; ?>" class="btn btn-secondary btn-sm" style="background:#28a745; color:white; border:none;"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
                    <a href="export_fees.php?action=pdf&filter=<?php echo $filter; ?>&class_id=<?php echo $class_id; ?>" target="_blank" class="btn btn-secondary btn-sm" style="background:#dc3545; color:white; border:none;"><i class="fa-solid fa-file-pdf"></i> Print / PDF</a>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table" id="dataTable">
                    <thead><tr><th><input type="checkbox" id="selectAllFees" onclick="toggleSelectAllFees(this)"></th><th>Student</th><th>Class</th><th>Fee Type</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($f = mysqli_fetch_assoc($fees)): ?>
                    <tr>
                        <td>
                            <?php if ($f['payment_status'] !== 'Paid'): ?>
                            <input type="checkbox" class="fee-checkbox" value="<?php echo $f['fee_id']; ?>" 
                                   data-name="<?php echo htmlspecialchars($f['student_name']); ?>" 
                                   data-type="<?php echo htmlspecialchars($f['fee_type']); ?>" 
                                   data-amount="<?php echo $f['amount']; ?>"
                                   onchange="updateBulkBtnVisibility()">
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($f['student_name']); ?></strong><br><small><?php echo htmlspecialchars($f['roll_number']); ?></small></td>
                        <td><?php echo htmlspecialchars($f['class_name'].' '.$f['section']); ?></td>
                        <td><?php echo htmlspecialchars($f['fee_type']); ?></td>
                        <td><strong>₹<?php echo number_format($f['amount'],2); ?></strong></td>
                        <td><?php echo $f['due_date'] ? date('M d, Y', strtotime($f['due_date'])) : '—'; ?></td>
                        <td><span class="badge badge-<?php echo strtolower($f['payment_status']); ?>"><?php echo $f['payment_status']; ?></span></td>
                        <td class="actions-cell">
                            <?php if ($f['payment_status'] !== 'Paid'): ?>
                            <button class="btn btn-sm btn-success" onclick="openPayModal(<?php echo $f['fee_id']; ?>)"><i class="fa-solid fa-credit-card"></i> Pay</button>
                            <?php else: ?>
                                <div style="font-size:12px; line-height:1.4;">
                                    <strong><?php echo htmlspecialchars($f['payment_method']); ?></strong><br>
                                    <small>Receipt: <?php echo htmlspecialchars($f['receipt_number']); ?></small>
                                    <?php if (!empty($f['payment_proof'])): ?>
                                        <br><a href="<?php echo BASE_URL; ?>/uploads/payments/<?php echo $f['payment_proof']; ?>" target="_blank" style="color:var(--primary); font-weight:600;"><i class="fa-solid fa-file-image"></i> View Proof</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <a href="export_fees.php?action=student_report&student_id=<?php echo $f['student_id']; ?>" target="_blank" class="btn btn-sm btn-secondary" style="margin-top:5px; background:#17a2b8; color:white; border:none;" title="Print Student Statement"><i class="fa-solid fa-print"></i></a>
                            <form method="POST" style="display:inline" onsubmit="return confirmDelete()"><input type="hidden" name="action" value="delete"><input type="hidden" name="fee_id" value="<?php echo $f['fee_id']; ?>"><button class="btn btn-sm btn-danger" style="margin-top:5px;"><i class="fa-solid fa-trash-can"></i></button></form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Assign Fee Modal -->
    <div id="assignModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Assign Fee</h2><span class="close" onclick="closeModal('assignModal')">&times;</span></div>
    <form method="POST" id="assignFeeForm"><input type="hidden" name="action" value="assign"><div class="modal-body">
        <div class="form-row">
            <div class="form-group"><label>Select Class *</label>
                <select id="assign_class_id" onchange="filterStudentsForFee(); validateAssignForm()" required>
                    <option value="">-- Select Class --</option>
                    <?php mysqli_data_seek($classes, 0); while($c=mysqli_fetch_assoc($classes)): ?>
                    <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllStudentsForFee()" id="selectAllBtn" style="display:none; margin-bottom:5px;">Select All</button>
            </div>
        </div>
        
        <div class="form-group"><label>Students *</label>
            <div class="student-select-wrapper" style="border: 1px solid var(--border); border-radius: 8px; overflow: hidden; background: #fff;">
                <div class="search-bar" style="padding: 8px; border-bottom: 1px solid #eee; display: flex; align-items: center; background: #fcfcfc;">
                    <i class="fa-solid fa-magnifying-glass" style="color: #aaa; margin-right: 8px; font-size: 13px;"></i>
                    <input type="text" id="studentSearch" placeholder="Search student by name or roll..." onkeyup="searchStudentsForFee()" style="border: none; outline: none; width: 100%; font-size: 13px; background: transparent;">
                </div>
                <div id="student_list_container" style="max-height: 220px; overflow-y: auto; padding: 5px;">
                    <p id="no_class_msg" style="color: var(--gray); font-style: italic; padding: 20px; text-align: center; font-size: 13px;">
                        <i class="fa-solid fa-arrow-up" style="display: block; margin-bottom: 5px; font-size: 18px; opacity: 0.5;"></i>
                        Please select a class first
                    </p>
                    <div id="student_grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 5px;">
                        <?php foreach($all_students as $st): ?>
                        <div class="student-item class-group-<?php echo $st['class_id']; ?>" style="display:none;">
                            <label class="student-label" style="display: flex; align-items: center; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: background 0.2s; border: 1px solid transparent; font-size: 13px;">
                                <input type="checkbox" name="student_ids[]" value="<?php echo $st['student_id']; ?>" class="fee-student-checkbox" style="width: 16px; height: 16px; margin-right: 12px; cursor: pointer;" onchange="updateSelectedCount(); validateAssignForm()">
                                <span class="st-name"><?php echo htmlspecialchars($st['label']); ?></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="select-footer" style="padding: 8px 15px; background: #f8f9fa; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #666;">
                    <span id="selected_count">0 students selected</span>
                    <a href="javascript:void(0)" onclick="clearStudentSelection(); validateAssignForm()" style="color: var(--danger); text-decoration: none; font-weight: 500;">Clear All</a>
                </div>
            </div>
            <style>
                .student-label:hover { background: #f0f4ff; border-color: #d0d7ff; }
                .student-label input:checked + .st-name { font-weight: 600; color: var(--primary); }
                .student-label input:checked { accent-color: var(--primary); }
                #student_list_container::-webkit-scrollbar { width: 6px; }
                #student_list_container::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }
            </style>
        </div>

        <div class="form-row">
            <div class="form-group"><label>Fee Type *</label><select name="fee_type" onchange="validateAssignForm()" required><option value="">-- Select Fee Type --</option><option value="Tuition Fee">Tuition Fee</option><option value="Exam Fee">Exam Fee</option><option value="Library Fee">Library Fee</option><option value="Transport Fee">Transport Fee</option><option value="Other">Other</option></select></div>
            <div class="form-group"><label>Amount (₹) *</label><input type="number" name="amount" step="0.01" oninput="validateAssignForm()" required></div>
        </div>
        <div class="form-group"><label>Due Date *</label><input type="date" name="due_date" min="<?php echo date('Y-m-d'); ?>" onchange="validateAssignForm()" required></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('assignModal')">Cancel</button><button type="submit" id="assignFeeSubmitBtn" class="btn btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">Assign Fee</button></div></form></div></div>

    <!-- Pay Modal -->
    <div id="payModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Record Payment</h2><span class="close" onclick="closeModal('payModal')">&times;</span></div>
    <form method="POST" enctype="multipart/form-data" id="recordPaymentForm"><input type="hidden" name="action" value="pay"><input type="hidden" name="fee_id" id="pay_fee_id"><div class="modal-body">
        <div class="form-group"><label>Payment Method *</label>
            <select name="payment_method" id="pay_method" required onchange="toggleProofField(this.value); validatePayForm()">
                <option value="">-- Select Payment Method --</option>
                <option value="Cash">Cash</option>
                <option value="UPI">UPI</option>
                <option value="Card">Card</option>
                <option value="Cheque">Cheque</option>
                <option value="Bank Transfer">Bank Transfer</option>
            </select>
        </div>
        <div class="form-group"><label>Receipt / Transaction Number *</label><input type="text" name="receipt_number" id="pay_receipt" required placeholder="Enter Receipt No or Txn ID" oninput="validatePayForm()"></div>
        <div class="form-group" id="proof_field_container">
            <label id="proof_label">Payment Proof (Screenshot/Scan)</label>
            <input type="file" name="payment_proof" class="form-control" accept="image/*,application/pdf">
            <small class="text-muted" id="proof_hint">Recommended for UPI and Bank Transfers.</small>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('payModal')">Cancel</button><button type="submit" id="paySubmitBtn" class="btn btn-success" disabled style="opacity: 0.5; cursor: not-allowed;">Confirm Payment</button></div></form></div></div>

    <!-- Bulk Pay Modal -->
    <div id="bulkPayModal" class="modal"><div class="modal-content" style="width:80%; max-width:900px;"><div class="modal-header"><h2>Bulk Record Payment</h2><span class="close" onclick="closeModal('bulkPayModal')">&times;</span></div>
    <form method="POST" id="bulkPayForm"><input type="hidden" name="action" value="bulk_pay"><div class="modal-body">
        <div class="form-row">
            <div class="form-group"><label>Common Payment Method *</label>
                <select name="payment_method" id="bulk_pay_method" required onchange="validateBulkForm()">
                    <option value="">-- Select Payment Method --</option>
                    <option value="Cash">Cash</option>
                    <option value="UPI">UPI</option>
                    <option value="Card">Card</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
            </div>
            <div class="form-group"><label>Base Receipt Number (Optional)</label>
                <input type="text" id="base_receipt" placeholder="Enter base number to auto-fill" oninput="autoFillReceipts()">
                <small class="text-muted">Type here to pre-fill all students below.</small>
            </div>
        </div>
        
        <div style="max-height:400px; overflow-y:auto; border:1px solid #ddd; border-radius:8px; margin-top:15px;">
            <table class="data-table">
                <thead><tr><th>Student</th><th>Fee Type</th><th>Amount</th><th>Receipt Number *</th></tr></thead>
                <tbody id="bulk_pay_list"></tbody>
            </table>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('bulkPayModal')">Cancel</button><button type="submit" id="bulkPaySubmitBtn" class="btn btn-success" disabled style="opacity:0.5; cursor:not-allowed;">Confirm Bulk Payment</button></div></form></div></div>

    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>
    function openPayModal(id){document.getElementById('pay_fee_id').value=id;openModal('payModal');}

    function toggleProofField(method) {
        const hint = document.getElementById('proof_hint');
        if (method === 'UPI' || method === 'Bank Transfer') {
            hint.style.color = 'var(--primary)';
            hint.style.fontWeight = 'bold';
            hint.innerHTML = '<i class="fa-solid fa-circle-info"></i> Please upload the payment screenshot.';
        } else {
            hint.style.color = '#666';
            hint.style.fontWeight = 'normal';
            hint.innerHTML = 'Recommended for UPI and Bank Transfers.';
        }
    }

    function filterStudentsForFee() {
        const classId = document.getElementById('assign_class_id').value;
        const container = document.getElementById('student_list_container');
        const items = container.querySelectorAll('.student-item');
        const noMsg = document.getElementById('no_class_msg');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const searchInput = document.getElementById('studentSearch');
        
        // Reset search and count
        searchInput.value = '';
        clearStudentSelection();

        if (!classId) {
            items.forEach(item => item.style.display = 'none');
            noMsg.style.display = 'block';
            selectAllBtn.style.display = 'none';
        } else {
            noMsg.style.display = 'none';
            selectAllBtn.style.display = 'inline-block';
            selectAllBtn.textContent = 'Select All';
            items.forEach(item => {
                if (item.classList.contains('class-group-' + classId)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        updateSelectedCount();
    }

    function selectAllStudentsForFee() {
        const classId = document.getElementById('assign_class_id').value;
        if (!classId) return;
        
        const checkboxes = document.querySelectorAll('.student-item.class-group-' + classId + ' .fee-student-checkbox');
        const btn = document.getElementById('selectAllBtn');
        
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        
        btn.textContent = allChecked ? 'Select All' : 'Deselect All';
        updateSelectedCount();
    }

    function searchStudentsForFee() {
        const classId = document.getElementById('assign_class_id').value;
        const query = document.getElementById('studentSearch').value.toLowerCase();
        if (!classId) return;

        const items = document.querySelectorAll('.student-item.class-group-' + classId);
        items.forEach(item => {
            const text = item.querySelector('.st-name').textContent.toLowerCase();
            if (text.includes(query)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.fee-student-checkbox:checked').length;
        document.getElementById('selected_count').textContent = checked + ' student(s) selected';
    }

    function clearStudentSelection() {
        document.querySelectorAll('.fee-student-checkbox').forEach(cb => cb.checked = false);
        const selectAllBtn = document.getElementById('selectAllBtn');
        if (selectAllBtn) selectAllBtn.textContent = 'Select All';
        updateSelectedCount();
    }

    function validateAssignForm() {
        const classId = document.getElementById('assign_class_id').value;
        const studentSelected = document.querySelectorAll('.fee-student-checkbox:checked').length > 0;
        const feeType = document.querySelector('select[name="fee_type"]').value;
        const amount = document.querySelector('input[name="amount"]').value;
        const dueDate = document.querySelector('input[name="due_date"]').value;
        
        const btn = document.getElementById('assignFeeSubmitBtn');
        if (classId && studentSelected && feeType && amount && dueDate) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
        }
    }

    function validatePayForm() {
        const method = document.getElementById('pay_method').value;
        const receipt = document.getElementById('pay_receipt').value;
        const btn = document.getElementById('paySubmitBtn');
        
        if (method && receipt.trim().length > 0) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
        }
    }

    function toggleSelectAllFees(master) {
        document.querySelectorAll('.fee-checkbox').forEach(cb => cb.checked = master.checked);
        updateBulkBtnVisibility();
    }

    function updateBulkBtnVisibility() {
        const checkedCount = document.querySelectorAll('.fee-checkbox:checked').length;
        document.getElementById('bulkPayBtn').style.display = checkedCount > 0 ? 'inline-block' : 'none';
    }

    function openBulkPayModal() {
        const checked = document.querySelectorAll('.fee-checkbox:checked');
        const list = document.getElementById('bulk_pay_list');
        list.innerHTML = '';
        
        checked.forEach(cb => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${cb.dataset.name}</td>
                <td>${cb.dataset.type}</td>
                <td>₹${cb.dataset.amount}</td>
                <td>
                    <input type="hidden" name="fee_ids[]" value="${cb.value}">
                    <input type="text" name="receipt_numbers[]" class="bulk-receipt-input" required placeholder="Receipt No" oninput="validateBulkForm()">
                </td>
            `;
            list.appendChild(row);
        });
        openModal('bulkPayModal');
        validateBulkForm();
    }

    function autoFillReceipts() {
        const base = document.getElementById('base_receipt').value;
        const inputs = document.querySelectorAll('.bulk-receipt-input');
        inputs.forEach((input, index) => {
            if (base) {
                // If it ends with a number, increment it
                const match = base.match(/(\d+)$/);
                if (match) {
                    const num = parseInt(match[1]) + index;
                    const prefix = base.substring(0, base.length - match[1].length);
                    // Ensure the number has same leading zeros if any
                    const numStr = num.toString().padStart(match[1].length, '0');
                    input.value = prefix + numStr;
                } else {
                    input.value = base + (index + 1);
                }
            } else {
                input.value = '';
            }
        });
        validateBulkForm();
    }

    function validateBulkForm() {
        const method = document.getElementById('bulk_pay_method').value;
        const inputs = document.querySelectorAll('.bulk-receipt-input');
        const allReceiptsFilled = Array.from(inputs).every(input => input.value.trim().length > 0);
        const btn = document.getElementById('bulkPaySubmitBtn');
        
        if (method && allReceiptsFilled && inputs.length > 0) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
        }
    }
    </script>
</body>
</html>

