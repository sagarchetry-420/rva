console.log("Quotes JS initializing...");

fetch('../school_management/index.php?module=api&action=quotes')
    .then(response => response.json())
    .then(fetchedQuotes => {
        let quotes = [];
        if (fetchedQuotes && fetchedQuotes.length > 0) {
            // Map our db fields to the expected format
            quotes = fetchedQuotes.map(q => ({
                text: q.quote_text,
                author: "- " + q.author
            }));
        } else {
            // Fallback default quotes
            quotes = [
                { text: "Education is not the learning of facts, but the training of the mind to think.", author: "- Albert Einstein" },
                { text: "The roots of education are bitter, but the fruit is sweet.", author: "- Aristotle" },
                { text: "Education is the most powerful weapon which you can use to change the world.", author: "- Nelson Mandela" }
            ];
        }

        let currentIndex = 0;
        let autoPlayInterval;

        const quoteText = document.getElementById('quote-text');
        const quoteAuthor = document.getElementById('quote-author');
        const quoteDotsContainer = document.getElementById('quote-dots');
        const quoteSection = document.querySelector('.quote-section');

        if (quoteText && quoteAuthor && quoteDotsContainer) {
            console.log("Quote elements found, starting rotation with dots.");
            
            // Setup initial quote
            quoteText.textContent = quotes[currentIndex].text;
            quoteAuthor.textContent = quotes[currentIndex].author;

            // Setup initial transition styles
            quoteText.style.transition = 'all 0.6s cubic-bezier(0.25, 1, 0.5, 1)';
            quoteAuthor.style.transition = 'all 0.6s cubic-bezier(0.25, 1, 0.5, 1)';
            quoteText.style.transform = 'translateX(0)';
            quoteAuthor.style.transform = 'translateX(0)';

            // Generate Dots
            quotes.forEach((_, index) => {
                const dot = document.createElement('div');
                dot.classList.add('quote-dot');
                if (index === 0) dot.classList.add('active');
                dot.addEventListener('click', () => {
                    const direction = index > currentIndex ? 1 : -1;
                    goToQuote(index, direction);
                });
                quoteDotsContainer.appendChild(dot);
            });

            const updateDots = () => {
                const dots = document.querySelectorAll('.quote-dot');
                dots.forEach((dot, index) => {
                    if (index === currentIndex) dot.classList.add('active');
                    else dot.classList.remove('active');
                });
            };

            const goToQuote = (index, direction = 1) => {
                if (index === currentIndex) return;
                currentIndex = index;
                
                // Instantly update dots for immediate UI feedback
                updateDots();
                
                // Fade out and slide out (in the direction of navigation)
                quoteText.style.transform = `translateX(${direction * -50}px)`;
                quoteAuthor.style.transform = `translateX(${direction * -50}px)`;
                quoteText.style.opacity = '0';
                quoteAuthor.style.opacity = '0';

                setTimeout(() => {
                    // Change text
                    quoteText.textContent = quotes[currentIndex].text;
                    quoteAuthor.textContent = quotes[currentIndex].author;
                    
                    // Snap to opposite side while hidden (turn off transitions temporarily)
                    quoteText.style.transition = 'none';
                    quoteAuthor.style.transition = 'none';
                    quoteText.style.transform = `translateX(${direction * 50}px)`;
                    quoteAuthor.style.transform = `translateX(${direction * 50}px)`;
                    
                    // Force reflow so the browser registers the snapped position before turning transitions back on
                    void quoteText.offsetWidth;
                    
                    // Turn transitions back on and slide in
                    quoteText.style.transition = 'all 0.6s cubic-bezier(0.25, 1, 0.5, 1)';
                    quoteAuthor.style.transition = 'all 0.6s cubic-bezier(0.25, 1, 0.5, 1)';
                    quoteText.style.transform = 'translateX(0)';
                    quoteAuthor.style.transform = 'translateX(0)';
                    quoteText.style.opacity = '1';
                    quoteAuthor.style.opacity = '1';
                }, 600);
                
                resetInterval();
            };

            const nextQuote = () => {
                goToQuote((currentIndex + 1) % quotes.length, 1);
            };

            const prevQuote = () => {
                goToQuote((currentIndex - 1 + quotes.length) % quotes.length, -1);
            };

            const startInterval = () => {
                autoPlayInterval = setInterval(nextQuote, 5000);
            };

            const resetInterval = () => {
                clearInterval(autoPlayInterval);
                startInterval();
            };

            // Swipe / Drag Logic
            let startX = 0;
            let isDragging = false;

            const handleDragStart = (e) => {
                isDragging = true;
                startX = e.type.includes('mouse') ? e.pageX : e.touches[0].clientX;
                resetInterval(); // Pause while dragging
            };

            const handleDragEnd = (e) => {
                if (!isDragging) return;
                isDragging = false;
                
                const endX = e.type.includes('mouse') ? e.pageX : e.changedTouches[0].clientX;
                const diffX = startX - endX;

                if (diffX > 50) {
                    // Swiped left -> next
                    nextQuote();
                } else if (diffX < -50) {
                    // Swiped right -> prev
                    prevQuote();
                }
            };

            // Mouse Events
            quoteSection.addEventListener('mousedown', handleDragStart);
            quoteSection.addEventListener('mouseup', handleDragEnd);
            quoteSection.addEventListener('mouseleave', () => {
                if (isDragging) {
                    isDragging = false; // Cancel drag if cursor leaves area
                }
            });

            // Touch Events for Mobile
            quoteSection.addEventListener('touchstart', handleDragStart, {passive: true});
            quoteSection.addEventListener('touchend', handleDragEnd);

            // Start auto-play
            startInterval();

        } else {
            console.error("Quote elements not found!");
        }
    })
    .catch(error => {
        console.error("Error fetching quotes:", error);
    });
