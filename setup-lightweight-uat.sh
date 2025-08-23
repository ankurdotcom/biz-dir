#!/bin/bash

# Lightweight UAT Testing Setup for BizDir
# Using PHP Built-in Server for Rapid Testing
# Version: 1.0
# Date: August 23, 2025

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE} BizDir Lightweight UAT Environment Setup${NC}"
echo -e "${BLUE}==================================================${NC}"
echo ""

# Configuration
UAT_PORT=8080
UAT_DIR="/tmp/bizdir-uat"
CURRENT_DIR=$(pwd)

echo -e "${BLUE}🚀 Setting up lightweight UAT environment...${NC}"

# Create UAT directory structure
setup_uat_directory() {
    echo -e "${BLUE}📁 Creating UAT directory structure...${NC}"
    
    rm -rf "$UAT_DIR"
    mkdir -p "$UAT_DIR"
    
    # Create basic WordPress structure for testing
    mkdir -p "$UAT_DIR/wp-content/plugins"
    mkdir -p "$UAT_DIR/wp-content/themes"
    mkdir -p "$UAT_DIR/wp-admin"
    mkdir -p "$UAT_DIR/wp-includes"
    
    echo -e "${GREEN}✅ UAT directory structure created${NC}"
}

# Copy BizDir files
copy_bizdir_files() {
    echo -e "${BLUE}📂 Copying BizDir files to UAT environment...${NC}"
    
    # Copy plugin files
    if [ -d "$CURRENT_DIR/mvp/wp-content/plugins/biz-dir-core" ]; then
        cp -r "$CURRENT_DIR/mvp/wp-content/plugins/biz-dir-core" "$UAT_DIR/wp-content/plugins/"
        echo -e "${GREEN}✅ Plugin files copied${NC}"
    fi
    
    # Copy theme files if they exist
    if [ -d "$CURRENT_DIR/mvp/wp-content/themes/biz-dir" ]; then
        cp -r "$CURRENT_DIR/mvp/wp-content/themes/biz-dir" "$UAT_DIR/wp-content/themes/"
        echo -e "${GREEN}✅ Theme files copied${NC}"
    fi
    
    # Copy configuration files
    if [ -d "$CURRENT_DIR/mvp/config" ]; then
        cp -r "$CURRENT_DIR/mvp/config" "$UAT_DIR/"
        echo -e "${GREEN}✅ Configuration files copied${NC}"
    fi
}

