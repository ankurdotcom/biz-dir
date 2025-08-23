# Configuration Management Guide

## Overview
This document provides comprehensive guidance for managing configurations, credentials, and environment setup for the Business Directory WordPress Plugin project.

## 🔐 Security-First Configuration Management

### External Configuration Files (DO NOT COMMIT)
Keep these files OUTSIDE the project repository for security:

```
/home/ankur/biz-dir-configs/
├── production/
│   ├── wp-config.php          # Production database credentials
│   ├── .env                   # Environment variables
│   └── ssl-certificates/      # SSL certificates
├── staging/
│   ├── wp-config.php          # Staging database credentials
│   └── .env                   # Staging environment variables
├── development/
│   ├── wp-config.php          # Local development credentials
│   └── .env                   # Development environment variables
└── documentation/
    ├── server-setup.md        # Server configuration instructions
    ├── dns-settings.md        # DNS and domain configuration
    └── third-party-apis.md    # API keys and external service configs
```

### Required Configuration Files

#### 1. External Test Results Directory
**CRITICAL**: Test results, logs, and coverage reports are stored outside the repository for security and performance.

```bash
# External test results structure
/home/ankur/biz-dir-test-results/
├── logs/                 # Test execution logs
├── results/              # Test result files (HTML, XML, JSON)  
├── coverage/             # Code coverage reports
├── artifacts/            # Test artifacts and temporary files
├── reports/              # Generated test reports
└── archives/             # Archived old test runs

# Setup command
./setup-external-test-results.sh
```

#### 2. Database Configuration (`wp-config.php`)
```php
<?php
// Database settings
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASSWORD', 'your_secure_password');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// Security keys (generate at https://api.wordpress.org/secret-key/1.1/salt/)
define('AUTH_KEY',         'your-unique-auth-key');
define('SECURE_AUTH_KEY',  'your-unique-secure-auth-key');
define('LOGGED_IN_KEY',    'your-unique-logged-in-key');
define('NONCE_KEY',        'your-unique-nonce-key');
define('AUTH_SALT',        'your-unique-auth-salt');
define('SECURE_AUTH_SALT', 'your-unique-secure-auth-salt');
define('LOGGED_IN_SALT',   'your-unique-logged-in-salt');
define('NONCE_SALT',       'your-unique-nonce-salt');

// WordPress debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Table prefix
$table_prefix = 'wp_bizdir_';
```

#### 2. Environment Variables (`.env`)
```bash
# Database Configuration
DB_NAME=biz_directory_db
DB_USER=biz_user
DB_PASSWORD=your_secure_password_here
DB_HOST=localhost
DB_PORT=3306

# WordPress Configuration
WP_HOME=https://yourdomain.com
WP_SITEURL=https://yourdomain.com
WP_DEBUG=true

# Plugin Configuration
BIZDIR_API_KEY=your_business_api_key
BIZDIR_GOOGLE_MAPS_API=your_google_maps_api_key
BIZDIR_ANALYTICS_ID=your_analytics_id

# Email Configuration
SMTP_HOST=your_smtp_host
SMTP_PORT=587
SMTP_USER=your_email@domain.com
SMTP_PASS=your_email_password

# Cache Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password
```

## 📋 Required Dependencies

### System Requirements
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.3+)
- **WordPress**: 6.0 or higher
- **Apache/Nginx**: Latest stable version
- **Composer**: 2.0 or higher
- **Node.js**: 16.0 or higher (for build tools)
- **Git**: Latest version

### PHP Extensions
```bash
# Required PHP extensions
php-mysqli
php-mbstring
php-xml
php-zip
php-curl
php-gd
php-intl
php-json
php-openssl
```

### WordPress Dependencies
- WordPress core 6.0+
- MySQL database with appropriate user permissions
- URL rewriting enabled (mod_rewrite for Apache)

## 🛠 Project Setup Instructions

### 1. Initial Environment Setup
```bash
# Clone the repository
git clone https://github.com/ankurdotcom/biz-dir.git
cd biz-dir

# Install PHP dependencies
composer install

# Set up WordPress test environment
cd mvp
./bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run initial tests
./run-tests.sh
```

### 2. Database Setup
```sql
-- Create database
CREATE DATABASE biz_directory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user with appropriate privileges
CREATE USER 'biz_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON biz_directory_db.* TO 'biz_user'@'localhost';
FLUSH PRIVILEGES;

-- Import schema
USE biz_directory_db;
SOURCE config/schema.sql;
SOURCE config/analytics_schema.sql;
SOURCE config/monetization_schema.sql;
```

### 3. WordPress Configuration
```bash
# Copy wp-config template
cp /path/to/external/configs/development/wp-config.php /path/to/wordpress/wp-config.php

# Set proper permissions
chmod 644 wp-config.php
chown www-data:www-data wp-config.php
```

### 4. Plugin Activation
```bash
# Activate the plugin through WP-CLI
wp plugin activate business-directory

# Or through WordPress admin:
# Dashboard > Plugins > Business Directory > Activate
```

## 🔄 Environment-Specific Configurations

### Development Environment
- Enable WP_DEBUG
- Use local database
- Disable caching
- Enable error logging
- Use development API keys

### Staging Environment
- Limited debugging
- Staging database
- Enable caching with short TTL
- Use staging API keys
- Monitor performance

### Production Environment
- Disable debugging
- Production database with backups
- Enable full caching
- Use production API keys
- Enable security monitoring

## 🔧 Configuration Templates

### Apache Virtual Host
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/biz-directory
    
    <Directory /var/www/html/biz-directory>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/bizdir_error.log
    CustomLog ${APACHE_LOG_DIR}/bizdir_access.log combined
</VirtualHost>
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/biz-directory;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 📊 Monitoring and Maintenance

### Log Files to Monitor
- `/var/log/apache2/bizdir_error.log`
- `/var/log/nginx/error.log`
- `/wp-content/debug.log`
- `/mvp/tests/logs/`

### Regular Maintenance Tasks
- Update WordPress core monthly
- Update plugins and themes
- Run security scans
- Backup database weekly
- Monitor plugin performance
- Update knowledge trackers

## 🆘 Troubleshooting Checklist

### Common Issues
1. **Database Connection Errors**
   - Check wp-config.php credentials
   - Verify database server status
   - Test database connectivity

2. **Plugin Activation Failures**
   - Check PHP error logs
   - Verify all dependencies installed
   - Run composer install

3. **Performance Issues**
   - Enable query debugging
   - Check cache configuration
   - Review slow query logs

### Emergency Contacts
- **Database Admin**: admin@company.com
- **Server Admin**: sysadmin@company.com
- **Plugin Developer**: dev@company.com

---

## 📝 Document Maintenance

**Last Updated**: August 23, 2025
**Next Review**: September 23, 2025
**Maintained By**: Development Team

> **Important**: Keep this document updated with any configuration changes. 
> Update the knowledge trackers after any major configuration modifications.
