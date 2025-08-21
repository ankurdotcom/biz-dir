<?php
/**
 * Base Test Case
 *
 * @package BizDir\Tests
 */

namespace BizDir\Tests;

require_once __DIR__ . '/setup_helpers.php';
require_once __DIR__ . '/Database_Manager.php';

use WP_UnitTestCase;

class Base_Test_Case extends WP_UnitTestCase {
    protected $autoloader;
    protected $db_manager;
    protected $test_db_name;
    protected $setup_helper;

    public function setUp(): void {
        error_log("\n[TEST START] ==========================================");
        error_log("[TEST INFO] Running: " . get_class($this) . "::" . $this->getName());
        
        try {
            parent::setUp();
            
            // Initialize setup helper
            $this->setup_helper = new Setup_Helper();
            
            // Create a new test database for this test
            $this->db_manager = Database_Manager::getInstance();
            
            $test_name = get_class($this) . '_' . $this->getName();
            error_log("[TEST SETUP] Creating test database for: $test_name");
            
            try {
                $this->test_db_name = $this->db_manager->createTestDatabase($test_name);
            } catch (\Exception $e) {
                error_log("[DATABASE ERROR] Failed to create test database: " . $e->getMessage());
                throw $e;
            }
            
            error_log("[TEST SETUP] Database created: " . $this->test_db_name);
            
            // Get the global wpdb after database setup
            global $wpdb;
            
            try {
                // Verify database connection before proceeding
                $current_db = $wpdb->get_var("SELECT DATABASE()");
                if ($current_db !== $this->test_db_name) {
                    throw new \RuntimeException(
                        "Connected to wrong database. Expected: {$this->test_db_name}, Got: " . 
                        ($current_db ?: 'none')
                    );
                }
                
                error_log("[TEST SETUP] Database connection verified");
                
                // Disable foreign key checks temporarily
                $wpdb->query('SET FOREIGN_KEY_CHECKS=0');
                
                // Create schema in new test database
                error_log("[TEST SETUP] Creating schema...");
                $this->db_manager->createSchema();
                
                // Verify database setup
                if (!$this->db_manager->checkDatabaseSetup($this->test_db_name)) {
                    throw new \RuntimeException("Database setup verification failed");
                }
                
                error_log("[TEST SETUP] Schema creation successful");
                
            } catch (\Exception $e) {
                error_log("[SCHEMA ERROR] Failed to create/verify schema: " . $e->getMessage());
                // Clean up the database if schema creation fails
                if ($this->test_db_name) {
                    $this->db_manager->dropTestDatabase($this->test_db_name);
                }
                throw $e;
            } finally {
                // Always re-enable foreign key checks
                $wpdb->query('SET FOREIGN_KEY_CHECKS=1');
            }
            
        } catch (\Exception $e) {
            error_log("[FATAL ERROR] Test setup failed: " . $e->getMessage());
            error_log("[STACK TRACE] " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function tearDown(): void {
        error_log("[CLEANUP] Starting cleanup for: " . get_class($this) . "::" . $this->getName());
        
        // Log any warnings or notices that occurred during the test
        $errors = error_get_last();
        if ($errors) {
            error_log("[TEST WARNINGS] Last error: " . json_encode($errors));
        }
        
        // Drop the test database
        if ($this->test_db_name) {
            $this->db_manager->dropTestDatabase($this->test_db_name);
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