# Create minimal WordPress mock for testing
create_wp_mock() {
    echo -e "${BLUE}📱 Creating WordPress testing mock...${NC}"
    
    # Create index.php
    cat > "$UAT_DIR/index.php" <<'EOF'
<?php
// BizDir UAT Testing Mock
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Mock WordPress environment
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Load WordPress functions mock
require_once __DIR__ . '/wp-includes/functions.php';

// Load BizDir plugin
if (file_exists(__DIR__ . '/wp-content/plugins/biz-dir-core/biz-dir-core.php')) {
    require_once __DIR__ . '/wp-content/plugins/biz-dir-core/biz-dir-core.php';
}

echo "<h1>BizDir UAT Testing Environment</h1>";
echo "<p>Environment Status: <strong style='color: green;'>Active</strong></p>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";

// Check if plugin directory exists
if (is_dir(__DIR__ . '/wp-content/plugins/biz-dir-core')) {
    echo "<p>BizDir Plugin: <strong style='color: green;'>Found</strong></p>";
    
    // List plugin files
    $plugin_files = glob(__DIR__ . '/wp-content/plugins/biz-dir-core/includes/*/*.php');
    echo "<p>Plugin Files: " . count($plugin_files) . " PHP files</p>";
    
    echo "<h2>Plugin Structure:</h2>";
    echo "<ul>";
    foreach ($plugin_files as $file) {
        $relative_path = str_replace(__DIR__ . '/wp-content/plugins/biz-dir-core/', '', $file);
        echo "<li>" . htmlspecialchars($relative_path) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>BizDir Plugin: <strong style='color: red;'>Not Found</strong></p>";
}

// Test basic PHP functionality
echo "<h2>PHP Tests:</h2>";
echo "<ul>";
echo "<li>JSON Support: " . (function_exists('json_encode') ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') . "</li>";
echo "<li>cURL Support: " . (function_exists('curl_init') ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') . "</li>";
echo "<li>MySQLi Support: " . (extension_loaded('mysqli') ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') . "</li>";
echo "<li>OpenSSL Support: " . (extension_loaded('openssl') ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') . "</li>";
echo "</ul>";

// Configuration test
if (file_exists(__DIR__ . '/config')) {
    echo "<h2>Configuration Files:</h2>";
    $config_files = glob(__DIR__ . '/config/*.sql');
    echo "<ul>";
    foreach ($config_files as $file) {
        $filename = basename($file);
        $size = filesize($file);
        echo "<li>{$filename} ({$size} bytes)</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><strong>UAT Environment Ready for Testing</strong></p>";
echo "<p>Use this environment to test BizDir functionality without full WordPress installation.</p>";
?>
EOF

    # Create minimal WordPress functions mock
    mkdir -p "$UAT_DIR/wp-includes"
    cat > "$UAT_DIR/wp-includes/functions.php" <<'EOF'
<?php
// Minimal WordPress functions mock for UAT testing

// Mock WordPress constants
if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', __DIR__ . '/../wp-content');
}

// Mock WordPress functions
function add_action($hook, $function_to_add, $priority = 10, $accepted_args = 1) {
    // Mock implementation for testing
    return true;
}

function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    // Mock implementation for testing
    return true;
}

function plugin_dir_path($file) {
    return dirname($file) . '/';
}

function plugin_dir_url($file) {
    return 'http://localhost:8080/wp-content/plugins/' . basename(dirname($file)) . '/';
}

function wp_die($message = '', $title = '', $args = array()) {
    die($message);
}

function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function sanitize_text_field($str) {
    return trim(strip_tags($str));
}

// Mock WordPress error logging
function error_log($message) {
    $log_file = __DIR__ . '/../debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

echo "<!-- WordPress functions mock loaded for UAT testing -->\n";
?>
EOF

    echo -e "${GREEN}✅ WordPress testing mock created${NC}"
}

# Create test runner script
create_test_runner() {
    echo -e "${BLUE}🧪 Creating UAT test runner...${NC}"
    
    cat > "$UAT_DIR/test-runner.php" <<'EOF'
<?php
// BizDir UAT Test Runner
error_reporting(E_ALL);
ini_set('display_errors', 1);

class UATTestRunner {
    private $tests_passed = 0;
    private $tests_failed = 0;
    private $test_results = [];
    
    public function run_all_tests() {
        echo "<h1>BizDir UAT Test Suite</h1>";
        echo "<p>Running comprehensive UAT tests...</p>";
        
        $this->test_file_structure();
        $this->test_php_syntax();
        $this->test_plugin_loading();
        $this->test_configuration_files();
        $this->test_security_basics();
        
        $this->display_results();
    }
    
    private function test_file_structure() {
        echo "<h2>File Structure Tests</h2>";
        
        $required_files = [
            'wp-content/plugins/biz-dir-core/biz-dir-core.php',
            'wp-content/plugins/biz-dir-core/includes',
            'config'
        ];
        
        foreach ($required_files as $file) {
            $exists = file_exists(__DIR__ . '/' . $file);
            $this->log_test("File/Directory Exists: $file", $exists);
        }
    }
    
    private function test_php_syntax() {
        echo "<h2>PHP Syntax Tests</h2>";
        
        $php_files = glob(__DIR__ . '/wp-content/plugins/biz-dir-core/includes/*/*.php');
        $syntax_errors = 0;
        
        foreach ($php_files as $file) {
            $output = shell_exec("php -l '$file' 2>&1");
            $has_syntax_error = strpos($output, 'Parse error') !== false;
            
            if ($has_syntax_error) {
                $syntax_errors++;
                $this->log_test("PHP Syntax: " . basename($file), false, $output);
            }
        }
        
        $this->log_test("Overall PHP Syntax Check", $syntax_errors === 0, 
                       $syntax_errors === 0 ? "All files passed" : "$syntax_errors files have syntax errors");
    }
    
    private function test_plugin_loading() {
        echo "<h2>Plugin Loading Tests</h2>";
        
        $plugin_file = __DIR__ . '/wp-content/plugins/biz-dir-core/biz-dir-core.php';
        if (file_exists($plugin_file)) {
            $content = file_get_contents($plugin_file);
            
            // Test plugin header
            $has_plugin_header = strpos($content, 'Plugin Name:') !== false;
            $this->log_test("Plugin Header Present", $has_plugin_header);
            
            // Test autoloader
            $has_autoloader = strpos($content, 'autoloader') !== false || strpos($content, 'Autoloader') !== false;
            $this->log_test("Autoloader Reference Found", $has_autoloader);
            
            // Test initialization
            $has_init = strpos($content, 'init') !== false;
            $this->log_test("Initialization Code Found", $has_init);
        } else {
            $this->log_test("Plugin File Exists", false);
        }
    }
    
    private function test_configuration_files() {
        echo "<h2>Configuration Tests</h2>";
        
        $config_files = ['schema.sql', 'monetization_schema.sql', 'analytics_schema.sql'];
        
        foreach ($config_files as $file) {
            $file_path = __DIR__ . '/config/' . $file;
            $exists = file_exists($file_path);
            $this->log_test("Config File: $file", $exists);
            
            if ($exists) {
                $size = filesize($file_path);
                $this->log_test("Config File Size: $file", $size > 0, "$size bytes");
            }
        }
    }
    
    private function test_security_basics() {
        echo "<h2>Security Tests</h2>";
        
        // Test for potential security issues in PHP files
        $php_files = glob(__DIR__ . '/wp-content/plugins/biz-dir-core/includes/*/*.php');
        $security_issues = 0;
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            
            // Check for direct access protection
            if (strpos($content, 'ABSPATH') === false && strpos($content, 'defined') === false) {
                $security_issues++;
            }
        }
        
        $this->log_test("Direct Access Protection", $security_issues === 0, 
                       $security_issues === 0 ? "All files protected" : "$security_issues files lack protection");
    }
    
    private function log_test($test_name, $passed, $details = '') {
        if ($passed) {
            $this->tests_passed++;
            echo "<p style='color: green;'>✓ $test_name: PASSED";
        } else {
            $this->tests_failed++;
            echo "<p style='color: red;'>✗ $test_name: FAILED";
        }
        
        if ($details) {
            echo " - $details";
        }
        echo "</p>";
        
        $this->test_results[] = [
            'name' => $test_name,
            'passed' => $passed,
            'details' => $details
        ];
    }
    
    private function display_results() {
        echo "<hr>";
        echo "<h2>Test Summary</h2>";
        echo "<p><strong>Total Tests:</strong> " . ($this->tests_passed + $this->tests_failed) . "</p>";
        echo "<p><strong>Passed:</strong> <span style='color: green;'>{$this->tests_passed}</span></p>";
        echo "<p><strong>Failed:</strong> <span style='color: red;'>{$this->tests_failed}</span></p>";
        
        $pass_rate = $this->tests_passed + $this->tests_failed > 0 ? 
                     round(($this->tests_passed / ($this->tests_passed + $this->tests_failed)) * 100, 1) : 0;
        echo "<p><strong>Pass Rate:</strong> {$pass_rate}%</p>";
        
        if ($this->tests_failed === 0) {
            echo "<p style='color: green; font-weight: bold;'>🎉 All tests passed! UAT environment is ready.</p>";
        } else {
            echo "<p style='color: orange; font-weight: bold;'>⚠️ Some tests failed. Review results before proceeding.</p>";
        }
    }
}

// Run tests
$runner = new UATTestRunner();
$runner->run_all_tests();
?>
EOF

    echo -e "${GREEN}✅ UAT test runner created${NC}"
}

# Start PHP server
start_php_server() {
    echo -e "${BLUE}🌐 Starting PHP development server for UAT...${NC}"
    
    cd "$UAT_DIR"
    
    # Check if port is available
    if lsof -i:$UAT_PORT &>/dev/null; then
        echo -e "${YELLOW}⚠️ Port $UAT_PORT is already in use${NC}"
        # Find alternative port
        UAT_PORT=8081
        if lsof -i:$UAT_PORT &>/dev/null; then
            UAT_PORT=8082
        fi
        echo -e "${YELLOW}Using alternative port: $UAT_PORT${NC}"
    fi
    
    echo -e "${GREEN}✅ Starting server on http://localhost:$UAT_PORT${NC}"
    echo -e "${YELLOW}Press Ctrl+C to stop the server${NC}"
    echo ""
    
    # Start PHP server in background and save PID
    php -S localhost:$UAT_PORT > /tmp/uat-server.log 2>&1 &
    SERVER_PID=$!
    echo $SERVER_PID > /tmp/uat-server.pid
    
    # Wait a moment for server to start
    sleep 2
    
    echo -e "${GREEN}✅ UAT server started (PID: $SERVER_PID)${NC}"
    echo -e "${BLUE}Access URLs:${NC}"
    echo -e "  • Main UAT Environment: http://localhost:$UAT_PORT"
    echo -e "  • Test Runner: http://localhost:$UAT_PORT/test-runner.php"
    echo ""
}

# Test connectivity
test_connectivity() {
    echo -e "${BLUE}🔍 Testing UAT environment connectivity...${NC}"
    
    # Wait for server to be ready
    sleep 3
    
    if curl -s "http://localhost:$UAT_PORT" > /dev/null; then
        echo -e "${GREEN}✅ UAT environment is accessible${NC}"
        return 0
    else
        echo -e "${RED}❌ UAT environment is not accessible${NC}"
        return 1
    fi
}

# Display summary
display_summary() {
    echo ""
    echo -e "${BLUE}==================================================${NC}"
    echo -e "${GREEN}🎉 Lightweight UAT Environment Setup Complete!${NC}"
    echo -e "${BLUE}==================================================${NC}"
    echo ""
    echo -e "${YELLOW}UAT Environment Details:${NC}"
    echo -e "Port: $UAT_PORT"
    echo -e "Directory: $UAT_DIR"
    echo -e "Server PID: $(cat /tmp/uat-server.pid 2>/dev/null || echo 'Not found')"
    echo ""
    echo -e "${YELLOW}Access URLs:${NC}"
    echo -e "• Main Environment: http://localhost:$UAT_PORT"
    echo -e "• Test Runner: http://localhost:$UAT_PORT/test-runner.php"
    echo ""
    echo -e "${YELLOW}Next Steps:${NC}"
    echo -e "1. Open http://localhost:$UAT_PORT in your browser"
    echo -e "2. Run tests at http://localhost:$UAT_PORT/test-runner.php"
    echo -e "3. Review test results and fix any issues"
    echo -e "4. Stop server: kill \$(cat /tmp/uat-server.pid)"
    echo ""
    echo -e "${GREEN}Happy Testing! 🚀${NC}"
}

# Main execution
main() {
    setup_uat_directory
    copy_bizdir_files
    create_wp_mock
    create_test_runner
    start_php_server
    
    if test_connectivity; then
        display_summary
    else
        echo -e "${RED}❌ Failed to start UAT environment${NC}"
        exit 1
    fi
}

# Run setup
main
