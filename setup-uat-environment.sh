#!/bin/bash

# Pre-Production UAT Environment Setup Script
# BizDir Business Directory Platform
# Version: 1.0
# Date: August 23, 2025

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
UAT_DOMAIN="uat.biz-dir.local"
UAT_DB_NAME="bizdir_uat"
UAT_DB_USER="bizdir_uat_user"
UAT_DB_PASS="secure_uat_password_2025"
UAT_WP_ADMIN_USER="uatadmin"
UAT_WP_ADMIN_PASS="UATAdmin@2025"
UAT_WP_ADMIN_EMAIL="uat@biz-dir.local"

echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE} BizDir Pre-Production UAT Environment Setup${NC}"
echo -e "${BLUE}==================================================${NC}"
echo ""

# Function to check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        echo -e "${RED}❌ This script should not be run as root for security reasons${NC}"
        echo -e "${YELLOW}Please run as a regular user with sudo privileges${NC}"
        exit 1
    fi
}

# Function to check prerequisites
check_prerequisites() {
    echo -e "${BLUE}🔍 Checking prerequisites...${NC}"
    
    # Check if required commands exist
    commands=("curl" "wget" "mysql" "php" "apache2" "git")
    for cmd in "${commands[@]}"; do
        if ! command -v $cmd &> /dev/null; then
            echo -e "${RED}❌ $cmd is not installed${NC}"
            exit 1
        fi
    done
    
    # Check PHP version
    php_version=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
    if (( $(echo "$php_version < 8.0" | bc -l) )); then
        echo -e "${RED}❌ PHP 8.0+ required. Current version: $php_version${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✅ All prerequisites met${NC}"
}

# Function to setup UAT database
setup_database() {
    echo -e "${BLUE}🗄️  Setting up UAT database...${NC}"
    
    # Create database and user
    sudo mysql -e "CREATE DATABASE IF NOT EXISTS ${UAT_DB_NAME};"
    sudo mysql -e "CREATE USER IF NOT EXISTS '${UAT_DB_USER}'@'localhost' IDENTIFIED BY '${UAT_DB_PASS}';"
    sudo mysql -e "GRANT ALL PRIVILEGES ON ${UAT_DB_NAME}.* TO '${UAT_DB_USER}'@'localhost';"
    sudo mysql -e "FLUSH PRIVILEGES;"
    
    echo -e "${GREEN}✅ Database ${UAT_DB_NAME} created successfully${NC}"
}

# Function to setup WordPress core
setup_wordpress() {
    echo -e "${BLUE}📱 Setting up WordPress core...${NC}"
    
    # Create UAT directory
    UAT_DIR="/var/www/html/bizdir-uat"
    sudo mkdir -p $UAT_DIR
    sudo chown -R $USER:www-data $UAT_DIR
    sudo chmod -R 755 $UAT_DIR
    
    # Download WordPress
    cd $UAT_DIR
    if [ ! -f "wp-config.php" ]; then
        wget https://wordpress.org/latest.tar.gz
        tar xzf latest.tar.gz --strip-components=1
        rm latest.tar.gz
    fi
    
    # Download WP-CLI if not exists
    if [ ! -f "/usr/local/bin/wp" ]; then
        curl -O https://raw.githubusercontent.com/wp-cli/wp-cli/v2.8.1/wp-cli.phar
        chmod +x wp-cli.phar
        sudo mv wp-cli.phar /usr/local/bin/wp
    fi
    
    # Create wp-config.php
    wp config create --dbname=$UAT_DB_NAME --dbuser=$UAT_DB_USER --dbpass=$UAT_DB_PASS --dbhost=localhost --force
    
    # Install WordPress
    wp core install --url="http://$UAT_DOMAIN" --title="BizDir UAT Environment" \
                   --admin_user=$UAT_WP_ADMIN_USER --admin_password=$UAT_WP_ADMIN_PASS \
                   --admin_email=$UAT_WP_ADMIN_EMAIL --skip-email
    
    echo -e "${GREEN}✅ WordPress core setup completed${NC}"
}

# Function to copy BizDir files
copy_bizdir_files() {
    echo -e "${BLUE}📂 Copying BizDir platform files...${NC}"
    
    UAT_DIR="/var/www/html/bizdir-uat"
    CURRENT_DIR=$(pwd)
    
    # Copy plugin files
    sudo cp -r "$CURRENT_DIR/mvp/wp-content/plugins/biz-dir-core" "$UAT_DIR/wp-content/plugins/"
    
    # Copy theme files if they exist
    if [ -d "$CURRENT_DIR/mvp/wp-content/themes/biz-dir" ]; then
        sudo cp -r "$CURRENT_DIR/mvp/wp-content/themes/biz-dir" "$UAT_DIR/wp-content/themes/"
    fi
    
    # Set proper permissions
    sudo chown -R www-data:www-data "$UAT_DIR/wp-content/"
    sudo chmod -R 755 "$UAT_DIR/wp-content/"
    
    # Activate plugin and theme
    cd $UAT_DIR
    wp plugin activate biz-dir-core
    if [ -d "wp-content/themes/biz-dir" ]; then
        wp theme activate biz-dir
    fi
    
    echo -e "${GREEN}✅ BizDir files copied and activated${NC}"
}

