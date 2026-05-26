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

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
    <div class="form-card" style="text-align: center; padding: 20px; border-top: 4px solid #4CAF50;">
        <h2 style="margin: 0; color: #4CAF50;"><?php echo $attendanceSummary['Present'] ?? 0; ?></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Present</p>
    </div>
    <div class="form-card" style="text-align: center; padding: 20px; border-top: 4px solid #F44336;">
        <h2 style="margin: 0; color: #F44336;"><?php echo $attendanceSummary['Absent'] ?? 0; ?></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Absent</p>
    </div>
    <div class="form-card" style="text-align: center; padding: 20px; border-top: 4px solid #FF9800;">
        <h2 style="margin: 0; color: #FF9800;"><?php echo $attendanceSummary['Leave'] ?? 0; ?></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Leave</p>
    </div>
    <div class="form-card" style="text-align: center; padding: 20px; border-top: 4px solid #2196F3;">
        <h2 style="margin: 0; color: #2196F3;"><?php echo $attendanceSummary['Half Leave'] ?? 0; ?></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Half Leave</p>
    </div>
</div>

<div class="form-card">
    <h3><i class="fas fa-history"></i> Recent Attendance (Last 30 Days)</h3>
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
                            <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
