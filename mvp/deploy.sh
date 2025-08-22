#!/bin/bash

# BizDir Platform Deployment Script
# =================================

echo "🚀 BizDir Platform Deployment"
echo "============================="
echo ""

# Configuration
DOMAIN_NAME="${1:-localhost}"
DB_NAME="${2:-biz_directory}"
DB_USER="${3:-biz_user}"
DB_PASS="${4:-$(openssl rand -base64 32)}"

echo "📋 Deployment Configuration:"
echo "   Domain: $DOMAIN_NAME"
echo "   Database: $DB_NAME"
echo "   DB User: $DB_USER"
echo "   Generated Password: $DB_PASS"
echo ""

# Create deployment directory structure
echo "📁 Creating deployment structure..."
mkdir -p deploy/{config,scripts,backups}

# Generate WordPress config
echo "⚙️  Generating WordPress configuration..."
cat > deploy/wp-config.php << EOF
<?php
/**
 * WordPress Configuration for BizDir Platform
 * Auto-generated on $(date)
 */

// Database settings
define('DB_NAME', '$DB_NAME');
define('DB_USER', '$DB_USER');
define('DB_PASSWORD', '$DB_PASS');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// Security keys (generate new ones for production)
define('AUTH_KEY',         '$(openssl rand -base64 64)');
define('SECURE_AUTH_KEY',  '$(openssl rand -base64 64)');
define('LOGGED_IN_KEY',    '$(openssl rand -base64 64)');
define('NONCE_KEY',        '$(openssl rand -base64 64)');
define('AUTH_SALT',        '$(openssl rand -base64 64)');
define('SECURE_AUTH_SALT', '$(openssl rand -base64 64)');
define('LOGGED_IN_SALT',   '$(openssl rand -base64 64)');
define('NONCE_SALT',       '$(openssl rand -base64 64)');

// WordPress table prefix
\$table_prefix = 'wp_';

// Environment settings
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

// Security enhancements
define('DISALLOW_FILE_EDIT', true);
define('FORCE_SSL_ADMIN', true);
define('AUTOMATIC_UPDATER_DISABLED', true);

// Performance optimizations
define('WP_CACHE', true);
define('COMPRESS_CSS', true);
define('COMPRESS_SCRIPTS', true);
define('CONCATENATE_SCRIPTS', true);

// That's all, stop editing!
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';
EOF

# Generate database setup script
echo "🗄️  Generating database setup script..."
cat > deploy/scripts/setup-database.sql << EOF
-- BizDir Platform Database Setup
-- Generated on $(date)

CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;

USE $DB_NAME;

-- Core WordPress tables will be created by WordPress installer
-- BizDir custom tables will be created by plugin activation

EOF

# Generate Apache/Nginx configurations
echo "🌐 Generating web server configurations..."

# Apache configuration
cat > deploy/config/apache-bizdir.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    DocumentRoot /var/www/html/bizdir
    
    <Directory /var/www/html/bizdir>
        AllowOverride All
        Require all granted
        
        # Security headers
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
        
        # WordPress permalinks
        RewriteEngine On
        RewriteBase /
        RewriteRule ^index\.php$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.php [L]
    </Directory>
    
    # Security: Hide sensitive files
    <Files "wp-config.php">
        Require all denied
    </Files>
    
    <Files ".htaccess">
        Require all denied
    </Files>
    
    # Performance: Enable compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/plain
        AddOutputFilterByType DEFLATE text/html
        AddOutputFilterByType DEFLATE text/xml
        AddOutputFilterByType DEFLATE text/css
        AddOutputFilterByType DEFLATE application/xml
        AddOutputFilterByType DEFLATE application/xhtml+xml
        AddOutputFilterByType DEFLATE application/rss+xml
        AddOutputFilterByType DEFLATE application/javascript
        AddOutputFilterByType DEFLATE application/x-javascript
    </IfModule>
    
    ErrorLog \${APACHE_LOG_DIR}/bizdir_error.log
    CustomLog \${APACHE_LOG_DIR}/bizdir_access.log combined
