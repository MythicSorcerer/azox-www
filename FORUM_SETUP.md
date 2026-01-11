# Azox Network Forum & Messaging System Setup Guide

This guide will help you set up the complete forum and messaging system for the Azox Network website.

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- mod_rewrite enabled (for clean URLs)

### Installation Steps

#### 1. Database Setup
```sql
-- Create the database
CREATE DATABASE azox_network;
USE azox_network;

-- Import the schema
SOURCE config/database.sql;
```

#### 2. Configure Database Connection
Edit `config/database.php` and update these settings:
```php
define('DB_HOST', 'localhost');     // Your MySQL host
define('DB_NAME', 'azox_network');  // Database name
define('DB_USER', 'your_username'); // MySQL username
define('DB_PASS', 'your_password'); // MySQL password
```

#### 3. Set Permissions
```bash
# Make logs directory writable
chmod 755 logs/
chmod 644 logs/activity.log

# Ensure PHP can write to session directory
chmod 755 /tmp
```

#### 4. Test Installation
1. Navigate to `/auth/register.php`
2. Create a test account
3. Login and test the forum functionality
4. Access `/admin/dashboard.php` (admin account required)

## 🏗️ System Architecture

### File Structure
```
/
├── config/
│   ├── database.sql          # Database schema
│   ├── database.php          # Database connection & helpers
│   └── auth.php              # Authentication system
├── includes/
│   └── nav.php               # Dynamic navigation component
├── auth/
│   ├── login.php             # Login form
│   ├── register.php          # Registration form
│   └── logout.php            # Logout handler
├── forum/
│   └── index.php             # Forum categories
├── thread/
│   └── index.php             # Thread view & replies
├── messages/
│   ├── index.php             # Chat interface
│   └── api.php               # Messaging API
├── notifications/
│   └── index.php             # Notifications page
├── admin/
│   └── dashboard.php         # Admin panel
└── style.css                 # All styles (updated)
```

### Database Tables
- `users` - User accounts and authentication
- `forum_categories` - Forum category structure
- `forum_threads` - Discussion threads
- `forum_posts` - Individual posts/replies
- `messages` - Chat messages
- `notifications` - User notifications
- `user_sessions` - Session management

## 🔧 Features Implemented

### ✅ Authentication System
- **User Registration**: Full validation with password hashing
- **Login/Logout**: Secure session management
- **Role-based Access**: User and Admin roles
- **Session Security**: Token-based validation with expiration

### ✅ Forum System
- **Categories**: Organized discussion areas
- **Threads**: Topic-based discussions
- **Posts**: Threaded replies with pagination
- **Moderation**: Admin controls (expandable)

### ✅ Real-time Messaging
- **Multi-channel Chat**: General, PvP, Trading, Help channels
- **Live Updates**: AJAX polling for real-time feel
- **Online Status**: Active user tracking
- **Rate Limiting**: Spam prevention

### ✅ Notifications
- **Forum Notifications**: Reply alerts and mentions
- **Real-time Badges**: Unread count in navigation
- **Notification Center**: Centralized notification management

### ✅ Admin Dashboard
- **System Statistics**: User counts, activity metrics
- **Recent Activity**: Monitor users, threads, messages
- **Quick Actions**: Administrative tools (expandable)

### ✅ Dynamic Navigation
- **Login States**: Different nav for logged in/out users
- **Admin Access**: Special admin navigation items
- **Notification Bell**: Live unread count display
- **Mobile Responsive**: Hamburger menu integration

## 🎨 Styling & Design

All styles are integrated into the main `style.css` file:
- **Authentication Forms**: Login/register styling
- **Forum Layout**: Categories, threads, posts
- **Chat Interface**: Slack-style messaging
- **Admin Dashboard**: Statistics and management
- **Responsive Design**: Mobile-first approach

## 🔐 Security Features

### Authentication Security
- **Password Hashing**: bcrypt with salt
- **CSRF Protection**: Token validation on forms
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization
- **Session Security**: Secure token management

### Rate Limiting
- **Message Limits**: 10 messages per minute per user
- **Login Attempts**: Built-in protection
- **Session Validation**: Automatic cleanup

## 🚀 Usage Guide

### For Users
1. **Register**: Create account at `/auth/register.php`
2. **Login**: Access at `/auth/login.php`
3. **Forum**: Browse categories at `/forum/`
4. **Chat**: Real-time messaging at `/messages/`
5. **Notifications**: Check alerts at `/notifications/`

### For Admins
1. **Dashboard**: Access at `/admin/dashboard.php`
2. **User Management**: Monitor user activity
3. **Content Moderation**: Review threads and messages
4. **System Monitoring**: Track statistics and performance

## 🔧 Customization

### Adding New Forum Categories
```sql
INSERT INTO forum_categories (name, description, sort_order) 
VALUES ('New Category', 'Description here', 10);
```

### Adding New Chat Channels
Edit `messages/index.php` and `messages/api.php`:
```php
$channels = [
    'general' => 'General Chat',
    'pvp' => 'PvP Discussion',
    'trading' => 'Trading',
    'help' => 'Help & Support',
    'new_channel' => 'New Channel Name'  // Add here
];
```

### Customizing Styles
All styles are in `style.css`. Key sections:
- **Authentication**: Lines 864-1050
- **Forum**: Lines 1051-1300
- **Messaging**: Lines 1301-1500
- **Admin**: Inline styles in dashboard

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check MySQL credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists and user has permissions

**Session Issues**
- Check PHP session configuration
- Ensure `/tmp` is writable
- Clear browser cookies

**Permission Errors**
- Set proper file permissions (755 for directories, 644 for files)
- Ensure web server can write to logs directory

**Chat Not Loading**
- Check browser console for JavaScript errors
- Verify API endpoints are accessible
- Check database connection

### Debug Mode
Enable logging in `config/database.php`:
```php
// Check logs/activity.log for detailed information
logActivity("Debug message", 'info');
```

## 🔄 Future Enhancements

### Planned Features
- **WebSocket Integration**: True real-time messaging
- **File Uploads**: Image and file sharing
- **Advanced Moderation**: Automated spam detection
- **User Profiles**: Extended user information
- **Private Messages**: Direct user messaging
- **Forum Search**: Full-text search capability
- **Mobile App**: API for mobile applications

### Performance Optimizations
- **Database Indexing**: Optimize query performance
- **Caching**: Redis/Memcached integration
- **CDN Integration**: Static asset delivery
- **Image Optimization**: Automatic image processing

## 📞 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review logs in `logs/activity.log`
3. Contact the development team
4. Submit issues via the project repository

---

**Azox Network Forum & Messaging System v1.0**  
*Built with PHP, MySQL, and modern web standards*