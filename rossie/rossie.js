/**
 * Rossie Chatbot Frontend Logic
 * Automatically injects the UI and handles messaging.
 */

(function () {
    // 1. Inject CSS
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    // Assume it's served from the same directory as the script
    const scriptSrc = document.currentScript ? document.currentScript.src : '/rossie/rossie.js';
    const basePath = scriptSrc.substring(0, scriptSrc.lastIndexOf('/'));
    link.href = basePath + '/rossie.css';
    document.head.appendChild(link);

    // FontAwesome for icons (if not already loaded)
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const fa = document.createElement('link');
        fa.rel = 'stylesheet';
        fa.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        document.head.appendChild(fa);
    }

    // Preload the avatar image to optimize loading speed
    if (!document.querySelector('link[href="/rossie/rossie.png"]')) {
        const preloadLink = document.createElement('link');
        preloadLink.rel = 'preload';
        preloadLink.as = 'image';
        preloadLink.href = '/rossie/rossie.png';
        document.head.appendChild(preloadLink);
    }

    // 2. Inject HTML UI
    const defaultHistory = `
        <div class="rossie-message-row bot">
            <img src="/rossie/rossie.png" class="rossie-msg-avatar" alt="Rossie" loading="eager" decoding="async" width="28" height="28" draggable="false">
            <div class="rossie-message bot">Hi! I am Rossie, How can I help you today?</div>
        </div>
    `;
    const savedHistory = sessionStorage.getItem('rossie_chat_history') || defaultHistory;

    const container = document.createElement('div');
    container.id = 'rossie-chatbot-container';
    container.innerHTML = `
        <div id="rossie-chatbot-window">
            <div class="rossie-header">
                <div class="rossie-header-info">
                    <div class="rossie-avatar"><img src="/rossie/rossie.png" alt="Rossie" loading="eager" fetchpriority="high" decoding="async" width="40" height="40" draggable="false"></div>
                    <div>
                        <h3 class="rossie-title">Rossie</h3>
                        <div class="rossie-status">Online</div>
                    </div>
                </div>
                <div class="rossie-header-actions">
                    <button class="rossie-voice-btn" id="rossie-voice-btn" title="Toggle Voice"><i class="fas fa-volume-mute"></i></button>
                    <button class="rossie-close-btn" id="rossie-close-btn"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="rossie-body" id="rossie-body">
                ${savedHistory}
            </div>
            <div class="rossie-input-area">
                <input type="text" id="rossie-input" class="rossie-input" placeholder="Type a message..." autocomplete="off">
                <button id="rossie-mic-btn" class="rossie-mic-btn" title="Click to speak"><i class="fas fa-microphone"></i></button>
                <button id="rossie-send-btn" class="rossie-send-btn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
        <button id="rossie-chatbot-toggle" aria-label="Open Chat">
            <img src="/rossie/rossie.png" alt="Rossie AI" loading="eager" fetchpriority="high" decoding="async" width="60" height="60" draggable="false">
        </button>
    `;
    document.body.appendChild(container);

    // 3. Logic
    const toggleBtn = document.getElementById('rossie-chatbot-toggle');
    const closeBtn = document.getElementById('rossie-close-btn');
    const chatWindow = document.getElementById('rossie-chatbot-window');
    const voiceBtn = document.getElementById('rossie-voice-btn');
    const voiceIcon = voiceBtn.querySelector('i');
    const micBtn = document.getElementById('rossie-mic-btn');
    const inputField = document.getElementById('rossie-input');
    const sendBtn = document.getElementById('rossie-send-btn');
    
    let currentAudio = null;
    let voiceEnabled = sessionStorage.getItem('rossie_voice_enabled') === 'true';
    if (voiceEnabled) {
        voiceIcon.classList.remove('fa-volume-mute');
        voiceIcon.classList.add('fa-volume-up');
    }

    voiceBtn.addEventListener('click', () => {
        voiceEnabled = !voiceEnabled;
        sessionStorage.setItem('rossie_voice_enabled', voiceEnabled);
        if (voiceEnabled) {
            voiceIcon.classList.remove('fa-volume-mute');
            voiceIcon.classList.add('fa-volume-up');
        } else {
            voiceIcon.classList.remove('fa-volume-up');
            voiceIcon.classList.add('fa-volume-mute');
            if (currentAudio) {
                currentAudio.pause();
                currentAudio = null;
            }
        }
    });

    // TTS Function (ElevenLabs API via Backend)
    function speakText(text) {
        if (!voiceEnabled) return;
        if (currentAudio) {
            currentAudio.pause();
        }

        fetch('/rossie/tts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text: text })
        })
        .then(response => {
            if (!response.ok) throw new Error('TTS Error');
            return response.blob();
        })
        .then(blob => {
            const audioUrl = URL.createObjectURL(blob);
            currentAudio = new Audio(audioUrl);
            currentAudio.play();
        })
        .catch(err => {
            console.error('ElevenLabs TTS failed:', err);
        });
    }
    // Speech Recognition Logic
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;
    let isRecording = false;

    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = 'en-IN'; // Default to generic English or could match voice

        recognition.onstart = function() {
            isRecording = true;
            micBtn.classList.add('recording');
            inputField.placeholder = "Listening...";
        };

        recognition.onresult = function(event) {
            const transcript = event.results[0][0].transcript;
            inputField.value = transcript;
        };

        recognition.onerror = function(event) {
            console.error('Speech recognition error', event.error);
            isRecording = false;
            micBtn.classList.remove('recording');
            inputField.placeholder = "Type a message...";
        };

        recognition.onend = function() {
            isRecording = false;
            micBtn.classList.remove('recording');
            inputField.placeholder = "Type a message...";
            if (inputField.value.trim() !== "") {
                // Auto-send when finished speaking
                sendBtn.click();
            }
        };

        micBtn.addEventListener('click', () => {
            if (isRecording) {
                recognition.stop();
            } else {
                recognition.start();
            }
        });
    } else {
        // Browser does not support SpeechRecognition
        micBtn.style.display = 'none';
    }

    const chatHeader = document.querySelector('.rossie-header');

    // Restore state from sessionStorage
    let savedX = sessionStorage.getItem('rossie_pos_x');
    let savedY = sessionStorage.getItem('rossie_pos_y');
    const isOpen = sessionStorage.getItem('rossie_is_open') === 'true';

    if (savedX && savedY) {
        // Clamp to screen boundaries to prevent off-screen spawning on mobile
        const maxX = window.innerWidth - 60;
        const maxY = window.innerHeight - 60;
        savedX = Math.max(0, Math.min(parseInt(savedX), maxX));
        savedY = Math.max(0, Math.min(parseInt(savedY), maxY));

        container.style.left = savedX + 'px';
        container.style.top = savedY + 'px';
        container.style.bottom = 'auto'; 
        container.style.right = 'auto';
    }

    // Adjust position smoothly if window resizes
    window.addEventListener('resize', () => {
        const rect = container.getBoundingClientRect();
        const maxX = window.innerWidth - rect.width;
        const maxY = window.innerHeight - rect.height;
        if (rect.left > maxX || rect.top > maxY) {
            container.style.left = Math.max(0, Math.min(rect.left, maxX)) + 'px';
            container.style.top = Math.max(0, Math.min(rect.top, maxY)) + 'px';
        }
    });

    if (isOpen) {
        chatWindow.classList.add('rossie-active');
        toggleBtn.style.display = 'none';
        // Scroll to bottom on load if there's history
        setTimeout(() => {
            if (chatBody) chatBody.scrollTop = chatBody.scrollHeight;
        }, 50);
        // Give a tiny delay for focus so it doesn't break page scrolling on load
        setTimeout(() => {
            const input = document.getElementById('rossie-input');
            if (input) input.focus();
        }, 100);
    }

    // Dragging logic
    let isDragging = false;
    let hasDragged = false;
    let dragOffsetX = 0;
    let dragOffsetY = 0;

    function startDrag(clientX, clientY, target) {
        isDragging = true;
        hasDragged = false;
        if (target === chatHeader) chatHeader.style.cursor = 'grabbing';
        if (target === toggleBtn) toggleBtn.style.cursor = 'grabbing';
        
        const rect = container.getBoundingClientRect();
        dragOffsetX = clientX - rect.left;
        dragOffsetY = clientY - rect.top;
        document.body.style.userSelect = 'none';
    }

    function onDrag(clientX, clientY, e) {
        if (!isDragging) return;
        hasDragged = true;
        if (e && e.cancelable) e.preventDefault(); // Prevent scrolling on mobile while dragging

        let newX = clientX - dragOffsetX;
        let newY = clientY - dragOffsetY;
        
        // Prevent dragging off screen
        const maxX = window.innerWidth - container.offsetWidth;
        const maxY = window.innerHeight - container.offsetHeight;
        
        newX = Math.max(0, Math.min(newX, maxX));
        newY = Math.max(0, Math.min(newY, maxY));
        
        container.style.left = newX + 'px';
        container.style.top = newY + 'px';
        container.style.bottom = 'auto'; 
        container.style.right = 'auto';
    }

    function stopDrag() {
        if (!isDragging) return;
        isDragging = false;
        chatHeader.style.cursor = 'grab';
        toggleBtn.style.cursor = 'pointer';
        document.body.style.userSelect = '';
        
        if (container.style.left && container.style.top) {
            sessionStorage.setItem('rossie_pos_x', parseInt(container.style.left));
            sessionStorage.setItem('rossie_pos_y', parseInt(container.style.top));
        }
    }

    // Mouse events
    chatHeader.addEventListener('mousedown', (e) => startDrag(e.clientX, e.clientY, chatHeader));
    toggleBtn.addEventListener('mousedown', (e) => startDrag(e.clientX, e.clientY, toggleBtn));
    document.addEventListener('mousemove', (e) => onDrag(e.clientX, e.clientY));
    document.addEventListener('mouseup', stopDrag);

    // Touch events for mobile
    chatHeader.addEventListener('touchstart', (e) => startDrag(e.touches[0].clientX, e.touches[0].clientY, chatHeader), { passive: true });
    toggleBtn.addEventListener('touchstart', (e) => startDrag(e.touches[0].clientX, e.touches[0].clientY, toggleBtn), { passive: true });
    document.addEventListener('touchmove', (e) => onDrag(e.touches[0].clientX, e.touches[0].clientY, e), { passive: false });
    document.addEventListener('touchend', stopDrag);
    const chatBody = document.getElementById('rossie-body');

    function saveChatHistory() {
        if (chatBody) {
            sessionStorage.setItem('rossie_chat_history', chatBody.innerHTML);
        }
    }

    let isTyping = false;

    // Toggle Window
    toggleBtn.addEventListener('click', (e) => {
        if (hasDragged) {
            hasDragged = false;
            return;
        }
        chatWindow.classList.add('rossie-active');
        toggleBtn.style.display = 'none';
        sessionStorage.setItem('rossie_is_open', 'true');
        inputField.focus();
    });

    closeBtn.addEventListener('click', () => {
        chatWindow.classList.remove('rossie-active');
        toggleBtn.style.display = 'flex';
        sessionStorage.setItem('rossie_is_open', 'false');
    });

    // Close chat if clicking outside
    function closeOnOutsideClick(e) {
        if (chatWindow.classList.contains('rossie-active')) {
            if (!chatWindow.contains(e.target) && !toggleBtn.contains(e.target)) {
                chatWindow.classList.remove('rossie-active');
                toggleBtn.style.display = 'flex';
                sessionStorage.setItem('rossie_is_open', 'false');
            }
        }
    }
    
    document.addEventListener('mousedown', closeOnOutsideClick);
    document.addEventListener('touchstart', closeOnOutsideClick, { passive: true });

    // Send Message
    function sendMessage() {
        const text = inputField.value.trim();
        if (!text || isTyping) return;

        // Add user message
        appendMessage(text, 'user');
        inputField.value = '';

        // Show typing indicator
        showTyping();

        // API Call
        fetch(basePath + '/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: text })
        })
            .then(response => response.json())
            .then(data => {
                removeTyping();
                if (data.error) {
                    appendMessage('Oops, something went wrong: ' + data.error, 'bot');
                } else {
                    if (data.reply) {
                        // Parse markdown text
                        let reply = data.reply;
                        
                        // Headers
                        reply = reply.replace(/^### (.*$)/gim, '<strong>$1</strong><br>');
                        reply = reply.replace(/^## (.*$)/gim, '<strong>$1</strong><br>');
                        reply = reply.replace(/^# (.*$)/gim, '<strong>$1</strong><br>');
                        
                        // Bold
                        reply = reply.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
                        
                        // Italics
                        reply = reply.replace(/\*(.*?)\*/g, '<i>$1</i>');
                        
                        // Bullet points (convert starting asterisk or hyphen to bullet)
                        reply = reply.replace(/^[\*\-] (.*$)/gim, '• $1');
                        
                        // Fix double bullet italics issue (if a bullet was caught by italics)
                        reply = reply.replace(/<i> (.*?)<\/i>/g, '• $1');
                        
                        // Newlines
                        reply = reply.replace(/\n/g, '<br>');

                        appendMessage(reply, 'bot');
                    }
                    
                    if (data.tool_call) {
                        handleToolCall(data);
                    }
                    
                    if (!data.reply && !data.tool_call) {
                        appendMessage('Sorry, I have no answer.', 'bot');
                    }
                }
            })
            .catch(error => {
                removeTyping();
                appendMessage('Error connecting to the server.', 'bot');
                console.error('Rossie API Error:', error);
            });
    }

    sendBtn.addEventListener('click', sendMessage);
    inputField.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    function appendMessage(text, sender) {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'rossie-message-row ' + sender;
        
        if (sender === 'bot') {
            rowDiv.innerHTML = `
                <img src="/rossie/rossie.png" class="rossie-msg-avatar" alt="Rossie" loading="lazy" decoding="async" width="28" height="28" draggable="false">
                <div class="rossie-message bot">${text}</div>
            `;
            speakText(text);
        } else {
            rowDiv.innerHTML = `
                <div class="rossie-message user">${text}</div>
            `;
        }
        
        chatBody.appendChild(rowDiv);
        scrollToBottom();
        saveChatHistory();
    }

    function handleToolCall(data) {
        if (data.tool_call === 'create_quote') {
            const args = data.args;
            const quoteText = args.quote_text || '';
            const author = args.author || '';

            // Check if admin by looking for CSRF token
            const csrfInput = document.querySelector('input[name="_csrf_token"]');
            if (!csrfInput) {
                appendMessage('No, I cannot post a quote for you.', 'bot');
                return;
            }

            // Create confirmation UI
            const rowDiv = document.createElement('div');
            rowDiv.className = 'rossie-message-row bot';
            rowDiv.innerHTML = `
                <img src="/rossie/rossie.png" class="rossie-msg-avatar" alt="Rossie" loading="lazy" decoding="async" width="28" height="28" draggable="false">
                <div class="rossie-message bot" style="width: 100%;">
                    I drafted the following quote:<br><br>
                    <i>"${quoteText}"</i><br>
                    <b>- ${author}</b><br><br>
                    Do you want me to post this to the website?
                    <div style="margin-top: 10px; display: flex; gap: 5px;">
                        <button class="rossie-btn-confirm" style="padding: 6px 12px; background: #0d6efd; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">Confirm & Post</button>
                        <button class="rossie-btn-cancel" style="padding: 6px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">Cancel</button>
                    </div>
                </div>
            `;
            chatBody.appendChild(rowDiv);
            scrollToBottom();
            saveChatHistory();

            const confirmBtn = rowDiv.querySelector('.rossie-btn-confirm');
            const cancelBtn = rowDiv.querySelector('.rossie-btn-cancel');

            cancelBtn.addEventListener('click', () => {
                confirmBtn.disabled = true;
                cancelBtn.disabled = true;
                cancelBtn.style.opacity = '0.6';
                confirmBtn.style.opacity = '0.6';
                appendMessage('Quote posting cancelled.', 'bot');
            });

            confirmBtn.addEventListener('click', () => {
                confirmBtn.disabled = true;
                cancelBtn.disabled = true;
                confirmBtn.style.opacity = '0.6';
                cancelBtn.style.opacity = '0.6';
                confirmBtn.innerText = 'Posting...';
                
                const formData = new URLSearchParams();
                formData.append('action', 'create');
                formData.append('quote_text', quoteText);
                formData.append('author', author);
                formData.append('_csrf_token', csrfInput.value);

                fetch('http://localhost/school_management/admin/quotes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                })
                .then(response => {
                    if (response.ok || response.redirected || response.status === 200 || response.status === 302) {
                        appendMessage('Quote added successfully!', 'bot');
                        if (window.location.href.includes('/admin/quotes')) {
                            setTimeout(() => window.location.reload(), 1500);
                        }
                    } else {
                        appendMessage('Failed to post the quote. Server returned an error.', 'bot');
                    }
                })
                .catch(error => {
                    console.error('Error posting quote:', error);
                    appendMessage('An error occurred while posting the quote.', 'bot');
                });
            });
        }
    }

    function showTyping() {
        isTyping = true;
        sendBtn.disabled = true;
        const rowDiv = document.createElement('div');
        rowDiv.className = 'rossie-message-row bot';
        rowDiv.id = 'rossie-typing-indicator';
        rowDiv.innerHTML = `
            <img src="/rossie/rossie.png" class="rossie-msg-avatar" alt="Rossie" loading="lazy" decoding="async" width="28" height="28">
            <div class="rossie-typing">
                <div class="rossie-typing-dot"></div>
                <div class="rossie-typing-dot"></div>
                <div class="rossie-typing-dot"></div>
            </div>
        `;
        chatBody.appendChild(rowDiv);
        scrollToBottom();
    }

    function removeTyping() {
        isTyping = false;
        const typingDiv = document.getElementById('rossie-typing-indicator');
        if (typingDiv) typingDiv.remove();
        
        // Frontend Cooldown to prevent spam
        sendBtn.disabled = true;
        inputField.disabled = true;
        inputField.placeholder = "Please wait...";
        setTimeout(() => {
            sendBtn.disabled = false;
            inputField.disabled = false;
            inputField.placeholder = "Type a message...";
            if (chatWindow.classList.contains('rossie-active')) {
                inputField.focus();
            }
        }, 2000);
    }

    function scrollToBottom() {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

})();
