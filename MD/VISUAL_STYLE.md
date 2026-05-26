# VISUAL_STYLE.md
# Aesthetic Presets — Lock Your Visual Identity
# Pick ONE preset and paste it into your session after MASTER_PROMPT.md

---

## HOW TO USE

Copy your chosen preset block and paste it early in your session.
AI will maintain this aesthetic across ALL sections you build.
You can mix elements from multiple presets — just describe what to combine.

---

## PRESET 1 — DARK LUXURY

```
[VISUAL STYLE: DARK LUXURY]

Color Palette:
  - Background:     #0a0a0a (near black)
  - Surface:        #111111
  - Accent primary: #c9a84c (gold)
  - Accent alt:     #ffffff (white)
  - Text primary:   #f0ece4
  - Text muted:     rgba(240,236,228,0.45)

Typography:
  - Display font:   Cormorant Garamond or Playfair Display (Google Fonts) — elegant serif
  - Body font:      DM Sans or Jost — clean modern sans
  - Heading weight: 300–400 (thin elegance, not bold)
  - Letter spacing: 0.05em on headings, 0.15em on labels

Texture & Depth:
  - Subtle grain overlay: `opacity: 0.03–0.06` noise SVG or CSS
  - Section dividers: thin 1px gold lines or negative space
  - Shadows: dark, large, very soft (box-shadow: 0 40px 80px rgba(0,0,0,0.5))
  - Glassmorphism accents: backdrop-filter: blur(20px) on overlay cards

Spacing:
  - Generous vertical padding: 140px–200px per section
  - Content max-width: 1200px centered
  - Breathing room is a design feature — don't crowd

Motion:
  - Slow, cinematic entrances: 1000ms–1400ms
  - Easing: cubic-bezier(0.25, 0.46, 0.45, 0.94)
  - No bouncy animations — everything is smooth and deliberate
  - Parallax on all hero imagery
```

---

## PRESET 2 — EDITORIAL / MAGAZINE

```
[VISUAL STYLE: EDITORIAL MAGAZINE]

Color Palette:
  - Background:     #f8f5f0 (warm off-white)
  - Surface:        #ffffff
  - Accent primary: #e63329 (editorial red)
  - Accent alt:     #1a1a1a
  - Text primary:   #1a1a1a
  - Text muted:     #6b6b6b

Typography:
  - Display font:   Unbounded or Clash Display or Anton — condensed, bold
  - Body font:      Libre Baskerville or Lora — readable serif
  - Headlines: ALL CAPS, tight tracking (-0.02em), large scale (8vw–12vw)
  - Pull quotes: italic serif, oversized

Layout Style:
  - Asymmetric grid — not everything aligned
  - Big numbers as section markers (01, 02, 03)
  - Horizontal rules (thin lines) as structural dividers
  - Mix portrait and landscape image ratios within grid
  - Text overlaps image in hero

Motion:
  - Fast, snappy transitions: 300ms–500ms
  - Text lines reveal (overflow: hidden + translateY)
  - Images scale from 1.1 → 1.0 on entrance (cover-style zoom in)
  - Hover: underline draw, color invert effects
```

---

## PRESET 3 — CREATIVE STUDIO / AWWWARDS

```
[VISUAL STYLE: CREATIVE STUDIO]

Color Palette:
  - Background:     #0d0d14 (deep blue-black)
  - Surface:        #13131e
  - Accent primary: #6c47ff (electric purple)
  - Accent glow:    #a78bfa
  - Text primary:   #eae8ff
  - Text muted:     rgba(234,232,255,0.4)

Typography:
  - Display font:   Syne or Neue Machina or Instrument Serif — modern, distinctive
  - Body font:      Space Grotesk or DM Mono (monospace accents)
  - Mix serif + sans for contrast within hero
  - Large numbers: tabular nums, outline stroke style

Visual Effects:
  - Gradient mesh background (purple + blue soft blobs)
  - Glassmorphism cards: backdrop-filter: blur(24px) + rgba border
  - Glow accents: box-shadow with accent color, low opacity
  - Horizontal scrolling sections
  - Custom cursor: larger circle, changes color on hover

Motion:
  - Smooth inertia scrolling (lerp-based)
  - Scroll-triggered SVG line draws
  - 3D perspective card tilts on hover
  - Staggered word entrance on headline
  - Floating ambient shapes in background
```

---

## PRESET 4 — BRUTALIST / RAW

