#!/bin/bash

# UAT Test Execution Script
# BizDir Business Directory Platform
# Version: 1.0
# Date: August 23, 2025

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
UAT_DOMAIN="uat.biz-dir.local"
UAT_URL="http://$UAT_DOMAIN"
ADMIN_USER="uatadmin"
ADMIN_PASS="UATAdmin@2025"
TEST_RESULTS_DIR="/home/ankur/biz-dir-test-results/uat"

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE} BizDir Pre-Production UAT Test Execution${NC}"
echo -e "${BLUE}==================================================${NC}"
echo ""

# Function to log test results
log_test_result() {
    local test_name="$1"
    local status="$2"
    local details="$3"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    if [ "$status" = "PASS" ]; then
        PASSED_TESTS=$((PASSED_TESTS + 1))
        echo -e "${GREEN}✅ $test_name: PASSED${NC}"
    else
        FAILED_TESTS=$((FAILED_TESTS + 1))
        echo -e "${RED}❌ $test_name: FAILED${NC}"
        echo -e "${RED}   Details: $details${NC}"
    fi
    
    # Log to file
    echo "$timestamp,$test_name,$status,$details" >> "$TEST_RESULTS_DIR/test_execution.csv"
}

# Function to setup test results directory
setup_test_results() {
    mkdir -p "$TEST_RESULTS_DIR"
    echo "Timestamp,Test Name,Status,Details" > "$TEST_RESULTS_DIR/test_execution.csv"
}

# Function to test basic connectivity
test_basic_connectivity() {
    echo -e "${BLUE}🌐 Testing Basic Connectivity...${NC}"
    
    # Test HTTP access
    if curl -s -o /dev/null -w "%{http_code}" "$UAT_URL" | grep -q "200"; then
        log_test_result "HTTP Connectivity" "PASS" "Website accessible via HTTP"
    else
        log_test_result "HTTP Connectivity" "FAIL" "Website not accessible via HTTP"
    fi
    
    # Test HTTPS access
    if curl -k -s -o /dev/null -w "%{http_code}" "https://$UAT_DOMAIN" | grep -q "200"; then
        log_test_result "HTTPS Connectivity" "PASS" "Website accessible via HTTPS"
    else
        log_test_result "HTTPS Connectivity" "FAIL" "Website not accessible via HTTPS"
    fi
    
    # Test WordPress admin access
    if curl -s "$UAT_URL/wp-admin/" | grep -q "WordPress"; then
        log_test_result "WordPress Admin Access" "PASS" "WordPress admin panel accessible"
    else
        log_test_result "WordPress Admin Access" "FAIL" "WordPress admin panel not accessible"
    fi
}

# Function to test WordPress core functionality
test_wordpress_core() {
    echo -e "${BLUE}📱 Testing WordPress Core Functionality...${NC}"
    
    cd "/var/www/html/bizdir-uat"
    
    # Test core integrity
    if wp core verify-checksums 2>/dev/null; then
        log_test_result "WordPress Core Integrity" "PASS" "Core files are intact"
    else
        log_test_result "WordPress Core Integrity" "FAIL" "Core files may be corrupted"
    fi
    
    # Test database connection
    if wp db check 2>/dev/null; then
        log_test_result "Database Connection" "PASS" "Database connection successful"
    else
        log_test_result "Database Connection" "FAIL" "Database connection failed"
    fi
    
    # Test plugin status
    if wp plugin is-active biz-dir-core 2>/dev/null; then
        log_test_result "BizDir Plugin Status" "PASS" "BizDir plugin is active"
    else
        log_test_result "BizDir Plugin Status" "FAIL" "BizDir plugin is not active"
    fi
}

# Function to test user authentication
test_user_authentication() {
    echo -e "${BLUE}👤 Testing User Authentication...${NC}"
    
    cd "/var/www/html/bizdir-uat"
    
    # Test admin login capability
    if wp user list --role=administrator 2>/dev/null | grep -q "$ADMIN_USER"; then
        log_test_result "Admin User Exists" "PASS" "Admin user account exists"
    else
        log_test_result "Admin User Exists" "FAIL" "Admin user account missing"
    fi
    
    # Test role-based users
    if wp user list --role=contributor 2>/dev/null | grep -q "testcontributor"; then
        log_test_result "Contributor User Exists" "PASS" "Test contributor user exists"
    else
        log_test_result "Contributor User Exists" "FAIL" "Test contributor user missing"
    fi
    
    if wp user list --role=editor 2>/dev/null | grep -q "testmoderator"; then
        log_test_result "Moderator User Exists" "PASS" "Test moderator user exists"
    else
        log_test_result "Moderator User Exists" "FAIL" "Test moderator user missing"
    fi
}

