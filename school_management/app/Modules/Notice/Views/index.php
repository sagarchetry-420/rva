<?php
/**
 * Notices View (Admin)
 * Variables: $pageTitle, $notices
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Create and manage school-wide notices</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openModal('addNoticeModal')"><i class="fas fa-plus"></i> New Notice</button>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="<?php echo moduleUrl('admin', 'notices'); ?>" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; width: 100%;">
        <div class="filter-group">
            <label>Filter Type</label>
            <select name="filter_type" id="filterType" onchange="updateFilterInput()" style="padding: 9px 14px; border: 2px solid var(--border); border-radius: 6px; font-size: 13px; min-width: 160px;">
                <option value="">-- Select Filter --</option>
                <option value="year" <?php echo $filterType === 'year' ? 'selected' : ''; ?>>By Year</option>
                <option value="date" <?php echo $filterType === 'date' ? 'selected' : ''; ?>>By Date</option>
                <option value="day" <?php echo $filterType === 'day' ? 'selected' : ''; ?>>By Day of Week</option>
            </select>
        </div>

        <div class="filter-group" id="filterValueGroup" style="display: none;">
            <label>Select Value</label>
            <!-- Year Filter -->
            <select name="filter_value" id="yearFilter" style="padding: 9px 14px; border: 2px solid var(--border); border-radius: 6px; font-size: 13px; min-width: 160px; display: none;">
                <option value="">-- Select Year --</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo $filterValue == $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Date Filter -->
            <input type="date" name="filter_value" id="dateFilter" value="<?php echo htmlspecialchars($filterValue); ?>" max="<?php echo date('Y-m-d'); ?>" style="padding: 9px 14px; border: 2px solid var(--border); border-radius: 6px; font-size: 13px; min-width: 160px; display: none;">

            <!-- Day of Week Filter -->
            <select name="filter_value" id="dayFilter" style="padding: 9px 14px; border: 2px solid var(--border); border-radius: 6px; font-size: 13px; min-width: 160px; display: none;">
                <option value="">-- Select Day --</option>
                <option value="1" <?php echo $filterValue == 1 ? 'selected' : ''; ?>>Sunday</option>
                <option value="2" <?php echo $filterValue == 2 ? 'selected' : ''; ?>>Monday</option>
                <option value="3" <?php echo $filterValue == 3 ? 'selected' : ''; ?>>Tuesday</option>
                <option value="4" <?php echo $filterValue == 4 ? 'selected' : ''; ?>>Wednesday</option>
                <option value="5" <?php echo $filterValue == 5 ? 'selected' : ''; ?>>Thursday</option>
                <option value="6" <?php echo $filterValue == 6 ? 'selected' : ''; ?>>Friday</option>
                <option value="7" <?php echo $filterValue == 7 ? 'selected' : ''; ?>>Saturday</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 22px;"><i class="fas fa-search"></i> Apply Filter</button>
        <a href="<?php echo moduleUrl('admin', 'notices'); ?>" class="btn btn-secondary" style="margin-top: 22px;"><i class="fas fa-times"></i> Clear Filter</a>
    </form>
</div>

<?php if (empty($notices)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-bullhorn"></i></div>
        <p>No notices published yet. Click "New Notice" to create one.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Audience</th>
                    <th>Date</th>
                    <th>Attachment</th>
                    <th>Status</th>
                    <th class="actions-cell">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notices as $n): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($n['title']); ?></strong><br>
                            <small style="color:var(--gray);"><?php echo htmlspecialchars(mb_strimwidth($n['description'] ?? '', 0, 80, '...')); ?></small>
                        </td>
                        <td><span class="badge" style="background:#e3f2fd; padding:3px 8px; border-radius:4px;"><?php echo ucfirst(htmlspecialchars($n['target_audience'] ?? 'All')); ?></span></td>
                        <td><?php echo isset($n['created_at']) ? date('d M Y', strtotime($n['created_at'])) : 'N/A'; ?></td>
                        <td>
                            <?php if (!empty($n['attachment_path'])): ?>
                                <a href="/RVA/<?php echo htmlspecialchars($n['attachment_path']); ?>" target="_blank" download class="btn btn-sm btn-primary" title="Download Attachment">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            <?php else: ?>
                                <span style="color:var(--gray); font-size: 12px;">No attachment</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($n['is_active'] ?? 1): ?>
                                <span style="color:var(--success); font-weight:bold;"><i class="fas fa-eye"></i> Visible</span>
                            <?php else: ?>
                                <span style="color:var(--gray);"><i class="fas fa-eye-slash"></i> Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <div style="display:flex; gap:5px;">
                                <form method="POST" action="<?php echo moduleUrl('admin', 'notices'); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="notice_id" value="<?php echo $n['notice_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-info" title="Toggle Visibility"><i class="fas fa-eye"></i></button>
                                </form>
                                <form method="POST" action="<?php echo moduleUrl('admin', 'notices'); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="broadcast">
                                    <input type="hidden" name="notice_id" value="<?php echo $n['notice_id']; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo !empty($n['is_broadcasted']) ? 'btn-success' : 'btn-warning'; ?>" title="Broadcast Notice"><i class="fas fa-bullhorn"></i></button>
                                </form>
                                <form method="POST" action="<?php echo moduleUrl('admin', 'notices'); ?>" style="display:inline;" onsubmit="return confirm('Delete this notice?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="notice_id" value="<?php echo $n['notice_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (isset($pagination)): ?>
            <div class="no-print" data-html2canvas-ignore="true" style="padding: 10px;">
                <?php echo renderPagination($pagination); ?>
                <div style="text-align: center; margin-top: 10px; color: var(--gray); font-size: 13px;">
                    Showing page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['pages']; ?> (Total: <?php echo $pagination['total']; ?> notices)
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Add Notice Modal -->
<div id="addNoticeModal" class="modal">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h2>Publish New Notice</h2>
            <span class="close" onclick="closeModal('addNoticeModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'notices'); ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" required maxlength="200" placeholder="e.g. School Closed on Monday">
                </div>
                <div class="form-group">
                    <label>Content *</label>
                    <textarea name="content" rows="5" required placeholder="Full notice content..."></textarea>
                </div>
                <div class="form-group">
                    <label>Target Audience</label>
                    <select name="target_role" class="form-control">
                        <option value="all">Everyone</option>
                        <option value="teacher">Teachers Only</option>
                        <option value="student">Students Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Attachment (Optional)</label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip">
                    <small style="color: #666;">Accepted: PDF, Word, Excel, PowerPoint, Images, ZIP (Max 5MB)</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addNoticeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-bullhorn"></i> Publish Notice</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateFilterInput() {
    const filterType = document.getElementById('filterType').value;
    const yearFilter = document.getElementById('yearFilter');
    const dateFilter = document.getElementById('dateFilter');
    const dayFilter = document.getElementById('dayFilter');
    const filterValueGroup = document.getElementById('filterValueGroup');

    // Hide all filters
    yearFilter.style.display = 'none';
    dateFilter.style.display = 'none';
    dayFilter.style.display = 'none';

    if (!filterType) {
        filterValueGroup.style.display = 'none';
        return;
    }

    filterValueGroup.style.display = 'block';

    // Show appropriate filter
    if (filterType === 'year') {
        yearFilter.style.display = 'block';
        yearFilter.name = 'filter_value';
        dateFilter.name = '';
        dayFilter.name = '';
    } else if (filterType === 'date') {
        dateFilter.style.display = 'block';
        dateFilter.name = 'filter_value';
        yearFilter.name = '';
        dayFilter.name = '';
    } else if (filterType === 'day') {
        dayFilter.style.display = 'block';
        dayFilter.name = 'filter_value';
        yearFilter.name = '';
        dateFilter.name = '';
    }
}

// Initialize filter display on page load
document.addEventListener('DOMContentLoaded', function() {
    updateFilterInput();
});
</script>