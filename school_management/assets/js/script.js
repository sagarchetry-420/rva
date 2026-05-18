/**
 * School Management System - Main JavaScript
 */

// ═══ SIDEBAR TOGGLE ═══
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}

// Close sidebar on outside click (mobile)
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('menuToggle');
    if (sidebar && toggle && window.innerWidth <= 768) {
        if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
});

// ═══ MODAL FUNCTIONS ═══
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

// Close modal on backdrop click
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
    }
});

// ═══ CONFIRM DELETE ═══
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item? This action cannot be undone.');
}

// ═══ FLASH MESSAGES AUTO-DISMISS ═══
document.addEventListener('DOMContentLoaded', function() {
    const flash = document.getElementById('flashMessage');
    if (flash) {
        setTimeout(function() {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-10px)';
            setTimeout(() => flash.remove(), 300);
        }, 4000);
    }
});

// ═══ TABLE SEARCH ═══
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    
    const filter = input.value.toLowerCase();
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// ═══ GLOBAL FORM VALIDATION & WORKFLOWS ═══
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Skip forms that explicitly opt-out
        if (form.classList.contains('no-auto-validate')) return;
        
        form.addEventListener('submit', function(e) {
            // Check HTML5 validity
            if (!form.checkValidity()) {
                e.preventDefault();
                // Find first invalid element and focus it
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) firstInvalid.focus();
                
                // Add a shake effect to invalid inputs
                const invalidInputs = form.querySelectorAll(':invalid');
                invalidInputs.forEach(input => {
                    input.classList.remove('shake');
                    void input.offsetWidth; // trigger reflow
                    input.classList.add('shake');
                });
                return;
            }
            
            // Prevent multiple submissions and show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                // Save original text if not already saved
                if (!submitBtn.dataset.originalHtml) {
                    submitBtn.dataset.originalHtml = submitBtn.innerHTML;
                }
                
                // Set loading state
                submitBtn.innerHTML = 'Processing <i class="fa-solid fa-spinner fa-spin"></i>';
                submitBtn.classList.add('loading');
                submitBtn.style.opacity = '0.8';
                submitBtn.style.cursor = 'not-allowed';
                
                // Disable button after a tiny delay so form still submits
                setTimeout(() => {
                    submitBtn.disabled = true;
                }, 50);
            }
        });
    });
});

// Custom confirm dialog for delete actions
function confirmDelete(message) {
    // We could use a custom modal here, but for now we'll stick to native confirm
    // while ensuring it looks like an intentional workflow pause
    return confirm(message || 'Are you absolutely sure you want to delete this item? This action cannot be undone.');
}

// ═══ SELECT ALL CHECKBOXES ═══
function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
}
