<?php
/**
 * Academic Sessions Management View
 */
?>
<style>
.action-menu { position: relative; display: inline-flex; align-items: center; gap: 5px; }
.action-menu-btn { background: none; border: none; cursor: pointer; font-size: 18px; color: var(--gray); padding: 5px 10px; border-radius: 4px; }
.action-menu-btn:hover { background: #f1f5f9; color: var(--primary); }
.action-menu-content { display: none; position: absolute; right: 0; top: 100%; background-color: white; min-width: 150px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; border-radius: 6px; overflow: hidden; border: 1px solid var(--border); }
.action-menu-content a, .action-menu-content button { display: block; width: 100%; text-align: left; padding: 10px 15px; text-decoration: none; color: var(--text); border: none; background: none; cursor: pointer; font-size: 13px; }
.action-menu-content a:hover, .action-menu-content button:hover { background-color: #f8fafc; color: var(--primary); }
.action-menu-content .text-danger:hover { color: #ef4444; }
.action-menu:hover .action-menu-content { display: block; }
tbody tr:last-child .action-menu-content, 
tbody tr:nth-last-child(2) .action-menu-content,
tbody tr:nth-last-child(3) .action-menu-content { top: auto; bottom: 100%; }
</style>
<div class="page-header">
    <div>
        <h1><i class="fas fa-calendar-check"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Manage academic years and switch the active session</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-primary" onclick="openAddSessionModal()">
            <i class="fas fa-plus"></i> Add New Session
        </button>
    </div>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Session Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Description</th>
                <th>Status</th>
                <th class="actions-cell">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sessions)): ?>
                <tr>
                    <td colspan="6"><div class="empty-state"><div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div><p>No academic sessions found.</p></div></td>
                </tr>
            <?php else: ?>
                <?php foreach ($sessions as $s): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($s['session_name']); ?></strong></td>
                        <td><?php echo date('M d, Y', strtotime($s['start_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($s['end_date'])); ?></td>
                        <td><?php echo htmlspecialchars($s['description'] ?: '-'); ?></td>
                        <td>
                            <?php if ($s['is_current']): ?>
                                <span style="background-color: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;"><i class="fas fa-check"></i> Active</span>
                            <?php else: ?>
                                <span style="background-color: #e5e7eb; color: #374151; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell" style="overflow: visible; white-space: nowrap;">
                            <div style="display: inline-flex; align-items: center; gap: 5px;">
                                <button type="button" class="btn btn-sm btn-info" onclick='openEditSessionModal(<?php echo htmlspecialchars(json_encode($s)); ?>)' style="border-radius: 4px;">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <div class="action-menu">
                                    <button class="action-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="action-menu-content">
                                    <?php if (!$s['is_current']): ?>
                                        <form method="POST" action="<?php echo moduleUrl('admin', 'academic_sessions'); ?>" onsubmit="return confirm('Are you sure you want to set this as the active session? This will affect all users immediately.');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="set_active">
                                            <input type="hidden" name="session_id" value="<?php echo $s['session_id']; ?>">
                                            <button type="submit" style="color: #10b981;"><i class="fas fa-check-circle" style="width: 20px;"></i> Set Active</button>
                                        </form>
                                        
                                        <hr style="margin: 5px 0; border: none; border-top: 1px solid var(--border);">
                                        
                                        <form method="POST" action="<?php echo moduleUrl('admin', 'academic_sessions'); ?>" onsubmit="return confirmDelete('Delete this session? This action cannot be undone.');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="session_id" value="<?php echo $s['session_id']; ?>">
                                            <button type="submit" class="text-danger"><i class="fas fa-trash" style="width: 20px;"></i> Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <button disabled style="color: #9ca3af; cursor: not-allowed;"><i class="fas fa-ban" style="width: 20px;"></i> No other actions</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Add/Edit Session -->
<div id="sessionModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2 id="modalTitle">Add Academic Session</h2>
            <span class="close" onclick="closeModal('sessionModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'academic_sessions'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create" id="formAction">
            <input type="hidden" name="session_id" value="" id="sessionId">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>Session Name *</label>
                    <input type="text" name="session_name" id="sessionName" required placeholder="e.g. 2025-26">
                </div>
                
                <div class="form-row" style="display:flex; gap:15px; margin-bottom:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Start Month *</label>
                        <select name="start_month" id="startMonth" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                            <option value="">-- Select Month --</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Start Year *</label>
                        <input type="number" name="start_year" id="startYear" min="2000" max="2100" placeholder="e.g. 2026" required>
                    </div>
                </div>
                
                <div class="form-row" style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>End Month *</label>
                        <select name="end_month" id="endMonth" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                            <option value="">-- Select Month --</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>End Year *</label>
                        <input type="number" name="end_year" id="endYear" min="2000" max="2100" placeholder="e.g. 2027" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="sessionDescription" rows="3" placeholder="Optional description..."></textarea>
                </div>

                <div class="form-group" id="isCurrentContainer" style="display: flex; align-items: center; gap: 10px; background: #fffbeb; padding: 10px; border-radius: 4px; border: 1px solid #fde68a;">
                    <input type="checkbox" id="isCurrent" name="is_current" value="1" style="width: 18px; height: 18px; margin: 0;">
                    <div>
                        <label for="isCurrent" style="margin: 0; font-weight: bold; color: #b45309; cursor: pointer;">Set as Active Session</label>
                        <div style="font-size: 12px; color: #b45309; margin-top: 2px;">This will instantly switch the entire system to this year.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('sessionModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveBtn"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('startYear').addEventListener('input', function() {
        const startYearVal = parseInt(this.value, 10);
        if (!isNaN(startYearVal)) {
            const endYearVal = startYearVal + 1;
            document.getElementById('endYear').value = endYearVal;
            
            // Format session name like "2026-27"
            const endYearShort = endYearVal.toString().substring(2);
            document.getElementById('sessionName').value = startYearVal + '-' + endYearShort;
        } else {
            document.getElementById('endYear').value = '';
            document.getElementById('sessionName').value = '';
        }
    });

    function openAddSessionModal() {
        document.getElementById('modalTitle').textContent = 'Add Academic Session';
        document.getElementById('formAction').value = 'create';
        document.getElementById('sessionId').value = '';
        
        document.getElementById('sessionName').value = '';
        document.getElementById('startMonth').value = '';
        document.getElementById('startYear').value = '';
        document.getElementById('endMonth').value = '';
        document.getElementById('endYear').value = '';
        document.getElementById('sessionDescription').value = '';
        
        document.getElementById('isCurrentContainer').style.display = 'flex';
        document.getElementById('isCurrent').checked = false;
        
        document.getElementById('saveBtn').innerHTML = '<i class="fas fa-save"></i> Create Session';
        openModal('sessionModal');
    }

    function openEditSessionModal(session) {
        document.getElementById('modalTitle').textContent = 'Edit Academic Session';
        document.getElementById('formAction').value = 'update';
        document.getElementById('sessionId').value = session.session_id;
        
        document.getElementById('sessionName').value = session.session_name;
        document.getElementById('startYear').value = session.start_date.substring(0, 4);
        document.getElementById('startMonth').value = parseInt(session.start_date.substring(5, 7), 10);
        document.getElementById('endYear').value = session.end_date.substring(0, 4);
        document.getElementById('endMonth').value = parseInt(session.end_date.substring(5, 7), 10);
        document.getElementById('sessionDescription').value = session.description;
        
        document.getElementById('isCurrentContainer').style.display = 'none'; // Only set active via the dedicated button in the table
        
        document.getElementById('saveBtn').innerHTML = '<i class="fas fa-save"></i> Save Changes';
        openModal('sessionModal');
    }
</script>
