// Scroll-triggered black wipe animation for School Administration images
const cardImages = document.querySelectorAll('.card-image');

const observerOptions = {
    threshold: 0.2,
    rootMargin: '0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            // Add loaded class to trigger animation
            setTimeout(() => {
                entry.target.classList.add('loaded');
            }, 100);
            // Stop observing after animation is triggered
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe all card images
cardImages.forEach(img => {
    observer.observe(img);
});

// Also trigger for images already visible on page load
window.addEventListener('load', () => {
    cardImages.forEach(img => {
        const rect = img.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            setTimeout(() => {
                img.classList.add('loaded');
            }, 200);
        }
    });
});

