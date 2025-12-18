<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Play Now — Azox — Trial by Fate</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <!-- Play Now Section -->
  <main class="news-container">
    <div class="news-header">
      <div class="eyebrow"><span class="dot"></span>Join the Battle</div>
      <h1 class="news-title">How to Play</h1>
      <p class="news-subtitle">Step-by-step instructions to connect to the Azox Network and begin your Trial by Fate.</p>
    </div>

    <div class="news-grid" id="playNowGrid">
      <div class="loading">Loading connection guide...</div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-inner">
      <p>&copy; 2025 Azox Network</p>
    </div>
  </footer>

  <script>
    // Play Now data (embedded to avoid CORS issues with local files)
    const playNowData = [
      {
        filename: 'play-now.md',
        date: 'November 26, 2025',
        title: 'Connect to Azox Network',
        category: 'Connection Guide',
        content: `Ready to begin your Trial by Fate? Follow these detailed instructions to connect to the Azox Network and start your journey in our hardcore PvP survival world.

## Server Information

**Server IP:** \`azox.net\`
**Version:** Java Edition 1.20.x
**Game Mode:** Survival (Hard Mode)
**PvP:** Always Enabled
**Max Players:** 500

## Quick Start Guide

### Step 1: Launch Minecraft Java Edition
* Open Minecraft Java Edition (Bedrock Edition is not supported)
* Make sure you're running version 1.20.x or compatible
* Log in with your Minecraft account

### Step 2: Access Multiplayer
* From the main menu, click **"Multiplayer"**
* If this is your first time, click **"Proceed"** to acknowledge multiplayer warnings

### Step 3: Add Azox Network Server
* Click **"Add Server"** button
* Enter the following information:
  * **Server Name:** Azox Network (or any name you prefer)
  * **Server Address:** \`azox.net\`
* Click **"Done"** to save the server

### Step 4: Connect to the Server
* Select "Azox Network" from your server list
* Click **"Join Server"**
* Wait for the connection to establish

## Alternative Connection Method

### Direct Connection
If you prefer not to save the server:
* Click **"Direct Connection"** instead of "Add Server"
* Enter \`azox.net\` in the server address field
* Click **"Join Server"**

## First Time Setup

### Character Creation
* Choose your starting location carefully - you cannot change it later
* Your spawn point will be randomized within the starting area
* **Remember:** There are no safe zones - PvP is always enabled

### Essential First Steps
1. **Gather basic resources immediately** - wood, stone, food
2. **Find or build shelter before nightfall** - monsters spawn in darkness
3. **Stay alert** - other players can attack you at any time
4. **Read the rules** - Use \`/rules\` command in-game or visit our rules page

## System Requirements

### Minimum Requirements
* **Minecraft:** Java Edition 1.20.x
* **RAM:** 4GB allocated to Minecraft
* **Internet:** Stable broadband connection
* **Account:** Valid Minecraft Java Edition license

### Recommended Specifications
* **RAM:** 8GB+ allocated to Minecraft
* **CPU:** Intel i5 or AMD Ryzen 5 equivalent
* **Internet:** Low-latency connection for optimal PvP performance
* **Graphics:** Dedicated GPU for better performance

## Troubleshooting Connection Issues

### Cannot Connect to Server
* **Check your internet connection** - Ensure you have stable internet
* **Verify server address** - Make sure you entered \`azox.net\` correctly
* **Check Minecraft version** - Ensure you're running a compatible version
* **Restart Minecraft** - Close and reopen the game client

### Connection Timeout
* **Server may be full** - Wait a few minutes and try again
* **Network issues** - Check your firewall and antivirus settings
* **ISP blocking** - Some ISPs block game servers; try a VPN if needed

### Login Issues
* **Invalid session** - Restart Minecraft and log in again
* **Account problems** - Ensure your Minecraft account is valid and active
* **Authentication servers down** - Wait and try again later

## Important Server Rules Reminder

Before you start playing, remember these critical rules:

### PvP Guidelines
* **Always-on PvP** - Combat can happen anywhere, anytime
* **No retreat** - Once combat begins in designated areas, you must fight
* **Gear score requirements** - Some areas require minimum equipment levels

### Survival Rules
* **Hard mode difficulty** - Monsters are stronger and more dangerous
* **Limited resources** - Plan your resource gathering carefully
* **Permanent consequences** - Deaths have significant penalties

### Community Standards
* **Respect other players** - No harassment or toxic behavior
* **Fair play only** - No cheating, hacking, or exploiting
* **English in global chat** - Use English for server-wide communication

## Getting Help

### In-Game Commands
* \`/help\` - View available commands
* \`/rules\` - Display server rules
* \`/spawn\` - Return to spawn area (if available)
* \`/staff\` - Contact online staff members

### Community Support
* **Discord Server:** [discord.gg/azox](https://discord.gg/azox)
* **Website:** Browse our guides and tutorials
* **Email Support:** support@azox.net for technical issues

### New Player Tips
* **Start small** - Don't attempt major builds immediately
* **Make allies** - Form alliances for protection and resources
* **Learn the map** - Familiarize yourself with key locations
* **Practice PvP** - Hone your combat skills in safe areas

---

**Ready to face your fate?** The Azox Network awaits. Remember: in this world, only the strongest survive, and retreat is not an option.

**Good luck, warrior. May your blade stay sharp and your resolve unbroken.**

*Last Updated: November 26, 2025*`
      }
    ];

    // Simple markdown parser for basic formatting
    function parseMarkdown(text) {
      // Split into lines for better processing
      const lines = text.split('\n');
      const result = [];
      let inList = false;
      
      for (let i = 0; i < lines.length; i++) {
        let line = lines[i];
        
        // Skip empty lines
        if (line.trim() === '') {
          if (inList) {
            result.push('</ul>');
            inList = false;
          }
          result.push('');
          continue;
        }
        
        // Headers
        if (line.startsWith('### ')) {
          if (inList) { result.push('</ul>'); inList = false; }
          result.push(`<h3>${line.substring(4)}</h3>`);
        } else if (line.startsWith('## ')) {
          if (inList) { result.push('</ul>'); inList = false; }
          result.push(`<h2>${line.substring(3)}</h2>`);
        } else if (line.startsWith('# ')) {
          if (inList) { result.push('</ul>'); inList = false; }
          result.push(`<h1>${line.substring(2)}</h1>`);
        }
        // Horizontal rule
        else if (line.trim() === '---') {
          if (inList) { result.push('</ul>'); inList = false; }
          result.push('<hr>');
        }
        // List items
        else if (line.startsWith('* ')) {
          if (!inList) {
            result.push('<ul>');
            inList = true;
          }
          result.push(`<li>${line.substring(2)}</li>`);
        }
        // Numbered list items
        else if (/^\d+\.\s/.test(line)) {
          if (inList) { result.push('</ul>'); inList = false; }
          if (!result[result.length - 1]?.startsWith('<ol>')) {
            result.push('<ol>');
          }
          result.push(`<li>${line.replace(/^\d+\.\s/, '')}</li>`);
        }
        // Regular paragraphs
        else {
          if (inList) { result.push('</ul>'); inList = false; }
          if (result[result.length - 1]?.startsWith('<ol>')) {
            result.push('</ol>');
          }
          result.push(`<p>${line}</p>`);
        }
      }
      
      // Close any open list
      if (inList) {
        result.push('</ul>');
      }
      if (result[result.length - 1]?.startsWith('<ol>')) {
        result.push('</ol>');
      }
      
      return result.join('\n')
        // Apply inline formatting
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        // Convert markdown links to HTML
        .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
        // Clean up empty paragraphs
        .replace(/<p><\/p>/g, '')
        .replace(/<p>\s*<\/p>/g, '');
    }

    // Load and display play now guide
    function loadPlayNow() {
      const playNowGrid = document.getElementById('playNowGrid');
      playNowGrid.innerHTML = '';

      playNowData.forEach(guide => {
        const htmlContent = parseMarkdown(guide.content);

        const guideElement = document.createElement('article');
        guideElement.className = 'news-article';
        guideElement.innerHTML = `
          <div class="news-meta">
            <span class="news-date">${guide.date}</span>
            <span class="news-category">${guide.category}</span>
          </div>
          <h2>${guide.title}</h2>
          <div class="news-content">${htmlContent}</div>
        `;

        playNowGrid.appendChild(guideElement);
      });
    }

    // Load play now guide when page loads
    document.addEventListener('DOMContentLoaded', loadPlayNow);

    // Hamburger menu
    (function(){
      const hamburger = document.getElementById('hamburger');
      const mobileMenu = document.getElementById('mobileMenu');
      if(!hamburger || !mobileMenu) return;
      
      hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        mobileMenu.classList.toggle('active');
      });

      // Close menu when clicking on a link
      mobileMenu.addEventListener('click', (e) => {
        if(e.target.tagName === 'A') {
          hamburger.classList.remove('active');
          mobileMenu.classList.remove('active');
        }
      });

      // Close menu when clicking outside
      document.addEventListener('click', (e) => {
        if(!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) {
          hamburger.classList.remove('active');
          mobileMenu.classList.remove('active');
        }
      });
    })();
  </script>
</body>
</html>