</VirtualHost>

# SSL Configuration (requires SSL certificate)
<VirtualHost *:443>
    ServerName $DOMAIN_NAME
    DocumentRoot /var/www/html/bizdir
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/$DOMAIN_NAME.crt
    SSLCertificateKeyFile /etc/ssl/private/$DOMAIN_NAME.key
    
    # Include all the same directives from port 80
    Include /etc/apache2/sites-available/bizdir-common.conf
</VirtualHost>
EOF

# Nginx configuration
cat > deploy/config/nginx-bizdir.conf << EOF
server {
    listen 80;
    server_name $DOMAIN_NAME;
    root /var/www/html/bizdir;
    index index.php index.html index.htm;
    
    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";
    
    # WordPress permalinks
    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }
    
    # PHP processing
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Security: Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ ~$ {
        deny all;
    }
    
    location ~* /(?:uploads|files)/.*\.php$ {
        deny all;
    }
    
    # Static files caching
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;
    
    access_log /var/log/nginx/bizdir_access.log;
    error_log /var/log/nginx/bizdir_error.log;
}
EOF

# Generate deployment checklist
echo "📋 Generating deployment checklist..."
cat > deploy/DEPLOYMENT_CHECKLIST.md << EOF
# BizDir Platform Deployment Checklist

## Pre-Deployment Requirements
- [ ] Server with PHP 8.0+ installed
- [ ] MySQL 8.0+ or MariaDB 10.3+ database server
- [ ] Apache 2.4+ or Nginx 1.18+ web server
- [ ] SSL certificate for HTTPS
- [ ] Domain name configured and pointing to server

## Deployment Steps

