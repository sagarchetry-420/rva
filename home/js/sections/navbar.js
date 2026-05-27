// Menu Toggle
const menuTrigger = document.querySelector('.menu-trigger');
const navMenu = document.querySelector('.menu');
const navbar = document.querySelector('.navbar');

// Transparent to Solid Navbar on Scroll
window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Generic Page Transition Function (Accessible anywhere)
window.triggerPageTransition = function(targetUrl, pageName) {
    // Check if an overlay already exists, else create it
    let overlay = document.getElementById('notice-transition-out');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'notice-transition';
        overlay.id = 'notice-transition-out';
        document.body.appendChild(overlay);
    }
    
    // Set the dynamic text based on the destination
    let textHTML = `
        <div class="nt-content" style="text-align: center;">
            <img src="/RVA/assets/logo/logo_small.png" alt="Rose Valley Academy Logo" style="width: 80px; margin-bottom: 20px; animation: pulse 2s infinite;">
            <div class="nt-item"><span>Loading</span></div>`;
    
    if (pageName) {
        textHTML += `<div class="nt-item"><span>${pageName}...</span></div>`;
    }
    textHTML += `</div>`;
    overlay.innerHTML = textHTML;

    // Set flag so the incoming page knows to play its half of the animation
    sessionStorage.setItem('pageTransition', 'true');
    
    // Force a reflow so the browser registers the element before animating
    void overlay.offsetWidth;
    
    // Trigger the slide-in from the right
    overlay.classList.add('active');
    
    // Wait for the slide-in (0.8s) + small buffer before actually changing the URL
    setTimeout(() => {
        window.location.href = targetUrl;
    }, 1000);
};

if (menuTrigger) {
    menuTrigger.addEventListener('click', (e) => {
        // Prevent clicking inside the open menu from closing it
        if (e.target.closest('.menu')) return;
        
        menuTrigger.classList.toggle('active');
    });

        // Close menu when a link is clicked
    document.querySelectorAll('.menu a').forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            
            // If this link navigates to a new page (excluding the login link or external links)
            if (href && !href.startsWith('#') && !href.includes('auth/login')) {
                e.preventDefault();
                menuTrigger.classList.remove('active');
                
                // Determine page name for the overlay text
                let pageName = 'Page';
                if (href.includes('gallery')) pageName = 'Gallery';
                else if (href.includes('notices')) pageName = 'Notices';
                else if (href.includes('index')) pageName = 'Home';
                
                // If returning to Home, skip the full-screen transition overlay
                if (href.includes('index')) {
                    setTimeout(() => {
                        window.location.href = href;
                    }, 800);
                } else {
                    // Wait for the 0.8s slide-up menu animation to complete, then trigger the seamless page transition
                    setTimeout(() => {
                        window.triggerPageTransition(href, pageName);
                    }, 800);
                }
            } else {
                // It's just a scrolling anchor link on the same page, close instantly
                menuTrigger.classList.remove('active');
            }
        });
    });
}

    // Add smooth page transitions to Footer links as well
document.querySelectorAll('.footer a').forEach(link => {
    link.addEventListener('click', (e) => {
        const href = link.getAttribute('href');
        
        if (href && !href.startsWith('#') && !href.includes('auth/login')) {
            e.preventDefault();
            
            let pageName = 'Page';
            if (href.includes('gallery')) pageName = 'Gallery';
            else if (href.includes('notices')) pageName = 'Notices';
            else if (href.includes('index')) pageName = 'Home';
            
            if (href.includes('index')) {
                // Instantly navigate to home without the loading animation
                window.location.href = href;
            } else {
                // Instantly start the full-screen transition (no need to wait for menu)
                window.triggerPageTransition(href, pageName);
            }
        }
        // Anchor links (#) will execute natively
    });
});

// Active nav link on scroll
const navLinks = document.querySelectorAll('.nav-link');
navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        if (link.getAttribute('href').startsWith('#')) {
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        }
    });
});

// Update active nav link based on current page
window.addEventListener('load', () => {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href.includes(currentPage) || (currentPage === '' && href.includes('index'))) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
});

// Scroll to top smooth behavior
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && document.querySelector(href)) {
            e.preventDefault();
            document.querySelector(href).scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});
