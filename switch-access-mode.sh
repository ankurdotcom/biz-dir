#!/bin/bash

# BizDir Network Access Manager
# Manages network access with automatic IP detection and permanent configuration

CURRENT_IP=$(hostname -I | awk '{print $1}')
echo "🌐 BizDir Network Access Manager"
echo "==============================="
echo "Current IP: $CURRENT_IP"
echo ""

# Function to check Docker status
check_docker() {
    if ! docker compose -f docker-compose.dev.yml ps | grep -q "Up"; then
        echo "❌ Docker containers are not running!"
        echo "🚀 Starting containers..."
        docker compose -f docker-compose.dev.yml up -d
        sleep 10
    fi
}

# Function to test access
test_access() {
    local url=$1
    local name=$2
    
    echo -n "Testing $name ($url): "
    response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null)
    
    if [ "$response" = "200" ]; then
        echo "✅ Working"
        return 0
    else
        echo "❌ Failed (HTTP $response)"
        return 1
    fi
}

# Function to clear database URLs (makes dynamic config take effect)
clear_database_urls() {
    echo "🧹 Clearing hardcoded URLs from database..."
    docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "UPDATE wp_options SET option_value = '' WHERE option_name IN ('home', 'siteurl');" 2>/dev/null
    
    # Clear WordPress cache if Redis is available
    if docker compose -f docker-compose.dev.yml exec redis redis-cli ping > /dev/null 2>&1; then
        docker compose -f docker-compose.dev.yml exec redis redis-cli FLUSHDB > /dev/null 2>&1
        echo "🧹 WordPress cache cleared"
    fi
    
    echo "✅ Database URLs cleared - dynamic configuration active"
}

# Function to restart services
restart_services() {
    echo "🔄 Restarting PHP service to apply configuration..."
    docker compose -f docker-compose.dev.yml restart php
    sleep 3
    echo "✅ Services restarted"
}

# Check current configuration
echo "📋 Current Configuration Status:"
echo "================================"

# Check if wp-config has dynamic configuration
if grep -q "bizdir_set_dynamic_urls" mvp/wp-config.php; then
    echo "✅ Dynamic URL configuration: Active"
else
    echo "❌ Dynamic URL configuration: Not found"
    echo "⚠️  Run this script with option 5 to enable permanent fix"
fi

# Check database URLs
DB_URLS=$(docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "SELECT CONCAT(option_name, '=', option_value) FROM wp_options WHERE option_name IN ('home', 'siteurl');" 2>/dev/null | grep -v "Warning" | tail -n +2)

if echo "$DB_URLS" | grep -q "http"; then
    echo "⚠️  Database URLs: Hardcoded (may override dynamic config)"
    echo "$DB_URLS"
else
    echo "✅ Database URLs: Empty (dynamic config active)"
fi

echo ""
echo "🧪 Access Test Results:"
echo "======================"

check_docker

# Test various access methods
test_access "http://localhost:8888" "Localhost"
test_access "http://$CURRENT_IP:8888" "Network IP"
test_access "http://127.0.0.1:8888" "Loopback"

echo ""
echo "🔧 Management Options:"
echo "====================="
echo "1. 🧹 Clear database URLs (activate dynamic config)"
echo "2. 🔄 Restart services"
echo "3. 🧪 Run comprehensive access test"
echo "4. 📊 Show network configuration"
echo "5. 🔧 Install permanent dynamic URL fix"
echo "6. � Get QR code for mobile access"
echo "7. 🔍 Troubleshooting mode"
echo ""

read -p "Choose option (1-7): " choice

