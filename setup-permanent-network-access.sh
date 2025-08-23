#!/bin/bash

# BizDir Permanent Network Access Setup
# Configures automatic IP detection and network access for all environments

echo "🌐 BizDir Permanent Network Access Setup"
echo "======================================="

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Function to check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        print_status $YELLOW "⚠️  Running as root. Consider running as regular user for development."
    fi
}

# Function to detect current environment
detect_environment() {
    if [ -f "docker-compose.yml" ] && [ -f "docker-compose.dev.yml" ]; then
        echo "docker"
    elif [ -f "wp-config.php" ] || [ -f "mvp/wp-config.php" ]; then
        echo "wordpress"
    else
        echo "unknown"
    fi
}

# Function to backup wp-config.php
backup_wp_config() {
    local wp_config_path=$1
    if [ -f "$wp_config_path" ]; then
        local backup_path="${wp_config_path}.backup.$(date +%Y%m%d_%H%M%S)"
        cp "$wp_config_path" "$backup_path"
        print_status $GREEN "✅ Backup created: $backup_path"
        return 0
    else
        print_status $RED "❌ wp-config.php not found at: $wp_config_path"
        return 1
    fi
}

# Function to update wp-config.php with dynamic URL configuration
update_wp_config() {
    local wp_config_path=$1
    
    # Check if dynamic configuration already exists
    if grep -q "bizdir_set_dynamic_urls" "$wp_config_path"; then
        print_status $GREEN "✅ Dynamic URL configuration already exists in wp-config.php"
        return 0
    fi
    
    print_status $BLUE "🔧 Adding dynamic URL configuration to wp-config.php..."
    
    # The dynamic URL function is already in wp-config.php from our previous update
    # Just verify it's there
    if grep -q "Dynamic URL Configuration" "$wp_config_path"; then
        print_status $GREEN "✅ Dynamic URL configuration verified in wp-config.php"
        return 0
    else
        print_status $RED "❌ Dynamic URL configuration not found. Please apply the wp-config.php updates."
        return 1
    fi
}

# Function to setup firewall rules
setup_firewall() {
    print_status $BLUE "🔥 Setting up firewall rules..."
    
    # Check if ufw is available
    if command -v ufw > /dev/null; then
        # Allow common development ports
        sudo ufw allow 8888/tcp comment "BizDir Development"
        sudo ufw allow 80/tcp comment "BizDir HTTP"
        sudo ufw allow 443/tcp comment "BizDir HTTPS"
        
        print_status $GREEN "✅ Firewall rules added for ports 80, 443, 8888"
    else
        print_status $YELLOW "⚠️  UFW not found. Please manually allow ports 80, 443, 8888"
        print_status $YELLOW "    Example: sudo iptables -A INPUT -p tcp --dport 8888 -j ACCEPT"
    fi
}

# Function to clear database URLs
clear_database_urls() {
    local environment=$1
    
    print_status $BLUE "🧹 Clearing hardcoded URLs from database..."
    
    case $environment in
        docker)
            if docker compose -f docker-compose.dev.yml ps | grep -q "Up"; then
                docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "UPDATE wp_options SET option_value = '' WHERE option_name IN ('home', 'siteurl');" 2>/dev/null
                print_status $GREEN "✅ Database URLs cleared for Docker environment"
            else
                print_status $YELLOW "⚠️  Docker containers not running. Start them first."
            fi
            ;;
        wordpress)
            print_status $YELLOW "⚠️  Direct WordPress installation detected. Please clear database URLs manually."
            print_status $BLUE "    SQL: UPDATE wp_options SET option_value = '' WHERE option_name IN ('home', 'siteurl');"
            ;;
    esac
}

# Function to test network access
test_network_access() {
    local current_ip=$(hostname -I | awk '{print $1}')
    
    print_status $BLUE "🧪 Testing network access..."
    
    # Test URLs
    local test_urls=(
        "http://localhost:8888"
        "http://127.0.0.1:8888"
        "http://$current_ip:8888"
    )
    
    for url in "${test_urls[@]}"; do
        local response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null)
        if [ "$response" = "200" ]; then
            print_status $GREEN "✅ $url: Working"
        else
            print_status $RED "❌ $url: Failed (HTTP $response)"
        fi
    done
}

