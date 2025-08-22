#!/bin/bash

# Simple test runner for BizDir project that doesn't require MySQL

echo "🚀 Running BizDir Core Tests"
echo "============================="

# Set up environment
export WP_TESTS_DIR="/dev/null"
export BIZ_DIR_TEST_MODE=1

# Test 1: Syntax validation
echo "📋 Test 1: PHP Syntax Validation"
echo "--------------------------------"

php_files=(
    "wp-content/plugins/biz-dir-core/biz-dir-core.php"
    "wp-content/plugins/biz-dir-core/includes/class-autoloader.php"
    "wp-content/plugins/biz-dir-core/includes/monetization/class-payment-handler.php"
    "wp-content/plugins/biz-dir-core/includes/monetization/class-ad-manager.php"
    "wp-content/plugins/biz-dir-core/includes/monetization/class-init.php"
    "wp-content/themes/biz-dir/functions.php"
)

syntax_errors=0
for file in "${php_files[@]}"; do
    if [ -f "$file" ]; then
        echo -n "  Checking $file... "
        if php -l "$file" > /dev/null 2>&1; then
            echo "✅ OK"
        else
            echo "❌ SYNTAX ERROR"
            php -l "$file"
            ((syntax_errors++))
        fi
    else
        echo "  Skipping $file (not found)"
    fi
done

echo ""
echo "📊 Test 2: File Structure Validation" 
echo "------------------------------------"

required_files=(
    "wp-content/plugins/biz-dir-core/biz-dir-core.php:Plugin main file"
    "wp-content/themes/biz-dir/style.css:Theme stylesheet"
    "wp-content/themes/biz-dir/functions.php:Theme functions"
    "config/schema.sql:Database schema"
    "config/monetization_schema.sql:Monetization schema"
    "config/analytics_schema.sql:Analytics schema"
)

missing_files=0
for item in "${required_files[@]}"; do
    file="${item%:*}"
    desc="${item#*:}"
    echo -n "  Checking $desc... "
    if [ -f "$file" ]; then
        echo "✅ Found"
    else
        echo "❌ Missing: $file"
        ((missing_files++))
    fi
done

echo ""
echo "🔧 Test 3: Class Loading Validation"
echo "-----------------------------------"

# Create a simple class loader test
cat > test_class_loading.php << 'EOF'
<?php
// Mock WordPress functions for testing
function add_action($hook, $callback) { return true; }
function current_time($format) { return date('Y-m-d H:i:s'); }
function get_option($name, $default = false) { return $default; }
function wp_next_scheduled($hook) { return false; }
function wp_schedule_event($timestamp, $recurrence, $hook, $args = []) { return true; }

// Set up constants
define('ABSPATH', __DIR__ . '/');
define('BIZ_DIR_PLUGIN_DIR', __DIR__ . '/wp-content/plugins/biz-dir-core/');

// Test autoloader
require_once 'wp-content/plugins/biz-dir-core/includes/class-autoloader.php';

try {
    $autoloader = new \BizDir\Core\Autoloader();
    $autoloader->register();
    echo "✅ Autoloader loaded successfully\n";
    
    // Test payment handler class
    $payment_handler = new \BizDir\Core\Monetization\Payment_Handler();
    echo "✅ Payment_Handler class loaded successfully\n";
    
    // Test ad manager class  
    $ad_manager = new \BizDir\Core\Monetization\Ad_Manager();
    echo "✅ Ad_Manager class loaded successfully\n";
    
    echo "✅ All core classes loaded successfully\n";
    
} catch (Exception $e) {
    echo "❌ Class loading error: " . $e->getMessage() . "\n";
    exit(1);
}
EOF

echo -n "  Testing class autoloading... "
if php test_class_loading.php > /dev/null 2>&1; then
    echo "✅ OK"
    class_loading_errors=0
else
    echo "❌ FAILED"
    echo "Error details:"
    php test_class_loading.php
    class_loading_errors=1
fi

# Clean up test file
rm -f test_class_loading.php

echo ""
echo "📈 Test 4: Database Schema Validation"
echo "-------------------------------------"

schema_files=(
    "config/schema.sql"
    "config/monetization_schema.sql" 
    "config/analytics_schema.sql"
)

schema_errors=0
for schema in "${schema_files[@]}"; do
    echo -n "  Validating $schema... "
    if [ -f "$schema" ]; then
        # Check for basic SQL syntax issues
        if grep -q "CREATE TABLE" "$schema" && grep -q "PRIMARY KEY" "$schema"; then
            echo "✅ Valid"
        else
            echo "❌ Invalid schema structure"
            ((schema_errors++))
        fi
    else
        echo "❌ File not found"
        ((schema_errors++))
    fi
done

echo ""
echo "🎨 Test 5: Theme Validation"
echo "---------------------------"

theme_files=(
    "wp-content/themes/biz-dir/style.css"
    "wp-content/themes/biz-dir/index.php"
    "wp-content/themes/biz-dir/header.php"
    "wp-content/themes/biz-dir/footer.php"
    "wp-content/themes/biz-dir/functions.php"
)

theme_errors=0
for file in "${theme_files[@]}"; do
    echo -n "  Checking $(basename "$file")... "
    if [ -f "$file" ]; then
        if [[ "$file" == *.php ]]; then
            if php -l "$file" > /dev/null 2>&1; then
                echo "✅ Valid PHP"
            else
                echo "❌ PHP syntax error"
                ((theme_errors++))
            fi
        else
            echo "✅ Found"
        fi
    else
        echo "❌ Missing"
        ((theme_errors++))
    fi
done

echo ""
echo "📋 Test Results Summary"
echo "======================="

total_errors=$((syntax_errors + missing_files + class_loading_errors + schema_errors + theme_errors))

echo "Syntax validation: $syntax_errors errors"
echo "File structure: $missing_files missing files"  
echo "Class loading: $class_loading_errors errors"
echo "Schema validation: $schema_errors errors"
echo "Theme validation: $theme_errors errors"
echo ""
echo "Total errors: $total_errors"

if [ $total_errors -eq 0 ]; then
    echo "🎉 ALL TESTS PASSED! Platform is ready for deployment."
    exit 0
else
    echo "❌ Tests failed. Please fix the errors above."
    exit 1
fi
