# Deployment Guide - Attendance Management System

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- SSL certificate (recommended for production)

## Deployment Steps

### 1. Server Setup

```bash
# Upload all files to your web server
# Recommended: /var/www/html/attendance or /public_html/
```

### 2. Environment Configuration

```bash
# Copy .env.example to .env
cp .env.example .env

# Edit .env with your production settings
nano .env
```

**Important .env settings:**
```
APP_ENV=production
APP_DEBUG=false
DB_HOST=your-database-host
DB_USER=your-database-user
DB_PASS=your-secure-password
DB_NAME=attendance_php
```

### 3. Database Setup

```bash
# Import database schema
mysql -u your_user -p attendance_php < database/schema.sql

# Or use phpMyAdmin to import the schema
```

### 4. File Permissions

```bash
# Set proper permissions
chmod 755 -R .
chmod 644 .env
chmod 755 uploads/
chmod 755 logs/
chmod 644 config.php

# Make uploads and logs writable
chmod 775 uploads/
chmod 775 logs/
chown -R www-data:www-data uploads/
chown -R www-data:www-data logs/
```

### 5. Apache Configuration

Ensure `.htaccess` is enabled in your Apache configuration:

```apache
<Directory /var/www/html/attendance>
    AllowOverride All
    Require all granted
</Directory>
```

For **Nginx**, use this configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/attendance;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # Deny access to sensitive files
    location ~ /\.(env|htaccess|git) {
        deny all;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

### 6. SSL Certificate (Recommended)

```bash
# Using Let's Encrypt (free)
sudo certbot --apache -d yourdomain.com
# or for Nginx
sudo certbot --nginx -d yourdomain.com
```

### 7. Security Checklist

- [ ] Change default admin credentials
- [ ] Enable HTTPS/SSL
- [ ] Set APP_ENV=production in .env
- [ ] Set APP_DEBUG=false in .env
- [ ] Use strong database passwords
- [ ] Restrict database access to localhost
- [ ] Enable firewall (UFW/iptables)
- [ ] Regular backups configured
- [ ] File upload limits configured
- [ ] Session security enabled

### 8. Performance Optimization

```bash
# Enable OpCache (php.ini)
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000

# Enable compression
sudo a2enmod deflate
sudo a2enmod expires
sudo a2enmod headers

# Restart Apache
sudo systemctl restart apache2
```

### 9. Backup Strategy

```bash
# Database backup
mysqldump -u user -p attendance_php > backup_$(date +%Y%m%d).sql

# File backup
tar -czf backup_files_$(date +%Y%m%d).tar.gz uploads/ .env

# Automate with cron (daily at 2 AM)
0 2 * * * /path/to/backup_script.sh
```

### 10. Monitoring

- Check logs regularly: `tail -f logs/error.log`
- Monitor disk space for uploads
- Set up uptime monitoring
- Monitor database performance

## Post-Deployment

1. Test all features:
   - Login (admin, organization)
   - Student/Employee management
   - Attendance tracking
   - Payment processing
   - QR code generation
   - File uploads

2. Create first admin user:
```bash
php add_admin.php
```

3. Create test organization and verify all functionality

## Troubleshooting

### "Database connection error"
- Check .env database credentials
- Verify MySQL is running: `sudo systemctl status mysql`
- Check database exists: `mysql -u root -p -e "SHOW DATABASES;"`

### "Permission denied" errors
- Check file permissions: `ls -la`
- Set proper ownership: `chown -R www-data:www-data .`

### Upload errors
- Check PHP upload limits in php.ini
- Verify uploads/ directory permissions
- Check .htaccess upload settings

### Session issues
- Clear browser cache/cookies
- Check session directory permissions
- Verify session settings in php.ini

## Maintenance

### Regular Tasks
- Weekly: Check error logs
- Monthly: Update dependencies (if using Composer)
- Quarterly: Review and optimize database
- Annually: Renew SSL certificates

### Updates
```bash
# Backup before updating
./backup.sh

# Pull latest changes (if using Git)
git pull origin main

# Clear cache if needed
rm -rf cache/*
```

## Support

For issues or questions:
- Check logs: `logs/error.log`
- Review this deployment guide
- Contact system administrator

## Security Notes

⚠️ **Never commit .env file to version control**
⚠️ **Change default passwords immediately**
⚠️ **Keep PHP and MySQL updated**
⚠️ **Regular security audits recommended**
⚠️ **Monitor for suspicious activity**

---
Last Updated: January 2026
