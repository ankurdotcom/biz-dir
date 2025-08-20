<?php
/**
 * Base Test Case
 *
 * @package BizDir\Tests
 */

namespace BizDir\Tests;

use WP_UnitTestCase;

class Base_Test_Case extends WP_UnitTestCase {
    protected $autoloader;

    public function setUp(): void {
        error_log("\n[TEST START] ==========================================");
        error_log("[TEST INFO] Running: " . get_class($this) . "::" . $this->getName());
        
        parent::setUp();
        
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES");
        
        error_log("[DB INFO] Database host: " . DB_HOST);
        error_log("[DB INFO] Database name: " . DB_NAME);
        error_log("[DB INFO] Database prefix: " . $wpdb->prefix);
        error_log("[DB INFO] Tables found: " . count($tables));
        error_log("[DB INFO] Table list: " . implode(', ', $tables));
        
        // Re-initialize schema if needed
        $reputation_table = $wpdb->prefix . 'biz_user_reputation';
        if (!in_array($reputation_table, $tables)) {
            error_log("[SCHEMA] Reputation table '$reputation_table' not found");
            error_log("[SCHEMA] Attempting to recreate schema...");
            
            // Load plugin file for constants
            $plugin_file = dirname(dirname(__FILE__)) . '/wp-content/plugins/biz-dir-core/biz-dir-core.php';
            error_log("[SCHEMA] Loading plugin file: $plugin_file");
            require_once $plugin_file;
            
            // Load and execute schema
            $schema_file = dirname(dirname(__FILE__)) . '/config/schema.sql';
            error_log("[SCHEMA] Loading schema from: $schema_file");
            if (!file_exists($schema_file)) {
                error_log("[SCHEMA ERROR] Schema file not found!");
                throw new \RuntimeException("Schema file not found at: $schema_file");
            }
            
            $schema_sql = str_replace('{prefix}', $wpdb->prefix, file_get_contents($schema_file));
            $result = $wpdb->query($schema_sql);
            
            if ($result === false) {
                error_log("[SCHEMA ERROR] Failed to execute schema: " . $wpdb->last_error);
                throw new \RuntimeException("Schema creation failed: " . $wpdb->last_error);
            }
            error_log("[SCHEMA] Schema created successfully");
        }
    }

    public function tearDown(): void {
        error_log("[CLEANUP] Starting cleanup for: " . get_class($this) . "::" . $this->getName());
        
        // Log any warnings or notices that occurred during the test
        $errors = error_get_last();
        if ($errors) {
            error_log("[TEST WARNINGS] Last error: " . json_encode($errors));
        }
        
        // Get memory usage
        $memory = memory_get_usage(true);
        $peak_memory = memory_get_peak_usage(true);
        error_log(sprintf("[MEMORY] Current: %.2f MB, Peak: %.2f MB", 
            $memory / 1024 / 1024,
            $peak_memory / 1024 / 1024
        ));
        
        parent::tearDown();
        error_log("[TEST END] ============================================\n");
    }
}
