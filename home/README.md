# Rose Valley Academy - Home Page Website

## 📁 Folder Structure
```
home/
├── index.html          (Landing page)
├── gallery.html        (Gallery page with moments)
├── css/
│   └── style.css       (Complete styling - matches school theme)
├── js/
│   ├── script.js       (Navigation & animations)
│   └── gallery.js      (Gallery filtering & lightbox)
└── assets/
    └── (Uses images from root ../assets/ folder)
```

## 🌐 Pages

### 2. Landing Page (index.html)
- **Navbar** - Sticky navigation with mobile menu
- **Hero Section** - Background image from school_image/img3.jpg
- **Principal Section** - Principal's image & welcome message
  - Professional photo layout
  - Principal's message with vision
  - Signature section
- **About Section** - 3 cards with faculty images
  - Principal's image
  - Dean image
  - Administrative Coordinator image
- **Facilities Grid** - 6 facilities with school images
- **Notices Section** - Important announcements
- **Contact Section** - Location, phone, email
- **Footer** - Links and copyright

### 2. Gallery Page (gallery.html)
- **Gallery Hero** - Title section
- **Filter Buttons** - All, Sports, Academic, Cultural
- **Gallery Grid** - 8 images with overlay content
  - NCC.jpg (Sports)
  - NCC2.jpg (Cultural)
  - photo.jpg (Academic)
  - photo2.jpg (Sports)
  - photo3.jpg (Cultural)
  - photo4.jpg (Academic)
  - photo5.jpg (Sports)
  - photo6.jpg (Cultural)
- **Lightbox Modal** - Click image to view full screen
  - Arrow keys: Navigate between images
  - ESC: Close lightbox
  - Click outside: Close lightbox

## 🎨 Styling Features
- **Navbar**: White background with maroon text & border
- **Color Scheme**: Maroon (#800000), Navy (#1565c0), Orange (#f57f17)
- **Logo**: Real school logo (50px height)
- **School Name**: Rose Valley Academy
- **Fonts**: Georgia (headings), Helvetica (body)
- **Responsive Design**: Mobile, Tablet, Desktop
- **Animations**: Smooth parallax, transitions, hover effects
- **Parallax Hero**: Background moves at 50% scroll speed

### Images Used (From Root Assets)
```
../assets/
├── school_image/
│   ├── img3.jpg (Hero background) ⭐ MAIN
│   └── img2.jpg (Facility image)
├── principal's_image/
│   └── principal's image1.jpg (About card)
├── dean/
│   └── dean1.jpg (About card)
├── administrative coordinator/
│   └── administrative coordinator.jpg (About card)
└── gallery/
    ├── ncc.jpg (Gallery)
    ├── ncc2.jpg (Gallery)
    ├── photo.jpg (Gallery & Facility)
    ├── photo2.jpg (Gallery & Facility)
    ├── photo3.jpg (Gallery & Facility)
    ├── photo4.jpg (Gallery & Facility)
    ├── photo5.jpg (Gallery)
    └── photo6.jpg (Gallery)
```

## 🔗 Navigation
- **Home**: Scrolls to hero section
- **Principal**: Principal's message & image
- **About**: Scrolls to about section
- **Facilities**: Scrolls to facilities section
- **Notices**: Scrolls to notices section
- **Gallery**: Links to gallery.html
- **Contact**: Scrolls to contact section

## 📱 Responsive Breakpoints
- **Desktop**: Full layout with 2-column principal section
- **Tablet (≤768px)**: Single column, mobile menu, stacked principal section
- **Mobile (≤480px)**: Optimized for small screens

## ✨ Features
✅ **White Navbar** - Professional appearance with dark text
✅ **Real School Logo** - 50px height, responsive scaling
✅ **School Name**: Rose Valley Academy
✅ **Parallax Hero** - Smooth scroll depth effect
✅ **Principal Section** - Image, message & signature
✅ Fully responsive design
✅ Smooth scrolling navigation
✅ Gallery with filtering system
✅ Lightbox modal viewer
✅ Keyboard navigation (gallery)
✅ Mobile hamburger menu
✅ Hover animations on cards
✅ Gradient backgrounds
✅ Professional color scheme (Maroon/Navy)
✅ No links to school_management folder

## 🚀 Live URLs
- Landing Page: `http://localhost/home/`
- Gallery Page: `http://localhost/home/gallery.html`
