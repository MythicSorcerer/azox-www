<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Contact — Azox — Trial by Fate</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <!-- Contact Section -->
  <main class="news-container">
    <div class="news-header">
      <div class="eyebrow"><span class="dot"></span>Get in Touch</div>
      <h1 class="news-title">Contact Us</h1>
      <p class="news-subtitle">Need help or want to connect with the community? Reach out through Discord or email for support and assistance.</p>
    </div>

    <div class="news-grid" id="contactGrid">
      <div class="loading">Loading contact information...</div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-inner">
      <p>&copy; 2025 Azox Network</p>
    </div>
  </footer>

  <script>
    // Contact data (embedded to avoid CORS issues with local files)
    const contactData = [
      {
        filename: 'contact.md',
        date: 'November 26, 2025',
        title: 'Contact Us',
        category: 'Support',
        content: `Need help, have questions, or want to connect with the Azox Network community? We're here to assist you! Choose the best way to reach us based on your needs.

## Discord Server

Join our active Discord community for real-time support, discussions, and updates!

**Discord Server:** [discord.gg/azox](https://discord.gg/azox)

### What you'll find on Discord:
* **#general-chat** - Community discussions and casual conversation
* **#support** - Get help from staff and experienced players
* **#announcements** - Important server updates and news
* **#events** - Tournament announcements and event coordination
* **#trading** - Buy, sell, and trade with other players
* **#faction-recruitment** - Find or advertise faction opportunities
* **#bug-reports** - Report issues and technical problems
* **#suggestions** - Share your ideas for server improvements

### Discord Benefits:
* **Instant Support** - Get help from staff and community members
* **Real-time Updates** - Server status, maintenance notifications, and breaking news
* **Community Events** - Participate in Discord-exclusive events and giveaways
* **Voice Channels** - Coordinate with faction members and friends
* **Bot Integration** - Server stats, player lookups, and useful commands

## Email Support

For formal inquiries, account issues, or detailed support requests, contact us via email.

**Support Email:** [support@azox.net](mailto:support@azox.net)

### When to use email:
* **Account Issues** - Login problems, password resets, account recovery
* **Ban Appeals** - Formal appeals with evidence and explanations
* **Technical Problems** - Detailed bug reports with screenshots/logs
* **Business Inquiries** - Partnership opportunities, sponsorships
* **Privacy Concerns** - Data requests, account deletion, privacy issues
* **Billing Questions** - Donation receipts, payment issues

### Email Response Times:
* **General Support:** 24-48 hours
* **Urgent Issues:** 12-24 hours
* **Ban Appeals:** 3-5 business days
* **Business Inquiries:** 1-2 business days

## Staff Team

Our dedicated staff team is here to help ensure a fair and enjoyable experience for all players.

### Staff Roles:
* **Administrators** - Server management and major decisions
* **Moderators** - Rule enforcement and community management
* **Support Team** - Player assistance and technical help
* **Event Coordinators** - Tournament organization and special events

### How to Contact Staff:
1. **Discord** - Use @Staff mention or create a support ticket
2. **In-Game** - Use \`/staff\` command or message staff members directly
3. **Email** - For formal issues requiring documentation

## Community Guidelines

When contacting us, please:

### Be Respectful
* Use polite and professional language
* Respect staff time and decisions
* Follow community guidelines in all interactions

### Be Specific
* Provide detailed descriptions of issues
* Include relevant screenshots or evidence
* Mention your in-game username and relevant details

### Be Patient
* Allow appropriate response time based on contact method
* Avoid spamming multiple channels with the same request
* Understand that complex issues may take time to resolve

## Frequently Asked Questions

### How do I report a player?
Use the \`/report <player> <reason>\` command in-game or contact staff on Discord with evidence.

### How do I appeal a ban?
Send a detailed email to support@azox.net with your username, ban reason, and explanation.

### How do I join the Discord server?
Click the invite link: [discord.gg/azox](https://discord.gg/azox) or ask for an invite in-game.

### How do I reset my password?
Contact support@azox.net with your username and registered email address.

### How do I report a bug?
Use the #bug-reports channel on Discord or email support@azox.net with detailed information.

---

**We're here to help!** Don't hesitate to reach out through Discord or email. Our community and staff are committed to making your Azox Network experience the best it can be.

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
        // Convert markdown links to HTML
        .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
        // Clean up empty paragraphs
        .replace(/<p><\/p>/g, '')
        .replace(/<p>\s*<\/p>/g, '');
    }

    // Load and display contact information
    function loadContact() {
      const contactGrid = document.getElementById('contactGrid');
      contactGrid.innerHTML = '';

      contactData.forEach(contact => {
        const htmlContent = parseMarkdown(contact.content);

        const contactElement = document.createElement('article');
        contactElement.className = 'news-article';
        contactElement.innerHTML = `
          <div class="news-meta">
            <span class="news-date">${contact.date}</span>
            <span class="news-category">${contact.category}</span>
          </div>
          <h2>${contact.title}</h2>
          <div class="news-content">${htmlContent}</div>
        `;

        contactGrid.appendChild(contactElement);
      });
    }

    // Load contact information when page loads
    document.addEventListener('DOMContentLoaded', loadContact);

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