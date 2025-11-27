# Azox Network Website & Community Platform

A comprehensive web platform for the Azox Network Minecraft server featuring a modern forum system, real-time messaging, user authentication, and administrative tools. Built with PHP, MySQL, and modern web technologies.

## 🎮 About Azox Network

Azox Network is a hardcore PvP Minecraft server running **Season III: Trial by Fate** - an always-on PvP survival experience where retreat is not an option and only the strongest survive.

**Server Details:**
- **IP:** `azox.net`
- **Version:** Java Edition 1.20.x
- **Mode:** Survival (Hard Mode)
- **PvP:** Always Enabled
- **Max Players:** 500

## 🌟 Platform Features

### 🎨 Design & User Experience
- **Dark Theme:** Professional dark design with crimson (#DC143C) accent colors
- **Responsive Design:** Fully responsive across all device sizes
- **Mobile Navigation:** Hamburger menu for mobile devices (≤820px)
- **Hover-Based UI:** Clean interface with hover-revealed action buttons
- **Smooth Animations:** CSS transitions and hover effects throughout
- **Accessibility:** ARIA labels and keyboard navigation support

### 🔐 User Authentication System
- **Secure Registration:** Password hashing with PHP's `password_hash()`
- **Session Management:** Secure PHP sessions with activity tracking
- **Role-Based Access:** User and admin role system
- **Ban System:** Comprehensive user banning with enforcement across all features
- **Login State Persistence:** Remember user sessions across visits

### 💬 Forum System
- **Multi-Category Forums:** Organized discussion categories
- **Thread Management:** Create, view, and manage discussion threads
- **Post System:** Reply to threads with rich text content
- **User Permissions:** Thread creators and admins can delete content
- **Smart Filtering:** Hide threads with no visible posts
- **Thread Statistics:** View counts, reply counts, and activity tracking
- **Pagination:** Efficient pagination for large discussions

### 📨 Real-Time Messaging
- **Multi-Channel Chat:** General, PvP, Trading, and Help channels
- **Live Updates:** Real-time message polling every 2 seconds
- **User Presence:** Online user tracking and status indicators
- **Message Management:** Users can delete their own messages
- **Admin Moderation:** Admins can moderate all messages
- **Typing Indicators:** Real-time typing status (framework ready)
- **Message Formatting:** Support for basic markdown formatting

### 🛡️ Administrative Tools
- **Admin Dashboard:** Comprehensive moderation interface
- **User Management:** Ban/unban users with instant enforcement
- **Content Moderation:** Delete threads, posts, and messages
- **Activity Monitoring:** Track user activity and online status
- **Bulk Actions:** Efficient moderation tools with confirmation dialogs

### 📱 Mobile-First Navigation
- **Hamburger Menu:** Animated 3-line icon that transforms to X when active
- **Mobile Dropdown:** Backdrop blur menu with smooth animations
- **Auto-Close:** Menu closes when clicking links or outside the menu area
- **Touch-Friendly:** Optimized for mobile interaction

### 📄 Content Management
- **Dynamic News:** Markdown-based news system with automatic parsing
- **Event System:** Tournament announcements and community events
- **FAQ System:** Collapsible FAQ sections with search functionality
- **Static Pages:** Rules, contact, and play guides

### 🛠️ Tools & Utilities
- **Pomodoro Timer:** Focus timer with customizable work/break intervals
- **Claim System:** (In development) Web-based item claiming interface
- **Notification System:** Real-time notifications for forum activity

## 🏗️ Project Structure

```
azox-www/
├── index.html                    # Homepage
├── index.php                     # PHP homepage with auth
├── style.css                     # Main stylesheet (all CSS centralized)
├── README.md                     # This documentation
├── .gitignore                    # Git ignore rules
│
├── config/                       # Configuration & Database
│   ├── database.sql             # Complete MySQL schema
│   ├── database.php             # Database connection handler
│   ├── auth.php                 # Authentication system
│   └── update_messages_table.sql # Database updates
│
├── auth/                        # Authentication Pages
│   ├── login.php               # User login page
│   ├── register.php            # User registration page
│   └── logout.php              # Logout handler
│
├── forum/                       # Forum System
│   ├── index.php               # Forum categories listing
│   └── category.php            # Category thread listings
│
├── thread/                      # Thread System
│   └── index.php               # Thread view with posts
│
├── messages/                    # Real-Time Messaging
│   ├── index.php               # Chat interface
│   └── api.php                 # Chat API endpoints
│
├── admin/                       # Administrative Tools
│   ├── dashboard.php           # Admin control panel
│   └── actions.php             # Admin action handlers
│
├── api/                         # API Endpoints
│   └── user_actions.php        # User self-service actions
│
├── includes/                    # Shared Components
│   └── nav.php                 # Dynamic navigation component
│
├── notifications/               # Notification System
│   └── index.php               # Notifications page
│
├── faq/                         # FAQ System
│   └── index.php               # FAQ page with collapsible sections
│
├── contact/                     # Contact Information
│   ├── index.html              # Contact page
│   ├── index.php               # PHP contact page
│   └── contact.md              # Contact content
│
├── events/                      # Events System
│   ├── index.html              # Events page
│   ├── index.php               # PHP events page
│   └── *.md                    # Event markdown files
│
├── news/                        # News System
│   ├── index.html              # News page
│   ├── index.php               # PHP news page
│   └── *.md                    # News markdown files
│
├── play-now/                    # Connection Guide
│   ├── index.html              # Connection guide page
│   └── play-now.md             # Minecraft connection instructions
│
├── rules/                       # Server Rules
│   ├── index.html              # Rules page
│   ├── index.php               # PHP rules page
│   └── rules.md                # Server rules content
│
└── tools/                       # Utility Tools
    ├── index.html              # Tools overview
    ├── index.php               # PHP tools page
    ├── claim/
    │   └── index.html          # Item claiming tool (in development)
    └── timer/
        └── index.html          # Pomodoro focus timer
```

## 🚀 Getting Started

### Prerequisites
- **Web Server:** Apache, Nginx, or PHP built-in server
- **PHP:** Version 7.4 or higher with PDO MySQL extension
- **MySQL:** Version 5.7 or higher (or MariaDB equivalent)
- **Modern Browser:** Chrome, Firefox, Safari, Edge

### Installation

#### 1. Database Setup
```bash
# Install MySQL via Homebrew (macOS)
brew install mysql
brew services start mysql

# Or install via package manager (Linux)
sudo apt-get install mysql-server
sudo systemctl start mysql

# Create database
mysql -u root -p
CREATE DATABASE azox_network;
```

#### 2. Import Database Schema
```bash
# Import the complete schema
mysql -u root -p azox_network < config/database.sql

# Apply any updates if needed
mysql -u root -p azox_network < config/update_messages_table.sql
```

#### 3. Configure Database Connection
Edit `config/database.php` with your database credentials:
```php
$host = 'localhost';
$dbname = 'azox_network';
$username = 'your_username';
$password = 'your_password';
```

#### 4. Start Development Server
```bash
# Using PHP built-in server
php -S localhost:8080

# Or configure Apache/Nginx virtual host
```

#### 5. Create Admin User
1. Register a new account through `/auth/register.php`
2. Update the user role in database:
```sql
UPDATE users SET role = 'admin' WHERE username = 'your_username';
```

### Production Deployment
1. Upload all files to your web server
2. Configure database connection
3. Set proper file permissions (755 for directories, 644 for files)
4. Configure SSL certificate for HTTPS
5. Set up database backups and monitoring

## 🗄️ Database Schema

### Core Tables

#### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    is_banned BOOLEAN DEFAULT FALSE,
    banned_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Forum Categories
```sql
CREATE TABLE forum_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Forum Threads
```sql
CREATE TABLE forum_threads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    author_id INT NOT NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_locked BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    reply_count INT DEFAULT 0,
    last_post_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES forum_categories(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);
```

#### Forum Posts
```sql
CREATE TABLE forum_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    thread_id INT NOT NULL,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL,
    FOREIGN KEY (thread_id) REFERENCES forum_threads(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);
```

#### Messages (Chat)
```sql
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    channel VARCHAR(50) DEFAULT 'general',
    content TEXT NOT NULL,
    message_type ENUM('user', 'system') DEFAULT 'user',
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id)
);
```

#### Notifications
```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT NULL,
    related_type VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### User Sessions
