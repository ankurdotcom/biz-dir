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
        
        // Re-initialize schema before each test
        error_log("[SCHEMA] Recreating schema tables...");
        
        // Disable foreign key checks temporarily
        $wpdb->query('SET autocommit=0');
        $wpdb->query('SET FOREIGN_KEY_CHECKS=0');
        $wpdb->query('START TRANSACTION');
        
        try {
            // Drop existing tables in reverse order of dependencies
            $biz_tables = [
                'biz_moderation_queue',
                'biz_reviews',
                'biz_tags',
                'biz_user_reputation',
                'biz_businesses',
                'biz_towns'
            ];
            
            foreach ($biz_tables as $table) {
                $full_table = $wpdb->prefix . $table;
                if (in_array($full_table, $tables)) {
                    error_log("[SCHEMA] Dropping table: $full_table");
                    $wpdb->query("DROP TABLE IF EXISTS `$full_table`");
                }
            }
            
            $wpdb->query('COMMIT');
            $wpdb->query('START TRANSACTION');
            
            // Load and execute schema
            $schema_file = dirname(dirname(__FILE__)) . '/config/schema.sql';
            error_log("[SCHEMA] Loading schema from: $schema_file");
            if (!file_exists($schema_file)) {
                error_log("[SCHEMA ERROR] Schema file not found!");
                throw new \RuntimeException("Schema file not found at: $schema_file");
            }
            
            $schema_sql = str_replace('{prefix}', $wpdb->prefix, file_get_contents($schema_file));
            
            // Split schema into individual statements
            $statements = array_filter(
                array_map(
                    'trim',
                    preg_split("/;\s*[\r\n]+/", $schema_sql)
                )
            );

            // Execute each statement separately
            foreach ($statements as $statement) {
                if (empty($statement) || strpos($statement, '--') === 0) {
                    continue; // Skip empty lines and comments
                }
                
                $result = $wpdb->query($statement);
                if ($result === false) {
                    error_log("[SCHEMA ERROR] Failed to execute statement: " . $statement);
                    error_log("[SCHEMA ERROR] Error: " . $wpdb->last_error);
                    throw new \RuntimeException("Schema creation failed: " . $wpdb->last_error);
                }
            }
            
            // Add a test town for business setup
            $town_id = $wpdb->insert($wpdb->prefix . 'biz_towns', [
                'name' => 'Test Town',
                'slug' => 'test-town-' . uniqid(),
                'region' => 'Test Region'
            ]);
            
            $wpdb->query('COMMIT');
            $wpdb->query('START TRANSACTION');
            
            error_log("[SCHEMA] Schema created successfully");
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        } finally {
            // Re-enable foreign key checks but stay in transaction
            $wpdb->query('SET FOREIGN_KEY_CHECKS=1');
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