# Function to test database schema
test_database_schema() {
    echo -e "${BLUE}🗄️  Testing Database Schema...${NC}"
    
    UAT_DB_NAME="bizdir_uat"
    UAT_DB_USER="bizdir_uat_user"
    UAT_DB_PASS="secure_uat_password_2025"
    
    # Test core tables exist
    tables=("bd_towns" "bd_businesses" "bd_reviews" "bd_tags" "bd_business_tags")
    
    for table in "${tables[@]}"; do
        if mysql -u"$UAT_DB_USER" -p"$UAT_DB_PASS" "$UAT_DB_NAME" -e "DESCRIBE $table;" &>/dev/null; then
            log_test_result "Table $table Exists" "PASS" "Database table $table exists"
        else
            log_test_result "Table $table Exists" "FAIL" "Database table $table missing"
        fi
    done
    
    # Test monetization tables
    monetization_tables=("bd_subscriptions" "bd_payments" "bd_advertisements")
    
    for table in "${monetization_tables[@]}"; do
        if mysql -u"$UAT_DB_USER" -p"$UAT_DB_PASS" "$UAT_DB_NAME" -e "DESCRIBE $table;" &>/dev/null; then
            log_test_result "Monetization Table $table" "PASS" "Monetization table $table exists"
        else
            log_test_result "Monetization Table $table" "FAIL" "Monetization table $table missing"
        fi
    done
}

# Function to test page performance
test_page_performance() {
    echo -e "${BLUE}⚡ Testing Page Performance...${NC}"
    
    # Test homepage load time
    start_time=$(date +%s.%3N)
    if curl -s -o /dev/null "$UAT_URL"; then
        end_time=$(date +%s.%3N)
        load_time=$(echo "$end_time - $start_time" | bc)
        
        if (( $(echo "$load_time < 3.0" | bc -l) )); then
            log_test_result "Homepage Load Time" "PASS" "Load time: ${load_time}s (< 3s target)"
        else
            log_test_result "Homepage Load Time" "FAIL" "Load time: ${load_time}s (> 3s target)"
        fi
    else
        log_test_result "Homepage Load Time" "FAIL" "Could not access homepage"
    fi
    
    # Test WordPress admin load time
    start_time=$(date +%s.%3N)
    if curl -s -o /dev/null "$UAT_URL/wp-admin/"; then
        end_time=$(date +%s.%3N)
        load_time=$(echo "$end_time - $start_time" | bc)
        
        if (( $(echo "$load_time < 5.0" | bc -l) )); then
            log_test_result "Admin Panel Load Time" "PASS" "Load time: ${load_time}s (< 5s target)"
        else
            log_test_result "Admin Panel Load Time" "FAIL" "Load time: ${load_time}s (> 5s target)"
        fi
    else
        log_test_result "Admin Panel Load Time" "FAIL" "Could not access admin panel"
    fi
}

# Function to test security headers
test_security_headers() {
    echo -e "${BLUE}🔒 Testing Security Headers...${NC}"
    
    # Test for security headers
    headers=$(curl -s -I "$UAT_URL")
    
    if echo "$headers" | grep -qi "X-Content-Type-Options"; then
        log_test_result "X-Content-Type-Options Header" "PASS" "Security header present"
    else
        log_test_result "X-Content-Type-Options Header" "FAIL" "Security header missing"
    fi
    
    if echo "$headers" | grep -qi "X-Frame-Options"; then
        log_test_result "X-Frame-Options Header" "PASS" "Security header present"
    else
        log_test_result "X-Frame-Options Header" "FAIL" "Security header missing"
    fi
    
    # Test HTTPS redirect if configured
    if curl -s -I "$UAT_URL" | grep -qi "location.*https"; then
        log_test_result "HTTPS Redirect" "PASS" "HTTP to HTTPS redirect configured"
    else
        log_test_result "HTTPS Redirect" "INFO" "No HTTP to HTTPS redirect (may be intentional for UAT)"
    fi
}

