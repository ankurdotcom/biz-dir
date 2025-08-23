#!/bin/bash

# BizDir Seed Data Management Script
# Easily load different seed data sets for various purposes

echo "🌱 BizDir Seed Data Management"
echo "============================="

# Function to check if Docker is running
check_docker() {
    if ! docker compose -f docker-compose.dev.yml ps | grep -q "Up"; then
        echo "❌ Docker containers are not running!"
        echo "🚀 Starting containers..."
        docker compose -f docker-compose.dev.yml up -d
        sleep 10
    fi
}

# Function to backup current database
backup_database() {
    echo "💾 Creating backup of current database..."
    timestamp=$(date +"%Y%m%d-%H%M%S")
    docker compose -f docker-compose.dev.yml exec -T db mysqldump -u bizdir -pbizdir123 bizdir_dev > "backup-${timestamp}.sql"
    echo "✅ Backup saved as backup-${timestamp}.sql"
}

# Function to load seed data
load_seed_data() {
    local seed_file=$1
    local description=$2
    
    echo "📥 Loading $description..."
    echo "   File: $seed_file"
    
    if [ ! -f "$seed_file" ]; then
        echo "❌ Seed file not found: $seed_file"
        return 1
    fi
    
    # Load the seed data
    docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev < "$seed_file"
    
    if [ $? -eq 0 ]; then
        echo "✅ Successfully loaded $description"
        
        # Clear WordPress cache if Redis is available
        if docker compose -f docker-compose.dev.yml exec redis redis-cli ping > /dev/null 2>&1; then
            docker compose -f docker-compose.dev.yml exec redis redis-cli FLUSHDB > /dev/null 2>&1
            echo "🧹 WordPress cache cleared"
        fi
    else
        echo "❌ Failed to load $description"
        return 1
    fi
}

# Display available seed data files
echo ""
echo "📂 Available Seed Data Files:"
echo ""
echo "1. Development Data (seed-data-dev.sql)"
echo "   📋 Comprehensive development data with 10 sample businesses"
echo "   🎯 Best for: Development, feature testing, local setup"
echo ""
echo "2. Production Data (seed-data-production.sql)"
echo "   📋 Clean production setup with categories and essential pages"
echo "   🎯 Best for: Fresh production deployment, live sites"
echo ""
echo "3. Testing Data (seed-data-testing.sql)"
echo "   📋 Comprehensive test scenarios and edge cases"
echo "   🎯 Best for: UAT testing, regression testing, QA"
echo ""
echo "4. Demo Data (seed-data-demo.sql)"
echo "   📋 Attractive sample businesses for demonstrations"
echo "   🎯 Best for: Client demos, sales presentations, showcasing"
echo ""
echo "5. Custom SQL File"
echo "   📋 Load your own SQL file"
echo ""

# Get user choice
read -p "Choose seed data to load (1-5): " choice

# Check Docker first
check_docker

case $choice in
    1)
        read -p "Create backup before loading? (y/n): " backup
        if [ "$backup" = "y" ]; then
            backup_database
        fi
        load_seed_data "seed-data-dev.sql" "Development Data"
        ;;
    2)
        read -p "Create backup before loading? (y/n): " backup
        if [ "$backup" = "y" ]; then
            backup_database
        fi
        load_seed_data "seed-data-production.sql" "Production Data"
        ;;
    3)
        read -p "Create backup before loading? (y/n): " backup
        if [ "$backup" = "y" ]; then
            backup_database
        fi
        load_seed_data "seed-data-testing.sql" "Testing Data"
        ;;
    4)
        read -p "Create backup before loading? (y/n): " backup
        if [ "$backup" = "y" ]; then
            backup_database
        fi
        load_seed_data "seed-data-demo.sql" "Demo Data"
        ;;
    5)
        read -p "Enter path to SQL file: " custom_file
        if [ -f "$custom_file" ]; then
            read -p "Create backup before loading? (y/n): " backup
            if [ "$backup" = "y" ]; then
                backup_database
            fi
            load_seed_data "$custom_file" "Custom SQL Data"
        else
            echo "❌ File not found: $custom_file"
        fi
        ;;
    *)
        echo "❌ Invalid choice"
        exit 1
        ;;
esac

echo ""
echo "🎉 Seed Data Management Complete!"
echo ""
echo "📊 Quick Stats:"
docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "
SELECT 
    'Businesses' as Type, COUNT(*) as Count FROM wp_posts WHERE post_type = 'post' AND post_status = 'publish'
UNION ALL
SELECT 
    'Categories' as Type, COUNT(*) as Count FROM wp_terms WHERE term_id > 1
UNION ALL
SELECT 
    'Reviews' as Type, COUNT(*) as Count FROM wp_comments WHERE comment_approved = '1'
UNION ALL
SELECT 
    'Users' as Type, COUNT(*) as Count FROM wp_users;
" 2>/dev/null | grep -v "Warning"

echo ""
echo "🌐 Access your site:"
echo "   Local: http://localhost:8888"
echo "   Network: http://$(hostname -I | awk '{print $1}'):8888"
echo ""
echo "📚 Available seed data files:"
echo "   🔧 Development: seed-data-dev.sql"
echo "   🚀 Production: seed-data-production.sql"
echo "   🧪 Testing: seed-data-testing.sql"
echo "   🎯 Demo: seed-data-demo.sql"
echo ""
echo "💡 Tips:"
echo "   - Use Development data for local development"
echo "   - Use Production data for clean live deployment"  
echo "   - Use Testing data for comprehensive UAT"
echo "   - Use Demo data for client presentations"
