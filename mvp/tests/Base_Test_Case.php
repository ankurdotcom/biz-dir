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
        
        // Always recreate schema for each test
        error_log("[SCHEMA] Recreating schema tables...");
        
        // Drop existing tables in reverse order to respect foreign keys
        $tables_to_drop = [
            'biz_moderation_queue',
            'biz_reviews',
            'biz_tags',
            'biz_user_reputation',
            'biz_businesses',
            'biz_towns'
        ];
        
        foreach ($tables_to_drop as $table) {
            $full_table = $wpdb->prefix . $table;
            error_log("[SCHEMA] Dropping table: $full_table");
            $wpdb->query("DROP TABLE IF EXISTS `$full_table`");
        }
        
        // Create tables manually to ensure correct order and structure
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
        error_log("[SCHEMA] Created towns table");
        
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
        error_log("[SCHEMA] Created businesses table");
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}biz_reviews` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `business_id` bigint(20) UNSIGNED NOT NULL,
            `user_id` bigint(20) UNSIGNED NOT NULL,
            `rating` decimal(2,1) NOT NULL,
            `comment` text,
            `status` varchar(20) DEFAULT 'pending',
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `business_id` (`business_id`),
            KEY `user_id` (`user_id`),
            KEY `status` (`status`),
            CONSTRAINT `fk_review_business` FOREIGN KEY (`business_id`) REFERENCES `{$wpdb->prefix}biz_businesses` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        error_log("[SCHEMA] Created reviews table");
        
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
        error_log("[SCHEMA] Created tags table");
        
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
        error_log("[SCHEMA] Created moderation_queue table");
        
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
        error_log("[SCHEMA] Created user_reputation table");
        
        // Verify tables were created
        $tables = $wpdb->get_col("SHOW TABLES");
        foreach ($tables_to_drop as $table) {
            $full_table = $wpdb->prefix . $table;
            if (!in_array($full_table, $tables)) {
                error_log("[SCHEMA ERROR] Table $full_table was not created!");
                throw new \RuntimeException("Schema verification failed: $full_table not found");
            }
        }
        error_log("[SCHEMA] All tables created successfully");
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
