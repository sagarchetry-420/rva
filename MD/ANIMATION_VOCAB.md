# ANIMATION_VOCAB.md
# Motion Direction Language — Translate Your Vision Into Code Intent
# Use [TECH] tags in your prompts for precise animation implementation.

---

## SCROLL-BASED ANIMATIONS

### Scroll Parallax (slow drift)
**You say:** "slow scroll parallax on the image"
**[TECH]:**
```js
window.addEventListener('scroll', () => {
  const y = window.scrollY;
  element.style.transform = `translateY(${y * -0.3}px)`;
});
```
Speed multipliers: `0.1` = very slow | `0.3` = medium | `0.6` = fast | `1.0` = scroll speed

### Scroll Parallax (layered depth)
**You say:** "multiple elements move at different speeds for depth"
**[TECH]:** Each layer gets different multiplier:
- Background image: `scrollY * -0.1`
- Mid shape: `scrollY * -0.25`
- Foreground text: `scrollY * -0.4`

### Scroll Reveal (element enters as you scroll)
**You say:** "element fades/slides in when scrolled into view"
**[TECH]:** `IntersectionObserver` | `threshold: 0.15` | toggle `.is-visible` class | CSS transition on `opacity` + `translateY`

### Scroll Progress Bar
**You say:** "thin line at top fills as user scrolls"
**[TECH]:** `width: scrollY / (document.body.scrollHeight - innerHeight) * 100 + '%'`

### Scroll-Pinned / Scrubbed Animation
**You say:** "animation plays as you scroll, like a video scrub"
**[TECH]:** Map `scrollY` range to animation progress using `lerp()` or linear interpolation

---

## ENTRANCE ANIMATIONS (on page load)

### Cinematic Fade Up
**You say:** "text slides up and fades in on load, cinematic feel"
**[TECH]:**
```css
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(40px); }
  to   { opacity: 1; transform: translateY(0); }
}
.animate { animation: fadeUp 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards; }
```

### Staggered Entrance (children one by one)
**You say:** "each word / card / item enters one after another"
**[TECH]:** `animation-delay: calc(var(--i) * 0.1s)` on each child | set `--i: 0, 1, 2...` via inline style or `:nth-child`

### Scale In (zoom from center)
**You say:** "element zooms in from small to full size"
**[TECH]:**
```css
@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.85); }
  to   { opacity: 1; transform: scale(1); }
}
```

### Slide In From Left / Right
**You say:** "element slides in from the side"
**[TECH]:**
```css
@keyframes slideLeft  { from { transform: translateX(-80px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes slideRight { from { transform: translateX(80px);  opacity: 0; } to { transform: translateX(0); opacity: 1; } }
```

### Text Reveal (mask wipe)
**You say:** "text reveals like a curtain pulling away"
**[TECH]:** Wrap text in `overflow: hidden` parent | animate inner `translateY(100%)` → `translateY(0)` | creates clean wipe reveal

### Letter-by-Letter Entrance
**You say:** "each letter animates in separately"
**[TECH]:** Split text into `<span>` per character via JS | stagger `animation-delay` per index

---

## HOVER ANIMATIONS

### Magnetic Hover (element follows cursor slightly)
**You say:** "element slightly follows the mouse, like magnetic"
**[TECH]:**
```js
el.addEventListener('mousemove', (e) => {
  const rect = el.getBoundingClientRect();
  const x = (e.clientX - rect.left - rect.width/2) * 0.2;
  const y = (e.clientY - rect.top - rect.height/2) * 0.2;
  el.style.transform = `translate(${x}px, ${y}px)`;
});
el.addEventListener('mouseleave', () => { el.style.transform = 'translate(0,0)'; });
```

### Tilt / 3D Perspective Hover
**You say:** "card tilts in 3D when you hover"
**[TECH]:**
```js
el.addEventListener('mousemove', (e) => {
  const rect = el.getBoundingClientRect();
  const x = (e.clientY - rect.top  - rect.height/2) / rect.height * 20;
  const y = (e.clientX - rect.left - rect.width/2)  / rect.width  * -20;
  el.style.transform = `perspective(600px) rotateX(${x}deg) rotateY(${y}deg)`;
});
```