```sql
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## 🔧 Technical Implementation

### Authentication System (`config/auth.php`)
```php
// Core authentication functions
function isLoggedIn()           // Check if user is logged in
function getCurrentUser()       // Get current user data
function requireLogin()         // Redirect if not logged in
function isAdmin()             // Check if user has admin role
function isBanned()            // Check if user is banned
function generateCSRFToken()   // Generate CSRF protection token
function verifyCSRFToken()     // Verify CSRF token
```

### Database Layer (`config/database.php`)
```php
// Database operations
function fetchAll($sql, $params)      // Fetch multiple rows
function fetchRow($sql, $params)      // Fetch single row
function fetchCount($sql, $params)    // Count rows
function executeQuery($sql, $params)  // Execute query
function insertAndGetId($sql, $params) // Insert and return ID
function beginTransaction()           // Start transaction
function commitTransaction()          // Commit transaction
function rollbackTransaction()        // Rollback transaction
```

### Forum System Features
- **Category Management:** Organized forum categories with descriptions
- **Thread Creation:** Users can create new discussion threads
- **Post Replies:** Threaded discussion system with replies
- **Moderation Tools:** Admin and user content management
- **Smart Filtering:** Hide threads with no visible posts
- **Pagination:** Efficient handling of large discussions
- **Statistics:** View counts, reply counts, activity tracking

### Real-Time Messaging Features
- **Multi-Channel Support:** General, PvP, Trading, Help channels
- **Live Updates:** 2-second polling for real-time experience
- **User Presence:** Online/away status indicators
- **Message Formatting:** Basic markdown support
- **Moderation:** User and admin message management
- **Activity Tracking:** User activity updates

### Administrative Features
- **User Management:** Ban/unban with instant enforcement
- **Content Moderation:** Delete threads, posts, messages
- **Bulk Actions:** Efficient moderation workflows
- **Activity Monitoring:** Track user engagement
- **Role Management:** User/admin role system

## 🎨 Design System

### Color Palette
```css
/* Core Colors */
--bg-0: #121214        /* Primary background */
--bg-1: #1a1c1f        /* Secondary background */
--bg-2: #202225        /* Tertiary background */
--text: #e8eaed        /* Primary text */
--text-dim: #b7bcc2    /* Secondary text */
--crimson: #DC143C     /* Primary accent */
--crimson-2: #880000   /* Dark crimson */
--crimson-3: #440000   /* Darkest crimson */

