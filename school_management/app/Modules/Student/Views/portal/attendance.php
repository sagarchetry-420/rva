<?php
/**
 * Student Attendance View
 * Variables: $student, $session, $attendanceSummary, $recentAttendance
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-check-to-slot"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Your attendance record for <?php echo htmlspecialchars($session['session_name'] ?? 'N/A'); ?></p>
    </div>
</div>

<style>
    .stat-card { text-align: center; padding: 20px; }
    .attendance-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
    
    @media (max-width: 768px) {
        .attendance-grid { gap: 8px; margin-bottom: 15px; grid-template-columns: repeat(2, 1fr); }
        .stat-card { padding: 10px; }
        .stat-card h2 { font-size: 1.5rem !important; margin: 0; }
        .stat-card p { font-size: 11px; margin: 2px 0 0 0 !important; }
    }
</style>

<div class="attendance-grid">
    <div class="form-card stat-card" style="border-top: 4px solid #4CAF50;">
        <h2 style="color: #4CAF50;"><?php echo $attendanceSummary['Present'] ?? 0; ?></h2>
        <p style="color: #666;">Present</p>
    </div>
    <div class="form-card stat-card" style="border-top: 4px solid #F44336;">
        <h2 style="color: #F44336;"><?php echo $attendanceSummary['Absent'] ?? 0; ?></h2>
        <p style="color: #666;">Absent</p>
    </div>
    <div class="form-card stat-card" style="border-top: 4px solid #FF9800;">
        <h2 style="color: #FF9800;"><?php echo $attendanceSummary['Leave'] ?? 0; ?></h2>
        <p style="color: #666;">Leave</p>
    </div>
    <div class="form-card stat-card" style="border-top: 4px solid #2196F3;">
        <h2 style="color: #2196F3;"><?php echo $attendanceSummary['Half Leave'] ?? 0; ?></h2>
        <p style="color: #666;">Half Leave</p>
    </div>
</div>

<div class="form-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 15px; gap: 10px;">
        <h3 style="margin: 0;"><i class="fas fa-history"></i> Attendance Records</h3>
        <form method="GET" style="display: flex; gap: 10px; align-items: center; margin: 0;">
            <select name="month" class="form-select form-select-sm" style="width: auto; min-width: 120px;">
                <option value="">All Months</option>
                <?php
                for ($m = 1; $m <= 12; $m++) {
                    $mStr = str_pad($m, 2, '0', STR_PAD_LEFT);
                    $mName = date('F', mktime(0, 0, 0, $m, 1));
                    $selected = ($month === $mStr) ? 'selected' : '';
                    echo "<option value=\"$mStr\" $selected>$mName</option>";
                }
                ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </form>
    </div>
    <?php if (empty($recentAttendance)): ?>
        <p style="color: #666; text-align: center; padding: 20px;">No attendance records found.</p>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAttendance as $record): ?>
                        <tr>
                            <td><?php echo date('D, d M Y', strtotime($record['attendance_date'])); ?></td>
                            <td>
                                <?php 
                                $status = $record['status'];
                                $badgeClass = 'badge ';
                                if ($status === 'Present') $badgeClass .= 'bg-success';
                                elseif ($status === 'Absent') $badgeClass .= 'bg-danger';
                                elseif ($status === 'Leave') $badgeClass .= 'bg-warning text-dark';
                                elseif ($status === 'Half Leave') $badgeClass .= 'bg-info text-dark';
                                else $badgeClass .= 'bg-secondary';
                                ?>
                                <span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                            </td>
                            <?php 
                                $rawRemarks = $record['remarks'] ?? '-';
                                $cleanRemarks = $rawRemarks;
                                if (preg_match('/\((From: .*? To: .*?|Date: .*?)\)$/', $rawRemarks, $matches)) {
                                    $cleanRemarks = trim(str_replace($matches[0], '', $rawRemarks));
                                    if (empty($cleanRemarks)) $cleanRemarks = '-';
                                }
                            ?>
                            <td><?php echo htmlspecialchars($cleanRemarks); ?></td>
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
        <nav aria-label="Attendance pagination">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?month=<?php echo htmlspecialchars($month); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>

    <?php endif; ?>
</div>
