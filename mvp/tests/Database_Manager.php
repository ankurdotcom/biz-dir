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
            'prefix' => $GLOBALS['wpdb']->prefix
        ];
    }
    
    public function createTestDatabase($test_name) {
        global $wpdb;
        
        // Generate a unique database name using test name and timestamp
        $timestamp = time();
        $test_db_name = $this->test_db_prefix . sanitize_file_name($test_name) . '_' . $timestamp;
        
        // Connect to MySQL without database
        $wpdb_admin = new \wpdb(
            $this->original_db_config['user'],
            $this->original_db_config['password'],
            '',
            $this->original_db_config['host']
        );
        
        // Create new test database
        $wpdb_admin->query("CREATE DATABASE IF NOT EXISTS `$test_db_name`");
        
        if ($wpdb_admin->last_error) {
            throw new \RuntimeException("Failed to create test database: " . $wpdb_admin->last_error);
        }
        
        // Update WordPress database configuration
        $this->updateDatabaseConfig($test_db_name);
        
        // Reconnect wpdb with new database
        $wpdb = new \wpdb(
            DB_USER,
            DB_PASSWORD,
            DB_NAME,
            DB_HOST
        );
        $GLOBALS['wpdb'] = $wpdb;
        
        return $test_db_name;
    }
    
    public function dropTestDatabase($db_name) {
        // Connect to MySQL without database
        $wpdb_admin = new \wpdb(
            $this->original_db_config['user'],
            $this->original_db_config['password'],
            '',
            $this->original_db_config['host']
        );
        
        // Drop the test database
        $wpdb_admin->query("DROP DATABASE IF EXISTS `$db_name`");
        
        if ($wpdb_admin->last_error) {
            error_log("Warning: Failed to drop test database $db_name: " . $wpdb_admin->last_error);
        }
        
        // Restore original database configuration
        $this->restoreDatabaseConfig();
    }
    
    private function updateDatabaseConfig($db_name) {
        define('DB_NAME_TEMP', $db_name);
        
        // Update wp-config.php constants if they exist
        if (defined('DB_NAME')) {
            $this->defineConstant('DB_NAME', $db_name);
        }
        
        global $wpdb;
        $wpdb->prefix = $this->test_db_prefix . $wpdb->prefix;
    }
    
    private function restoreDatabaseConfig() {
        // Restore original database configuration
        if (defined('DB_NAME') && defined('DB_NAME_TEMP')) {
            $this->defineConstant('DB_NAME', $this->original_db_config['name']);
        }
        
        global $wpdb;
        $wpdb->prefix = $this->original_db_config['prefix'];
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
    
    public function createSchema() {
        global $wpdb;
        
        // Create tables in correct dependency order
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
