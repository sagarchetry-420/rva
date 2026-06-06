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
    <form method="GET" action="<?php echo moduleUrl('admin', 'notices'); ?>" id="filterForm" onsubmit="return validateFilter()" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; width: 100%;">
        <div class="filter-group">
            <label>Filter Type</label>
            <select name="filter_type" id="filterType" onchange="updateFilterInput()" style="height: 42px; padding: 0 14px; border: 2px solid var(--border); border-radius: 6px; font-size: 13px; min-width: 160px; outline: none;">
                <option value="">-- Select Filter --</option>
                <option value="year" <?php echo $filterType === 'year' ? 'selected' : ''; ?>>By Year</option>
                <option value="date" <?php echo $filterType === 'date' ? 'selected' : ''; ?>>By Date</option>
                <option value="day" <?php echo $filterType === 'day' ? 'selected' : ''; ?>>By Day of Week</option>
            </select>
        </div>

        <div class="filter-group" id="filterValueGroup" style="display: none;">
            <label>Select Value</label>
            <!-- Year Filter -->
            <select name="filter_value" id="yearFilter" style="height: 42px; padding: 0 14px; border: 2px solid var(--border); border-radius: 6px; font-size: 13px; min-width: 160px; outline: none; display: none;">
                <option value="">-- Select Year --</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo $filterValue == $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Date Filter -->
            <input type="date" name="filter_value" id="dateFilter" value="<?php echo htmlspecialchars($filterValue); ?>" max="<?php echo date('Y-m-d'); ?>" style="height: 42px; padding: 0 14px; border: 2px solid var(--border); border-radius: 6px; font-size: 13px; min-width: 160px; outline: none; display: none;">

            <!-- Day of Week Filter -->
            <select name="filter_value" id="dayFilter" style="height: 42px; padding: 0 14px; border: 2px solid var(--border); border-radius: 6px; font-size: 13px; min-width: 160px; outline: none; display: none;">
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

        <button type="submit" class="btn btn-primary" style="height: 42px; padding: 0 20px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;"><i class="fas fa-search"></i> Apply Filter</button>
        <a href="<?php echo moduleUrl('admin', 'notices'); ?>" class="btn btn-secondary" style="height: 42px; padding: 0 20px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;"><i class="fas fa-times"></i> Clear Filter</a>
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
                    <th class="actions-cell" style="display: table-cell; text-align: right;">Actions</th>
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
                                <a href="/<?php echo htmlspecialchars($n['attachment_path']); ?>" target="_blank" download class="btn btn-sm btn-primary" title="Download Attachment">
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
                        <td class="actions-cell" style="display: table-cell; vertical-align: middle;">
                            <div style="display: flex; gap: 8px; align-items: center; justify-content: flex-end;">
                                <form method="POST" action="<?php echo moduleUrl('admin', 'notices'); ?>" style="margin: 0;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="broadcast">
                                    <input type="hidden" name="notice_id" value="<?php echo $n['notice_id']; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo !empty($n['is_broadcasted']) ? 'btn-success' : 'btn-primary'; ?>" title="<?php echo !empty($n['is_broadcasted']) ? 'Remove Broadcast' : 'Broadcast Notice'; ?>" style="padding: 5px 10px;">
                                        <i class="fas fa-bullhorn"></i>
                                    </button>
                                </form>

                                <div style="position: relative;">
                                    <button type="button" style="background: transparent; border: none; color: var(--gray); cursor: pointer; padding: 5px; font-size: 16px; outline: none;" onclick="toggleActionMenu(this)" title="More Actions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="action-menu-dropdown" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 5px; background: white; border: 1px solid var(--border); border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 100; min-width: 150px; padding: 5px 0; text-align: left;">
                                        <form method="POST" action="<?php echo moduleUrl('admin', 'notices'); ?>" style="display: block; margin: 0; padding: 0;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="notice_id" value="<?php echo $n['notice_id']; ?>">
                                            <button type="submit" style="width: 100%; text-align: left; padding: 8px 15px; background: none; border: none; cursor: pointer; color: var(--text);">
                                                <i class="fas fa-<?php echo $n['is_active'] ? 'eye-slash' : 'eye'; ?> fa-fw"></i> <?php echo $n['is_active'] ? 'Hide Notice' : 'Show Notice'; ?>
                                            </button>
                                        </form>
                                        <div style="height: 1px; background: var(--border); margin: 5px 0;"></div>
                                        <form method="POST" action="<?php echo moduleUrl('admin', 'notices'); ?>" style="display: block; margin: 0; padding: 0;" onsubmit="return confirm('Delete this notice?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="notice_id" value="<?php echo $n['notice_id']; ?>">
                                            <button type="submit" style="width: 100%; text-align: left; padding: 8px 15px; background: none; border: none; cursor: pointer; color: var(--danger);">
                                                <i class="fas fa-trash fa-fw"></i> Delete
                                            </button>
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
        <form method="POST" action="<?php echo moduleUrl('admin', 'notices'); ?>" enctype="multipart/form-data" onsubmit="return validateAndSanitizeNotice(this)">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" required maxlength="200" placeholder="e.g. School Closed on Monday" pattern="^[a-zA-Z0-9\s\-_.,!?()]+$" title="Only alphanumeric characters, spaces, and basic punctuation are allowed">
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
                    <input type="file" name="attachment" id="noticeAttachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip">
                    <small style="color: #666;">Accepted: PDF, Word, Excel, PowerPoint, Images, ZIP.</small>
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

