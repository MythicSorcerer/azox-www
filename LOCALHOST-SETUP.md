# Azox Network - Localhost Development Setup

## ✅ Setup Complete!

The Azox Network website has been successfully configured for localhost development. All core functionality is working correctly.

## 🚀 Quick Start

1. **Start the development server:**
   ```bash
   php -S localhost:8000
   ```

2. **Access the website:**
   - Homepage: http://localhost:8000/
   - Forum: http://localhost:8000/forum/
   - Login: http://localhost:8000/auth/login.php
   - Admin Dashboard: http://localhost:8000/admin/dashboard.php

## 🔐 Test Accounts

### Admin Account
- **Username:** `admin`
- **Password:** `admin123`
- **Email:** `admin@azox.net`
- **Role:** Admin

### Test User Accounts
- **Username:** `testuser` | **Password:** `test123`
- **Username:** `joejoe` | **Password:** (existing user)
- **Username:** `testuser111` | **Password:** (existing user)

## 🗄️ Database Configuration

- **Database:** `azox_network`
- **Host:** `localhost`
- **User:** `root`
- **Password:** (empty)
- **Port:** `3306`

### Database Tables Created:
- `users` - User accounts and authentication
- `forum_categories` - Forum category structure
- `forum_threads` - Discussion threads
- `forum_posts` - Individual posts
- `messages` - Chat/messaging system
- `notifications` - User notifications
- `user_sessions` - Session management

## 📁 Key Files Configured

### Database Configuration
- [`config/database.php`](config/database.php) - Database connection settings
- [`config/database_simple.sql`](config/database_simple.sql) - Database schema

### Authentication System
- [`config/auth.php`](config/auth.php) - User authentication functions
- [`auth/login.php`](auth/login.php) - Login page
- [`auth/register.php`](auth/register.php) - Registration page

### Include Files
- [`includes/nav.php`](includes/nav.php) - Navigation component
- [`includes/content.php`](includes/content.php) - Content management
- [`includes/markdown.php`](includes/markdown.php) - Markdown parser

## 🔧 Development Features Enabled

- **Error Reporting:** Enabled for development debugging
- **Development Mode:** Active in database configuration
- **Session Management:** Working with database storage
- **CSRF Protection:** Implemented for forms
- **Password Hashing:** Secure bcrypt hashing

## 🌐 Available Pages

### Public Pages
- **Homepage** (`/index.php`) - Main landing page
- **News** (`/news/`) - News articles from markdown files
- **Events** (`/events/`) - Event listings
- **FAQ** (`/faq/`) - Frequently asked questions
- **Contact** (`/contact/`) - Contact information
- **Rules** (`/rules/`) - Server rules
- **Tools** (`/tools/`) - Utility tools
- **Map** (`/map/`) - Server map

### User Pages (Login Required)
- **Forum** (`/forum/`) - Community discussions
- **Messages** (`/messages/`) - Chat system
- **Notifications** (`/notifications/`) - User notifications
- **Settings** (`/settings/`) - User preferences

### Admin Pages (Admin Login Required)
- **Admin Dashboard** (`/admin/dashboard.php`) - Administrative interface

## 📊 Current Database Status

- **Users:** 4 registered users (including admin)
- **Forum Categories:** 8 unique categories (16 total with duplicates)
- **Forum Threads:** 2 existing threads
- **Forum Posts:** 6 total posts

## 🔍 Testing Completed

✅ PHP 8.4.13 compatibility verified  
✅ MySQL 9.5.0 connection working  
✅ Database schema imported successfully  
✅ All required PHP extensions available  
✅ Authentication system functional  
✅ Forum system operational  
✅ Navigation and includes working  
✅ Session management active  
✅ CSRF protection enabled  

## 🚨 Security Notes for Development

- **Admin password** should be changed from default `admin123`
- **Database credentials** are set for localhost development only
- **Error reporting** is enabled for debugging (disable in production)
- **Development mode** is active (disable in production)

## 🔄 Next Steps for Production

1. Update database credentials in [`config/database.php`](config/database.php)
2. Disable development mode and error reporting
3. Change default admin password
4. Configure proper web server (Apache/Nginx)
5. Set up SSL certificates
6. Configure proper file permissions
7. Remove or secure [`setup.php`](setup.php)

## 📝 Notes

- The forum has some duplicate categories that may need cleanup
- All core functionality is working correctly
- The website is ready for development and testing
- Database triggers for reply counts are functional

---

**Development Server Status:** ✅ Running on http://localhost:8000/  
**Last Updated:** November 27, 2025