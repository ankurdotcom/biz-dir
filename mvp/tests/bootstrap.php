<?php
/**
 * PHPUnit bootstrap file
 */

$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
    exit(1);
}

// Load core test infrastructure
require_once $_tests_dir . '/includes/functions.php';

/**
 * Enhanced debug function for test environment
 * 
 * @param string $message The message to log
 * @param string $level The log level (INFO, DEBUG, ERROR, WARN)
 * @param array $context Additional context data to log
 */
function _biz_dir_debug($message, $level = 'INFO', $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $pid = getmypid();
    $memory = sprintf('%.2fMB', memory_get_usage(true) / 1024 / 1024);
    
    $context_str = empty($context) ? '' : ' | ' . json_encode($context);
    $log_message = sprintf(
        '[%s][PID:%d][MEM:%s][%s] %s%s',
        $timestamp,
        $pid,
        $memory,
        $level,
        $message,
        $context_str
    );
    
    error_log($log_message);
}

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    _biz_dir_debug('Starting plugin load sequence...');
    
    // First, load the main plugin file to define constants
    $plugin_file = dirname(dirname(__FILE__)) . '/wp-content/plugins/biz-dir-core/biz-dir-core.php';
    _biz_dir_debug('Loading plugin from: ' . $plugin_file);
    
    if (!file_exists($plugin_file)) {
        _biz_dir_debug('ERROR: Plugin file not found!');
        throw new RuntimeException('Plugin file not found at: ' . $plugin_file);
    }
    
    require_once $plugin_file;
    _biz_dir_debug('Plugin file loaded successfully with constants:');
    _biz_dir_debug('- BIZ_DIR_PLUGIN_DIR = ' . BIZ_DIR_PLUGIN_DIR);
    
    // Now load and register the autoloader
    $autoloader_path = BIZ_DIR_PLUGIN_DIR . 'includes/class-autoloader.php';
    _biz_dir_debug('Loading autoloader from: ' . $autoloader_path);
    
    if (!file_exists($autoloader_path)) {
        _biz_dir_debug('ERROR: Autoloader file not found!');
        throw new RuntimeException('Autoloader file not found at: ' . $autoloader_path);
    }
    
    require_once $autoloader_path;
    _biz_dir_debug('Autoloader file loaded successfully');
    
    $loader = new \BizDir\Core\Autoloader();
    $loader->register();
    _biz_dir_debug('Autoloader registered successfully');

    // Register test namespace
    spl_autoload_register(function($class) {
        if (strpos($class, 'BizDir\\Tests\\') !== 0) {
            return;
        }
        $path = str_replace(
            ['BizDir\\Tests\\', '\\'],
            ['', DIRECTORY_SEPARATOR],
            $class
        );
        $path = str_replace('_', '-', strtolower($path));
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . $path . '.php';
        error_log("Attempting to load test class: $class from $file");
        if (file_exists($file)) {
            require_once $file;
            error_log("Successfully loaded test class: $class");
            return true;
        }
        error_log("Could not find test class file: $file");
    });
    _biz_dir_debug('Plugin file loaded successfully');
    
    // Initialize database schema
    _biz_dir_debug('Initializing database schema...');
    _biz_dir_install_schema();
    _biz_dir_debug('Database schema initialized');
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Database initialization function
function _biz_dir_install_schema() {
    global $wpdb;
    
    _biz_dir_debug('Starting database schema installation...', 'INFO', [
        'php_version' => PHP_VERSION,
        'mysql_version' => $wpdb->db_version(),
        'wp_version' => $GLOBALS['wp_version']
    ]);

    $schema_file = dirname(dirname(__FILE__)) . '/config/schema.sql';
    _biz_dir_debug('Loading schema file', 'INFO', ['path' => $schema_file]);

    if (!file_exists($schema_file)) {
        _biz_dir_debug('Schema file not found', 'ERROR', [
            'path' => $schema_file,
            'searched_paths' => [
                'cwd' => getcwd(),
                'script_dir' => __DIR__,
                'absolute_path' => realpath($schema_file)
            ]
        ]);
        throw new RuntimeException('Schema file not found at: ' . $schema_file);
    }

    $schema_sql = file_get_contents($schema_file);
    _biz_dir_debug('Schema file loaded', 'INFO', [
        'size' => strlen($schema_sql),
        'md5' => md5($schema_sql)
    ]);

    // Replace {prefix} with actual test prefix
    _biz_dir_debug('Using database prefix: ' . $wpdb->prefix);
    $schema_sql = str_replace('{prefix}', $wpdb->prefix, $schema_sql);

    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $schema_sql))
    );

    _biz_dir_debug('Found ' . count($statements) . ' SQL statements to execute');

    // Execute each statement
    foreach ($statements as $index => $statement) {
        if (!empty($statement)) {
            $start_time = microtime(true);
            
            _biz_dir_debug('Executing SQL statement', 'INFO', [
                'statement_number' => $index + 1,
                'total_statements' => count($statements),
                'statement_length' => strlen($statement)
            ]);
            
            $result = $wpdb->query($statement);
            $execution_time = microtime(true) - $start_time;
            
            if ($result === false) {
                _biz_dir_debug('SQL execution failed', 'ERROR', [
                    'statement_number' => $index + 1,
                    'error' => $wpdb->last_error,
                    'error_number' => $wpdb->last_error ? $wpdb->errno : null,
                    'execution_time' => round($execution_time, 4)
                ]);
            } else {
                _biz_dir_debug('SQL executed successfully', 'INFO', [
                    'statement_number' => $index + 1,
                    'affected_rows' => $wpdb->rows_affected,
                    'execution_time' => round($execution_time, 4)
                ]);
            }
        }
    }
}

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
