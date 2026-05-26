<?php
/**
 * Gallery View (Admin)
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-images"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Manage the school gallery photos</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openModal('addGalleryModal')"><i class="fas fa-plus"></i> Add Photo</button>
    </div>
</div>

<?php if (empty($gallery)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-images"></i></div>
        <p>No photos found in the gallery. Click "Add Photo" to upload one.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th class="actions-cell">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gallery as $g): ?>
                    <tr>
                        <td>
                            <?php if (!empty($g['image_path'])): ?>
                                <img src="/rva/<?php echo htmlspecialchars($g['image_path']); ?>" alt="Photo" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer;" class="clickable-photo" data-image="<?php echo htmlspecialchars($g['image_path']); ?>" data-title="<?php echo htmlspecialchars($g['title']); ?>" data-category="<?php echo htmlspecialchars($g['category'] ?? 'General'); ?>" onclick="openPhotoPreview_gal(this)">
                            <?php else: ?>
                                <div style="width: 80px; height: 60px; background: #eee; border-radius: 4px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-muted"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($g['title']); ?></strong></td>
                        <td><span class="badge" style="background:#e3f2fd; padding:3px 8px; border-radius:4px;"><?php echo htmlspecialchars($g['category'] ?? 'General'); ?></span></td>
                        <td>
                            <?php if ($g['is_active']): ?>
                                <span style="color:var(--success); font-weight:bold;"><i class="fas fa-eye"></i> Visible</span>
                            <?php else: ?>
                                <span style="color:var(--gray);"><i class="fas fa-eye-slash"></i> Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <div style="display:flex; gap:5px;">
                                <form method="POST" action="<?php echo moduleUrl('admin', 'gallery'); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-info" title="Toggle Visibility"><i class="fas fa-eye"></i></button>
                                </form>
                                <form method="POST" action="<?php echo moduleUrl('admin', 'gallery'); ?>" style="display:inline;" onsubmit="return confirm('Delete this photo?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
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
                    Showing page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['pages']; ?> (Total: <?php echo $pagination['total']; ?> photos)
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Add Gallery Modal -->
<div id="addGalleryModal" class="modal">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h2>Add New Photo</h2>
            <span class="close" onclick="closeModal('addGalleryModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'gallery'); ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label>Title / Description *</label>
                    <input type="text" name="title" required placeholder="e.g. Annual Sports Day 2026">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" class="form-control" placeholder="e.g. Campus, Sports, General" required>
                </div>
                <div class="form-group">
                    <label>Photo *</label>
                    <input type="file" name="image" accept="image/*" required class="form-control" onchange="previewImage(this, 'galleryPreview')">
                    <div style="margin-top: 10px;">
                        <img id="galleryPreview" src="#" alt="Preview" style="max-width: 100%; max-height: 200px; display: none; border-radius: 8px;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addGalleryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Photo</button>
            </div>
        </form>
    </div>
</div>

<script>
if (typeof previewImage !== 'function') {
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
}
</script>

<!-- Photo Preview Modal (Gallery) -->
<div id="photoPreviewModal_gal" class="modal">
    <div class="modal-content" style="max-width:700px; text-align: center;">
        <div class="modal-header">
            <h2>Photo Preview</h2>
            <span class="close" onclick="closeModal('photoPreviewModal_gal')">&times;</span>
        </div>
        <div class="modal-body">
            <img id="previewImg_gal" src="" alt="Photo Preview" style="max-width: 100%; max-height: 500px; border-radius: 8px; border: 2px solid #ddd; padding: 10px;">
            <p id="previewTitle_gal" style="margin-top: 15px; font-weight: bold; font-size: 18px;"></p>
            <p id="previewCategory_gal" style="color: #666; font-size: 14px;"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('photoPreviewModal_gal')">Close</button>
        </div>
    </div>
</div>

<script>
function previewPhoto_gal() {
    const input = document.getElementById('photoInput_gal');
    const preview = document.getElementById('photoPreview_gal');
    const img = document.getElementById('photoImg_gal');

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

function openPhotoPreview_gal(element) {
    const imageSrc = element.src;
    const title = element.getAttribute('data-title');
    const category = element.getAttribute('data-category');
    const previewImg = document.getElementById('previewImg_gal');
    const previewTitle = document.getElementById('previewTitle_gal');
    const previewCategory = document.getElementById('previewCategory_gal');

    console.log('Image Source:', imageSrc);
    console.log('Title:', title);
    console.log('Category:', category);

    previewImg.onerror = function() {
        console.error('Failed to load image:', imageSrc);
    };

    previewImg.src = imageSrc;
    previewTitle.textContent = title;
    previewCategory.textContent = 'Category: ' + category;
    openModal('photoPreviewModal_gal');
}
</script>