/* Metallic Accents */
--metal-hi: #d6d7da    /* Polished steel */
--metal-mid: #9aa0a6   /* Brushed steel */
--metal-low: #5f6368   /* Gunmetal */
```

### Typography
- **Primary Font:** System UI stack (ui-sans-serif, system-ui, -apple-system, etc.)
- **Monospace Font:** UI monospace stack for code and server IPs
- **Headings:** Bold weights with crimson accents
- **Body Text:** Clean, readable hierarchy

### Component System
- **Buttons:** Gradient crimson buttons with hover effects
- **Cards:** Semi-transparent backgrounds with backdrop blur
- **Navigation:** Sticky header with mobile hamburger menu
- **Forms:** Dark inputs with crimson focus states
- **Modals:** Backdrop blur with smooth animations
- **Alerts:** Color-coded success/error/info messages

### Hover-Based UI
- **Delete Buttons:** Hidden by default, appear on hover
- **Action Buttons:** Contextual appearance for cleaner interface
- **Smooth Transitions:** 0.2s ease animations throughout
- **Reduced Clutter:** Professional, clean appearance

## 📱 Responsive Design

### Breakpoints
```css
/* Mobile First Approach */
@media (max-width: 640px)  { /* Mobile */ }
@media (max-width: 820px)  { /* Tablet - Hamburger menu appears */ }
@media (max-width: 960px)  { /* Desktop - Layout changes */ }
```

### Mobile Optimizations
- **Touch-Friendly:** Larger tap targets for mobile
- **Readable Text:** Appropriate font sizes across devices
- **Efficient Layouts:** Grid and flexbox for responsive design
- **Fast Loading:** Optimized for mobile networks

## 🔒 Security Features

### Authentication Security
- **Password Hashing:** PHP `password_hash()` with bcrypt
- **Session Management:** Secure PHP sessions with regeneration
- **CSRF Protection:** Token-based CSRF prevention
- **Input Sanitization:** XSS prevention with `htmlspecialchars()`
- **SQL Injection Prevention:** PDO prepared statements

### User Protection
- **Ban System:** Comprehensive user banning with enforcement
- **Role-Based Access:** Proper permission checking
- **Content Ownership:** Users can only delete their own content
- **Admin Verification:** Admin actions require proper authentication

### Data Protection
- **Prepared Statements:** All database queries use PDO prepared statements
- **Input Validation:** Server-side validation for all user input
- **Output Encoding:** Proper encoding to prevent XSS
- **Error Handling:** Secure error messages without information disclosure

## 🚀 API Endpoints

### Authentication API
- `POST /auth/login.php` - User login
- `POST /auth/register.php` - User registration
- `GET /auth/logout.php` - User logout

### Forum API
- `GET /forum/` - List forum categories
- `GET /forum/category.php?id={id}` - List threads in category
- `POST /forum/category.php` - Create new thread
- `GET /thread/?id={id}` - View thread with posts
- `POST /thread/?id={id}` - Reply to thread

### Messaging API
- `GET /messages/api.php?action=get_messages&channel={channel}` - Get messages
- `POST /messages/api.php` - Send message
- `POST /messages/api.php` - Delete message
- `GET /messages/api.php?action=get_online_users` - Get online users

### Admin API
- `POST /admin/actions.php` - Admin actions (ban, delete, etc.)

### User API
- `POST /api/user_actions.php` - User self-service actions

## 🛠️ Development Guidelines

### Code Style
- **PHP:** PSR-12 coding standards with clear function names
- **HTML:** Semantic markup with proper accessibility
- **CSS:** BEM-inspired naming with logical organization
- **JavaScript:** ES6+ features with clear function names
- **Comments:** Comprehensive documentation in code

### File Organization
- **Logical Structure:** Clear directory hierarchy by feature
- **Consistent Naming:** Kebab-case for files and directories
- **Separation of Concerns:** HTML structure, CSS presentation, JS behavior
- **Modular Code:** Reusable components and functions

### Database Guidelines
- **Normalized Schema:** Proper relational database design
- **Foreign Keys:** Maintain referential integrity
- **Indexing:** Optimize query performance
- **Transactions:** Use transactions for multi-table operations

### Security Guidelines
- **Input Validation:** Validate all user input server-side
- **Output Encoding:** Encode all output to prevent XSS
- **Prepared Statements:** Use PDO prepared statements for all queries
- **Authentication:** Verify user permissions for all actions

## 🧪 Testing

### Manual Testing Checklist
- [ ] User registration and login flow
- [ ] Forum category and thread creation
- [ ] Post creation and deletion
- [ ] Real-time messaging functionality
- [ ] Admin moderation tools
- [ ] Mobile responsive design
- [ ] Cross-browser compatibility

### Database Testing
- [ ] User authentication with various inputs
- [ ] Forum operations with concurrent users
- [ ] Message sending and deletion
- [ ] Admin actions and permissions
- [ ] Ban system enforcement

### Security Testing
- [ ] SQL injection attempts
- [ ] XSS prevention
- [ ] CSRF protection
- [ ] Authentication bypass attempts
- [ ] Permission escalation tests

## 🚀 Future Enhancements

### Planned Features
- **Advanced Moderation:** More sophisticated moderation tools
- **User Profiles:** Extended user profile system
- **Private Messaging:** Direct user-to-user messaging
- **File Uploads:** Image and file sharing capabilities
- **Search System:** Full-text search across forum content
- **Email Notifications:** Email alerts for forum activity
- **Mobile App:** Native mobile application

### Technical Improvements
- **Caching System:** Redis or Memcached for performance
- **WebSocket Integration:** True real-time messaging
- **API Rate Limiting:** Prevent abuse and spam
- **Advanced Analytics:** User engagement tracking
- **CDN Integration:** Asset delivery optimization
- **Automated Testing:** Unit and integration tests

### Performance Optimizations
- **Database Optimization:** Query optimization and indexing
- **Asset Minification:** CSS and JavaScript compression
- **Image Optimization:** WebP format and lazy loading
- **Service Workers:** Offline functionality and caching

## 🤝 Contributing

### Development Setup
1. Fork the repository
2. Set up local development environment
3. Create a feature branch
4. Make your changes with proper testing
5. Submit a pull request with detailed description

### Code Standards
- Follow existing code style and organization
- Test responsive design on multiple screen sizes
- Ensure accessibility compliance
- Document any new features or changes
- Include security considerations in all changes

### Database Changes
- Create migration scripts for schema changes
- Test with existing data
- Document any breaking changes
- Provide rollback procedures

## 📞 Support

### Community
- **Discord:** [discord.gg/CbnmVUueXn](https://discord.gg/CbnmVUueXn)
- **Website:** Browse guides and tutorials
- **Email:** support@azox.net

### Technical Issues
- Check browser console for JavaScript errors
- Verify database connection and permissions
- Test on different browsers and devices
- Check PHP error logs for server-side issues
- Contact support with detailed error information

### Common Issues
- **Database Connection:** Verify credentials in `config/database.php`
- **Permission Errors:** Check file permissions (755/644)
- **Session Issues:** Verify PHP session configuration
- **MySQL Errors:** Check MySQL service status and logs

## 📄 License

This project is proprietary software for the Azox Network. All rights reserved.

## 🏷️ Version History

### v3.0.0 (November 2025) - Community Platform
- ✅ Complete forum system with categories, threads, and posts
- ✅ Real-time messaging with multi-channel support
- ✅ User authentication and session management
- ✅ Administrative tools and moderation system
- ✅ Ban system with comprehensive enforcement
- ✅ User self-service content management
- ✅ Hover-based UI for cleaner interface
- ✅ Mobile-responsive design throughout
- ✅ Comprehensive security implementation
- ✅ MySQL database with proper schema design

### v2.0.0 (November 2025) - Enhanced Website
- ✅ Responsive hamburger menu system
- ✅ Comprehensive Play Now connection guide
- ✅ Centralized CSS architecture
- ✅ Mobile-first responsive design
- ✅ Pomodoro productivity timer
- ✅ Enhanced accessibility features
- ✅ FAQ system with collapsible sections

### v1.0.0 (Initial Release) - Basic Website
- Basic website structure
- News and events system
- Server information pages
- Contact and rules sections

---

**Built with ❤️ for the Azox Network community**

*Last Updated: November 27, 2025*
