<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Rules — Azox — Trial by Fate</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <!-- Rules Section -->
  <main class="news-container">
    <div class="news-header">
      <div class="eyebrow"><span class="dot"></span>Server Guidelines</div>
      <h1 class="news-title">Server Rules</h1>
      <p class="news-subtitle">Essential rules and guidelines for fair play and community standards on the Azox Network.</p>
    </div>

    <div class="news-grid" id="rulesGrid">
      <div class="loading">Loading server rules...</div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-inner">
      <p>&copy; 2025 Azox Network</p>
    </div>
  </footer>

  <script>
    // Rules data (embedded to avoid CORS issues with local files)
    const rulesData = [
      {
        filename: 'rules.md',
        date: 'November 26, 2025',
        title: 'Azox Network Server Rules',
        category: 'Server Guidelines',
        content: `Welcome to the Azox Network! To ensure a fair and enjoyable experience for all players, please read and follow these rules carefully. Violations may result in warnings, temporary bans, or permanent removal from the server.

## General Conduct

### 1. Respect All Players
* Treat all players with respect and courtesy
* No harassment, bullying, or discrimination of any kind
* Keep chat appropriate and family-friendly
* No excessive profanity or offensive language

### 2. No Cheating or Exploiting
* Absolutely no hacking, cheating, or use of unauthorized modifications
* No exploiting game bugs or glitches for personal advantage
* No duplication of items or currency
* Report any discovered exploits to staff immediately

### 3. Fair Play
* No griefing or intentional destruction of other players' builds
* Respect faction territories and boundaries
* No spawn camping or excessive targeting of new players
* Play within the spirit of the game

## PvP Rules

### 4. Combat Guidelines
* PvP is always enabled - be prepared for combat
* No retreat once combat has begun in designated areas
* Minimum gear score requirements apply for tournaments
* No teaming in solo events unless explicitly allowed

### 5. Faction Warfare
* Faction wars must be declared through proper channels
* Respect ceasefire agreements and truces
* No inside raiding or betrayal without proper roleplay justification
* Alliance changes must be announced publicly

## Economy & Trading

### 6. Trading Standards
* No scamming or fraudulent trades
* Honor all agreed-upon deals and contracts
* No market manipulation through artificial scarcity
* Report suspicious trading activity to staff

### 7. Resource Management
* No hoarding of essential resources to grief other players
* Respect mining claims and established territories
* No stealing from faction storage without permission
* Share community resources fairly

## Communication

### 8. Chat Rules
* English only in global chat
* Use appropriate channels for different topics
* No spamming, advertising, or excessive caps
* No sharing personal information or doxxing

### 9. Discord Integration
* Follow Discord server rules when linked accounts are active
* No ban evasion through alternate accounts
* Maintain consistent identity across platforms

## Consequences

### Warning System
* **First Offense:** Warning and explanation of rules
* **Second Offense:** Temporary ban (24-48 hours)
* **Third Offense:** Extended ban (1-7 days)
* **Severe Violations:** Immediate permanent ban

### Appeal Process
* Appeals can be submitted through Discord or website
* Include evidence and explanation of circumstances
* Staff decisions are final after review process
* False appeals may result in extended penalties

## Staff Guidelines

### 10. Staff Interaction
* Respect staff decisions and authority
* Report rule violations through proper channels
* No arguing with staff in public chat
* Use ticket system for disputes or appeals

### 11. Staff Conduct
* Staff members are held to higher standards
* No abuse of powers or favoritism
* Transparent decision-making process
* Regular review of staff actions

## Special Events

### 12. Tournament Rules
* Registration required before deadlines
* No outside assistance during solo events
* Follow specific event rules as announced
* Prizes are non-transferable unless stated otherwise

### 13. Community Events
* Participation is voluntary but encouraged
* Follow event-specific guidelines
* Respect event organizers and participants
* Have fun and maintain sportsmanship

---

**Remember:** These rules are subject to change. Players are responsible for staying updated on current rules. Ignorance of rules is not an excuse for violations.

**Questions?** Contact staff through Discord or use \`/help\` in-game.

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
        // Regular paragraphs
        else {
          if (inList) { result.push('</ul>'); inList = false; }
          result.push(`<p>${line}</p>`);
        }
      }
      
      // Close any open list
      if (inList) {
        result.push('</ul>');
      }
      
      return result.join('\n')
        // Apply inline formatting
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        // Clean up empty paragraphs
        .replace(/<p><\/p>/g, '')
        .replace(/<p>\s*<\/p>/g, '');
    }

    // Load and display rules
    function loadRules() {
      const rulesGrid = document.getElementById('rulesGrid');
      rulesGrid.innerHTML = '';

      rulesData.forEach(rule => {
        const htmlContent = parseMarkdown(rule.content);

        const ruleElement = document.createElement('article');
        ruleElement.className = 'news-article';
        ruleElement.innerHTML = `
          <div class="news-meta">
            <span class="news-date">${rule.date}</span>
            <span class="news-category">${rule.category}</span>
          </div>
          <h2>${rule.title}</h2>
          <div class="news-content">${htmlContent}</div>
        `;

        rulesGrid.appendChild(ruleElement);
      });
    }

    // Load rules when page loads
    document.addEventListener('DOMContentLoaded', loadRules);

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