# Function to import database schema
import_database_schema() {
    echo -e "${BLUE}🗃️  Importing database schema...${NC}"
    
    CURRENT_DIR=$(pwd)
    
    # Import schemas
    if [ -f "$CURRENT_DIR/mvp/config/schema.sql" ]; then
        mysql -u$UAT_DB_USER -p$UAT_DB_PASS $UAT_DB_NAME < "$CURRENT_DIR/mvp/config/schema.sql"
        echo -e "${GREEN}✅ Core schema imported${NC}"
    fi
    
    if [ -f "$CURRENT_DIR/mvp/config/monetization_schema.sql" ]; then
        mysql -u$UAT_DB_USER -p$UAT_DB_PASS $UAT_DB_NAME < "$CURRENT_DIR/mvp/config/monetization_schema.sql"
        echo -e "${GREEN}✅ Monetization schema imported${NC}"
    fi
    
    if [ -f "$CURRENT_DIR/mvp/config/analytics_schema.sql" ]; then
        mysql -u$UAT_DB_USER -p$UAT_DB_PASS $UAT_DB_NAME < "$CURRENT_DIR/mvp/config/analytics_schema.sql"
        echo -e "${GREEN}✅ Analytics schema imported${NC}"
    fi
}

# Function to create test data
create_test_data() {
    echo -e "${BLUE}👥 Creating test data...${NC}"
    
    UAT_DIR="/var/www/html/bizdir-uat"
    cd $UAT_DIR
    
    # Create test users
    wp user create testcontributor contributor@uat.test --role=contributor --user_pass=TestPass123
    wp user create testmoderator moderator@uat.test --role=editor --user_pass=TestPass123
    wp user create testbusiness business@uat.test --role=subscriber --user_pass=TestPass123
    
    # Create test towns
    wp post create --post_type=town --post_title="Test City" --post_status=publish
    wp post create --post_type=town --post_title="Demo Town" --post_status=publish
    
    # Create test business categories
    wp term create business_category "Restaurants" --description="Food and dining establishments"
    wp term create business_category "Technology" --description="IT and tech companies"
    wp term create business_category "Healthcare" --description="Medical and healthcare services"
    
    echo -e "${GREEN}✅ Test data created${NC}"
}

# Function to setup Apache virtual host
setup_apache_vhost() {
    echo -e "${BLUE}🌐 Setting up Apache virtual host...${NC}"
    
    # Create virtual host configuration
    sudo tee /etc/apache2/sites-available/bizdir-uat.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName $UAT_DOMAIN
    DocumentRoot /var/www/html/bizdir-uat
    
    <Directory /var/www/html/bizdir-uat>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/bizdir-uat-error.log
    CustomLog \${APACHE_LOG_DIR}/bizdir-uat-access.log combined
</VirtualHost>
EOF
    
    # Enable site and required modules
    sudo a2ensite bizdir-uat.conf
    sudo a2enmod rewrite
    sudo systemctl reload apache2
    
    # Add to hosts file for local testing
    if ! grep -q "$UAT_DOMAIN" /etc/hosts; then
        echo "127.0.0.1 $UAT_DOMAIN" | sudo tee -a /etc/hosts
    fi
    
    echo -e "${GREEN}✅ Apache virtual host configured${NC}"
}

# Function to setup SSL certificate (self-signed for UAT)
setup_ssl() {
    echo -e "${BLUE}🔒 Setting up SSL certificate...${NC}"
    
    # Create self-signed certificate for UAT
    sudo mkdir -p /etc/ssl/bizdir-uat
    sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout /etc/ssl/bizdir-uat/bizdir-uat.key \
        -out /etc/ssl/bizdir-uat/bizdir-uat.crt \
        -subj "/C=IN/ST=State/L=City/O=BizDir/CN=$UAT_DOMAIN"
    
    # Create HTTPS virtual host
    sudo tee /etc/apache2/sites-available/bizdir-uat-ssl.conf > /dev/null <<EOF
<VirtualHost *:443>
    ServerName $UAT_DOMAIN
    DocumentRoot /var/www/html/bizdir-uat
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/bizdir-uat/bizdir-uat.crt
    SSLCertificateKeyFile /etc/ssl/bizdir-uat/bizdir-uat.key
    
    <Directory /var/www/html/bizdir-uat>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/bizdir-uat-ssl-error.log
    CustomLog \${APACHE_LOG_DIR}/bizdir-uat-ssl-access.log combined
</VirtualHost>
EOF
    
    sudo a2enmod ssl
    sudo a2ensite bizdir-uat-ssl.conf
    sudo systemctl reload apache2
    
    echo -e "${GREEN}✅ SSL certificate configured${NC}"
}

