# LAYOUT_VOCAB.md
# Shape & Layout Language Dictionary
# Use these exact phrases in your prompts — AI will map them to precise CSS.

---

## HOW TO USE THIS FILE

When describing a layout or shape to AI, **copy the [TECH] line** and include it in your prompt.
This removes ambiguity and forces the correct implementation.

---

## SHAPES

### Half Circle (top half)
**You say:** "half circle, open side down, sits at top of section"
**[TECH]:** `border-radius: 0 0 50% 50%` | width > height | `overflow: hidden`

### Half Circle (bottom half)
**You say:** "half circle anchored at bottom of section, like a rising sun"
**[TECH]:** `position: absolute; bottom: 0` | `border-radius: 50% 50% 0 0` | width = 2x height

### Full Circle Crop (image inside circle)
**You say:** "image cropped in a circle"
**[TECH]:** `border-radius: 50%` | `overflow: hidden` | `object-fit: cover`

### Diagonal Cut / Skewed Section
**You say:** "section with diagonal bottom edge"
**[TECH]:** `clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%)`

### Blob / Organic Shape
**You say:** "blob shape behind the image"
**[TECH]:** `border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%` | animated with keyframes for morphing

### Pill / Capsule
**You say:** "pill-shaped button or tag"
**[TECH]:** `border-radius: 9999px`

### Triangle (pointing up)
**You say:** "triangle pointing upward"
**[TECH]:** CSS `clip-path: polygon(50% 0%, 0% 100%, 100% 100%)`

### Triangle (pointing down)
**You say:** "downward arrow shape / section divider triangle"
**[TECH]:** `clip-path: polygon(0 0, 100% 0, 50% 100%)`

### Arch / Archway
**You say:** "arch shape over an image or section"
**[TECH]:** `border-radius: 50% 50% 0 0 / 100% 100% 0 0` | tall narrow container

### Trapezoid
**You say:** "trapezoid shape, wider at top"
**[TECH]:** `clip-path: polygon(10% 0%, 90% 0%, 100% 100%, 0% 100%)`

---

## POSITIONING / ANCHORING

### Bottom-Anchored Element
**You say:** "stick to the bottom of the section"
**[TECH]:** Parent `position: relative` | Child `position: absolute; bottom: 0; left: 0; right: 0`

### Center-Bottom Anchored
**You say:** "centered at the bottom"
**[TECH]:** `position: absolute; bottom: 0; left: 50%; transform: translateX(-50%)`

### Full Bleed Background
**You say:** "image fills the entire section background"
**[TECH]:** `position: absolute; inset: 0; object-fit: cover; width: 100%; height: 100%`

### Bleeding Out of Container
**You say:** "element sticks out / bleeds above the section"
**[TECH]:** `margin-top: -80px` OR `position: absolute; top: -80px`

### Pinned to Viewport (Sticky)
**You say:** "element sticks while scrolling"
**[TECH]:** `position: sticky; top: 0`

### Overlapping Two Sections
**You say:** "card sits between two sections, half in each"
**[TECH]:** `position: relative; z-index: 10; margin-top: -60px` on the card

---

## LAYERING / DEPTH ORDER

Describe depth from back to front like this:

```
LAYER 0 (back)   — background color or full-bleed image
LAYER 1          — decorative shape (half circle, blob)
LAYER 2          — secondary image or texture overlay
LAYER 3          — primary image or hero content
LAYER 4          — text content
LAYER 5 (front)  — UI elements (buttons, badges, nav)
```

**[TECH]:** Use `z-index` values 0–5 matching the above.
Parent must have `position: relative` and `isolation: isolate`.

---

## OVERFLOW BEHAVIOR

| You say | [TECH] |
|---|---|
| "image escapes the shape" | `overflow: visible` on parent, `position: absolute` on image |
| "image is clipped inside the shape" | `overflow: hidden` on shape container |
| "image peeks out from top" | `overflow: hidden` on container, image `margin-top: -40px` |
| "section clips at edge" | `overflow: hidden` on section wrapper |

---

## GRID & LAYOUT PATTERNS

### Asymmetric Two-Column
**You say:** "big image left, text right, not equal columns"
**[TECH]:** `grid-template-columns: 65fr 35fr` or `7fr 3fr`

### Overlapping Grid
**You say:** "elements overlap each other in the grid"
**[TECH]:** CSS Grid with `grid-column` / `grid-row` overlap + `z-index`

### Masonry
**You say:** "Pinterest-style staggered image grid"
**[TECH]:** `columns: 3` CSS property OR CSS Grid with `grid-auto-rows`

### Full-Viewport Hero
**You say:** "hero takes up the full screen height"
**[TECH]:** `min-height: 100vh` | `display: flex; align-items: center`

### Centered Content Stack
**You say:** "everything centered vertically and horizontally"
**[TECH]:** `display: flex; flex-direction: column; align-items: center; justify-content: center`

---

## QUICK REFERENCE: YOUR WORDS → AI TECH TERMS

| Your Creative Words | Technical Terms to Include |
|---|---|
| half circle at bottom | `border-radius: 50% 50% 0 0`, `position: absolute; bottom: 0` |
| image bleeds out | `overflow: visible`, `position: absolute`, negative margin |
| clipped inside shape | `overflow: hidden`, `clip-path` |
| floating element | `position: absolute`, `animation: float` keyframe |
| full screen section | `min-height: 100vh` |
| diagonal edge | `clip-path: polygon()` |
| overlapping cards | `margin-top: -Npx`, `z-index`, `position: relative` |
| sticky nav | `position: sticky; top: 0` |
| image fills background | `position: absolute; inset: 0; object-fit: cover` |
| centered at bottom | `position: absolute; bottom: 0; left: 50%; transform: translateX(-50%)` |
