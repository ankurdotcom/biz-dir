<?php
/**
 * Full WordPress Loading Debug
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Full WordPress Loading Debug ===" . PHP_EOL;

// Set up proper server environment
$_SERVER['HTTP_HOST'] = 'localhost:8888';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '8888';

try {
    echo "Loading WordPress without SHORTINIT..." . PHP_EOL;
    
    // Load wp-config first
    require_once('/var/www/html/wp-config.php');
    echo "✅ wp-config.php loaded" . PHP_EOL;
    
    // This should trigger the full WordPress loading
    require_once('/var/www/html/wp-settings.php');
    echo "✅ wp-settings.php loaded" . PHP_EOL;
    
    echo "✅ Full WordPress loaded successfully!" . PHP_EOL;
    echo "Site URL: " . get_option('siteurl') . PHP_EOL;
    echo "Home URL: " . get_option('home') . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
} catch (Error $e) {
    echo "❌ PHP Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
} catch (ParseError $e) {
    echo "❌ Parse Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
}

echo "=== Debug Complete ===" . PHP_EOL;
?>
