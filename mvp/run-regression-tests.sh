#!/bin/bash

# Comprehensive Regression Testing Script for BizDir Platform
# This script runs all regression tests and generates detailed reports

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration - External test results directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
EXTERNAL_RESULTS_DIR="/home/ankur/biz-dir-test-results"
TEST_RESULTS_DIR="$EXTERNAL_RESULTS_DIR/results"
TEST_LOGS_DIR="$EXTERNAL_RESULTS_DIR/logs"
TEST_COVERAGE_DIR="$EXTERNAL_RESULTS_DIR/coverage"
PHPUNIT_BIN="$SCRIPT_DIR/vendor/bin/phpunit"
PHPUNIT_CONFIG="$SCRIPT_DIR/phpunit-production.xml"
PHPUNIT_FALLBACK="$SCRIPT_DIR/phpunit.xml"

# Create external directories if they don't exist
mkdir -p "$TEST_RESULTS_DIR"
mkdir -p "$TEST_LOGS_DIR" 
mkdir -p "$TEST_COVERAGE_DIR"

# Start time tracking
START_TIME=$(date +%s)
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Export environment variable for test scripts
export BIZDIR_TEST_RESULTS_DIR="$EXTERNAL_RESULTS_DIR"

echo -e "${BLUE}=================================================================================${NC}"
echo -e "${BLUE}🔄 BizDir Platform Comprehensive Regression Testing Suite${NC}"
echo -e "${BLUE}=================================================================================${NC}"
echo ""
echo -e "📅 Started at: $(date)"
echo -e "📁 Results directory: $TEST_RESULTS_DIR"
echo -e "📋 Logs directory: $TEST_LOGS_DIR"
echo ""

# Function to log messages
log_message() {
    local level=$1
    local message=$2
    local timestamp=$(date "+%Y-%m-%d %H:%M:%S")
    echo "[$timestamp][$level] $message" >> "$TEST_LOGS_DIR/regression_$TIMESTAMP.log"
    
    case $level in
        "ERROR")
            echo -e "${RED}❌ $message${NC}"
            ;;
        "SUCCESS")
            echo -e "${GREEN}✅ $message${NC}"
            ;;
        "WARNING")
            echo -e "${YELLOW}⚠️  $message${NC}"
            ;;
        "INFO")
            echo -e "${BLUE}ℹ️  $message${NC}"
            ;;
        *)
            echo "$message"
            ;;
    esac
}

# Function to run individual test suite
run_test_suite() {
    local suite_name=$1
    local test_class=$2
    
    log_message "INFO" "Running test suite: $suite_name"
    
    local start_time=$(date +%s.%N)
    local suite_log="$TEST_LOGS_DIR/${suite_name}_$TIMESTAMP.log"
    
    # Run the test suite
    if [ -f "$PHPUNIT_BIN" ]; then
        $PHPUNIT_BIN \
            --configuration "$PHPUNIT_CONFIG" \
            --testsuite "$suite_name" \
            --log-junit "$TEST_RESULTS_DIR/junit_${suite_name}_$TIMESTAMP.xml" \
            --testdox-html "$TEST_RESULTS_DIR/${suite_name}_testdox_$TIMESTAMP.html" \
            --coverage-html "$TEST_RESULTS_DIR/coverage_${suite_name}_$TIMESTAMP" \
            --coverage-text="$TEST_RESULTS_DIR/coverage_${suite_name}_$TIMESTAMP.txt" \
            > "$suite_log" 2>&1
        
        local exit_code=$?
    else
        log_message "ERROR" "PHPUnit not found at $PHPUNIT_BIN"
        return 1
    fi
    
    local end_time=$(date +%s.%N)
    local duration=$(echo "$end_time - $start_time" | bc)
    
    # Parse results
    if [ $exit_code -eq 0 ]; then
        log_message "SUCCESS" "$suite_name completed successfully (${duration}s)"
        echo "$suite_name:PASSED:$duration" >> "$TEST_RESULTS_DIR/summary_$TIMESTAMP.txt"
    else
        log_message "ERROR" "$suite_name failed with exit code $exit_code (${duration}s)"
        echo "$suite_name:FAILED:$duration" >> "$TEST_RESULTS_DIR/summary_$TIMESTAMP.txt"
        
        # Show last few lines of output for failed tests
        echo -e "\n${RED}Last 10 lines of output:${NC}"
        tail -n 10 "$suite_log"
    fi
    
    return $exit_code
}