# Function to test file permissions
test_file_permissions() {
    echo -e "${BLUE}📁 Testing File Permissions...${NC}"
    
    UAT_DIR="/var/www/html/bizdir-uat"
    
    # Test wp-config.php permissions
    if [ -f "$UAT_DIR/wp-config.php" ]; then
        perms=$(stat -c "%a" "$UAT_DIR/wp-config.php")
        if [ "$perms" = "644" ] || [ "$perms" = "600" ]; then
            log_test_result "wp-config.php Permissions" "PASS" "Permissions: $perms"
        else
            log_test_result "wp-config.php Permissions" "FAIL" "Insecure permissions: $perms"
        fi
    else
        log_test_result "wp-config.php Exists" "FAIL" "wp-config.php file missing"
    fi
    
    # Test wp-content permissions
    if [ -d "$UAT_DIR/wp-content" ]; then
        perms=$(stat -c "%a" "$UAT_DIR/wp-content")
        if [ "$perms" = "755" ]; then
            log_test_result "wp-content Permissions" "PASS" "Permissions: $perms"
        else
            log_test_result "wp-content Permissions" "WARN" "Permissions: $perms (should be 755)"
        fi
    else
        log_test_result "wp-content Directory" "FAIL" "wp-content directory missing"
    fi
}

# Function to test backup functionality
test_backup_functionality() {
    echo -e "${BLUE}💾 Testing Backup Functionality...${NC}"
    
    UAT_DB_NAME="bizdir_uat"
    UAT_DB_USER="bizdir_uat_user"
    UAT_DB_PASS="secure_uat_password_2025"
    
    # Test database backup
    backup_file="/tmp/uat_db_backup_test.sql"
    if mysqldump -u"$UAT_DB_USER" -p"$UAT_DB_PASS" "$UAT_DB_NAME" > "$backup_file" 2>/dev/null; then
        if [ -s "$backup_file" ]; then
            log_test_result "Database Backup" "PASS" "Database backup created successfully"
            rm -f "$backup_file"
        else
            log_test_result "Database Backup" "FAIL" "Database backup file is empty"
        fi
    else
        log_test_result "Database Backup" "FAIL" "Database backup failed"
    fi
    
    # Test file backup
    UAT_DIR="/var/www/html/bizdir-uat"
    backup_dir="/tmp/uat_files_backup_test"
    if tar -czf "${backup_dir}.tar.gz" -C "$UAT_DIR" . 2>/dev/null; then
        if [ -s "${backup_dir}.tar.gz" ]; then
            log_test_result "File Backup" "PASS" "File backup created successfully"
            rm -f "${backup_dir}.tar.gz"
        else
            log_test_result "File Backup" "FAIL" "File backup archive is empty"
        fi
    else
        log_test_result "File Backup" "FAIL" "File backup failed"
    fi
}

# Function to test API endpoints
test_api_endpoints() {
    echo -e "${BLUE}🔌 Testing API Endpoints...${NC}"
    
    # Test WordPress REST API
    if curl -s "$UAT_URL/wp-json/wp/v2/" | grep -q '"name"'; then
        log_test_result "WordPress REST API" "PASS" "WordPress REST API accessible"
    else
        log_test_result "WordPress REST API" "FAIL" "WordPress REST API not accessible"
    fi
    
    # Test if custom BizDir endpoints are available (if implemented)
    if curl -s "$UAT_URL/wp-json/bizdir/v1/" 2>/dev/null | grep -q "bizdir"; then
        log_test_result "BizDir Custom API" "PASS" "BizDir custom API endpoints available"
    else
        log_test_result "BizDir Custom API" "INFO" "BizDir custom API endpoints not found (may not be implemented yet)"
    fi
}

# Function to test error handling
test_error_handling() {
    echo -e "${BLUE}🚨 Testing Error Handling...${NC}"
    
    # Test 404 page
    status_code=$(curl -s -o /dev/null -w "%{http_code}" "$UAT_URL/nonexistent-page-test")
    if [ "$status_code" = "404" ]; then
        log_test_result "404 Error Handling" "PASS" "404 errors handled correctly"
    else
        log_test_result "404 Error Handling" "FAIL" "404 errors not handled correctly (returned $status_code)"
    fi
    
    # Test PHP error handling
    if curl -s "$UAT_URL" | grep -qi "fatal error\|parse error\|warning"; then
        log_test_result "PHP Error Display" "FAIL" "PHP errors visible on frontend"
    else
        log_test_result "PHP Error Display" "PASS" "No PHP errors visible on frontend"
    fi
}

