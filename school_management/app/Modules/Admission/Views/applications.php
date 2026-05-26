<?php
/**
 * Student Applications View
 * Variables: $pageTitle, $applications
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-pen-to-square"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Manage incoming student applications</p>
    </div>
</div>

<style>
.filter-form-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
    margin-bottom: 25px;
}
.search-input-group {
    position: relative;
    flex: 1;
    min-width: 300px;
    display: flex;
    align-items: center;
}
.search-input-group i {
    position: absolute;
    left: 18px;
    color: #9ca3af;
    font-size: 15px;
}
.search-input-group input {
    width: 100%;
    padding: 12px 16px 12px 45px;
    border: 1.5px solid #e5e7eb;
    border-radius: 4px;
    font-size: 14.5px;
    background-color: #fff;
    transition: all 0.2s ease;
    color: #374151;
}
.search-input-group input:focus {
    background-color: #ffffff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    outline: none;
}
.search-input-group input::placeholder {
    color: #9ca3af;
}
.filter-select {
    padding: 12px 20px;
    border: 1.5px solid #e5e7eb;
    border-radius: 4px;
    font-size: 14.5px;
    background-color: #fff;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 160px;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 16px;
}
.filter-select:focus {
    border-color: #3b82f6;
    background-color: #ffffff;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    outline: none;
}
.btn-filter {
    padding: 12px 24px;
    background: #1a73e8;
    color: #ffffff;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 6px rgba(26, 115, 232, 0.3);
}
.btn-filter:hover {
    background: #1557b0;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(26, 115, 232, 0.4);
}
.btn-export-pdf {
    padding: 12px 20px;
    background: #fff0f2;
    color: #e11d48;
    border: 1px solid #ffe4e6;
    border-radius: 4px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-export-pdf:hover {
    background: #ffe4e6;
    border-color: #fecdd3;
    color: #be123c;
}
.actions-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}
.action-dropdown {
    position: relative;
    display: inline-block;
}
.action-dropdown-btn {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    cursor: pointer;
    padding: 6px 12px;
    font-size: 14px;
    color: #4b5563;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.action-dropdown-btn:hover {
    background: #e5e7eb;
    color: #111827;
}
.action-dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #ffffff;
    min-width: 180px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    border-radius: 8px;
    z-index: 100;
    border: 1px solid #e5e7eb;
    padding: 8px 0;
}
.action-dropdown:hover .action-dropdown-content {
    display: block;
}
.action-dropdown-content form {
    margin: 0;
}
.action-dropdown-content a,
.action-dropdown-content button {
    color: #374151;
    padding: 10px 16px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
}
.action-dropdown-content a:hover,
.action-dropdown-content button:hover {
    background-color: #f9fafb;
    color: #111827;
}
.action-dropdown-content .text-danger {
    color: #dc2626;
}
.action-dropdown-content .text-danger:hover {
    background-color: #fef2f2;
    color: #b91c1c;
}
.action-dropdown-content .text-info {
    color: #2563eb;
}
.table-container {
    overflow: visible !important;
}
</style>

<form method="GET" action="index.php" class="filter-form-wrapper">
    <input type="hidden" name="module" value="admin">
    <input type="hidden" name="action" value="applications">
    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus ?? 'pending'); ?>">
    
    <div class="search-input-group">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Search by APP-ID, Student Name, or Phone..." value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>" autocomplete="off">
    </div>
    
    <div>
        <select name="class_id" class="filter-select">
            <option value="">All Classes</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?php echo $c['class_id']; ?>" <?php echo $filterClass === $c['class_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div>
        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
    </div>
    
    <?php if (($filterStatus ?? 'pending') === 'approved'): ?>
        <div>
            <button type="submit" name="download_pdf" value="1" class="btn-export-pdf" formtarget="_blank">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
        </div>
    <?php endif; ?>
</form>

<div class="tabs" style="margin-bottom: 20px; border-bottom: 2px solid #eee; display: flex; gap: 20px;">
    <?php $fs = $filterStatus ?? 'pending'; ?>
    <a href="<?php echo moduleUrl('admin', 'applications'); ?>?status=pending&class_id=<?php echo $filterClass; ?>" style="text-decoration:none; padding-bottom: 10px; font-weight: bold; border-bottom: <?php echo $fs === 'pending' ? '3px solid var(--primary)' : 'none'; ?>; color: <?php echo $fs === 'pending' ? 'var(--primary)' : '#666'; ?>;">Pending</a>
    <a href="<?php echo moduleUrl('admin', 'applications'); ?>?status=approved&class_id=<?php echo $filterClass; ?>" style="text-decoration:none; padding-bottom: 10px; font-weight: bold; border-bottom: <?php echo $fs === 'approved' ? '3px solid var(--primary)' : 'none'; ?>; color: <?php echo $fs === 'approved' ? 'var(--primary)' : '#666'; ?>;">Merit List (Approved)</a>
    <a href="<?php echo moduleUrl('admin', 'applications'); ?>?status=enrolled&class_id=<?php echo $filterClass; ?>" style="text-decoration:none; padding-bottom: 10px; font-weight: bold; border-bottom: <?php echo $fs === 'enrolled' ? '3px solid var(--primary)' : 'none'; ?>; color: <?php echo $fs === 'enrolled' ? 'var(--primary)' : '#666'; ?>;">Enrolled</a>
    <a href="<?php echo moduleUrl('admin', 'applications'); ?>?status=rejected&class_id=<?php echo $filterClass; ?>" style="text-decoration:none; padding-bottom: 10px; font-weight: bold; border-bottom: <?php echo $fs === 'rejected' ? '3px solid var(--primary)' : 'none'; ?>; color: <?php echo $fs === 'rejected' ? 'var(--primary)' : '#666'; ?>;">Rejected</a>
</div>

<?php if (empty($applications)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-inbox"></i></div>
        <p>No student applications received yet.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Application ID</th>
                    <th>Applicant Name</th>
                    <th>Class Applied</th>
                    <th>Contact</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="actions-cell">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><strong>APP-<?php echo str_pad($app['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                        <td><strong><?php echo htmlspecialchars($app['student_name'] ?? 'N/A'); ?></strong></td>
                        <td><?php echo htmlspecialchars(($app['class_name'] ?? '') . ' ' . ($app['section'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($app['phone'] ?? $app['email'] ?? 'N/A'); ?></td>
                        <td><?php echo isset($app['created_at']) ? date('d M Y', strtotime($app['created_at'])) : 'N/A'; ?></td>
                        <td>
                            <?php 
                            $status = $app['status'] ?? 'pending';
                            $badgeColor = 'var(--warning)';
                            if ($status === 'approved') $badgeColor = 'var(--info)';
                            if ($status === 'enrolled') $badgeColor = 'var(--success)';
                            if ($status === 'rejected') $badgeColor = 'var(--danger)';
                            ?>
                            <span style="color:<?php echo $badgeColor; ?>; font-weight:bold;">
                                <?php echo $status === 'approved' ? 'Merit List' : ucfirst($status); ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <div class="actions-wrapper">
                                <!-- Primary Action Button -->
                                <?php if (($app['status'] ?? 'pending') === 'pending'): ?>
                                    <form method="POST" action="<?php echo moduleUrl('admin', 'applications'); ?>" style="margin:0;" title="Approve & add to Merit List">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approve</button>
                                    </form>
                                <?php elseif (($app['status'] ?? '') === 'approved'): ?>
                                    <form method="POST" action="<?php echo moduleUrl('admin', 'applications'); ?>" style="margin:0;" title="Officially Enroll Student">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="enroll">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-user-plus"></i> Enroll</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#9ca3af; font-size:13px; font-style:italic;">No Actions</span>
                                <?php endif; ?>

                                <!-- Dropdown for Documents and Secondary Actions -->
                                <div class="action-dropdown">
                                    <button type="button" class="action-dropdown-btn" title="More Options" style="width: 32px; height: 32px;">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="action-dropdown-content">
                                        <!-- Documents Section -->
                                        <?php 
                                        if (!empty($app['documents'])) {
                                            $docs = json_decode($app['documents'], true);
                                            if (is_array($docs)) {
                                                foreach ($docs as $index => $docPath) {
                                                    $docNum = $index + 1;
                                                    echo '<a href="' . baseUrl($docPath) . '" target="_blank" class="text-info"><i class="fas fa-file-alt"></i> View Document ' . $docNum . '</a>';
                                                }
                                            } elseif (is_string($app['documents']) && strpos($app['documents'], 'uploads/') !== false) {
                                                echo '<a href="' . baseUrl($app['documents']) . '" target="_blank" class="text-info"><i class="fas fa-file-alt"></i> View Document</a>';
                                            }
                                        }
                                        ?>
                                        
                                        <!-- Divider if there are documents -->
                                        <?php if (!empty($app['documents'])): ?>
                                            <div style="border-top: 1px solid #e5e7eb; margin: 4px 0;"></div>
                                        <?php endif; ?>

                                        <?php if (($app['status'] ?? 'pending') === 'pending'): ?>
                                            <form method="POST" action="<?php echo moduleUrl('admin', 'applications'); ?>" onsubmit="return confirm('Reject this application?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                <button type="submit" class="text-danger"><i class="fas fa-times-circle"></i> Reject Application</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" action="<?php echo moduleUrl('admin', 'applications'); ?>" onsubmit="return confirm('Permanently delete this application?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <button type="submit" class="text-danger"><i class="fas fa-trash-alt"></i> Delete Record</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (isset($pagination)): ?>
            <div class="no-print" data-html2canvas-ignore="true">
                <?php echo renderPagination($pagination); ?>
                <div style="text-align: center; margin-top: 10px; color: var(--gray); font-size: 13px;">
                    Showing page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['pages']; ?> (Total: <?php echo $pagination['total']; ?> applications)
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
