# Azox Network Website

A modern, responsive website for the Azox Network Minecraft server featuring a dark theme with crimson accents, mobile-first navigation, and comprehensive player resources.

## 🎮 About Azox Network

Azox Network is a hardcore PvP Minecraft server running **Season III: Trial by Fate** - an always-on PvP survival experience where retreat is not an option and only the strongest survive.

**Server Details:**
- **IP:** `azox.net`
- **Version:** Java Edition 1.20.x
- **Mode:** Survival (Hard Mode)
- **PvP:** Always Enabled
- **Max Players:** 500

## 🌟 Features

### 🎨 Design & User Experience
- **Dark Theme:** Professional dark design with crimson (#DC143C) accent colors
- **Responsive Design:** Fully responsive across all device sizes
- **Mobile Navigation:** Hamburger menu for mobile devices (≤820px)
- **Smooth Animations:** CSS transitions and hover effects
- **Accessibility:** ARIA labels and keyboard navigation support

### 📱 Mobile-First Navigation
- **Hamburger Menu:** Animated 3-line icon that transforms to X when active
- **Mobile Dropdown:** Backdrop blur menu with smooth animations
- **Auto-Close:** Menu closes when clicking links or outside the menu area
- **Touch-Friendly:** Optimized for mobile interaction

### 📄 Pages & Content
- **Homepage:** Hero section with server IP and key features
- **News:** Dynamic news articles with markdown parsing
- **Events:** Tournament announcements and community events
- **Rules:** Comprehensive server rules and guidelines
- **Contact:** Discord and email support information
- **Play Now:** Detailed Minecraft connection instructions
- **Tools:** Productivity tools including Pomodoro timer

### 🛠️ Tools & Utilities
- **Pomodoro Timer:** Focus timer with customizable work/break intervals
- **Claim System:** (In development) Web-based item claiming interface
- **More Tools:** Additional utilities planned for future releases

## 🏗️ Project Structure

```
azox-www/
├── index.html              # Homepage
├── style.css              # Main stylesheet (all CSS centralized)
├── README.md              # Project documentation
├── contact/
│   ├── index.html         # Contact page
│   └── contact.md         # Contact content
├── events/
│   ├── index.html         # Events page
│   └── 2025-11-09-1-first.md
├── news/
│   ├── index.html         # News page
│   ├── 2025-11-09-1-first.md
│   └── 2025-11-09-2-update.md
├── play-now/
│   ├── index.html         # Connection guide page
│   └── play-now.md        # Minecraft connection instructions
├── rules/
│   ├── index.html         # Rules page
│   └── rules.md           # Server rules content
├── tools/
│   ├── index.html         # Tools overview
│   ├── claim/
│   │   └── index.html     # Item claiming tool (in development)
│   └── timer/
│       └── index.html     # Pomodoro focus timer
└── map/
    └── index.html         # Server map (placeholder)
```

## 🚀 Getting Started

### Prerequisites
- Modern web browser (Chrome, Firefox, Safari, Edge)
- Web server (for local development) or static hosting service

### Local Development
1. Clone the repository
2. Serve the files using a local web server:
   ```bash
   # Using Python 3
   python -m http.server 8000
   
   # Using Node.js (http-server)
   npx http-server
   
   # Using PHP
   php -S localhost:8000
   ```
3. Open `http://localhost:8000` in your browser

### Deployment
The website is built with static HTML, CSS, and JavaScript - no build process required. Simply upload all files to your web server or static hosting service.

**Recommended Hosting:**
- GitHub Pages
- Netlify
- Vercel
- Traditional web hosting

## 🎨 Design System

### Color Palette
```css
--bg-0: #121214        /* Primary background */
--bg-1: #1a1c1f        /* Secondary background */
--bg-2: #202225        /* Tertiary background */
--text: #e8eaed        /* Primary text */
--text-dim: #b7bcc2    /* Secondary text */
--crimson: #DC143C     /* Primary accent */
--crimson-2: #880000   /* Dark crimson */
--crimson-3: #440000   /* Darkest crimson */
```

### Typography
- **Primary Font:** System UI stack (ui-sans-serif, system-ui, -apple-system, etc.)
- **Monospace Font:** UI monospace stack for code and server IPs
- **Headings:** Bold weights with crimson accents
- **Body Text:** Clean, readable hierarchy

### Components
- **Buttons:** Gradient crimson buttons with hover effects
- **Cards:** Semi-transparent backgrounds with backdrop blur
- **Navigation:** Sticky header with mobile hamburger menu
- **Forms:** Dark inputs with crimson focus states

## 📱 Responsive Breakpoints

```css
/* Mobile First Approach */
@media (max-width: 640px)  { /* Mobile */ }
@media (max-width: 820px)  { /* Tablet - Hamburger menu appears */ }
@media (max-width: 960px)  { /* Desktop - Layout changes */ }
```

## 🔧 Technical Details

### CSS Architecture
- **Centralized Styles:** All CSS in `style.css` for maintainability
- **CSS Variables:** Consistent color and spacing system
- **Mobile-First:** Responsive design starting from mobile
- **Modern CSS:** Flexbox, Grid, and CSS custom properties

### JavaScript Features
- **Vanilla JS:** No external dependencies
- **Modular Code:** Self-contained functionality blocks
- **Event Handling:** Proper event delegation and cleanup
- **Browser Compatibility:** Modern browser support

### Performance
- **Optimized Images:** Efficient use of CSS gradients over images
- **Minimal Dependencies:** No external frameworks or libraries
- **Fast Loading:** Lightweight HTML, CSS, and JavaScript
- **Caching:** Static files with proper cache headers

## 🎯 Key Features Implementation

### Hamburger Menu System
```html
<!-- Hamburger button -->
<button class="hamburger" id="hamburger" aria-label="Toggle navigation menu">
  <span></span>
  <span></span>
  <span></span>
</button>

<!-- Mobile menu -->
<nav class="mobile-menu" id="mobileMenu" aria-label="Mobile navigation">
  <!-- Navigation links -->
</nav>
```

### Pomodoro Timer
- **Customizable Intervals:** Work, short break, and long break durations
- **Session Tracking:** Automatic progression through work/break cycles
- **Audio Notifications:** Browser notification API integration
- **Progress Visualization:** Real-time progress bar updates

### Content Management
- **Markdown Parsing:** Client-side markdown rendering for content
- **Dynamic Loading:** JavaScript-based content loading
- **Structured Data:** Organized content in markdown files

## 🛠️ Development Guidelines

### Code Style
- **HTML:** Semantic markup with proper accessibility
- **CSS:** BEM-inspired naming with logical organization
- **JavaScript:** ES6+ features with clear function names
- **Comments:** Comprehensive documentation in code

### File Organization
- **Logical Structure:** Clear directory hierarchy
- **Consistent Naming:** Kebab-case for files and directories
- **Separation of Concerns:** HTML structure, CSS presentation, JS behavior

### Browser Support
- **Modern Browsers:** Chrome 80+, Firefox 75+, Safari 13+, Edge 80+
- **Mobile Browsers:** iOS Safari, Chrome Mobile, Samsung Internet
- **Progressive Enhancement:** Core functionality works without JavaScript

## 🚀 Future Enhancements

### Planned Features
- **PHP Backend:** Server-side functionality for claim system
- **User Authentication:** Player account integration
- **Real-time Data:** Live server statistics and player counts
- **Advanced Tools:** More productivity and gaming utilities

### Technical Improvements
- **Build Process:** Asset optimization and minification
- **PWA Features:** Service worker and offline functionality
- **Performance:** Image optimization and lazy loading
- **SEO:** Enhanced meta tags and structured data

## 🤝 Contributing

### Development Setup
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test across different devices and browsers
5. Submit a pull request

### Code Standards
- Follow existing code style and organization
- Test responsive design on multiple screen sizes
- Ensure accessibility compliance
- Document any new features or changes

## 📞 Support

### Community
- **Discord:** [discord.gg/azox](https://discord.gg/azox)
- **Website:** Browse guides and tutorials
- **Email:** support@azox.net

### Technical Issues
- Check browser console for JavaScript errors
- Verify all files are properly uploaded
- Test on different browsers and devices
- Contact support with detailed error information

## 📄 License

This project is proprietary software for the Azox Network. All rights reserved.

## 🏷️ Version History

### v2.0.0 (November 2025)
- ✅ Responsive hamburger menu system
- ✅ Comprehensive Play Now connection guide
- ✅ Centralized CSS architecture
- ✅ Mobile-first responsive design
- ✅ Pomodoro productivity timer
- ✅ Enhanced accessibility features

### v1.0.0 (Initial Release)
- Basic website structure
- News and events system
- Server information pages
- Contact and rules sections

---

**Built with ❤️ for the Azox Network community**

*Last Updated: November 26, 2025*