// Smooth Parallax Effect for Hero Section
document.addEventListener('DOMContentLoaded', () => {
    const heroBackground = document.querySelector('.hero-background');

    if (heroBackground) {
        heroBackground.style.willChange = 'transform';

        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            // The background moves down at half the speed of the scroll, creating a parallax effect
            const parallaxOffset = scrollY * 0.5;
            heroBackground.style.transform = `translateY(${parallaxOffset}px)`;
        }, { passive: true });
    }

    // Rotating Text Logic
    const texts = [
        "Where curiosity<br>meets opportunity,<br>every single day.",
        "Empowering minds<br>to build a<br>brighter future.",
        "Discover your<br>potential in a<br>nurturing space."
    ];
    let currentTextIndex = 0;
    const rotatingTextElement = document.getElementById('hero-rotating-text');
    const rotatingTextOverlay = document.getElementById('hero-rotating-text-overlay');

    const renderLines = (htmlStr) => {
        return htmlStr.split('<br>').map(line => 
            `<span class="hero-line-wrapper"><span class="hero-line-inner">${line}</span></span>`
        ).join('');
    };

    if (rotatingTextElement) {
        // Format initial text
        rotatingTextElement.innerHTML = renderLines(texts[currentTextIndex]);
        if (rotatingTextOverlay) rotatingTextOverlay.innerHTML = renderLines(texts[currentTextIndex]);

        // Trigger reflow
        void rotatingTextElement.offsetWidth;

        // Initial animation
        rotatingTextElement.classList.add('slide-in');
        if (rotatingTextOverlay) rotatingTextOverlay.classList.add('slide-in');

        setInterval(() => {
            // 1. Slide out the text
            rotatingTextElement.classList.remove('slide-in');
            rotatingTextElement.classList.add('slide-out');
            if (rotatingTextOverlay) {
                rotatingTextOverlay.classList.remove('slide-in');
                rotatingTextOverlay.classList.add('slide-out');
            }

            // Wait for slide out to finish (0.8s + stagger max 0.3s = 1.1s)
            setTimeout(() => {
                currentTextIndex = (currentTextIndex + 1) % texts.length;
                rotatingTextElement.innerHTML = renderLines(texts[currentTextIndex]);
                if (rotatingTextOverlay) rotatingTextOverlay.innerHTML = renderLines(texts[currentTextIndex]);

                rotatingTextElement.classList.remove('slide-out');
                if (rotatingTextOverlay) rotatingTextOverlay.classList.remove('slide-out');

                // Trigger reflow
                void rotatingTextElement.offsetWidth;

                rotatingTextElement.classList.add('slide-in');
                if (rotatingTextOverlay) rotatingTextOverlay.classList.add('slide-in');
            }, 1200); // Wait long enough for the slowest line to finish sliding out

        }, 5000); // Change text every 5 seconds
    }
});
