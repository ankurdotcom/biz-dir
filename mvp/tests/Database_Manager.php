<?php

namespace BizDir\Tests;

class Database_Manager {
    private static $instance = null;
    private $test_db_prefix = 'test_biz_dir_';
    private $original_db_config = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->original_db_config = [
            'name' => defined('DB_NAME') ? DB_NAME : '',
            'user' => defined('DB_USER') ? DB_USER : '',
            'password' => defined('DB_PASSWORD') ? DB_PASSWORD : '',
            'host' => defined('DB_HOST') ? DB_HOST : '',
            'prefix' => isset($GLOBALS['wpdb']) ? $GLOBALS['wpdb']->prefix : 'wp_'
        ];
    }
    
    public function createTestDatabase($test_name) {
        global $wpdb;
        
        // Generate a unique database name using test name and timestamp
        $timestamp = time();
        $safe_test_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $test_name);
        $test_db_name = $this->test_db_prefix . $safe_test_name . '_' . $timestamp;
        
        _biz_dir_debug("Creating test database: $test_db_name");
        
        try {
            // Connect to MySQL without database
            $wpdb_admin = new \wpdb(
                $this->original_db_config['user'],
                $this->original_db_config['password'],
                '',
                $this->original_db_config['host']
            );
            
            // Suppress errors to handle them ourselves
            $wpdb_admin->suppress_errors();
            
            // Check if we have the necessary privileges first
            $show_grants = $wpdb_admin->get_results("SHOW GRANTS FOR CURRENT_USER()", ARRAY_N);
            if (!$show_grants) {
                throw new \RuntimeException(
                    "Failed to check database privileges: " . $wpdb_admin->last_error
                );
            }
            
            // Look for CREATE privilege
            $has_create = false;
            foreach ($show_grants as $grant) {
                if (strpos($grant[0], "ALL PRIVILEGES") !== false || 
                    strpos($grant[0], "CREATE") !== false) {
                    $has_create = true;
                    break;
                }
            }
            
            if (!$has_create) {
                throw new \RuntimeException(
                    "User lacks CREATE DATABASE privilege"
                );
            }
            
            // Create new test database with proper collation
            $create_sql = $wpdb_admin->prepare(
                "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                $test_db_name
            );
            $result = $wpdb_admin->query($create_sql);
            
            if ($result === false) {
                throw new \RuntimeException(
                    "Failed to create test database: " . $wpdb_admin->last_error
                );
            }
            
            // Verify the database was created
            $exists = $wpdb_admin->get_var(
                $wpdb_admin->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = %s", $test_db_name)
            );
            
            if (!$exists) {
                throw new \RuntimeException(
                    "Database creation appeared to succeed but database does not exist: $test_db_name"
                );
            }
            
            _biz_dir_debug("Test database created successfully: $test_db_name");
            
            // Grant all privileges to the test user if needed
            if ($this->original_db_config['user'] !== 'root') {
                $grant_sql = $wpdb_admin->prepare(
                    "GRANT ALL PRIVILEGES ON `%s`.* TO %s@'%s'",
                    $test_db_name,
                    $this->original_db_config['user'],
                    'localhost'
                );
                $grant_result = $wpdb_admin->query($grant_sql);
                
                if ($grant_result === false) {
                    throw new \RuntimeException(
                        "Failed to grant privileges on test database: " . $wpdb_admin->last_error
                    );
                }
                
                // Flush privileges to ensure they take effect
                $wpdb_admin->query("FLUSH PRIVILEGES");
            }

            // Select the newly created database
            $select_result = $wpdb_admin->select($test_db_name);
            if ($select_result === false) {
                throw new \RuntimeException(
                    "Failed to select test database $test_db_name: " . $wpdb_admin->last_error
                );
            }
            
            // Verify the database exists and we can select it
            $exists = $wpdb_admin->get_var("SELECT DATABASE()");
            if ($exists !== $test_db_name) {
                throw new \RuntimeException(
                    "Database exists but cannot be selected: $test_db_name (current: $exists)"
                );
            }

            // Update WordPress database configuration
            $this->updateDatabaseConfig($test_db_name);
            
            return $test_db_name;
        } catch (\Exception $e) {
            _biz_dir_debug("Failed to create test database", 'ERROR', [
                'error' => $e->getMessage(),
                'db_name' => isset($test_db_name) ? $test_db_name : 'unknown'
            ]);
            throw $e;
        }
    }
    
    public function dropTestDatabase($db_name) {
        if (!$db_name) {
            error_log("[ERROR] No database name provided to drop");
            return;
        }
        
        try {
            // Connect to MySQL without database
            $wpdb_admin = new \wpdb(
                $this->original_db_config['user'],
                $this->original_db_config['password'],
                '',
                $this->original_db_config['host']
            );
            
            $wpdb_admin->suppress_errors();
            
            // Verify database exists before attempting to drop
            $exists = $wpdb_admin->get_var(
                $wpdb_admin->prepare(
                    "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = %s",
                    $db_name
                )
            );
            
            if (!$exists) {
                error_log("[WARNING] Test database $db_name does not exist, nothing to drop");
                return;
            }
            
            // Drop the test database
            $wpdb_admin->query("DROP DATABASE IF EXISTS `$db_name`");
            
            if ($wpdb_admin->last_error) {
                error_log("[WARNING] Failed to drop test database $db_name: " . $wpdb_admin->last_error);
            } else {
                error_log("[INFO] Successfully dropped test database: $db_name");
            }
            
        } finally {
            // Always try to restore original database configuration
            $this->restoreDatabaseConfig();
        }
    }
    
    private function updateDatabaseConfig($db_name) {
        global $wpdb;
        
        _biz_dir_debug("Updating database configuration for: $db_name");
        
        try {
            // Create a new database connection
            $wpdb = new \wpdb(
                $this->original_db_config['user'],
                $this->original_db_config['password'],
                $db_name,
                $this->original_db_config['host']
            );
            
            // Update the global instance
            $GLOBALS['wpdb'] = $wpdb;
            
            // Verify connection
            if (!$wpdb->check_connection(false)) {
                throw new \RuntimeException("Failed to connect to test database: " . $wpdb->last_error);
            }
            
            // Verify we can perform operations
            $test_result = $wpdb->get_var("SELECT DATABASE()");
            if ($test_result !== $db_name) {
                throw new \RuntimeException(
                    "Connected to wrong database. Expected: $db_name, Got: " . 
                    ($test_result ?: 'none')
                );
            }
            
            // Update the prefix for test isolation
            $wpdb->set_prefix($this->test_db_prefix);
            
            _biz_dir_debug("Successfully updated database configuration");
            
            return true;
        } catch (\Exception $e) {
            _biz_dir_debug("Failed to update database configuration", 'ERROR', [
                'error' => $e->getMessage(),
                'db_name' => $db_name
            ]);
            throw $e;
        }
    }
    
    private function restoreDatabaseConfig() {
        global $wpdb;
        
        try {
            // Create a new database connection with original settings
            $wpdb = new \wpdb(
                $this->original_db_config['user'],
                $this->original_db_config['password'],
                $this->original_db_config['name'],
                $this->original_db_config['host']
            );
            
            // Update the global instance
            $GLOBALS['wpdb'] = $wpdb;
            
            // Restore original prefix
            $wpdb->set_prefix($this->original_db_config['prefix']);
            
            return true;
        } catch (\Exception $e) {
            _biz_dir_debug("Failed to restore database configuration", 'ERROR', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    private function defineConstant($name, $value) {
        // WordPress doesn't allow redefining constants, so we need to use runkit if available
        if (extension_loaded('runkit7') || extension_loaded('runkit')) {
            if (defined($name)) {
                runkit_constant_redefine($name, $value);
            } else {
                runkit_constant_add($name, $value);
            }
        }
    }
    
    public function checkDatabaseSetup($db_name) {
        global $wpdb;
        
        // Check if wpdb is properly connected
        if (!$wpdb) {
            error_log("[ERROR] wpdb is not initialized");
            return false;
        }
        
        // Try to query something simple
        $result = $wpdb->get_var("SELECT DATABASE()");
        if (!$result) {
            error_log("[ERROR] No database selected: " . $wpdb->last_error);
            return false;
        }
        
        // Check if we're connected to the right database
        if ($result !== $db_name) {
            error_log("[ERROR] Wrong database selected: $result (expected $db_name)");
            return false;
        }
        
        return true;
    }
    
    public function createSchema() {
        global $wpdb;
        
        try {
            // First check if tables exist with foreign keys
            $existing_tables = $wpdb->get_col("SHOW TABLES");
            $has_foreign_keys = false;
            
            if (!empty($existing_tables)) {
                foreach ($existing_tables as $table) {
                    $fk_check = $wpdb->get_results("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.TABLE_CONSTRAINTS 
                        WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
                        AND TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = '$table'
                    ");
                    
                    if (!empty($fk_check)) {
                        $has_foreign_keys = true;
                        break;
                    }
                }
            }
            
            // If we have foreign keys, we need to disable checks
            if ($has_foreign_keys) {
                $wpdb->query('SET FOREIGN_KEY_CHECKS=0');
            }
            
            try {
                // Drop existing tables in reverse dependency order to avoid foreign key constraints
                $tables_to_drop = [
                    'biz_seo_meta',
                    'biz_reviews',
                    'biz_tags',
                    'biz_moderation_queue',
                    'biz_user_reputation',
                    'biz_businesses',
                    'biz_towns'
                ];
                
                foreach ($tables_to_drop as $table) {
                    $full_table = $wpdb->prefix . $table;
                    error_log("[SCHEMA] Dropping table if exists: $full_table");
                    $wpdb->query("DROP TABLE IF EXISTS `$full_table`");
                }
                
                // Create tables in dependency order
                $this->createTownsTable();
                $this->createBusinessesTable();
                $this->createReviewsTable();
                $this->createTagsTable();
                $this->createModerationQueueTable();
                $this->createUserReputationTable();
                $this->createSeoMetaTable();
                
                // Verify tables were created
                $tables = $wpdb->get_col("SHOW TABLES");
                $required_tables = [
                    'towns', 'businesses', 'reviews', 'tags',
                    'moderation_queue', 'user_reputation', 'seo_meta'
                ];
                
                foreach ($required_tables as $table) {
                    $full_table = $wpdb->prefix . 'biz_' . $table;
                    if (!in_array($full_table, $tables)) {
                        throw new \RuntimeException("Schema verification failed: $full_table not found");
                    }
                }
            } finally {
                // Re-enable foreign key checks if we disabled them
                if ($has_foreign_keys) {
                    $wpdb->query('SET FOREIGN_KEY_CHECKS=1');
                }
            }
        } catch (\Exception $e) {
            error_log("[SCHEMA ERROR] Failed to create schema: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function createTownsTable() {
        global $wpdb;
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}biz_towns` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `slug` varchar(100) NOT NULL,
            `region` varchar(100) DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`),
            KEY `region` (`region`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    
    private function createBusinessesTable() {
        global $wpdb;
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}biz_businesses` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` varchar(200) NOT NULL,
            `slug` varchar(200) NOT NULL,
            `town_id` bigint(20) UNSIGNED NOT NULL,
            `owner_id` bigint(20) UNSIGNED NOT NULL,
            `category` varchar(100) DEFAULT NULL,
            `description` text,
            `contact_info` json DEFAULT NULL,
            `status` varchar(20) DEFAULT 'active',
            `is_sponsored` tinyint(1) DEFAULT '0',
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`),
            KEY `town_id` (`town_id`),
            KEY `owner_id` (`owner_id`),
            KEY `category` (`category`),
            KEY `status` (`status`),
            KEY `is_sponsored` (`is_sponsored`),
            CONSTRAINT `fk_business_town` FOREIGN KEY (`town_id`) REFERENCES `{$wpdb->prefix}biz_towns` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    
    private function createReviewsTable() {
        global $wpdb;
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}biz_reviews` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `business_id` bigint(20) UNSIGNED NOT NULL,
            `user_id` bigint(20) UNSIGNED NOT NULL,
            `rating` decimal(2,1) NOT NULL,
            `content` text,
            `status` varchar(20) DEFAULT 'pending',
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `business_id` (`business_id`),
            KEY `user_id` (`user_id`),
            KEY `status` (`status`),
            CONSTRAINT `fk_review_business` FOREIGN KEY (`business_id`) REFERENCES `{$wpdb->prefix}biz_businesses` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    
    private function createTagsTable() {
        global $wpdb;
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}biz_tags` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `business_id` bigint(20) UNSIGNED NOT NULL,
            `tag` varchar(50) NOT NULL,
            `weight` decimal(4,3) DEFAULT '1.000',
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `business_id` (`business_id`),
            KEY `tag` (`tag`),
            CONSTRAINT `fk_tag_business` FOREIGN KEY (`business_id`) REFERENCES `{$wpdb->prefix}biz_businesses` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    
    private function createModerationQueueTable() {
        global $wpdb;
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}biz_moderation_queue` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `content_type` varchar(50) NOT NULL,
            `content_id` bigint(20) UNSIGNED NOT NULL,
            `status` varchar(20) DEFAULT 'pending',
            `moderator_id` bigint(20) UNSIGNED DEFAULT NULL,
            `notes` text,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `content_type` (`content_type`),
            KEY `status` (`status`),
            KEY `moderator_id` (`moderator_id`),
            KEY `content_id` (`content_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    
    private function createUserReputationTable() {
        global $wpdb;
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}biz_user_reputation` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) UNSIGNED NOT NULL,
            `reputation_points` int NOT NULL DEFAULT '0',
            `level` varchar(20) DEFAULT 'contributor',
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id` (`user_id`),
            KEY `level` (`level`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    
    private function createSeoMetaTable() {
        global $wpdb;
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}biz_seo_meta` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `business_id` bigint(20) UNSIGNED NOT NULL,
            `meta_type` varchar(50) NOT NULL,
            `meta_key` varchar(100) NOT NULL,
            `meta_value` text NOT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `business_meta` (`business_id`, `meta_type`, `meta_key`),
            CONSTRAINT `fk_seo_meta_business` FOREIGN KEY (`business_id`) REFERENCES `{$wpdb->prefix}biz_businesses` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
}
