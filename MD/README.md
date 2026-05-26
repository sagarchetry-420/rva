# Creative Direction OS — Quick Start Guide
# Built for: Video Editors, Graphic Designers & Web Developers
# Purpose: Bridge the gap between your creative vision and AI implementation

---

## THE SYSTEM AT A GLANCE

```
creative-direction-os/
├── MASTER_PROMPT.md       ← Paste this FIRST in every AI session
├── VISUAL_STYLE.md        ← Pick your aesthetic preset
├── LAYOUT_VOCAB.md        ← Describe shapes & layout precisely
├── ANIMATION_VOCAB.md     ← Describe motion & animation precisely
├── SECTION_TEMPLATES.md   ← Fill-in templates per section
└── CHANGE_COMMANDS.md     ← Precise commands for revisions
```

---

## WORKFLOW: START TO FINISH

### Step 1 — Open a new AI chat
Paste the entire contents of `MASTER_PROMPT.md`
Wait for: "Creative Direction OS active."

### Step 2 — Set your visual style
Paste your chosen preset from `VISUAL_STYLE.md`
Example: paste the DARK LUXURY block

### Step 3 — Describe your section
Use a template from `SECTION_TEMPLATES.md`
Fill in all the brackets
Add [TECH] tags from `LAYOUT_VOCAB.md` and `ANIMATION_VOCAB.md` for anything specific

### Step 4 — Iterate precisely
Use commands from `CHANGE_COMMANDS.md`
Never say "make it better" — always say exactly what, from what, to what

---

## YOUR HALF-CIRCLE EXAMPLE — SOLVED

**Old way (AI fails):**
> "add a yellow half circle at the bottom with image inside and scroll parallax"

**New way (AI nails it):**
```
[SHAPE] bottom-center anchored semicircle
[TECH SHAPE] border-radius: 50% 50% 0 0 | width: 600px | height: 300px
[COLOR] #FFD700 (yellow)
[POSITION] position: absolute; bottom: 0; left: 50%; transform: translateX(-50%)
[PARENT] hero section — position: relative; overflow: visible
[IMAGE INSIDE] yes — position: absolute; top: -60px (bleeds above shape)
[IMAGE CLIP] overflow: hidden on the semicircle container
[LAYER] semicircle z-index: 1 | image z-index: 2 | navbar z-index: 100
[PARALLAX] window scroll listener — image translateY(scrollY * -0.25)
[PARALLAX EASING] will-change: transform | requestAnimationFrame preferred
[ENTRANCE] semicircle scales from 0.8 → 1 on load, 1000ms, ease-out
[MOBILE] semicircle width: 90vw | height: 45vw | image scales proportionally
```

---

## PRO TIPS

1. **Paste MASTER_PROMPT.md every new chat** — AI doesn't remember between sessions
2. **Be as specific as you can with [TECH] tags** — they eliminate AI guesswork
3. **One section at a time** — build hero, confirm it's right, then move to next section
4. **Copy-paste your current code** when asking for changes — give AI full context
5. **Use [KEEP] in every change command** — prevents AI from breaking what's already working
6. **Name your elements** — "the yellow semicircle" is better than "the shape"
7. **Test on mobile every time** — add `[MOBILE]` specs to every section

---

## COMMON PROBLEMS & FIXES

| Problem | Fix |
|---|---|
| AI replaced my design instead of adding to it | Add "[KEEP] all existing code, only add what I describe below" |
| Animation isn't smooth | Add "[TECH] will-change: transform, requestAnimationFrame loop" |
| Shape not right | Specify exact CSS: border-radius values or clip-path polygon |
| Font looks generic | Specify exact Google Font name and weight in your prompt |
| Layout breaks on mobile | Add separate [MOBILE] block in your section template |
| Colors look wrong | Always use hex codes, never color names |
| Z-index conflict | List all layers with z-index numbers explicitly |

---

## MADE FOR YOU

You're a video editor, graphic designer, and web developer —
you already think in layers, timing, composition, and motion.

This system is just the translation layer
between how your brain works and how AI needs to receive instructions.

Your vision is right. Now AI can follow it.
