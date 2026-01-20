# Attendance Management System - README

## 📋 Overview

A comprehensive web-based attendance management system for educational institutions and organizations to manage students, employees, attendance, payments, and finances.

## ✨ Features

### Core Modules
- 👥 **Student Management** - Add, edit, activate/deactivate students with QR codes
- 👔 **Employee Management** - Manage staff with attendance tracking
- 📊 **Attendance System** - Monthly calendar view with daily breakdown
- 💰 **Payment Tracking** - Student fees and employee salary management
- 📈 **Finance Overview** - Revenue, expenses, and financial reports
- 🎫 **Subscription Plans** - Organization subscription management
- 🔐 **Multi-role Access** - Admin and Organization user roles

### Advanced Features
- QR code generation for students/employees
- Monthly fee initialization
- Custom fee management
- Expense tracking with categories
- Advanced filtering and search
- Mobile-responsive design
- Modern UI with gradient cards
- Real-time statistics dashboards

## 🚀 Quick Start

### Requirements
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Modern web browser

### Installation

1. **Clone/Download the project**
```bash
git clone <repository-url>
cd attendanceAppPHP
```

2. **Configure Environment**
```bash
cp .env.example .env
nano .env  # Edit with your settings
```

3. **Import Database**
```sql
mysql -u root -p
CREATE DATABASE attendance_php;
USE attendance_php;
SOURCE database/schema.sql;
```

4. **Set Permissions**
```bash
chmod 755 -R .
chmod 775 uploads/ logs/
```

5. **Create Admin User**
```bash
php add_admin.php
```

6. **Access Application**
```
http://localhost/attendanceAppPHP
```

## 📁 Project Structure

```
attendanceAppPHP/
├── admin/              # Admin panel
├── org/                # Organization dashboard
├── assets/             # CSS, JS, images
│   └── css/
│       └── ux-improvements.css
├── uploads/            # User uploaded files
├── logs/               # Application logs
├── config.php          # Database & app configuration
├── functions.php       # Helper functions
├── .env.example        # Environment template
├── .htaccess          # Apache configuration
└── DEPLOYMENT.md      # Deployment guide
```

## 🔧 Configuration

### Environment Variables (.env)
```
APP_ENV=production
APP_DEBUG=false
DB_HOST=localhost
DB_NAME=attendance_php
DB_USER=your_user
DB_PASS=your_password
```

### Key Settings
- Timezone: `Asia/Kolkata` (configurable)
- Session timeout: 2 hours (configurable)
- Max upload: 5MB (configurable)
- Supported formats: JPG, PNG, PDF, DOC

## 👤 User Roles

### Admin
- Full system access
- Manage organizations
- View all data
- System configuration

### Organization
- Manage students/employees
- Track attendance
- Process payments
- View reports
- Manage subscriptions

## 🎨 UI/UX Features

- Modern gradient cards with hover effects
- Smooth scrolling and transitions
- Focus states for accessibility
- Responsive design (mobile-friendly)
- Icon-labeled inputs
- Loading states
- Toast notifications
- Custom scrollbar styling

## 📊 Statistics Dashboards

Every major page includes:
- Total counts
- Active/Inactive breakdown
- Financial summaries
- Monthly trends
- Visual charts

## 🔒 Security Features

- Password hashing (SHA-256)
- SQL injection prevention (prepared statements)
- XSS protection
- CSRF protection
- Session security
- File upload validation
- Input sanitization
- Secure headers

## 📱 Mobile Support

Fully responsive design works on:
- Desktop browsers
- Tablets
- Mobile phones
- Progressive Web App ready

## 🛠️ Maintenance

### Backups
```bash
# Linux/Mac
./backup.sh

# Windows
backup.bat
```

### Logs
```bash
tail -f logs/error.log
```

### Updates
1. Backup database and files
2. Pull latest changes
3. Run migrations (if any)
4. Clear cache

## 📖 Documentation

- [Deployment Guide](DEPLOYMENT.md) - Production deployment
- [API Documentation](API.md) - API endpoints (if applicable)
- [Database Schema](database/schema.sql) - Database structure

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Check `.env` credentials
- Verify MySQL is running
- Confirm database exists

**Upload Failures**
- Check folder permissions: `chmod 775 uploads/`
- Verify PHP upload limits
- Check disk space

**Session Issues**
- Clear browser cookies
- Check session directory permissions
- Verify session settings in `config.php`

## 📈 Performance Tips

1. Enable OpCache in `php.ini`
2. Use CDN for static assets
3. Enable Gzip compression
4. Optimize images before upload
5. Regular database optimization
6. Monitor slow queries

## 🔄 Version History

- v0.0.3 - Advance payment system
- v0.0.2 - Email integration
- v0.0.1 - Initial release

## 📝 License

[Your License Here]

## 👨‍💻 Support

For support:
- Check logs: `logs/error.log`
- Review documentation
- Contact administrator

## 🙏 Credits

Built with:
- PHP & MySQL
- Tailwind CSS
- Font Awesome
- DataTables
- Chart.js

---

**Note:** This is a production-ready system. Follow the deployment guide for proper setup.

Last Updated: January 2026