# Function to run basic syntax check
run_syntax_check() {
    log_message "INFO" "Running PHP syntax check on regression test files"
    
    local syntax_errors=0
    
    for test_file in tests/Regression/*.php; do
        if [ -f "$test_file" ]; then
            if ! php -l "$test_file" > /dev/null 2>&1; then
                log_message "ERROR" "Syntax error in $test_file"
                ((syntax_errors++))
            else
                log_message "INFO" "Syntax OK: $test_file"
            fi
        fi
    done
    
    if [ $syntax_errors -eq 0 ]; then
        log_message "SUCCESS" "All regression test files have valid syntax"
        return 0
    else
        log_message "ERROR" "Found $syntax_errors syntax errors in test files"
        return 1
    fi
}

# Function to check test dependencies
check_dependencies() {
    log_message "INFO" "Checking test dependencies and configuration"
    
    # Check PHP version
    local php_version=$(php -v | head -n1 | cut -d' ' -f2)
    log_message "INFO" "PHP Version: $php_version"
    
    # Check PHPUnit configuration files
    if [ -f "$PHPUNIT_CONFIG" ]; then
        log_message "SUCCESS" "Production PHPUnit configuration found: $PHPUNIT_CONFIG"
    elif [ -f "$PHPUNIT_FALLBACK" ]; then
        log_message "WARNING" "Using fallback PHPUnit configuration: $PHPUNIT_FALLBACK"
        PHPUNIT_CONFIG="$PHPUNIT_FALLBACK"
    else
        log_message "ERROR" "No PHPUnit configuration file found"
        return 1
    fi
    
    # Check PHPUnit
    if [ -f "$PHPUNIT_BIN" ]; then
        local phpunit_version=$($PHPUNIT_BIN --version | head -n1)
        log_message "SUCCESS" "PHPUnit found: $phpunit_version"
    else
        log_message "ERROR" "PHPUnit not found at $PHPUNIT_BIN"
        return 1
    fi
    
    # Check configuration file
    if [ -f "$PHPUNIT_CONFIG" ]; then
        log_message "SUCCESS" "PHPUnit configuration found: $PHPUNIT_CONFIG"
    else
        log_message "ERROR" "PHPUnit configuration not found: $PHPUNIT_CONFIG"
        return 1
    fi
    
    # Check test bootstrap
    if [ -f "tests/bootstrap.php" ]; then
        log_message "SUCCESS" "Test bootstrap found"
    else
        log_message "ERROR" "Test bootstrap not found"
        return 1
    fi
    
    return 0
}

# Function to generate HTML report
generate_html_report() {
    log_message "INFO" "Generating comprehensive HTML report"
    
    local report_file="$TEST_RESULTS_DIR/regression_report_$TIMESTAMP.html"
    local total_suites=0
    local passed_suites=0
    local failed_suites=0
    local total_time=0
    
    # Read summary data
    if [ -f "$TEST_RESULTS_DIR/summary_$TIMESTAMP.txt" ]; then
        while IFS=':' read -r suite status duration; do
            ((total_suites++))
            if [ "$status" = "PASSED" ]; then
                ((passed_suites++))
            else
                ((failed_suites++))
            fi
            total_time=$(echo "$total_time + $duration" | bc)
        done < "$TEST_RESULTS_DIR/summary_$TIMESTAMP.txt"
    fi
    
    cat > "$report_file" << EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizDir Regression Test Report - $TIMESTAMP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .header { text-align: center; margin-bottom: 30px; color: #333; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-card.passed { border-left: 4px solid #28a745; }
        .stat-card.failed { border-left: 4px solid #dc3545; }
        .stat-card.time { border-left: 4px solid #007bff; }
        .stat-number { font-size: 2em; font-weight: bold; margin-bottom: 5px; }
        .stat-label { color: #666; }
        .test-results { margin-top: 30px; }
        .test-suite { background: #f8f9fa; margin-bottom: 15px; padding: 15px; border-radius: 8px; }
        .test-suite.passed { border-left: 4px solid #28a745; }
        .test-suite.failed { border-left: 4px solid #dc3545; }
        .suite-name { font-weight: bold; font-size: 1.1em; margin-bottom: 5px; }
        .suite-status { display: inline-block; padding: 2px 8px; border-radius: 4px; color: white; font-size: 0.9em; }
        .status-passed { background: #28a745; }
        .status-failed { background: #dc3545; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 BizDir Platform Regression Test Report</h1>
            <p>Generated on $(date) | Report ID: $TIMESTAMP</p>
        </div>
        
        <div class="summary">
            <div class="stat-card">
                <div class="stat-number">$total_suites</div>
                <div class="stat-label">Total Test Suites</div>
            </div>
            <div class="stat-card passed">
                <div class="stat-number">$passed_suites</div>
                <div class="stat-label">Passed</div>
            </div>
            <div class="stat-card failed">
                <div class="stat-number">$failed_suites</div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat-card time">
                <div class="stat-number">$(printf "%.2f" $total_time)s</div>
                <div class="stat-label">Total Time</div>
            </div>
        </div>
        
        <div class="test-results">
            <h2>Test Suite Results</h2>
EOF

    # Add test suite results
    if [ -f "$TEST_RESULTS_DIR/summary_$TIMESTAMP.txt" ]; then
        while IFS=':' read -r suite status duration; do
            local css_class=$(echo "$status" | tr '[:upper:]' '[:lower:]')
            cat >> "$report_file" << EOF
            <div class="test-suite $css_class">
                <div class="suite-name">$suite</div>
                <span class="suite-status status-$css_class">$status</span>
                <span style="margin-left: 10px; color: #666;">Duration: $(printf "%.2f" $duration)s</span>
            </div>
EOF
        done < "$TEST_RESULTS_DIR/summary_$TIMESTAMP.txt"
    fi

    cat >> "$report_file" << EOF
        </div>
        
        <div class="footer">
            <p>BizDir Platform Regression Testing Suite | Automated Testing Framework</p>
            <p>For detailed logs, check: $TEST_LOGS_DIR/regression_$TIMESTAMP.log</p>
        </div>
    </div>
</body>
</html>
EOF

    log_message "SUCCESS" "HTML report generated: $report_file"
}

# Main execution
main() {
    log_message "INFO" "Starting BizDir regression testing suite"
    
    # Check dependencies
    if ! check_dependencies; then
        log_message "ERROR" "Dependency check failed"
        exit 1
    fi
    
    # Run syntax check
    if ! run_syntax_check; then
        log_message "ERROR" "Syntax check failed"
        exit 1
    fi
    
    # Initialize summary file
    echo "# BizDir Regression Test Summary - $TIMESTAMP" > "$TEST_RESULTS_DIR/summary_$TIMESTAMP.txt"
    
    # Test suites to run (matching production PHPUnit configuration)
    declare -A test_suites=(
        ["RegressionTests"]="Complete regression test suite"
        ["Security"]="OWASP security and authentication tests"
        ["BusinessLogic"]="Core business logic tests" 
        ["Performance"]="Database performance and optimization tests"
        ["SEO"]="SEO and structured data tests"
        ["Moderation"]="Content moderation workflow tests"
        ["Analytics"]="Analytics and data processing tests"
        ["Integration"]="Third-party integration tests"
        ["EndToEnd"]="End-to-end user journey tests"
    )
    
    local failed_suites=0
    local total_suites=${#test_suites[@]}
    
    # Run each test suite
    for suite in "${!test_suites[@]}"; do
        echo ""
        log_message "INFO" "Running test suite: $suite (${test_suites[$suite]})"
        
        if ! run_test_suite "$suite" "${test_suites[$suite]}"; then
            ((failed_suites++))
        fi
    done
    
    # Calculate total execution time
    local end_time=$(date +%s)
    local total_duration=$((end_time - START_TIME))
    
    # Generate reports
    generate_html_report
    
    # Display final summary
    echo ""
    echo -e "${BLUE}=================================================================================${NC}"
    echo -e "${BLUE}📊 Final Test Summary${NC}"
    echo -e "${BLUE}=================================================================================${NC}"
    echo -e "📅 Completed at: $(date)"
    echo -e "⏱️  Total duration: ${total_duration}s"
    echo -e "📊 Test suites: $total_suites"
    echo -e "✅ Passed: $((total_suites - failed_suites))"
    echo -e "❌ Failed: $failed_suites"
    echo -e "📁 Results location: $TEST_RESULTS_DIR"
    echo -e "📋 Logs location: $TEST_LOGS_DIR"
    
    if [ $failed_suites -eq 0 ]; then
        echo ""
        log_message "SUCCESS" "🎉 All regression tests passed! Platform is stable."
        echo -e "${BLUE}=================================================================================${NC}"
        exit 0
    else
        echo ""
        log_message "ERROR" "⚠️  $failed_suites test suite(s) failed. Please review the detailed logs."
        echo -e "${BLUE}=================================================================================${NC}"
        exit 1
    fi
}

# Run main function
main "$@"
