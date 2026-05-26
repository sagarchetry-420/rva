# CHANGE_COMMANDS.md
# Precise Iteration Commands — Make AI Understand Exactly What to Fix
# Use these command structures when revising, tweaking, or iterating.

---

## THE PROBLEM THIS SOLVES

Vague revision prompts like:
- "make it better"
- "the animation feels off"
- "change the hero"

...give AI too much room to guess — and it will guess wrong.

These commands give AI a precise contract for what to change, what to keep, and how to do it.

---

## COMMAND FORMAT

Every change command follows this structure:

```
[CHANGE] <what to change>
[TARGET] <exact element or section>
[FROM] <current state>
[TO] <new desired state>
[KEEP] <what must NOT change>
```

---

## VISUAL CHANGES

### Change a color
```
[CHANGE] Background color of the hero section
[TARGET] .hero or the hero section element
[FROM] Current dark background
[TO] Deep navy #0d1b2a
[KEEP] All text, shapes, and animations unchanged
```

### Change a shape
```
[CHANGE] Shape of the decorative element
[TARGET] The bottom-anchored decorative div in the hero
[FROM] Rectangle with rounded corners
[TO] True half-circle — border-radius: 50% 50% 0 0, width: 600px, height: 300px
[KEEP] Color, z-index, and image inside unchanged
```

### Change typography
```
[CHANGE] Headline font and size
[TARGET] The main h1 in the hero section
[FROM] Current font
[TO] Import "Cormorant Garamond" from Google Fonts, apply font-weight: 300, font-size: 9vw, letter-spacing: -0.02em
[KEEP] Color, animation, and position unchanged
```

### Change layout ratio
```
[CHANGE] Column proportions in the about section
[TARGET] The two-column grid
[FROM] Equal 50/50 columns
[TO] grid-template-columns: 65fr 35fr (image takes more space)
[KEEP] All content, colors, and animations unchanged
```

---

## ANIMATION CHANGES

### Change parallax speed
```
[CHANGE] Scroll parallax multiplier on the hero image
[TARGET] The parallax image inside the half-circle
[FROM] Current speed (whatever it is)
[TO] scrollY * -0.15 (very slow, almost imperceptible drift)
[KEEP] All other scroll behaviors and entrance animations unchanged
```

### Add entrance animation to specific element
```
[CHANGE] Add entrance animation
[TARGET] The subtitle paragraph in the hero
[FROM] No animation, appears instantly
[TO] Fade up from translateY(30px) with opacity 0 → 1, duration 800ms, delay 600ms after page load, easing cubic-bezier(0.25, 0.46, 0.45, 0.94)
[KEEP] All other elements and animations unchanged
```

### Change animation timing / feel
```
[CHANGE] Make the card hover animation feel heavier/slower
[TARGET] All .card elements in the services section
[FROM] Current transition (too fast / too snappy)
[TO] transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94), box-shadow 0.5s ease
[KEEP] What the hover does (lift + shadow) — only change speed and easing
```

### Remove an animation
```
[CHANGE] Remove the floating animation from the background shapes
[TARGET] The decorative circle elements in the hero background
[FROM] Floating up/down keyframe animation
[TO] Static, no animation — keep position and styling exactly as is
[KEEP] Everything else
```

---

## LAYOUT CHANGES

### Move an element
```
[CHANGE] Position of the decorative semicircle
[TARGET] The half-circle element
[FROM] bottom-center of the hero section
[TO] right side of the hero, vertically centered — position: absolute; right: -100px; top: 50%; transform: translateY(-50%)
[KEEP] Size, color, and image inside it
```

### Change element size
```
[CHANGE] Size of the half-circle
[TARGET] The bottom semicircle shape
[FROM] 400px wide, 200px tall
[TO] 700px wide, 350px tall — scale proportionally, maintain 2:1 ratio
[KEEP] Color, position anchor (bottom-center), image inside, parallax
```

### Change spacing
```
[CHANGE] Vertical padding of the services section
[TARGET] The .services-section padding
[FROM] Current padding
[TO] padding: 160px 0 — large generous breathing room
[KEEP] All content, grid, and animations unchanged
```

### Add a new element
```
[ADD] A floating badge / stat counter
[POSITION] Absolute, overlapping top-right corner of the hero image
[CONTENT] "12+ Years" in large number, "Experience" in small label below
[STYLE] Small dark pill shape, white text, subtle shadow
[ANIMATION] Delayed entrance — scale from 0 to 1 after 1200ms on load
[LAYER] z-index above the image, below the nav
```

---

## SECTION-LEVEL CHANGES

### Replace a section entirely
```
[REPLACE SECTION] The current about section
[REMOVE] Everything in the current about section
[BUILD NEW]
  - Two-column layout: image left (60%), text right (40%)
  - Image: full-height, object-fit: cover, border-radius: 0 on left, 24px on right
  - Text: section label "About", large heading, 2 paragraph body, one CTA link
  - Text entrance: stagger fade up with 0.1s delay between each element
  - Image entrance: clip-path wipe from bottom on scroll
[KEEP] Overall page style, color palette, and fonts
```

### Add a section between two existing ones
```
[INSERT SECTION] Between the hero and the services section
[SECTION TYPE] Marquee / scrolling ticker
[CONTENT] Repeating text: "Web Design · Motion · Development · Video Editing · Branding ·"
[STYLE] Full-width, dark background, white text, large font, horizontal infinite scroll
[SPEED] 30 seconds per loop, continuous, no pause
[DIRECTION] Left to right
```

---

## DEBUGGING COMMANDS

### Fix broken layout
```
[FIX] Layout is broken on mobile
[ISSUE] The two columns stack but the image is too tall and pushes content
[FIX TO] On screens below 768px: single column, image max-height: 300px, object-fit: cover, border-radius: 16px
[KEEP] Desktop layout unchanged
```

### Fix animation not working
```
[FIX] The scroll parallax on the hero image is not working
[ISSUE] Image is not moving on scroll
[CHECK] Ensure: window.addEventListener('scroll', handler), element has transform applied in JS, element is not inside a fixed container, will-change: transform is set
[KEEP] Everything else
```

### Fix z-index / overlap issue
```
[FIX] The semicircle shape is appearing above the navbar
[ISSUE] Z-index stacking conflict
[FIX TO] Set z-index: 1 on the semicircle, z-index: 100 on the navbar — ensure navbar has position: sticky or fixed, not static
[KEEP] All visual styling
```

---

## QUICK ONE-LINE COMMANDS

Use these for small fast changes:

| Command | What it does |
|---|---|
| `[SLOW DOWN] all entrance animations by 30%` | Increase duration by 30% across all animations |
| `[SHARPEN] the easing on hover effects` | Change to cubic-bezier(0.4, 0, 0.2, 1) |
| `[ADD GRAIN] subtle noise overlay to the hero` | CSS noise SVG overlay, opacity 0.04 |
| `[INCREASE] vertical spacing in all sections by 40px` | Add 40px to top and bottom padding |
| `[MAKE STICKY] the navbar` | position: sticky; top: 0; z-index: 100 |
| `[ADD SMOOTH SCROLL]` | scroll-behavior: smooth on html element |
| `[INVERT] hero section to light background` | Swap background to white/cream, text to dark |
| `[REMOVE] all box shadows` | Set box-shadow: none on all elements |
| `[ADD] will-change: transform to all animated elements` | GPU layer promotion for smooth animation |
| `[FIX] fonts not loading` | Verify Google Fonts import link is in <head>, use correct font-family name |