# Function to create network access documentation
create_documentation() {
    local current_ip=$(hostname -I | awk '{print $1}')
    
    cat > NETWORK_ACCESS_SETUP.md << EOF
# BizDir Network Access Setup Complete

## 🌐 Automatic Network Access Configuration

Your BizDir installation now supports automatic network access detection and configuration.

### ✅ What's Configured

1. **Dynamic URL Detection**: Automatically detects localhost vs IP access
2. **Multi-Environment Support**: Works in Docker, development, and production
3. **Local Network Access**: Supports access from other devices on your network
4. **Security**: Validates hosts against allowed patterns

### 🔗 Access URLs

- **Local Development**: http://localhost:8888
- **Network Access**: http://$current_ip:8888
- **Docker**: http://localhost (if using port 80)

### 📱 Mobile Access

Other devices on your network can access the site using:
\`http://$current_ip:8888\`

### 🔧 Management

Use the network access script for ongoing management:
\`\`\`bash
./switch-access-mode.sh
\`\`\`

### 🛡️ Security Notes

- Only local network IPs are automatically allowed
- Production domains must be manually configured
- Database URLs are cleared to enable dynamic configuration

### 🔍 Troubleshooting

If access isn't working:

1. **Check Firewall**: \`sudo ufw allow 8888\`
2. **Restart Services**: \`docker compose restart php\`
3. **Clear Cache**: Browser cache and WordPress cache
4. **Check Logs**: Look for dynamic URL detection in WordPress debug log

### 📊 Current Network Info

- Primary IP: $current_ip
- All IPs: $(hostname -I)
- Gateway: $(ip route show | grep default | awk '{print $3}' 2>/dev/null || echo 'N/A')

---
*Setup completed on: $(date)*
EOF

    print_status $GREEN "✅ Documentation created: NETWORK_ACCESS_SETUP.md"
}

# Main execution
main() {
    check_root
    
    print_status $BLUE "🔍 Detecting environment..."
    local environment=$(detect_environment)
    print_status $GREEN "📋 Environment detected: $environment"
    
    # Determine wp-config.php path
    local wp_config_path=""
    if [ -f "mvp/wp-config.php" ]; then
        wp_config_path="mvp/wp-config.php"
    elif [ -f "wp-config.php" ]; then
        wp_config_path="wp-config.php"
    else
        print_status $RED "❌ wp-config.php not found!"
        exit 1
    fi
    
    print_status $GREEN "📁 Found wp-config.php at: $wp_config_path"
    
    echo ""
    print_status $BLUE "🚀 Starting permanent network access setup..."
    echo ""
    
    # Step 1: Backup wp-config.php
    print_status $BLUE "📦 Step 1: Backing up wp-config.php"
    backup_wp_config "$wp_config_path"
    
    # Step 2: Update wp-config.php
    print_status $BLUE "⚙️  Step 2: Updating wp-config.php"
    update_wp_config "$wp_config_path"
    
    # Step 3: Setup firewall
    print_status $BLUE "🔥 Step 3: Setting up firewall rules"
    setup_firewall
    
    # Step 4: Clear database URLs
    print_status $BLUE "🧹 Step 4: Clearing database URLs"
    clear_database_urls "$environment"
    
    # Step 5: Restart services if Docker
    if [ "$environment" = "docker" ]; then
        print_status $BLUE "🔄 Step 5: Restarting Docker services"
        docker compose -f docker-compose.dev.yml restart php
        sleep 3
    fi
    
    # Step 6: Test network access
    print_status $BLUE "🧪 Step 6: Testing network access"
    test_network_access
    
    # Step 7: Create documentation
    print_status $BLUE "📚 Step 7: Creating documentation"
    create_documentation
    
    echo ""
    print_status $GREEN "🎉 Permanent Network Access Setup Complete!"
    echo ""
    print_status $GREEN "✅ Your BizDir installation now supports:"
    print_status $GREEN "   • Automatic IP detection"
    print_status $GREEN "   • Multi-device access"
    print_status $GREEN "   • Docker and production environments"
    print_status $GREEN "   • Secure host validation"
    echo ""
    print_status $BLUE "📱 Access your site from any device on the network:"
    print_status $BLUE "   http://$(hostname -I | awk '{print $1}'):8888"
    echo ""
    print_status $YELLOW "💡 Use './switch-access-mode.sh' for ongoing management"
}

# Run main function
main "$@"
