#!/bin/bash

# Quick Network Access Switcher for BizDir
# Switches between localhost and IP access modes

CURRENT_IP=$(hostname -I | awk '{print $1}')
echo "🌐 BizDir Quick Access Switcher"
echo "=============================="
echo "Current IP: $CURRENT_IP"
echo ""

# Check current configuration
CURRENT_URL=$(docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "SELECT option_value FROM wp_options WHERE option_name = 'home';" 2>/dev/null | tail -n1)

echo "Current site URL: $CURRENT_URL"
echo ""
echo "Select mode:"
echo "1. 🏠 Localhost mode (http://localhost:8888)"
echo "2. 🌐 Network mode (http://$CURRENT_IP:8888)"
echo "3. 🧪 Test current access"

read -p "Enter choice (1-3): " choice

case $choice in
    1)
        echo "🔄 Switching to localhost mode..."
        docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "UPDATE wp_options SET option_value = 'http://localhost:8888' WHERE option_name IN ('home', 'siteurl');" 2>/dev/null
        echo "✅ Switched to localhost mode"
        echo "🔗 Access: http://localhost:8888"
        ;;
    2)
        echo "🔄 Switching to network mode..."
        docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "UPDATE wp_options SET option_value = 'http://$CURRENT_IP:8888' WHERE option_name IN ('home', 'siteurl');" 2>/dev/null
        echo "✅ Switched to network mode"
        echo "🔗 Local access: http://$CURRENT_IP:8888"
        echo "📱 From other devices: http://$CURRENT_IP:8888"
        echo ""
        echo "📋 Make sure firewall allows port 8888:"
        echo "   sudo ufw allow 8888"
        ;;
    3)
        echo "🧪 Testing access..."
        echo -n "Localhost: "
        curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8888
        echo -n "IP Access: "
        curl -s -o /dev/null -w "%{http_code}\n" http://$CURRENT_IP:8888
        echo ""
        echo "📊 Service status:"
        docker compose -f docker-compose.dev.yml ps | grep -E "php|nginx|db"
        ;;
    *)
        echo "❌ Invalid choice"
        exit 1
        ;;
esac

echo ""
echo "📚 Usage tips:"
echo "• Use localhost mode for local development"
echo "• Use network mode to test from other devices"
echo "• Other devices must be on same network"
echo "• If port blocked, run: sudo ufw allow 8888"
