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
        
        // Get the class name without namespace
        $class_name = str_replace('BizDir\\Tests\\', '', $class);
        
        // Try exact case first
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . $class_name . '.php';
        error_log("Attempting to load test class: $class from $file");
        
        if (file_exists($file)) {
            require_once $file;
            error_log("Successfully loaded test class: $class");
            return true;
        }
        
        error_log("Could not find test class file: $file");
        return false;
    });
    _biz_dir_debug('Plugin file loaded successfully');
    
    // Initialize database schemas
    _biz_dir_debug('Initializing database schemas...');
    _biz_dir_install_schema();
    _biz_dir_install_monetization_schema();
    _biz_dir_install_analytics_schema();
    _biz_dir_debug('Database schemas initialized');
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

    // Pre-cleanup: Drop existing tables in correct order to prevent foreign key constraint errors
    $tables_to_drop = array(
        'biz_analytics_metrics',
        'biz_analytics_searches',
        'biz_views',
        'biz_interactions',
        'biz_subscription_features',
        'biz_subscriptions',
        'biz_payments',
        'biz_user_reputation',
        'biz_moderation_queue',
        'biz_reviews',
        'biz_tags',
        'biz_businesses',
        'biz_towns'
    );

    foreach ($tables_to_drop as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        _biz_dir_debug("Dropped table if exists: {$wpdb->prefix}{$table}");
    }

    // Create tables in proper order
    $create_tables = array(
        'biz_towns',
        'biz_businesses',
        'biz_reviews',
        'biz_tags',
        'biz_moderation_queue',
        'biz_user_reputation'
    );

    foreach ($create_tables as $table) {
        // Extract CREATE TABLE statement for this table
        $pattern = "/CREATE TABLE IF NOT EXISTS `{prefix}{$table}`[^;]+;/s";
        if (preg_match($pattern, $schema_sql, $matches)) {
            $create_sql = $matches[0];
            $create_sql = str_replace('{prefix}', $wpdb->prefix, $create_sql);
            
            _biz_dir_debug("Creating table: {$wpdb->prefix}{$table}", 'INFO', [
                'sql' => $create_sql
            ]);

            $result = $wpdb->query($create_sql);
            
            if ($result === false) {
                _biz_dir_debug("Error creating table {$wpdb->prefix}{$table}", 'ERROR', [
                    'error' => $wpdb->last_error,
                    'sql' => $create_sql
                ]);
                throw new RuntimeException("Failed to create table {$wpdb->prefix}{$table}: " . $wpdb->last_error);
            }

            // Verify table structure
            $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}{$table}");
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            
            _biz_dir_debug("Created table {$wpdb->prefix}{$table}", 'SUCCESS', [
                'columns' => $column_names
            ]);
        } else {
            _biz_dir_debug("Could not find CREATE TABLE statement for {$table}", 'ERROR');
            throw new RuntimeException("Could not find CREATE TABLE statement for {$table}");
        }
    }

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
            
            // Get table name from statement
            if (preg_match('/CREATE TABLE IF NOT EXISTS \`([^\`]+)\`/', $statement, $matches)) {
                $table_name = $matches[1];
                _biz_dir_debug("Creating/updating table: $table_name", 'INFO', [
                    'statement' => $statement
                ]);
                
                // Drop table first to ensure clean creation
                $wpdb->query("DROP TABLE IF EXISTS `$table_name`");
                
                $result = $wpdb->query($statement);
                $execution_time = microtime(true) - $start_time;
                
                if ($result === false) {
                    _biz_dir_debug("Failed to create table: $table_name", 'ERROR', [
                        'error' => $wpdb->last_error,
                        'statement' => $statement
                    ]);
                } else {
                    // Verify table structure
                    $show_create = $wpdb->get_row("SHOW CREATE TABLE `$table_name`", ARRAY_A);
                    $columns = $wpdb->get_col("SHOW COLUMNS FROM `$table_name`");
                    
                    _biz_dir_debug("Table $table_name created successfully", 'SUCCESS', [
                        'create_statement' => $show_create['Create Table'],
                        'columns' => $columns
                    ]);
                }
            } else {
                $result = $wpdb->query($statement);
                $execution_time = microtime(true) - $start_time;
                
                if ($result === false) {
                    _biz_dir_debug('Failed to execute SQL statement', 'ERROR', [
                        'error' => $wpdb->last_error,
                        'statement' => $statement
                    ]);
                }
            }
            
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

// Function to install monetization schema
function _biz_dir_install_monetization_schema() {
    global $wpdb;
    
    _biz_dir_debug('Starting monetization schema installation...');

    // Drop monetization tables in correct order
    $monetization_tables = array(
        'biz_subscription_features',
        'biz_subscriptions', 
        'biz_payments'
    );

    foreach ($monetization_tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        _biz_dir_debug("Dropped monetization table {$wpdb->prefix}{$table}");
    }

    $schema_file = dirname(dirname(__FILE__)) . '/config/monetization_schema.sql';
    if (!file_exists($schema_file)) {
        _biz_dir_debug('Monetization schema file not found', 'ERROR');
        throw new RuntimeException('Monetization schema file not found at: ' . $schema_file);
    }

    $schema_sql = file_get_contents($schema_file);
    $schema_sql = str_replace('{prefix}', $wpdb->prefix, $schema_sql);

    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema_sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if ($wpdb->query($statement) === false) {
                _biz_dir_debug('Failed to execute monetization SQL', 'ERROR', [
                    'error' => $wpdb->last_error,
                    'statement' => $statement
                ]);
                throw new RuntimeException('Failed to execute monetization SQL: ' . $wpdb->last_error);
            }
        }
    }

    _biz_dir_debug('Monetization schema installed successfully');
}

// Function to install analytics schema
function _biz_dir_install_analytics_schema() {
    global $wpdb;
    
    _biz_dir_debug('Starting analytics schema installation...');

    // Drop analytics tables in correct order
    $analytics_tables = array(
        'biz_analytics_metrics',
        'biz_analytics_searches',
        'biz_views',
        'biz_interactions'
    );

    foreach ($analytics_tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        _biz_dir_debug("Dropped analytics table {$wpdb->prefix}{$table}");
    }

    $schema_file = dirname(dirname(__FILE__)) . '/config/analytics_schema.sql';
    if (!file_exists($schema_file)) {
        _biz_dir_debug('Analytics schema file not found', 'ERROR');
        throw new RuntimeException('Analytics schema file not found at: ' . $schema_file);
    }

    $schema_sql = file_get_contents($schema_file);
    $schema_sql = str_replace('{prefix}', $wpdb->prefix, $schema_sql);

    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema_sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if ($wpdb->query($statement) === false) {
                _biz_dir_debug('Failed to execute analytics SQL', 'ERROR', [
                    'error' => $wpdb->last_error,
                    'statement' => $statement
                ]);
                throw new RuntimeException('Failed to execute analytics SQL: ' . $wpdb->last_error);
            }
        }
    }

    _biz_dir_debug('Analytics schema installed successfully');
}

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
