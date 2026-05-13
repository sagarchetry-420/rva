---
name: landing-page-redesign
description: "Redesign school website landing page with distinctive, human-crafted aesthetics. Use when rebuilding a school website's public-facing homepage to avoid generic AI-generated designs. Creates cohesive, memorable experiences with unique typography, organic layouts, and intentional visual hierarchy."
---

# School Landing Page Redesign

This skill guides the complete redesign of a school website landing page, ensuring it looks deliberately designed—not AI-generated. The goal is to create something genuinely memorable with a clear aesthetic point of view.

## Design Thinking Process

Before implementing code, establish the school's visual identity:

### 1. Define the Aesthetic Direction

Choose ONE primary aesthetic philosophy that reflects the school's character:

**Academic & Heritage-Focused:**
- Elegant serif typography (like Playfair Display, Cormorant, Crimson Text)
- Muted, sophisticated color palette (deep blues, warm golds, cream backgrounds)
- Classic editorial layouts with generous whitespace
- Subtle textures (linen paper, light noise)
- Heritage badges, established-year emphasis
- **Tone:** Prestigious, timeless, scholarly

**Modern & Progressive:**
- Bold geometric sans-serifs (IBM Plex Sans, Space Mono, Grotesk variations)
- Vibrant accent colors with high contrast
- Asymmetrical layouts, diagonal compositions
- Minimal, clean spacing
- Motion and modern interactions
- **Tone:** Forward-thinking, energetic, inclusive

**Warm & Community-Focused:**
- Humanist sans-serifs (Poppins, Outfit, Comfortaa variations)
- Warm color palette (coral, terracotta, warm neutrals)
- Hand-drawn elements, organic shapes
- Friendly typography pairings
- Student/community photo emphasis
- **Tone:** Welcoming, approachable, inclusive

**Bold & Distinctive:**
- Uncommon serif + modern sans combo (Sohne + Monument Extended, etc.)
- Daring color choices (rich greens, deep purples, unexpected combinations)
- Dramatic compositional breaks, overlapping layers
- Maximum hierarchy contrast
- Intentional typography experiments
- **Tone:** Ambitious, unique, memorable

### 2. Visual Components Strategy

**Typography:**
- **Display Font:** Choose ONE distinctive serif OR sans-serif for headlines that sets the tone immediately
- **Body Font:** Select a complementary font that prioritizes readability but has character
- **Accent Font (optional):** For special labels, stats, or CTAs—pick something with personality
- Avoid generic: Skip Inter, Roboto, standard system fonts. Think Poppins, Sohne, Outfit, Playfair, Lora, etc.

**Color Palette:**
- **Primary color:** School's main identity color (gold, green, blue, etc.)
- **Accent color:** Contrasting color that creates visual hierarchy
- **Neutrals:** 2-3 carefully chosen grays/off-whites, not pure black/white
- **Semantic colors:** Success (subtle green), error (if needed), highlight
- **Avoid:** Flat purples on white, overdone gradients, muddy color mixing

**Spacing & Layout:**
- Embrace asymmetry—not everything needs equal padding
- Use rhythm: some sections tight, others with generous breathing room
- Grid-breaking elements: Images that overflow containers, text that spans unexpected widths
- Create "moments" of visual interest through unexpected layout choices

**Motion & Interactions:**
- Scroll-triggered animations for major sections (not every element)
- Hover states that feel intentional and feedback-rich
- Page load sequences that reveal hierarchy (staggered reveals, fade-ups)
- Avoid: Random floating animations, excessive transitions, overused effects

**Visual Details:**
- Decorative elements must serve a purpose—they should enhance identity, not clutter
- Organic shapes (blobs, curves) OR geometric precision—not a mix of both
- Photographic consistency: Use cohesive photography or illustrations, not random stock images
- Texture layering: Subtle noise, overlays, or patterns that unify the aesthetic

## Landing Page Structure

