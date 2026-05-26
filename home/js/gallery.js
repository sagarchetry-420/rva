document.addEventListener('DOMContentLoaded', function() {
    fetch('../school_management/index.php?module=api&action=gallery')
        .then(response => response.json())
        .then(data => {
            const galleryGrid = document.getElementById('galleryGrid');
            if (data && data.length > 0) {
                galleryGrid.innerHTML = '';
                data.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'gallery-item';
                    div.setAttribute('data-category', item.category || 'general');
                    
                    div.innerHTML = `
                        <img src="${item.image_path ? '../' + item.image_path : '../assets/gallery/placeholder.jpg'}" alt="${item.title}">
                        <div class="gallery-overlay">
                            <div class="gallery-content">
                                <h3>${item.title}</h3>
                                <p>${item.category || 'General'}</p>
                            </div>
                        </div>
                    `;
                    galleryGrid.appendChild(div);
                });
            } else {
                galleryGrid.innerHTML = '<p style="text-align: center; grid-column: 1 / -1;">No photos available.</p>';
            }

            // Initialize Gallery Pagination Functionality
            const galleryItems = document.querySelectorAll('.gallery-item');
            const loadMoreBtn = document.getElementById('loadMoreBtn');

            let visibleCount = 4; // Number of items shown initially

            // Initially hide items beyond visibleCount
            galleryItems.forEach((item, index) => {
                if (index >= visibleCount) {
                    item.classList.add('hide');
                }
            });

            // Hide button if all are already shown
            if (galleryItems.length <= visibleCount && loadMoreBtn) {
                loadMoreBtn.style.display = 'none';
            }

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    const step = 4; // Number of items to reveal per click
                    let nextCount = visibleCount + step;
                    
                    for (let i = visibleCount; i < nextCount; i++) {
                        if (galleryItems[i]) {
                            const item = galleryItems[i];
                            item.classList.remove('hide');
                            
                            // Add a small fade-in animation
                            item.style.opacity = '0';
                            item.style.transform = 'translateY(20px)';
                            
                            // Force reflow
                            void item.offsetWidth;
                            
                            item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                            item.style.opacity = '1';
                            item.style.transform = 'translateY(0)';
                        }
                    }
                    
                    visibleCount = nextCount;
                    
                    // If we've shown all photos, hide the button
                    if (visibleCount >= galleryItems.length) {
                        loadMoreBtn.style.display = 'none';
                    }
                    
                    // Update lightbox image array
                    visibleImages = Array.from(galleryItems).filter(img => !img.classList.contains('hide'));
                });
            }

            // Gallery Lightbox Functionality
            const galleryLightbox = document.getElementById('galleryLightbox');
            const lightboxImg = document.getElementById('lightboxImg');
            const lightboxCaption = document.getElementById('lightboxCaption');
            const lightboxClose = document.getElementById('lightboxClose');
            const lightboxPrev = document.getElementById('lightboxPrev');
            const lightboxNext = document.getElementById('lightboxNext');

            let currentImageIndex = 0;
            let visibleImages = [];

            // Open lightbox when clicking gallery item
            galleryItems.forEach((item) => {
                item.addEventListener('click', () => {
                    // Get only currently visible images based on filter
                    visibleImages = Array.from(galleryItems).filter(img => !img.classList.contains('hide'));
                    currentImageIndex = visibleImages.indexOf(item);

                    updateLightboxContent();
                    
                    galleryLightbox.classList.add('active');
                    document.body.style.overflow = 'hidden'; // Prevent scrolling
                });
            });

            function updateLightboxContent() {
                const currentItem = visibleImages[currentImageIndex];
                if (!currentItem) return;
                
                // Get image source
                const imgSrc = currentItem.querySelector('img').src;
                
                // Get caption dynamically from HTML
                const title = currentItem.querySelector('h3').textContent;
                const desc = currentItem.querySelector('p').textContent;
                
                // Slight fade effect between images
                lightboxImg.style.opacity = '0';
                lightboxCaption.style.opacity = '0';
                lightboxCaption.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    lightboxImg.src = imgSrc;
                    lightboxCaption.innerHTML = `<strong>${title}</strong><br><span style="font-size: 14px; font-weight: 400; opacity: 0.8;">${desc}</span>`;
                    
                    lightboxImg.style.opacity = '1';
                    lightboxCaption.style.opacity = '1';
                    lightboxCaption.style.transform = 'translateY(0)';
                }, 200);
            }

            // Close lightbox
            function closeLightbox() {
                galleryLightbox.classList.remove('active');
                document.body.style.overflow = 'auto'; // Restore scrolling
            }

            if(lightboxClose) {
                lightboxClose.addEventListener('click', closeLightbox);
            }

            // Close lightbox when clicking outside the image
            if(galleryLightbox) {
                galleryLightbox.addEventListener('click', (e) => {
                    // Only close if clicking the background, not the image or buttons
                    if (e.target === galleryLightbox || e.target.classList.contains('lightbox-content')) {
                        closeLightbox();
                    }
                });
            }

            // Previous image
            if(lightboxPrev) {
                lightboxPrev.addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent closing
                    currentImageIndex = (currentImageIndex - 1 + visibleImages.length) % visibleImages.length;
                    updateLightboxContent();
                });
            }

            // Next image
            if(lightboxNext) {
                lightboxNext.addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent closing
                    currentImageIndex = (currentImageIndex + 1) % visibleImages.length;
                    updateLightboxContent();
                });
            }

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (galleryLightbox && galleryLightbox.classList.contains('active')) {
                    if (e.key === 'ArrowLeft' && lightboxPrev) lightboxPrev.click();
                    if (e.key === 'ArrowRight' && lightboxNext) lightboxNext.click();
                    if (e.key === 'Escape') closeLightbox();
                }
            });

        })
        .catch(error => {
            console.error('Error fetching gallery data:', error);
        });
});

// Parallax Effect for Hero Background
const heroBg = document.querySelector('.gallery-hero-bg');
if (heroBg) {
    window.addEventListener('scroll', () => {
        // Use requestAnimationFrame for buttery smooth performance
        requestAnimationFrame(() => {
            const scrolled = window.scrollY;
            // Move the background down at 40% of the scroll speed
            heroBg.style.transform = `translateY(${scrolled * 0.4}px) scale(1.05)`;
        });
    }, { passive: true });
}