### Image Zoom on Hover
**You say:** "image zooms in slowly on hover"
**[TECH]:** `overflow: hidden` on wrapper | `img { transition: transform 0.6s ease; }` | `img:hover { transform: scale(1.08); }`

### Underline Draw on Hover
**You say:** "underline draws itself under link on hover"
**[TECH]:**
```css
a::after { content:''; display:block; height:1px; background:currentColor; transform: scaleX(0); transform-origin: left; transition: transform 0.35s ease; }
a:hover::after { transform: scaleX(1); }
```

### Button Fill on Hover
**You say:** "button background fills from left on hover"
**[TECH]:**
```css
button { background: linear-gradient(to right, #color 50%, transparent 50%); background-size: 200%; background-position: right; transition: background-position 0.4s ease; }
button:hover { background-position: left; }
```

---

## FLOATING / IDLE ANIMATIONS

### Float (gentle up-down)
**You say:** "element floats gently up and down"
**[TECH]:**
```css
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(-16px); }
}
.floating { animation: float 4s ease-in-out infinite; }
```

### Slow Rotation
**You say:** "element slowly rotates / spins"
**[TECH]:** `animation: spin 20s linear infinite;` | `@keyframes spin { to { transform: rotate(360deg); } }`

### Pulse / Breathing
**You say:** "element breathes / pulses softly"
**[TECH]:** `@keyframes pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.05); } }`

### Morphing Blob
**You say:** "blob shape slowly morphs / changes form"
**[TECH]:** Animate `border-radius` between two organic values with `ease-in-out infinite alternate`

---

## EASING CHEAT SHEET

| Feel | CSS Curve |
|---|---|
| Cinematic smooth | `cubic-bezier(0.25, 0.46, 0.45, 0.94)` |
| Snappy, energetic | `cubic-bezier(0.68, -0.55, 0.27, 1.55)` |
| Slow in, fast out | `cubic-bezier(0.4, 0, 1, 1)` |
| Fast in, slow out (elegant) | `cubic-bezier(0, 0, 0.2, 1)` |
| Elastic bounce | `cubic-bezier(0.175, 0.885, 0.32, 1.275)` |
| Dead linear (avoid) | `linear` |

---

## TIMING GUIDE

| Animation Type | Duration |
|---|---|
| Micro-interaction (hover, click) | 150ms – 300ms |
| UI transition (panel, modal) | 300ms – 500ms |
| Entrance animation | 600ms – 1000ms |
| Cinematic / hero reveal | 1000ms – 2000ms |
| Idle / ambient (float, spin) | 3000ms – 8000ms |

---

## YOUR WORDS → ANIMATION TECH

| Your Creative Words | Technical Terms |
|---|---|
| slow scroll parallax | `scrollY * -0.3` on `translateY`, `requestAnimationFrame` |
| slides in when scrolled to | `IntersectionObserver` + `translateY` + `opacity` transition |
| floats gently | `@keyframes float` with `translateY` oscillation |
| magnetic hover | `mousemove` → `translate(x * 0.2, y * 0.2)` |
| 3D card tilt | `perspective(600px) rotateX() rotateY()` on `mousemove` |
| text wipe reveal | `overflow: hidden` parent + `translateY` inner |
| cinematic entrance | `fadeUp` keyframe + `cubic-bezier(0.25, 0.46, 0.45, 0.94)` |
| one by one stagger | `animation-delay: calc(var(--i) * 0.12s)` |
| zoom on hover | `scale(1.08)` on `overflow: hidden` parent |
| underline draw | `::after` pseudo + `scaleX` transform |
| button fill | `background-position` shift on gradient |
| morphing blob | animated `border-radius` values |
| breathing pulse | `scale(1)` → `scale(1.05)` infinite alternate |
