<?php
/**
 * Dependency Diagnostic Test
 * Systematic analysis of mock framework loading
 * Following knowledge tracker methodology for thorough analysis
 */

echo "=== BizDir Mock Framework Dependency Diagnostic ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: Environment Validation
echo "STEP 1: Environment Validation\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current Directory: " . getcwd() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n\n";

// Step 2: File Existence Verification
echo "STEP 2: File Existence Verification\n";
$required_files = [
    'tests/bootstrap-mock.php',
    'tests/mocks/WP_UnitTestCase.php',
    'tests/mocks/WP_UnitTest_Factory.php',
    'tests/Regression/RegressionTestCase.php',
    'tests/Regression/AuthSecurityRegressionTest.php'
];

foreach ($required_files as $file) {
    $exists = file_exists($file) ? "✅ EXISTS" : "❌ MISSING";
    echo "  {$file}: {$exists}\n";
}
echo "\n";

// Step 3: Bootstrap Loading Analysis
echo "STEP 3: Bootstrap Loading Analysis\n";
try {
    echo "Loading bootstrap-mock.php...\n";
    require_once 'tests/bootstrap-mock.php';
    echo "✅ Bootstrap loaded successfully\n";
    
    // Check for MockWPDB class
    if (class_exists('MockWPDB')) {
        echo "✅ MockWPDB class available\n";
    } else {
        echo "❌ MockWPDB class NOT available\n";
    }
    
    // Check for WP_User class
    if (class_exists('WP_User')) {
        echo "✅ WP_User class available\n";
    } else {
        echo "❌ WP_User class NOT available\n";
    }
    
    // Check for key functions
    $required_functions = [
        'wp_generate_password',
        'wp_hash_password',
        'get_user_by',
        'wp_create_user',
        'wp_verify_nonce'
    ];
    
    echo "\nFunction Availability Check:\n";
    foreach ($required_functions as $func) {
        $available = function_exists($func) ? "✅ AVAILABLE" : "❌ MISSING";
        echo "  {$func}(): {$available}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Bootstrap loading failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Step 4: Factory Loading Analysis
echo "STEP 4: Factory Loading Analysis\n";
try {
    echo "Loading WP_UnitTest_Factory.php...\n";
    require_once 'tests/mocks/WP_UnitTest_Factory.php';
    echo "✅ Factory file loaded successfully\n";
    
    // Check factory classes
    $factory_classes = [
        'WP_UnitTest_Factory_Mock',
        'WP_UnitTest_Factory_For_User_Mock',
        'WP_UnitTest_Factory_For_Post_Mock'
    ];
    
    foreach ($factory_classes as $class) {
        $available = class_exists($class) ? "✅ AVAILABLE" : "❌ MISSING";
        echo "  {$class}: {$available}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Factory loading failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Step 5: WP_UnitTestCase Analysis
echo "STEP 5: WP_UnitTestCase Analysis\n";
try {
    echo "Loading WP_UnitTestCase.php...\n";
    require_once 'tests/mocks/WP_UnitTestCase.php';
    echo "✅ WP_UnitTestCase loaded successfully\n";
    
    if (class_exists('WP_UnitTestCase')) {
        echo "✅ WP_UnitTestCase class available\n";
        
        // Check class methods
        $reflection = new ReflectionClass('WP_UnitTestCase');
        echo "  Methods available: " . count($reflection->getMethods()) . "\n";
        
    } else {
        echo "❌ WP_UnitTestCase class NOT available\n";
    }
    
} catch (Exception $e) {
    echo "❌ WP_UnitTestCase loading failed: " . $e->getMessage() . "\n";
    echo "  Error details: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
echo "\n";

// Step 6: Memory and Performance Analysis
echo "STEP 6: Performance Analysis\n";
echo "Memory Usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";
echo "Peak Memory: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
echo "Execution Time: " . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . " ms\n\n";

echo "=== Diagnostic Complete ===\n";
echo "Refer to KNOWLEDGE_TRACKER.md for resolution patterns\n";
