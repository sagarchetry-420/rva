<?php
/**
 * Student Fees View
 * Variables: $student, $session, $fees
 */
?>
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1><i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
            <p>Your fee records</p>
        </div>
        <form method="GET" style="display: flex; align-items: center; gap: 10px;">
            <label for="session_id" style="margin: 0; font-weight: 500;">Session:</label>
            <select name="session_id" id="session_id" class="form-select form-select-sm" style="width: auto; min-width: 150px;" onchange="this.form.submit()">
                <?php foreach ($sessions as $sess): ?>
                    <option value="<?php echo $sess['session_id']; ?>" <?php echo $selectedSessionId == $sess['session_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sess['session_name']); ?>
                    </option>
                <?php endforeach; ?>
                <?php if(empty($sessions)): ?>
                    <option value="">No sessions found</option>
                <?php endif; ?>
            </select>
        </form>
    </div>
</div>

<?php if (empty($fees)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-receipt"></i></div>
        <p>No fee records found.</p>
    </div>
<?php else: ?>
    <?php
    $totalBalance = $totalAmount - $totalPaid;
    ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div class="form-card" style="text-align: center; padding: 20px; border-left: 4px solid #2196F3;">
            <p style="margin: 0; color: #666; font-size: 0.9rem;">Total Fees</p>
            <h2 style="margin: 5px 0 0 0; color: #333;"><?php echo number_format($totalAmount, 2); ?></h2>
        </div>
        <div class="form-card" style="text-align: center; padding: 20px; border-left: 4px solid #4CAF50;">
            <p style="margin: 0; color: #666; font-size: 0.9rem;">Total Paid</p>
            <h2 style="margin: 5px 0 0 0; color: #4CAF50;"><?php echo number_format($totalPaid, 2); ?></h2>
        </div>
        <div class="form-card" style="text-align: center; padding: 20px; border-left: 4px solid #F44336;">
            <p style="margin: 0; color: #666; font-size: 0.9rem;">Total Balance Due</p>
            <h2 style="margin: 5px 0 0 0; color: #F44336;"><?php echo number_format($totalBalance, 2); ?></h2>
        </div>
    </div>

    <style>
        /* Force traditional tabular layout on mobile but smaller */
        @media (max-width: 768px) {
            .fees-table {
                display: table !important;
                width: 100% !important;
                min-width: 0 !important;
            }
            .fees-table thead { display: table-header-group !important; }
            .fees-table tbody { display: table-row-group !important; }
            .fees-table tfoot { display: table-footer-group !important; }
            .fees-table tr { display: table-row !important; }
            .fees-table th, .fees-table td {
                display: table-cell !important;
                padding: 6px 3px !important;
                font-size: 11px !important;
                white-space: normal !important;
                word-wrap: break-word !important;
            }
            .fees-table td::before { display: none !important; }
        }
    </style>
    <div class="table-container" style="overflow-x: auto;">
        <table class="data-table fees-table">
            <thead>
                <tr>
                    <th>Service / Description</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fees as $fee): ?>
                    <?php $balance = $fee['amount'] - $fee['paid_amount']; ?>
                    <tr>
                        <td>
                            <?php 
                            $feeName = $fee['service_name'] ?? $fee['category_name'] ?? 'General Fee';
                            echo '<strong>' . htmlspecialchars($feeName) . '</strong>';
                            if (!empty($fee['remarks'])) {
                                echo '<br><small style="color:var(--gray);">' . htmlspecialchars($fee['remarks']) . '</small>';
                            }
                            ?>
                        </td>
                        <td><?php echo $fee['due_date'] ? date('d M Y', strtotime($fee['due_date'])) : '-'; ?></td>
                        <td><?php echo number_format($fee['amount'], 2); ?></td>
                        <td><?php echo number_format($fee['paid_amount'], 2); ?></td>
                        <td style="color: <?php echo $balance > 0 ? '#F44336' : '#333'; ?>; font-weight: <?php echo $balance > 0 ? 'bold' : 'normal'; ?>;">
                            <?php echo number_format($balance, 2); ?>
                        </td>
                        <td>
                            <?php 
                            $status = $fee['status'];
                            $badgeClass = 'badge ';
                            if ($status === 'Paid') $badgeClass .= 'bg-success';
                            elseif ($status === 'Partial') $badgeClass .= 'bg-warning text-dark';
                            else $badgeClass .= 'bg-danger';
                            ?>
                            <span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                        </td>
                        <td>
                            <?php if (!empty($fee['receipt_number'])): ?>
                                <a href="<?php echo moduleUrl('student', 'receipt'); ?>?receipt_no=<?php echo urlencode($fee['receipt_number']); ?>" target="_blank" class="btn btn-sm btn-info" style="font-size: 12px; padding: 4px 8px;">
                                    <i class="fas fa-download"></i> Receipt
                                </a>
                            <?php else: ?>
                                <span style="color:#999;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <style>
        .pagination { display: flex; padding-left: 0; list-style: none; justify-content: center; gap: 5px; margin-top: 20px; }
        .page-item { margin: 0; }
        .page-link { position: relative; display: block; padding: 8px 16px; color: #0d6efd; text-decoration: none; background-color: #fff; border: 1px solid #dee2e6; border-radius: 4px; transition: all 0.2s; }
        .page-link:hover { z-index: 2; color: #0a58ca; background-color: #e9ecef; border-color: #dee2e6; }
        .page-item.active .page-link { z-index: 3; color: #fff; background-color: #0d6efd; border-color: #0d6efd; }
    </style>
    <nav aria-label="Fees pagination">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?session_id=<?php echo htmlspecialchars($selectedSessionId); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>

<?php endif; ?>