function toggleActionMenu(btn) {
    // Close other dropdowns
    document.querySelectorAll('.action-menu-dropdown').forEach(function(menu) {
        if (menu !== btn.nextElementSibling) {
            menu.style.display = 'none';
        }
    });
    
    // Toggle current dropdown
    var menu = btn.nextElementSibling;
    menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.actions-cell')) {
        document.querySelectorAll('.action-menu-dropdown').forEach(function(menu) {
            menu.style.display = 'none';
        });
    }
});

function validateFilter() {
    var filterType = document.getElementById('filterType').value;
    if (filterType && !['year', 'date', 'day'].includes(filterType)) {
        return false;
    }
    
    if (filterType === 'year') {
        var year = document.getElementById('yearFilter').value;
        if (year && !/^\d{4}$/.test(year)) return false;
    } else if (filterType === 'date') {
        var date = document.getElementById('dateFilter').value;
        if (date && !/^\d{4}-\d{2}-\d{2}$/.test(date)) return false;
    } else if (filterType === 'day') {
        var day = document.getElementById('dayFilter').value;
        if (day && !/^[1-7]$/.test(day)) return false;
    }
    
    return true;
}

// Sanitization and validation for Notice modal
function validateAndSanitizeNotice(form) {
    const titleInput = form.elements['title'];
    const contentInput = form.elements['content'];
    
    // Basic sanitization: remove potential SQL injection vectors and XSS tags
    const sanitizeStr = (str) => {
        return str.replace(/['";\\]/g, '') // Basic SQLi prevention
                  .replace(/<[^>]*>?/gm, '') // Remove HTML tags
                  .trim();
    };

    // Strict sanitization for Title to remove symbols
    const sanitizeTitle = (str) => {
        let cleaned = sanitizeStr(str);
        // Allow only alphanumeric, spaces, and basic safe punctuation (- _ . , ! ? ( ))
        return cleaned.replace(/[^a-zA-Z0-9\s\-_.,!?()]/g, '').trim();
    };

    titleInput.value = sanitizeTitle(titleInput.value);
    contentInput.value = sanitizeStr(contentInput.value);

    if (!titleInput.value || !contentInput.value) {
        alert("Title and Content cannot be empty or contain only invalid characters.");
        return false;
    }

    // Check file size and type on submit as a fallback
    const fileInput = form.elements['attachment'];
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        if (file.size > 200 * 1024) {
            alert("Attachment size exceeds 200KB. Please select a smaller file.");
            return false;
        }
        
        const allowedExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip'];
        const fileName = file.name.toLowerCase();
        const fileExt = fileName.split('.').pop();
        if (!allowedExts.includes(fileExt) || /\.(php|phtml|phar|sh|exe|pl|cgi|js|html|htm)\b/i.test(fileName)) {
            alert("Invalid or malicious file type detected. Upload is prohibited.");
            return false;
        }
    }

    return true;
}

// Client-side image compression and file size limit
document.addEventListener('DOMContentLoaded', function() {
    const attachmentInput = document.getElementById('noticeAttachment');
    if (attachmentInput) {
        attachmentInput.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const allowedExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip'];
            const fileName = file.name.toLowerCase();
            const fileExt = fileName.split('.').pop();
            
            if (!allowedExts.includes(fileExt) || /\.(php|phtml|phar|sh|exe|pl|cgi|js|html|htm)\b/i.test(fileName)) {
                alert("Invalid or malicious file type detected. Upload is prohibited.");
                e.target.value = '';
                return;
            }

            const MAX_SIZE = 200 * 1024; // 200 KB

            if (file.size > MAX_SIZE) {
                if (file.type.startsWith('image/')) {
                    alert("Image is larger than 200KB. Compressing...");
                    try {
                        const compressedFile = await compressImage(file, MAX_SIZE);
                        const dt = new DataTransfer();
                        dt.items.add(compressedFile);
                        e.target.files = dt.files;
                        alert("Image compressed to " + (compressedFile.size / 1024).toFixed(2) + " KB.");
                    } catch (error) {
                        alert("Image compression failed. Please select an image under 200KB.");
                        e.target.value = '';
                    }
                } else {
                    alert("File size exceeds 200KB limit. Please choose a smaller file.");
                    e.target.value = '';
                }
            }
        });
    }
});

function compressImage(file, maxSize) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = function(event) {
            const img = new Image();
            img.src = event.target.result;
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                let width = img.width;
                let height = img.height;
                const MAX_WIDTH = 1200;
                const MAX_HEIGHT = 1200;
                
                if (width > height) {
                    if (width > MAX_WIDTH) {
                        height *= MAX_WIDTH / width;
                        width = MAX_WIDTH;
                    }
                } else {
                    if (height > MAX_HEIGHT) {
                        width *= MAX_HEIGHT / height;
                        height = MAX_HEIGHT;
                    }
                }
                
                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0, width, height);
                
                let quality = 0.9;
                const compress = () => {
                    canvas.toBlob((blob) => {
                        if (!blob) {
                            reject(new Error("Canvas to Blob failed"));
                            return;
                        }
                        if (blob.size <= maxSize || quality <= 0.1) {
                            const compressedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            resolve(compressedFile);
                        } else {
                            quality -= 0.1;
                            compress();
                        }
                    }, 'image/jpeg', quality);
                };
                compress();
            };
            img.onerror = () => reject(new Error("Failed to load image"));
        };
        reader.onerror = () => reject(new Error("Failed to read file"));
    });
}
</script>