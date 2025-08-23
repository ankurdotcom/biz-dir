#!/bin/bash

# =============================================================================
# BizDir Seed Data Setup Script
# =============================================================================
# Purpose: Automatically populate development environment with seed data
# Usage: ./setup-seed-data.sh
# Requirements: Docker Compose environment running
# Features: 
#   - Cleans existing seed data completely (fresh start)
#   - Resets AUTO_INCREMENT values for clean IDs
#   - Imports realistic Indian business directory data
#   - Verifies successful import
# =============================================================================

echo "🌱 BizDir Seed Data Setup"
echo "========================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if Docker Compose is running
echo -e "${BLUE}📋 Checking Docker Compose status...${NC}"
if ! docker compose -f docker-compose.dev.yml ps | grep -q "Up"; then
    echo -e "${RED}❌ Docker Compose services are not running${NC}"
    echo -e "${YELLOW}💡 Please start the services first: docker compose -f docker-compose.dev.yml up -d${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Docker Compose services are running${NC}"

# Check if database is accessible
echo -e "${BLUE}🔌 Testing database connection...${NC}"
if ! docker compose -f docker-compose.dev.yml exec db mysql -u bizdir -pbizdir123 -e "USE bizdir_dev;" 2>/dev/null; then
    echo -e "${RED}❌ Cannot connect to database${NC}"
    echo -e "${YELLOW}💡 Please check database container and credentials${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Database connection successful${NC}"

# Backup existing data (optional)
echo -e "${BLUE}💾 Creating backup of existing data...${NC}"
docker compose -f docker-compose.dev.yml exec db mysqldump -u bizdir -pbizdir123 bizdir_dev > "backup-$(date +%Y%m%d-%H%M%S).sql" 2>/dev/null
echo -e "${GREEN}✅ Backup created${NC}"

# Import seed data
echo -e "${BLUE}🌱 Importing seed data (fresh start with clean indexes)...${NC}"
if docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev < seed-data-dev.sql; then
    echo -e "${GREEN}✅ Seed data imported successfully!${NC}"
else
    echo -e "${RED}❌ Failed to import seed data${NC}"
    echo -e "${YELLOW}💡 Check the seed-data-dev.sql file for syntax errors${NC}"
    exit 1
fi

# Verify data import
echo -e "${BLUE}🔍 Verifying imported data...${NC}"

# Check categories
CATEGORIES_COUNT=$(docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "SELECT COUNT(*) FROM wp_terms WHERE term_id >= 601;" -s -N 2>/dev/null)
echo -e "${GREEN}📂 Categories imported: ${CATEGORIES_COUNT}${NC}"

# Check businesses
BUSINESSES_COUNT=$(docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "SELECT COUNT(*) FROM wp_posts WHERE post_type='business';" -s -N 2>/dev/null)
echo -e "${GREEN}🏢 Businesses imported: ${BUSINESSES_COUNT}${NC}"

# Check reviews
REVIEWS_COUNT=$(docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "SELECT COUNT(*) FROM wp_comments WHERE comment_type='review';" -s -N 2>/dev/null)
echo -e "${GREEN}⭐ Reviews imported: ${REVIEWS_COUNT}${NC}"

# Display access information
echo ""
echo -e "${GREEN}🎉 Seed Data Setup Complete!${NC}"
echo "================================"
echo ""
echo -e "${BLUE}🌐 Access URLs:${NC}"
echo "   Frontend: http://localhost:8888"
echo "   Admin:    http://localhost:8888/wp-admin/"
echo ""
echo -e "${BLUE}👤 Demo Login Credentials:${NC}"
echo "   Admin:            demouser / demouser@123456:)"
echo "   Business Owner 1: businessowner1 / demo123"
echo "   Business Owner 2: businessowner2 / demo123"
echo "   Customer:         customer1 / demo123"
echo ""
echo -e "${BLUE}🏢 Sample Data Created:${NC}"
echo "   • 20 Business Categories (Restaurants, Gyms, etc.)"
echo "   • 10 Sample Business Listings"
echo "   • 5 Customer Reviews with Ratings"
echo "   • Complete Business Metadata (phone, address, hours)"
echo "   • 3 Demo User Accounts"
echo ""
echo -e "${BLUE}🧪 Testing Scenarios:${NC}"
echo "   • Browse businesses by category"
echo "   • Search and filter functionality"
echo "   • Add/edit business listings"
echo "   • Submit customer reviews"
echo "   • Test user role permissions"
echo "   • Verify mobile responsiveness"
echo ""
echo -e "${YELLOW}💡 Pro Tip: Use 'docker compose -f docker-compose.dev.yml logs -f' to monitor logs${NC}"
echo ""
echo -e "${GREEN}Happy testing! 🚀${NC}"