# Function to setup monitoring
setup_monitoring() {
    echo -e "${BLUE}📊 Setting up monitoring and logging...${NC}"
    
    # Create log directories
    sudo mkdir -p /var/log/bizdir-uat
    sudo chown www-data:www-data /var/log/bizdir-uat
    
    # Setup log rotation
    sudo tee /etc/logrotate.d/bizdir-uat > /dev/null <<EOF
/var/log/bizdir-uat/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
EOF
    
    echo -e "${GREEN}✅ Monitoring setup completed${NC}"
}

# Function to run validation tests
run_validation_tests() {
    echo -e "${BLUE}🧪 Running validation tests...${NC}"
    
    UAT_DIR="/var/www/html/bizdir-uat"
    cd $UAT_DIR
    
    # Test WordPress installation
    if wp core verify-checksums; then
        echo -e "${GREEN}✅ WordPress core validation passed${NC}"
    else
        echo -e "${RED}❌ WordPress core validation failed${NC}"
    fi
    
    # Test plugin activation
    if wp plugin is-active biz-dir-core; then
        echo -e "${GREEN}✅ BizDir plugin is active${NC}"
    else
        echo -e "${RED}❌ BizDir plugin activation failed${NC}"
    fi
    
    # Test database connection
    if wp db check; then
        echo -e "${GREEN}✅ Database connection successful${NC}"
    else
        echo -e "${RED}❌ Database connection failed${NC}"
    fi
    
    # Test URL access
    if curl -s -o /dev/null -w "%{http_code}" "http://$UAT_DOMAIN" | grep -q "200"; then
        echo -e "${GREEN}✅ Website accessible via HTTP${NC}"
    else
        echo -e "${RED}❌ Website not accessible via HTTP${NC}"
    fi
}

# Function to display summary
display_summary() {
    echo ""
    echo -e "${BLUE}==================================================${NC}"
    echo -e "${GREEN}🎉 UAT Environment Setup Complete!${NC}"
    echo -e "${BLUE}==================================================${NC}"
    echo ""
    echo -e "${YELLOW}UAT Environment Details:${NC}"
    echo -e "URL: http://$UAT_DOMAIN"
    echo -e "HTTPS URL: https://$UAT_DOMAIN (self-signed certificate)"
    echo -e "Document Root: /var/www/html/bizdir-uat"
    echo ""
    echo -e "${YELLOW}Database Details:${NC}"
    echo -e "Database: $UAT_DB_NAME"
    echo -e "Username: $UAT_DB_USER"
    echo -e "Password: $UAT_DB_PASS"
    echo ""
    echo -e "${YELLOW}WordPress Admin:${NC}"
    echo -e "Username: $UAT_WP_ADMIN_USER"
    echo -e "Password: $UAT_WP_ADMIN_PASS"
    echo -e "Email: $UAT_WP_ADMIN_EMAIL"
    echo ""
    echo -e "${YELLOW}Test Users:${NC}"
    echo -e "Contributor: testcontributor / TestPass123"
    echo -e "Moderator: testmoderator / TestPass123"
    echo -e "Business: testbusiness / TestPass123"
    echo ""
    echo -e "${GREEN}Next Steps:${NC}"
    echo -e "1. Access http://$UAT_DOMAIN to verify installation"
    echo -e "2. Login to WordPress admin at http://$UAT_DOMAIN/wp-admin"
    echo -e "3. Configure payment gateways in test mode"
    echo -e "4. Begin UAT testing according to PRE_PROD_UAT_PLAN.md"
    echo ""
    echo -e "${BLUE}Happy Testing! 🚀${NC}"
}

# Main execution flow
main() {
    check_root
    check_prerequisites
    
    echo -e "${YELLOW}⚠️  This will set up a complete UAT environment.${NC}"
    echo -e "${YELLOW}⚠️  Make sure you have sudo privileges.${NC}"
    read -p "Continue? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Setup cancelled.${NC}"
        exit 0
    fi
    
    setup_database
    setup_wordpress
    copy_bizdir_files
    import_database_schema
    create_test_data
    setup_apache_vhost
    setup_ssl
    setup_monitoring
    run_validation_tests
    display_summary
}

# Run the script
main
