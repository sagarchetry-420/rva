/**
 * Client-Side Image Compression and Document Size Validation
 * Requires: Compressor.js (https://cdnjs.cloudflare.com/ajax/libs/compressorjs/1.2.1/compressor.min.js)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Attach to all file inputs that need compression/validation
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        // Skip csv inputs
        if (input.accept && input.accept.includes('.csv') && !input.accept.includes('image')) return;

        input.addEventListener('change', async function(e) {
            const files = Array.from(e.target.files);
            if (files.length === 0) return;

            // Find closest form and disable submit button
            const form = e.target.closest('form');
            let submitBtn = null;
            if (form) {
                submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.dataset.originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
                }
            }

            const dataTransfer = new DataTransfer();
            let hasError = false;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];

                // Check if file is a document (PDF, DOC, etc.)
                if (file.type === 'application/pdf' || file.name.match(/\.(pdf|doc|docx|xls|xlsx|ppt|pptx)$/i)) {
                    // 1.5MB limit for documents
                    if (file.size > 1.5 * 1024 * 1024) {
                        alert(`Document "${file.name}" is too large (${(file.size/1024/1024).toFixed(2)} MB).\nClient-side compression for PDFs is not supported. Please upload a document smaller than 1.5 MB.`);
                        hasError = true;
                        continue; 
                    }
                    dataTransfer.items.add(file);
                } 
                // Check if file is an image
                else if (file.type.startsWith('image/')) {
                    try {
                        const compressedFile = await compressImage(file);
                        dataTransfer.items.add(compressedFile);
                    } catch (err) {
                        console.error('Compression error:', err);
                        // Fallback to original if compression fails
                        dataTransfer.items.add(file);
                    }
                } 
                else {
                    // Other files, just add them
                    dataTransfer.items.add(file);
                }
            }

            if (hasError && dataTransfer.files.length === 0) {
                // If the only file was rejected, clear the input completely
                e.target.value = '';
            } else {
                // Update the input with the compressed/validated files
                e.target.files = dataTransfer.files;
                
                // Triggering a custom event so preview scripts can re-render if needed.
                const event = new CustomEvent('filesCompressed', { detail: { files: dataTransfer.files } });
                e.target.dispatchEvent(event);
            }

            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.dataset.originalText;
            }
        });
    });
});

/**
 * Compresses an image file using Compressor.js
 * Goal: Max 150KB. We use aggressive quality settings for large files.
 */
function compressImage(file) {
    return new Promise((resolve, reject) => {
        if (typeof Compressor === 'undefined') {
            console.warn('Compressor.js is not loaded.');
            resolve(file);
            return;
        }

        // If it's already tiny, don't compress
        if (file.size <= 150 * 1024) {
            resolve(file);
            return;
        }

        new Compressor(file, {
            quality: 0.6,
            maxWidth: 1920,
            maxHeight: 1920,
            success(result) {
                // The result is a Blob. Convert it back to a File object
                const compressedFile = new File([result], file.name, {
                    type: result.type,
                    lastModified: Date.now(),
                });
                console.log(`Compressed ${file.name} from ${(file.size/1024).toFixed(2)}KB to ${(compressedFile.size/1024).toFixed(2)}KB`);
                resolve(compressedFile);
            },
            error(err) {
                reject(err);
            },
        });
    });
}
