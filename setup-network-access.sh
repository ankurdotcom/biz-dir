#!/bin/bash

# Setup Network Access for BizDir
# This script helps configure WordPress to work with different IP addresses

echo "🌐 BizDir Network Access Configuration"
echo "======================================"

# Get current machine IP
CURRENT_IP=$(hostname -I | awk '{print $1}')
echo "📍 Current machine IP: $CURRENT_IP"

# Display current configuration
echo ""
echo "📋 Current allowed hosts in wp-config.php:"
grep -A 10 "allowed_hosts" mvp/wp-config.php | grep -E "'[0-9]|localhost"

echo ""
echo "🔧 Options:"
echo "1. Add new IP address to allowed hosts"
echo "2. View current configuration" 
echo "3. Reset to localhost only"
echo "4. Add all local network IPs (192.168.x.x)"
echo "5. Test current access"

read -p "Enter your choice (1-5): " choice

case $choice in
    1)
        read -p "Enter IP address to add (e.g., 192.168.1.50): " new_ip
        if [[ $new_ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
            # Add the new IP to wp-config.php
            sed -i "/\/\/ Add more IPs as needed/i\\    '$new_ip:8888'," mvp/wp-config.php
            echo "✅ Added $new_ip:8888 to allowed hosts"
            echo "🔄 Restarting PHP container..."
            docker compose -f docker-compose.dev.yml restart php
            echo "✅ Configuration updated!"
        else
            echo "❌ Invalid IP address format"
        fi
        ;;
    2)
        echo ""
        echo "📋 Current wp-config.php network configuration:"
        grep -A 15 "Dynamic URL Configuration" mvp/wp-config.php
        ;;
    3)
        echo "🔄 Resetting to localhost only..."
        # Backup current config
        cp mvp/wp-config.php mvp/wp-config.php.backup
        # Reset allowed hosts to localhost only
        sed -i '/192\.168\./d' mvp/wp-config.php
        docker compose -f docker-compose.dev.yml restart php
        echo "✅ Reset to localhost only access"
        ;;
    4)
        echo "🔄 Adding common local network ranges..."
        # Add common local network IPs
        for i in {1..254}; do
            if [[ $i -ne 100 ]]; then  # Skip current IP
                sed -i "/\/\/ Add more IPs as needed/i\\    '192.168.1.$i:8888'," mvp/wp-config.php
            fi
        done
        docker compose -f docker-compose.dev.yml restart php
        echo "✅ Added full 192.168.1.x range"
        ;;
    5)
        echo ""
        echo "🧪 Testing access URLs:"
        echo "📱 Local access: http://localhost:8888"
        echo "🌐 Network access: http://$CURRENT_IP:8888"
        echo ""
        echo "📋 Test from other devices using:"
        echo "   http://$CURRENT_IP:8888"
        echo ""
        echo "🔍 Checking if services are running..."
        docker compose -f docker-compose.dev.yml ps | grep -E "php|nginx"
        ;;
    *)
        echo "❌ Invalid choice"
        ;;
esac

echo ""
echo "📚 Next steps:"
echo "1. Test access from other devices: http://$CURRENT_IP:8888"
echo "2. If still having issues, check firewall settings"
echo "3. Ensure other devices are on the same network"
echo ""
echo "🛠️ Troubleshooting:"
echo "   - Firewall: sudo ufw allow 8888"
echo "   - Check network: ip route show"
echo "   - View logs: docker logs bizdir_php_dev"