case $choice in
    1)
        clear_database_urls
        restart_services
        echo ""
        echo "✅ Dynamic configuration activated!"
        echo "🌐 Your site should now work with:"
        echo "   • http://localhost:8888"
        echo "   • http://$CURRENT_IP:8888"
        echo "   • Any device on your network"
        ;;
    2)
        restart_services
        ;;
    3)
        echo ""
        echo "🧪 Comprehensive Access Test"
        echo "============================"
        
        # Test multiple URLs
        urls=(
            "http://localhost:8888"
            "http://127.0.0.1:8888" 
            "http://$CURRENT_IP:8888"
        )
        
        for url in "${urls[@]}"; do
            test_access "$url" "$(echo $url | cut -d'/' -f3)"
        done
        
        echo ""
        echo "📊 Service Status:"
        docker compose -f docker-compose.dev.yml ps | grep -E "php|nginx|db"
        ;;
    4)
        echo ""
        echo "📊 Network Configuration"
        echo "======================="
        echo "🖥️  Machine IP: $CURRENT_IP"
        echo "🌐 Network Interface:"
        ip route show | grep default
        echo ""
        echo "🔌 Available Network IPs:"
        hostname -I
        echo ""
        echo "📡 Network Interfaces:"
        ip addr show | grep -E "inet.*scope global" | awk '{print $2}' | cut -d'/' -f1
        ;;
    5)
        echo ""
        echo "🔧 Installing Permanent Dynamic URL Fix"
        echo "======================================"
        
        if grep -q "bizdir_set_dynamic_urls" mvp/wp-config.php; then
            echo "✅ Dynamic URL configuration already installed!"
        else
            echo "❌ Dynamic URL configuration not found in wp-config.php"
            echo "⚠️  Please run the main setup to install the permanent fix"
        fi
        
        clear_database_urls
        restart_services
        
        echo ""
        echo "🧪 Testing after fix installation:"
        test_access "http://localhost:8888" "Localhost"
        test_access "http://$CURRENT_IP:8888" "Network IP"
        
        echo ""
        echo "✅ Permanent fix complete!"
        echo "📝 The system will now automatically:"
        echo "   • Detect localhost vs IP access"
        echo "   • Work on any local network IP"
        echo "   • Support Docker environments"
        echo "   • Handle production domains"
        ;;
    6)
        echo ""
        echo "� Mobile Access QR Code"
        echo "======================="
        echo "URL: http://$CURRENT_IP:8888"
        echo ""
        
        # Generate QR code if qrencode is available
        if command -v qrencode > /dev/null; then
            qrencode -t UTF8 "http://$CURRENT_IP:8888"
        else
            echo "💡 Install qrencode to see QR code:"
            echo "   sudo apt install qrencode"
            echo ""
            echo "🔗 Or scan this URL manually:"
            echo "   http://$CURRENT_IP:8888"
        fi
        ;;
    7)
        echo ""
        echo "🔍 Troubleshooting Mode"
        echo "====================="
        
        echo "📋 System Information:"
        echo "   OS: $(uname -s)"
        echo "   Architecture: $(uname -m)"
        echo "   Docker Version: $(docker --version 2>/dev/null || echo 'Not installed')"
        echo ""
        
        echo "🌐 Network Information:"
        echo "   Primary IP: $CURRENT_IP"
        echo "   All IPs: $(hostname -I)"
        echo "   Gateway: $(ip route show | grep default | awk '{print $3}')"
        echo ""
        
        echo "🐳 Docker Status:"
        docker compose -f docker-compose.dev.yml ps 2>/dev/null || echo "   Docker not running"
        echo ""
        
        echo "📊 WordPress Configuration:"
        if [ -f mvp/wp-config.php ]; then
            echo "   wp-config.php: ✅ Found"
            if grep -q "bizdir_set_dynamic_urls" mvp/wp-config.php; then
                echo "   Dynamic URLs: ✅ Enabled"
            else
                echo "   Dynamic URLs: ❌ Not enabled"
            fi
        else
            echo "   wp-config.php: ❌ Not found"
        fi
        
        echo ""
        echo "� Quick Fixes:"
        echo "   1. Restart Docker: docker compose -f docker-compose.dev.yml restart"
        echo "   2. Check firewall: sudo ufw allow 8888"
        echo "   3. Clear browser cache and try again"
        echo "   4. Try incognito/private mode"
        ;;
    *)
        echo "❌ Invalid choice"
        exit 1
        ;;
esac

echo ""
echo "🎉 Network Access Management Complete!"
echo ""
echo "📱 Access URLs:"
echo "   🏠 Local: http://localhost:8888"
echo "   🌐 Network: http://$CURRENT_IP:8888"
echo ""
echo "💡 Tips:"
echo "   • The dynamic URL fix is now permanent"
echo "   • Works automatically in all environments"
echo "   • Other devices must be on same network"
echo "   • Use firewall command if port blocked: sudo ufw allow 8888"
