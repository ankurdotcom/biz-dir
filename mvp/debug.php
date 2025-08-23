<?php
/**
 * WordPress Debug Script
 * Quick debugging to identify the exact issue
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== WordPress Debug Script ===" . PHP_EOL;
echo "PHP Version: " . phpversion() . PHP_EOL;
echo "Memory Limit: " . ini_get('memory_limit') . PHP_EOL;
echo "Max Execution Time: " . ini_get('max_execution_time') . PHP_EOL;

// Test database connection
echo "\n--- Database Connection Test ---" . PHP_EOL;
$host = 'db';
$username = 'bizdir';
$password = 'bizdir123';
$database = 'bizdir_dev';

try {
    $mysqli = new mysqli($host, $username, $password, $database);
    if ($mysqli->connect_error) {
        echo "❌ Database connection failed: " . $mysqli->connect_error . PHP_EOL;
    } else {
        echo "✅ Database connection successful" . PHP_EOL;
        
        // Test a simple query
        $result = $mysqli->query("SELECT COUNT(*) as count FROM wp_options");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ wp_options table accessible, {$row['count']} records" . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "❌ Database exception: " . $e->getMessage() . PHP_EOL;
}

// Test wp-config.php loading
echo "\n--- WordPress Config Test ---" . PHP_EOL;
try {
    if (file_exists('/var/www/html/wp-config.php')) {
        echo "✅ wp-config.php exists" . PHP_EOL;
        
        // Set basic server variables for WordPress
        $_SERVER['HTTP_HOST'] = 'localhost:8888';
        $_SERVER['REQUEST_URI'] = '/debug.php';
        $_SERVER['SCRIPT_NAME'] = '/debug.php';
        
        // Try to load WordPress constants only
        $config_content = file_get_contents('/var/www/html/wp-config.php');
        if (strpos($config_content, 'DB_NAME') !== false) {
            echo "✅ Database constants found in wp-config.php" . PHP_EOL;
        } else {
            echo "❌ Database constants missing in wp-config.php" . PHP_EOL;
        }
        
    } else {
        echo "❌ wp-config.php not found" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Config loading error: " . $e->getMessage() . PHP_EOL;
}

// Test basic WordPress loading
echo "\n--- WordPress Loading Test ---" . PHP_EOL;
try {
    // Minimal WordPress loading
    define('SHORTINIT', true);
    require_once('/var/www/html/wp-config.php');
    echo "✅ WordPress config loaded successfully" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ WordPress loading error: " . $e->getMessage() . PHP_EOL;
} catch (Error $e) {
    echo "❌ PHP Error during WordPress loading: " . $e->getMessage() . PHP_EOL;
}

echo "\n=== Debug Complete ===" . PHP_EOL;
?>
