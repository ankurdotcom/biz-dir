# Project Setup and Deployment Guide

## 🚀 Complete Setup Instructions for New Machines

This guide ensures the Business Directory WordPress plugin can be set up on any new machine without missing dependencies or configurations.

## 📋 Pre-Setup Checklist

### Required Software
- [ ] **PHP 8.0+** with required extensions
- [ ] **MySQL 5.7+** or **MariaDB 10.3+**
- [ ] **WordPress 6.0+**
- [ ] **Composer 2.0+**
- [ ] **Git** (latest version)
- [ ] **Apache/Nginx** web server
- [ ] **WP-CLI** (recommended)

### Required Access
- [ ] GitHub repository access
- [ ] Database server credentials
- [ ] Domain/hosting access
- [ ] External configuration files location

## 🔧 Step-by-Step Setup Process

### Step 1: System Dependencies
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.0 php8.0-mysqli php8.0-mbstring php8.0-xml \
                 php8.0-zip php8.0-curl php8.0-gd php8.0-intl \
                 mysql-server composer git

# CentOS/RHEL
sudo yum install php php-mysqli php-mbstring php-xml \
                 php-zip php-curl php-gd php-intl \
                 mysql-server composer git

# macOS (using Homebrew)
brew install php mysql composer git
```

### Step 2: WordPress Installation
```bash
# Download WordPress
wget https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz
sudo mv wordpress /var/www/html/biz-directory
sudo chown -R www-data:www-data /var/www/html/biz-directory
```

### Step 3: Database Setup
```bash
# Start MySQL service
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
mysql -u root -p << EOF
CREATE DATABASE biz_directory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'biz_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON biz_directory_db.* TO 'biz_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF
```

### Step 4: Clone and Configure Project
```bash
# Clone the repository
cd /var/www/html/biz-directory/wp-content/plugins/
git clone https://github.com/ankurdotcom/biz-dir.git business-directory
cd business-directory

# Install PHP dependencies
composer install

# Copy configuration files from external location
cp /path/to/external/configs/wp-config.php /var/www/html/biz-directory/
cp /path/to/external/configs/.env ./

# Set proper permissions
sudo chown -R www-data:www-data /var/www/html/biz-directory
sudo chmod 644 /var/www/html/biz-directory/wp-config.php
```

### Step 5: Initialize Database Schema
```bash
# Navigate to project directory
cd /var/www/html/biz-directory/wp-content/plugins/business-directory/mvp

# Import database schemas
mysql -u biz_user -p biz_directory_db < config/schema.sql
mysql -u biz_user -p biz_directory_db < config/analytics_schema.sql
mysql -u biz_user -p biz_directory_db < config/monetization_schema.sql
```

### Step 6: WordPress Configuration
```bash
# Install WP-CLI (if not already installed)
curl -O https://raw.githubusercontent.com/wp-cli/wp-cli/v2.8.1/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Configure WordPress
cd /var/www/html/biz-directory
wp core install --url="yourdomain.com" --title="Business Directory" \
                --admin_user="admin" --admin_password="secure_password" \
                --admin_email="admin@yourdomain.com"

# Activate the plugin
wp plugin activate business-directory
```

### Step 7: Testing and Validation
```bash
# Run plugin tests
cd /var/www/html/biz-directory/wp-content/plugins/business-directory/mvp
./run-tests.sh

# Run regression tests
./run-regression-tests.sh

# Check WordPress installation
wp core verify-checksums
wp plugin list
```

## 🔍 Validation Checklist

### Database Connectivity
- [ ] WordPress can connect to database
- [ ] All required tables are created
- [ ] User permissions are correct

### Plugin Functionality
- [ ] Plugin activates without errors
- [ ] All dependencies are installed
- [ ] Test suite passes completely

### Web Server Configuration
- [ ] Virtual host/server block configured
- [ ] URL rewriting enabled
- [ ] Proper file permissions set

### Security Configuration
- [ ] wp-config.php has secure keys
- [ ] Database credentials are secure
- [ ] File permissions are restrictive

## 🚦 Environment-Specific Setup

### Development Environment
```bash
# Enable debugging
wp config set WP_DEBUG true --type=constant
wp config set WP_DEBUG_LOG true --type=constant
wp config set WP_DEBUG_DISPLAY false --type=constant

# Install development tools
composer install --dev
```

### Production Environment
```bash
# Disable debugging
wp config set WP_DEBUG false --type=constant

# Install only production dependencies
composer install --no-dev --optimize-autoloader

# Enable caching
wp plugin install w3-total-cache --activate
```

## 📁 File Structure Verification

After setup, verify this structure exists:
```
/var/www/html/biz-directory/
├── wp-config.php                    # WordPress configuration
├── wp-content/
│   └── plugins/
│       └── business-directory/      # Our plugin
│           ├── mvp/                 # Main plugin code
│           ├── wiki/                # Documentation
│           ├── prompt/              # Project specifications
│           └── composer.json        # PHP dependencies
```

## 🔄 Backup and Recovery

### Backup Strategy
```bash
# Database backup
mysqldump -u biz_user -p biz_directory_db > backup_$(date +%Y%m%d).sql

# File backup
tar -czf plugin_backup_$(date +%Y%m%d).tar.gz /var/www/html/biz-directory/wp-content/plugins/business-directory/

# Configuration backup
cp -r /path/to/external/configs/ backup/configs_$(date +%Y%m%d)/
```

### Recovery Process
```bash
# Restore database
mysql -u biz_user -p biz_directory_db < backup_20250823.sql

# Restore files
tar -xzf plugin_backup_20250823.tar.gz -C /

# Restore configurations
cp -r backup/configs_20250823/* /path/to/external/configs/
```

## 🆘 Common Setup Issues

### Issue: Database Connection Failed
**Solution:**
```bash
# Check MySQL service
sudo systemctl status mysql

# Test connection
mysql -u biz_user -p -h localhost

# Verify wp-config.php credentials
wp config get DB_NAME
wp config get DB_USER
```

### Issue: Plugin Won't Activate
**Solution:**
```bash
# Check PHP errors
tail -f /var/log/apache2/error.log

# Verify dependencies
composer install
composer dump-autoload

# Check WordPress compatibility
wp core version
wp plugin list --status=must-use
```

### Issue: Tests Failing
**Solution:**
```bash
# Install test dependencies
cd mvp
./bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Clear test cache
rm -rf tests/logs/*
rm .phpunit.result.cache

# Run individual test suites
./vendor/bin/phpunit tests/Business/
./vendor/bin/phpunit tests/User/
```

## 📞 Support and Maintenance

### Regular Maintenance Schedule
- **Daily**: Monitor error logs
- **Weekly**: Run test suites
- **Monthly**: Update dependencies
- **Quarterly**: Security audit

### Support Contacts
- **Technical Lead**: tech@company.com
- **DevOps**: devops@company.com
- **Database Admin**: dba@company.com

---

## 📝 Document History

**Created**: August 23, 2025
**Last Updated**: August 23, 2025
**Version**: 1.0
**Next Review**: September 23, 2025

> **Note**: This document should be updated whenever setup procedures change.
> Always test setup instructions on a clean environment before updating this guide.