### 1. Server Preparation
\`\`\`bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install apache2 mysql-server php8.0 php8.0-mysql php8.0-curl php8.0-gd php8.0-mbstring php8.0-xml php8.0-zip -y

# Enable Apache modules
sudo a2enmod rewrite ssl headers deflate
\`\`\`

### 2. Database Setup
\`\`\`bash
# Run as MySQL root user
sudo mysql < deploy/scripts/setup-database.sql
\`\`\`

### 3. File Deployment
\`\`\`bash
# Create web directory
sudo mkdir -p /var/www/html/bizdir

# Copy files (adjust paths as needed)
sudo cp -r wp-content /var/www/html/bizdir/
sudo cp deploy/wp-config.php /var/www/html/bizdir/
sudo cp -r [wordpress-core-files] /var/www/html/bizdir/

# Set correct permissions
sudo chown -R www-data:www-data /var/www/html/bizdir
sudo chmod -R 755 /var/www/html/bizdir
sudo chmod 600 /var/www/html/bizdir/wp-config.php
\`\`\`

### 4. Web Server Configuration
\`\`\`bash
# For Apache:
sudo cp deploy/config/apache-bizdir.conf /etc/apache2/sites-available/
sudo a2ensite apache-bizdir.conf
sudo systemctl reload apache2

# For Nginx:
sudo cp deploy/config/nginx-bizdir.conf /etc/nginx/sites-available/bizdir
sudo ln -s /etc/nginx/sites-available/bizdir /etc/nginx/sites-enabled/
sudo systemctl reload nginx
\`\`\`

### 5. WordPress Installation
1. Visit http://$DOMAIN_NAME/wp-admin/install.php
2. Complete WordPress installation wizard
3. Activate BizDir Core plugin
4. Activate BizDir theme
5. Configure payment gateway settings in admin

### 6. Post-Deployment Configuration

#### Plugin Configuration
- Go to **BizDir Settings** in WordPress admin
- Configure payment gateways (Razorpay, PayU, Stripe)
- Set up sponsorship plans and pricing
- Configure moderation settings
- Enable analytics tracking

#### Security Hardening
- [ ] Install security plugin (Wordfence recommended)
- [ ] Configure firewall rules
- [ ] Set up automated backups
- [ ] Enable fail2ban for login protection
- [ ] Regular security updates schedule

#### Performance Optimization
- [ ] Install caching plugin (WP Super Cache or W3 Total Cache)
- [ ] Configure CDN if needed
- [ ] Optimize database
- [ ] Set up monitoring (New Relic, Pingdom, etc.)

## Testing Checklist
- [ ] Homepage loads correctly
- [ ] Business listing creation works
- [ ] Search and filtering functional
- [ ] User registration and login working
- [ ] Payment processing functional
- [ ] Review system operational
- [ ] Moderation workflow working
- [ ] Mobile responsiveness verified
- [ ] SEO meta tags and schema markup present
- [ ] SSL certificate properly configured

## Production Monitoring
- [ ] Set up log monitoring
- [ ] Configure uptime monitoring
- [ ] Set up performance monitoring
- [ ] Database backup automation
- [ ] Security scan scheduling

## Maintenance Schedule
- **Daily**: Log review, backup verification
- **Weekly**: Security updates, performance review
- **Monthly**: Full backup, security audit
- **Quarterly**: Dependency updates, feature review

---

**Generated on**: $(date)
**Domain**: $DOMAIN_NAME
**Database**: $DB_NAME
**Generated by**: BizDir Deployment Script v1.0
EOF

# Generate backup script
echo "💾 Generating backup script..."
cat > deploy/scripts/backup.sh << 'EOF'
#!/bin/bash

# BizDir Platform Backup Script
BACKUP_DIR="/var/backups/bizdir"
DATE=$(date +%Y%m%d_%H%M%S)
SITE_DIR="/var/www/html/bizdir"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump $DB_NAME > $BACKUP_DIR/database_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C $SITE_DIR .

# Keep only last 30 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
EOF

chmod +x deploy/scripts/backup.sh

# Generate monitoring script
echo "📊 Generating monitoring script..."
cat > deploy/scripts/monitor.sh << 'EOF'
#!/bin/bash

# BizDir Platform Health Monitor
LOG_FILE="/var/log/bizdir-monitor.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Check web server status
if curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200"; then
    WEB_STATUS="OK"
else
    WEB_STATUS="FAILED"
fi

# Check database connectivity
if mysql -e "SELECT 1" $DB_NAME > /dev/null 2>&1; then
    DB_STATUS="OK"
else
    DB_STATUS="FAILED"
fi

# Check disk space
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 90 ]; then
    DISK_STATUS="WARNING (${DISK_USAGE}%)"
else
    DISK_STATUS="OK (${DISK_USAGE}%)"
fi

# Log status
echo "[$DATE] Web: $WEB_STATUS | DB: $DB_STATUS | Disk: $DISK_STATUS" >> $LOG_FILE

# Send alert if any service is down
if [ "$WEB_STATUS" != "OK" ] || [ "$DB_STATUS" != "OK" ]; then
    echo "ALERT: BizDir service issue detected at $DATE" | logger
fi
EOF

chmod +x deploy/scripts/monitor.sh

echo ""
echo "✅ Deployment package created successfully!"
echo ""
echo "📁 Generated files:"
echo "   deploy/wp-config.php                 - WordPress configuration"
echo "   deploy/scripts/setup-database.sql   - Database setup script"
echo "   deploy/config/apache-bizdir.conf    - Apache virtual host"
echo "   deploy/config/nginx-bizdir.conf     - Nginx server block"
echo "   deploy/scripts/backup.sh            - Automated backup script"
echo "   deploy/scripts/monitor.sh           - Health monitoring script"
echo "   deploy/DEPLOYMENT_CHECKLIST.md     - Complete deployment guide"
echo ""
echo "🚀 Next steps:"
echo "   1. Review deploy/DEPLOYMENT_CHECKLIST.md"
echo "   2. Set up your production server"
echo "   3. Run the database setup script"
echo "   4. Copy files to web directory"
echo "   5. Configure web server"
echo "   6. Complete WordPress installation"
echo ""
echo "💡 Database credentials:"
echo "   Database: $DB_NAME"
echo "   Username: $DB_USER"
echo "   Password: $DB_PASS"
echo ""
echo "🔐 Save these credentials securely!"