# Function to generate detailed report
generate_report() {
    echo ""
    echo -e "${BLUE}==================================================${NC}"
    echo -e "${BLUE} UAT Test Execution Summary${NC}"
    echo -e "${BLUE}==================================================${NC}"
    
    # Calculate pass rate
    if [ $TOTAL_TESTS -gt 0 ]; then
        pass_rate=$(echo "scale=1; $PASSED_TESTS * 100 / $TOTAL_TESTS" | bc)
    else
        pass_rate=0
    fi
    
    echo -e "${YELLOW}Test Statistics:${NC}"
    echo -e "Total Tests: $TOTAL_TESTS"
    echo -e "Passed: ${GREEN}$PASSED_TESTS${NC}"
    echo -e "Failed: ${RED}$FAILED_TESTS${NC}"
    echo -e "Pass Rate: $pass_rate%"
    echo ""
    
    # Generate HTML report
    html_report="$TEST_RESULTS_DIR/uat_test_report.html"
    cat > "$html_report" <<EOF
<!DOCTYPE html>
<html>
<head>
    <title>BizDir UAT Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .summary { background: #ecf0f1; padding: 15px; margin: 20px 0; }
        .pass { color: #27ae60; font-weight: bold; }
        .fail { color: #e74c3c; font-weight: bold; }
        .info { color: #3498db; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BizDir UAT Test Report</h1>
        <p>Generated on: $(date)</p>
    </div>
    
    <div class="summary">
        <h2>Test Summary</h2>
        <p><strong>Total Tests:</strong> $TOTAL_TESTS</p>
        <p><strong>Passed:</strong> <span class="pass">$PASSED_TESTS</span></p>
        <p><strong>Failed:</strong> <span class="fail">$FAILED_TESTS</span></p>
        <p><strong>Pass Rate:</strong> $pass_rate%</p>
    </div>
    
    <h2>Detailed Results</h2>
    <table>
        <tr>
            <th>Timestamp</th>
            <th>Test Name</th>
            <th>Status</th>
            <th>Details</th>
        </tr>
EOF
    
    # Add test results to HTML
    while IFS=',' read -r timestamp test_name status details; do
        if [ "$test_name" != "Test Name" ]; then  # Skip header
            status_class="info"
            if [ "$status" = "PASS" ]; then
                status_class="pass"
            elif [ "$status" = "FAIL" ]; then
                status_class="fail"
            fi
            echo "        <tr><td>$timestamp</td><td>$test_name</td><td class=\"$status_class\">$status</td><td>$details</td></tr>" >> "$html_report"
        fi
    done < "$TEST_RESULTS_DIR/test_execution.csv"
    
    cat >> "$html_report" <<EOF
    </table>
</body>
</html>
EOF
    
    echo -e "${GREEN}Detailed HTML report generated: $html_report${NC}"
    
    # Determine overall status
    if [ $FAILED_TESTS -eq 0 ]; then
        echo -e "${GREEN}🎉 All tests passed! UAT environment is ready.${NC}"
        return 0
    elif [ $FAILED_TESTS -le 3 ]; then
        echo -e "${YELLOW}⚠️  Some tests failed but environment may be usable.${NC}"
        echo -e "${YELLOW}Please review failed tests before proceeding.${NC}"
        return 1
    else
        echo -e "${RED}❌ Multiple tests failed. Environment needs attention.${NC}"
        echo -e "${RED}Please resolve issues before proceeding with UAT.${NC}"
        return 2
    fi
}

# Main execution
main() {
    setup_test_results
    
    echo -e "${YELLOW}Starting UAT environment validation...${NC}"
    echo ""
    
    test_basic_connectivity
    test_wordpress_core
    test_user_authentication
    test_database_schema
    test_page_performance
    test_security_headers
    test_file_permissions
    test_backup_functionality
    test_api_endpoints
    test_error_handling
    
    generate_report
}

# Check if UAT environment exists
if [ ! -d "/var/www/html/bizdir-uat" ]; then
    echo -e "${RED}❌ UAT environment not found!${NC}"
    echo -e "${YELLOW}Please run ./setup-uat-environment.sh first${NC}"
    exit 1
fi

# Run main function
main
