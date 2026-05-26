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
                        <td>
                            <?php if ($s['is_active'] ?? 1): ?>
                                <span style="color:var(--success); font-weight:bold;"><i class="fas fa-check-circle"></i> Active</span>
                            <?php else: ?>
                                <span style="color:var(--gray);"><i class="fas fa-pause-circle"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <div style="display:flex; gap:5px;">
                                <button type="button" class="btn btn-sm btn-warning" onclick="editService(<?php echo htmlspecialchars(json_encode($s)); ?>)"><i class="fas fa-edit"></i></button>
                                <form method="POST" action="<?php echo moduleUrl('admin', 'services'); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="service_id" value="<?php echo $s['service_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-sync"></i></button>
                                </form>
                                <form method="POST" action="<?php echo moduleUrl('admin', 'services'); ?>" style="display:inline;" onsubmit="return confirm('Delete this service?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="service_id" value="<?php echo $s['service_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
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
                    <input type="text" name="service_name" required maxlength="100" placeholder="e.g. Hostel, Transport">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Brief description of the service..."></textarea>
                </div>
                <div class="form-group">
                    <label>Fee Amount (₹)</label>
                    <input type="number" name="fee_amount" step="0.01" min="0" value="0" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
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
                    <input type="text" name="service_name" id="edit_service_name" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Fee Amount (₹)</label>
                    <input type="number" name="fee_amount" id="edit_fee_amount" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="is_active" id="edit_is_active" value="1"> Active</label>
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
    document.getElementById('edit_is_active').checked = service.is_active == 1;
    
    openModal('editServiceModal');
}
</script>