```
[VISUAL STYLE: BRUTALIST]

Color Palette:
  - Background:     #ffffff or #f0f0f0
  - Accent primary: #ff0000 or #ffff00 (harsh primaries only)
  - Accent alt:     #0000ff or #000000
  - Text:           #000000
  - No gradients. No soft colors. Hard edges only.

Typography:
  - Display font:   Impact, Arial Black, or Bebas Neue — brutal weight
  - Body:           Courier New or Space Mono — typewriter feel
  - Text can break layout edges (overflow intentional)
  - Mix sizes aggressively (massive headline + tiny body)

Layout Style:
  - Visible grid lines / borders as decoration
  - Off-grid placements — intentionally misaligned
  - Raw <hr> elements as dividers
  - Text overlapping images with harsh contrast
  - Diagonal text or rotated type

Visual Details:
  - NO shadows, NO rounded corners, NO gradients
  - Hard 2–4px solid borders
  - High-contrast only
  - Grain/noise texture OK

Motion:
  - Sharp, instant snap transitions
  - Glitch effects on hover (translate jitter)
  - No easing — or very aggressive cubic
  - Flash of color on hover (background inversion)
```

---

## PRESET 5 — SOFT MINIMAL / ORGANIC

```
[VISUAL STYLE: SOFT MINIMAL ORGANIC]

Color Palette:
  - Background:     #faf9f6 (natural cream)
  - Surface:        #ffffff
  - Accent primary: #7aa87a (sage green) or #c4956a (terracotta)
  - Text primary:   #2d2d2d
  - Text muted:     #8a8580

Typography:
  - Display font:   Fraunces or Sentient — organic variable serif
  - Body font:      Plus Jakarta Sans or Nunito — soft, rounded
  - Weight: light 300 for headlines, regular 400 for body
  - Letter spacing: natural, no tight tracking

Visual Details:
  - Soft drop shadows (shadow-color: rgba(0,0,0,0.06))
  - Rounded corners everywhere (24px–40px)
  - Blob shapes as background accents
  - Warm photo tones (natural light imagery)
  - Subtle texture: linen or paper grain

Motion:
  - Slow, gentle entrances: 800ms–1200ms
  - Elastic slight bounce: cubic-bezier(0.34, 1.56, 0.64, 1) on cards
  - Hover: gentle float lift (translateY(-4px))
  - No hard snaps — everything breathes
  - Scroll reveal with soft fade + drift up
```

---

## PRESET 6 — PORTFOLIO / VIDEO EDITOR / CREATIVE FREELANCER

```
[VISUAL STYLE: DARK CREATIVE PORTFOLIO]

Color Palette:
  - Background:     #080808
  - Surface:        #101010
  - Accent primary: [YOUR BRAND COLOR — replace this]
  - Accent alt:     #ffffff
  - Text:           #f0f0f0
  - Muted:          rgba(240,240,240,0.35)

Typography:
  - Display font:   Neue Haas Grotesk / Helvetica Neue / or Satoshi — clean Swiss style
  - Body font:      Inter or DM Sans
  - Hero text: very large (10vw–14vw), tight tracking (-0.03em)
  - Role/label text: 11px caps, spaced 0.2em, muted color

Sections:
  - Full-screen project showcases (video or image)
  - Work grid: hover reveals title overlay + play/view button
  - About: split layout with large self-image
  - Skills shown as animated progress or horizontal list
  - Minimal footer with social links

Motion:
  - Page load: slide-up title reveal, 1200ms
  - Project grid: hover darkens image + overlays details
  - Smooth scroll with slight inertia feel
  - Work items: scroll-triggered stagger entrance
  - Custom cursor: circle follows mouse, fills on hover
```

---

## CUSTOM STYLE BUILDER

If none of the presets match, fill this in:

```
[VISUAL STYLE: CUSTOM]

Mood/vibe in 3 words: [e.g. "dark, cinematic, minimal"]

Color Palette:
  - Background: [color]
  - Accent: [color]
  - Text: [color]

Typography feel: [e.g. "elegant serif + clean sans" / "all monospace" / "big bold display"]

References (websites that feel similar): [list URLs if any]

Layout preference: [centered / asymmetric / grid / full-bleed sections]

Motion preference: [slow/cinematic / snappy/energetic / subtle/minimal / heavy/dramatic]

What to AVOID: [e.g. "no gradients, no rounded cards, no purple"]
```