Design unique treatments for these sections (don't use generic component libraries):

### Navbar
- Custom logo treatment with school identity
- Navigation anchors that feel integrated with the design language
- Mobile-responsive without hamburger if possible (creative alternatives)
- Scroll state behavior that's satisfying and branded

### Hero Section
- **Large hero image:** Carefully framed school moment (not generic students)
- **Headline:** Distinctive typography treatment—large scale or unexpected sizing
- **Subheading:** Conveys school mission or unique value prop
- **CTA buttons:** Styled to match aesthetic—bold and commanding or subtle and refined
- **Decorative element:** 1-2 branded shapes, icons, or graphical elements unique to design
- **Stat callouts:** Founded year, student count, achievements—prominently featured

### About Section
- School story told with intentional visual emphasis
- Mix of text hierarchy with supporting imagery
- Color/typography treatment that differs from hero

### Courses/Programs
- Grid or list—NOT generic card layouts
- Custom icon or badge system
- Hover interactions that reveal more depth

### Achievements/Toppers
- Showcase student success with dignity—avoid generic trophy imagery
- Typography-focused design showing statistics, rankings, pass rates
- Visual celebration without kitsch

### Gallery
- Thoughtful photo curation showing school life
- Masonry, grid, or custom layout that feels intentional
- NOT Unsplash placeholder images

### Contact/CTA Section
- Prominent call-to-action designed to match brand
- Contact info presented with visual hierarchy
- Portal login buttons clearly distinguished

## Code Implementation Principles

**Use these patterns for distinctive design:**

1. **CSS Variables for Theme Control**
   - Define all colors, fonts, spacing as CSS variables
   - Easy to adjust without touching component logic
   - Create predictable, cohesive spacing scale

2. **Framer Motion for Intentional Animations**
   - Scroll-triggered reveals for sections
   - Staggered animations on section entry
   - Hover states on interactive elements

3. **Tailwind + Custom CSS**
   - Use Tailwind for utility base
   - Add custom CSS for unique shapes, textures, and effects
   - Leverage pseudo-elements for decorative elements

4. **Responsive Design**
   - Mobile-first: Design beautiful at 375px, then enhance
   - Desktop optimizations for large screens
   - Tablet breakpoints for medium devices

5. **Typography Scale**
   - Define 5-7 heading sizes with clear hierarchy
   - Body text at 16px+ for readability
   - Line-height of 1.5-1.6 for comfortable reading

## Anti-Patterns to Avoid

❌ **Don't:**
- Use purple gradients on white backgrounds
- Default to Inter, Roboto, or system fonts for display text
- Create floating cards that all look identical
- Add animations to every element
- Use Unsplash images without intentionality
- Make hover states that just add opacity
- Overuse emojis or generic icons
- Mix too many design directions (brutalist + maximalist + minimal)
- Create sections that have no visual distinction from each other
- Use generic "team" photos that could be any school

✅ **Do:**
- Choose a clear aesthetic direction and commit to it
- Use distinctive typography combinations
- Create visual hierarchy through color, scale, and spacing
- Intentional, purposeful animations only
- Curated, authentic school photography
- Hover states that add value or context
- Custom icons or visual language specific to the school
- Each section should feel visually distinct
- Photos that capture real school moments
- Design that a visitor will remember specifically for THIS school

## Implementation Checklist

- [ ] Define aesthetic direction (heritage, modern, community, bold, etc.)
- [ ] Select 2-3 distinctive fonts (display + body + optional accent)
- [ ] Create color palette (primary, accent, neutrals, semantic)
- [ ] Design navbar with custom branding
- [ ] Create hero section with unique visual hook
- [ ] Design about/mission section with intentional layout
- [ ] Build programs/courses section with custom treatments (not generic cards)
- [ ] Create gallery or achievements showcase
- [ ] Design contact/CTA section that stands out
- [ ] Add scroll animations for major sections
- [ ] Implement responsive design (mobile first)
- [ ] Add micro-interactions and hover states
- [ ] Audit: Does this look like it was designed for THIS school specifically?
- [ ] Audit: Could this design be confused with another school's website?

## Example Aesthetic Directions

**Example 1: Heritage + Editorial**
- Playfair Display + Lora (serif pairing)
- Deep forest green (#1A3A2A) + warm gold (#C39343) + cream (#FAF9F6)
- Large serif headlines, generous margins, muted photography
- Result: Prestigious, established, scholarly

**Example 2: Modern + Bold**
- Space Mono + IBM Plex Sans
- Vibrant teal (#00A3A3) + charcoal (#2C2C2C) + off-white
- Geometric patterns, overlapping elements, high contrast
- Result: Forward-thinking, ambitious, tech-savvy

**Example 3: Warm & Community**
- Poppins (bold) + Lora (body)
- Warm terracotta (#D97D62) + soft blue (#4A90A4) + cream
- Rounded corners, organic shapes, diverse photography
- Result: Welcoming, inclusive, student-focused

---

## When to Use This Skill

- **Redesigning a school landing page** to make it visually distinctive and memorable
- **Creating a new school website** that needs strong visual identity
- **Upgrading generic page layouts** to something with intentional design direction
- **Building sections** that need to avoid looking "AI-generated" or templated

## When NOT to Use This Skill

- Making small CSS tweaks to existing sections
- Adding a single component to an established design
- Fixing bugs or performance issues (those are separate tasks)
