<?php
/**
 * Hall of Fame View (Admin)
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-trophy"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Manage the Hall of Fame section on the homepage</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openModal('addFameModal')"><i class="fas fa-plus"></i> Add Entry</button>
    </div>
</div>

<?php if (empty($entries)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-trophy"></i></div>
        <p>No entries found. Click "Add Entry" to create one.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Achievement</th>
                    <th>Percentage</th>
                    <th>Status</th>
                    <th class="actions-cell">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $e): ?>
                    <tr>
                        <td>
                            <?php if (!empty($e['image_path'])): ?>
                                <img src="/rva/<?php echo htmlspecialchars($e['image_path']); ?>" alt="Photo" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%; cursor: pointer;" class="clickable-photo" data-image="<?php echo htmlspecialchars($e['image_path']); ?>" data-name="<?php echo htmlspecialchars($e['name']); ?>" onclick="openPhotoPreview_hof(this)">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-user text-muted"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($e['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($e['achievement']); ?></td>
                        <td><span class="badge" style="background:#e3f2fd; padding:3px 8px; border-radius:4px;"><?php echo htmlspecialchars($e['percentage']); ?></span></td>
                        <td>
                            <?php if ($e['is_active']): ?>
                                <span style="color:var(--success); font-weight:bold;"><i class="fas fa-eye"></i> Visible</span>
                            <?php else: ?>
                                <span style="color:var(--gray);"><i class="fas fa-eye-slash"></i> Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <div style="display:flex; gap:5px;">
                                <form method="POST" action="<?php echo moduleUrl('admin', 'hall-of-fame'); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-info" title="Toggle Visibility"><i class="fas fa-eye"></i></button>
                                </form>
                                <form method="POST" action="<?php echo moduleUrl('admin', 'hall-of-fame'); ?>" style="display:inline;" onsubmit="return confirm('Delete this entry?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (isset($pagination)): ?>
            <div class="no-print" data-html2canvas-ignore="true" style="padding: 15px;">
                <?php echo renderPagination($pagination); ?>
                <div style="text-align: center; margin-top: 10px; color: var(--gray); font-size: 13px;">
                    Showing page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['pages']; ?> (Total: <?php echo $pagination['total']; ?> entries)
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Add Entry Modal -->
<div id="addFameModal" class="modal">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h2>Add Hall of Fame Entry</h2>
            <span class="close" onclick="closeModal('addFameModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'hall-of-fame'); ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label>Student Name *</label>
                    <input type="text" name="name" required pattern="^[^0-9]+$" title="Name should not contain numbers" placeholder="e.g. Priya Sharma">
                </div>
                <div class="form-group">
                    <label>Achievement *</label>
                    <input type="text" name="achievement" required placeholder="e.g. 12th Science Topper">
                </div>
                <div class="form-group">
                    <label>Percentage / Score</label>
                    <input type="text" name="percentage" placeholder="e.g. 98.6%">
                </div>
                <div class="form-group">
                    <label>Photo *</label>
                    <input type="file" name="image" id="famePhotoInput" accept="image/*" required class="form-control" onchange="previewImage(this, 'famePreview')">
                    <div style="margin-top: 10px;">
                        <img id="famePreview" src="#" alt="Preview" style="max-width: 100%; max-height: 200px; display: none; border-radius: 8px;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addFameModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Entry</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }
}
</script>

<!-- Photo Preview Modal (Hall of Fame) -->
<div id="photoPreviewModal_hof" class="modal">
    <div class="modal-content" style="max-width:600px; text-align: center;">
        <div class="modal-header">
            <h2>Photo Preview</h2>
            <span class="close" onclick="closeModal('photoPreviewModal_hof')">&times;</span>
        </div>
        <div class="modal-body">
            <img id="previewImg_hof" src="" alt="Photo Preview" style="max-width: 100%; max-height: 500px; border-radius: 8px; border: 2px solid #ddd; padding: 10px;">
            <p id="previewName_hof" style="margin-top: 15px; font-weight: bold; font-size: 18px;"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('photoPreviewModal_hof')">Close</button>
        </div>
    </div>
</div>

<script>
function previewPhoto_hof() {
    const input = document.getElementById('photoInput_hof');
    const preview = document.getElementById('photoPreview_hof');
    const img = document.getElementById('photoImg_hof');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!validTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, GIF, or WebP)');
            input.value = '';
            preview.style.display = 'none';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function openPhotoPreview_hof(element) {
    const imageSrc = element.src;
    const studentName = element.getAttribute('data-name');
    const previewImg = document.getElementById('previewImg_hof');
    const previewName = document.getElementById('previewName_hof');

    console.log('Image Source:', imageSrc);
    console.log('Student Name:', studentName);

    previewImg.onerror = function() {
        console.error('Failed to load image:', imageSrc);
    };

    previewImg.src = imageSrc;
    previewName.textContent = studentName;
    openModal('photoPreviewModal_hof');
}
</script>
