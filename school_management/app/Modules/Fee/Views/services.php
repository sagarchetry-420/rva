<?php
/**
 * Student Services View
 * Variables: $pageTitle, $services
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Manage student services (hostel, transport, library, etc.)</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openModal('addServiceModal')"><i class="fas fa-plus"></i> Add Service</button>
    </div>
</div>

<?php if (empty($services)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-layer-group"></i></div>
        <p>No student services configured yet. Click "Add Service" to create one.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Service Name</th>
                    <th>Description</th>
                    <th>Fee Amount</th>
                    <th>Billing Cycle</th>
                    <th>Status</th>
                    <th class="actions-cell">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($s['service_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['description'] ?? '—'); ?></td>
                        <td>₹<?php echo number_format((float)($s['fee_amount'] ?? 0), 2); ?></td>
                        <td><?php echo htmlspecialchars($s['billing_cycle'] ?? 'One-Time'); ?></td>
                        <td>
                            <?php if ($s['is_active'] ?? 1): ?>
                                <span style="color:var(--success); font-weight:bold;"><i class="fas fa-check-circle"></i> Active</span>
                            <?php else: ?>
                                <span style="color:var(--gray);"><i class="fas fa-pause-circle"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <div style="display:flex; gap:5px; align-items:center; position:relative;" onmouseleave="this.querySelector('.dropdown-menu').style.display='none'">
                                <a href="<?php echo moduleUrl('admin', 'service_class_fees'); ?>?service_id=<?php echo $s['service_id']; ?>" class="btn btn-sm btn-primary" title="Class Prices">
                                    <i class="fas fa-tags"></i> Class Prices
                                </a>
                                
                                <button type="button" onclick="this.nextElementSibling.style.display='block'" title="More Actions" style="background:none; border:none; color:var(--text); cursor:pointer; padding:5px 8px; font-size:16px;">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu" style="display:none; position:absolute; right:0; top:100%; background:#fff; border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 2px 10px rgba(0,0,0,0.1); z-index:10; min-width:160px; overflow:hidden;">
                                    
                                    <a href="javascript:void(0)" onclick="editService(<?php echo htmlspecialchars(json_encode($s)); ?>)" style="display:block; padding:8px 15px; text-decoration:none; color:var(--text); border-bottom:1px solid var(--border);">
                                        <i class="fas fa-edit" style="width:20px;"></i> Edit
                                    </a>
                                    
                                    <form method="POST" action="<?php echo moduleUrl('admin', 'services'); ?>" style="display:block; margin:0; border-bottom:1px solid var(--border);">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="service_id" value="<?php echo $s['service_id']; ?>">
                                        <button type="submit" style="width:100%; text-align:left; background:none; border:none; padding:8px 15px; cursor:pointer; color:var(--text);">
                                            <?php if ($s['is_active'] ?? 1): ?>
                                                <i class="fas fa-ban" style="width:20px; color:var(--danger);"></i> Mark Inactive
                                            <?php else: ?>
                                                <i class="fas fa-check-circle" style="width:20px; color:var(--success);"></i> Mark Active
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" action="<?php echo moduleUrl('admin', 'services'); ?>" class="no-auto-validate" style="display:block; margin:0;" onsubmit="return confirmServiceDeletion('<?php echo addslashes(htmlspecialchars($s['service_name'], ENT_QUOTES)); ?>');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="service_id" value="<?php echo $s['service_id']; ?>">
                                        <button type="submit" style="width:100%; text-align:left; background:none; border:none; padding:8px 15px; cursor:pointer; color:var(--danger);">
                                            <i class="fas fa-trash" style="width:20px;"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Add Service Modal -->
<div id="addServiceModal" class="modal">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h2>Add New Service</h2>
            <span class="close" onclick="closeModal('addServiceModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'services'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label>Service Name *</label>
                    <input type="text" name="service_name" class="form-control" required maxlength="100" pattern="[a-zA-Z0-9\s\-_]+" title="Only letters, numbers, spaces, hyphens, and underscores are allowed" placeholder="e.g. Hostel, Transport">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" maxlength="255" placeholder="Brief description of the service..."></textarea>
                </div>
                <div class="form-group">
                    <label>Fee Amount (₹)</label>
                    <input type="number" name="fee_amount" class="form-control" step="0.01" min="0" value="0" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Billing Cycle *</label>
                    <select name="billing_cycle" class="form-control" required>
                        <option value="One-Time">One-Time</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Term-wise">Term-wise</option>
                        <option value="Yearly">Yearly</option>
                    </select>
                </div>
                <div class="form-group">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" name="is_active" id="add_is_active" value="1" checked style="width:auto; margin:0;">
                        <label for="add_is_active" style="margin:0; font-weight:normal;">Service is Active</label>
                    </div>
                    <small style="color:var(--gray); display:block; margin-top:5px; margin-left:23px; line-height:1.4;">Unchecking this prevents the service from being assigned to new students or billed automatically.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addServiceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Service</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Service Modal -->
<div id="editServiceModal" class="modal">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h2>Edit Service</h2>
            <span class="close" onclick="closeModal('editServiceModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'services'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="service_id" id="edit_service_id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label>Service Name *</label>
                    <input type="text" name="service_name" id="edit_service_name" class="form-control" required maxlength="100" pattern="[a-zA-Z0-9\s\-_]+" title="Only letters, numbers, spaces, hyphens, and underscores are allowed">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3" maxlength="255"></textarea>
                </div>
                <div class="form-group">
                    <label>Fee Amount (₹)</label>
                    <input type="number" name="fee_amount" id="edit_fee_amount" class="form-control" step="0.01" min="0" value="0" readonly style="background-color:#f1f2f6; cursor:not-allowed;">
                    <small style="color:var(--gray); display:block; margin-top:5px; line-height:1.4;">Base fee amount cannot be changed after creation. Use "Class Prices" to set specific class overrides.</small>
                </div>
                <div class="form-group">
                    <label>Billing Cycle *</label>
                    <select name="billing_cycle" id="edit_billing_cycle" class="form-control" required>
                        <option value="One-Time">One-Time</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Term-wise">Term-wise</option>
                        <option value="Yearly">Yearly</option>
                    </select>
                </div>
                <div class="form-group">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" style="width:auto; margin:0;">
                        <label for="edit_is_active" style="margin:0; font-weight:normal;">Service is Active</label>
                    </div>
                    <small style="color:var(--gray); display:block; margin-top:5px; margin-left:23px; line-height:1.4;">Unchecking this prevents the service from being assigned to new students or billed automatically.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editServiceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Service</button>
            </div>
        </form>
    </div>
</div>

<script>
function editService(service) {
    document.getElementById('edit_service_id').value = service.service_id;
    document.getElementById('edit_service_name').value = service.service_name;
    document.getElementById('edit_description').value = service.description || '';
    document.getElementById('edit_fee_amount').value = service.fee_amount || 0;
    document.getElementById('edit_billing_cycle').value = service.billing_cycle || 'One-Time';
    document.getElementById('edit_is_active').checked = service.is_active == 1;
    
    openModal('editServiceModal');
}

function confirmServiceDeletion(serviceName) {
    var input = prompt("WARNING: You are about to delete this service.\n\nTo confirm, please type the exact service name:\n" + serviceName);
    if (input === serviceName) {
        return true;
    } else if (input !== null) {
        alert("Service name did not match. Deletion cancelled.");
    }
    return false;
}
</script>
