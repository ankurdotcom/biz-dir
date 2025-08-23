<?php
/**
 * Mock WordPress Unit Test Case
 * 
 * Industry-standard mock implementation of WordPress WP_UnitTestCase
 * Following OWASP A06:2021 - Vulnerable and Outdated Components mitigation
 * 
 * @package BizDir
 * @subpackage Tests\Mocks
 * @since 1.0.0
 */

require_once dirname(__DIR__) . '/bootstrap-mock.php';
require_once __DIR__ . '/WP_UnitTest_Factory.php';

if (!class_exists('WP_UnitTestCase')) {
    /**
     * Mock WordPress Unit Test Case Class
     * 
     * Provides complete API compatibility with WordPress WP_UnitTestCase
     * while maintaining test isolation and security compliance
     */
    
    // Conditional parent class based on PHPUnit availability (Industry Standard Pattern)
    if (class_exists('PHPUnit\Framework\TestCase')) {
        // PHPUnit environment - extend TestCase
        abstract class WP_UnitTestCase_Base extends PHPUnit\Framework\TestCase {}
    } else {
        // Non-PHPUnit environment - create minimal base class
        abstract class WP_UnitTestCase_Base {
            protected function setUp(): void {}
            protected function tearDown(): void {}
            protected function assertEquals($expected, $actual, $message = '') {}
            protected function assertTrue($condition, $message = '') {}
            protected function assertFalse($condition, $message = '') {}
            protected function assertLessThan($expected, $actual, $message = '') {}
            protected function getName() { return 'mock_test'; }
            protected function getNumAssertions() { return 0; }
        }
    }
    
    abstract class WP_UnitTestCase extends WP_UnitTestCase_Base {
        
        /**
         * Test fixtures and setup data
         * 
         * @var array
         */
        protected static $fixtures = [];
        
        /**
         * WordPress factory for creating test objects
         * 
         * @var object
         */
        protected $factory;
        
        /**
         * Security: Track test execution for audit logging
         * 
         * @var array
         */
        private static $test_audit_log = [];
        
        /**
         * Set up test environment before each test
         * 
         * @return void
         */
        protected function setUp(): void {
            parent::setUp();
            
            // Security: Audit test execution (OWASP A09:2021 - Security Logging)
            $this->log_security_event('test_setup', [
                'test_class' => get_class($this),
                'test_method' => $this->getName(),
                'memory_usage' => memory_get_usage(true),
                'timestamp' => microtime(true)
            ]);
            
            // Initialize WordPress factory mock
            $this->factory = new WP_UnitTest_Factory_Mock();
            
            // Set up test database state
            $this->setup_test_database();
            
            // Initialize WordPress globals mock
            $this->setup_wordpress_globals();
            
            // Call WordPress-style setup method if it exists
            if (method_exists($this, 'set_up')) {
                $this->set_up();
            }
        }
        
        /**
         * Clean up after each test
         * 
         * @return void
         */
        protected function tearDown(): void {
            // Call WordPress-style teardown method if it exists
            if (method_exists($this, 'tear_down')) {
                $this->tear_down();
            }
            
            // Clean up test data
            $this->cleanup_test_data();
            
            // Security: Log test completion
            $this->log_security_event('test_teardown', [
                'test_class' => get_class($this),
                'test_method' => $this->getName(),
                'memory_peak' => memory_get_peak_usage(true),
                'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
            ]);
            
            parent::tearDown();
        }
        
        /**
         * Set up mock database for testing
         * 
         * @return void
         */
        private function setup_test_database(): void {
            global $wpdb;
            
            // Ensure mock WPDB is initialized
            if (!isset($wpdb) || !($wpdb instanceof MockWPDB)) {
                $wpdb = new MockWPDB();
            }
            
            // Set up test table prefixes
            $wpdb->prefix = 'wp_test_';
            $wpdb->base_prefix = 'wp_test_';
        }
        
        /**
         * Set up WordPress global variables mock
         * 
         * @return void
         */
        private function setup_wordpress_globals(): void {
            // Mock WordPress version
            if (!defined('WP_VERSION')) {
                define('WP_VERSION', '6.3.0');
            }
            
            // Mock WordPress database version
            global $wp_version, $wp_db_version;
            $wp_version = WP_VERSION;
            $wp_db_version = 55853;
            
            // Mock current user
            global $current_user;
            if (!isset($current_user)) {
                $current_user = $this->create_mock_user();
            }
            
            // Mock WordPress options
            global $wp_option;
            if (!isset($wp_option)) {
                $wp_option = $this->get_default_wp_options();
            }
        }
        
        /**
         * Create a mock WordPress user
         * 
         * @return object Mock user object
         */
        private function create_mock_user(): object {
            return (object) [
                'ID' => 1,
                'user_login' => 'test_admin',
                'user_email' => 'admin@test.local',
                'user_pass' => wp_hash_password('test_password'),
                'user_nicename' => 'test-admin',
                'user_registered' => current_time('mysql'),
                'user_activation_key' => '',
                'user_status' => 0,
                'display_name' => 'Test Administrator',
                'caps' => ['administrator' => true],
                'cap_key' => 'wp_test_capabilities',
                'roles' => ['administrator'],
                'allcaps' => [
                    'switch_themes' => true,
                    'edit_themes' => true,
                    'activate_plugins' => true,
                    'edit_plugins' => true,
                    'edit_users' => true,
                    'edit_files' => true,
                    'manage_options' => true,
                    'moderate_comments' => true,
                    'manage_categories' => true,
                    'manage_links' => true,
                    'upload_files' => true,
                    'import' => true,
                    'unfiltered_html' => true,
                    'edit_posts' => true,
                    'edit_others_posts' => true,
                    'edit_published_posts' => true,
                    'publish_posts' => true,
                    'edit_pages' => true,
                    'read' => true,
                    'level_10' => true,
                    'level_9' => true,
                    'level_8' => true,
                    'level_7' => true,
                    'level_6' => true,
                    'level_5' => true,
                    'level_4' => true,
                    'level_3' => true,
                    'level_2' => true,
                    'level_1' => true,
                    'level_0' => true,
                    'administrator' => true
                ]
            ];
        }
        
        /**
         * Get default WordPress options for testing
         * 
         * @return array Default options
         */
        private function get_default_wp_options(): array {
            return [
                'siteurl' => 'http://test.local',
                'home' => 'http://test.local',
                'blogname' => 'BizDir Test Site',
                'blogdescription' => 'Test WordPress site for BizDir',
                'users_can_register' => 0,
                'admin_email' => 'admin@test.local',
                'start_of_week' => 1,
                'use_balanceTags' => 0,
                'use_smilies' => 1,
                'require_name_email' => 1,
                'comments_notify' => 1,
                'posts_per_rss' => 10,
                'rss_use_excerpt' => 0,
                'mailserver_url' => 'mail.example.com',
                'mailserver_login' => 'login@example.com',
                'mailserver_pass' => 'password',
                'mailserver_port' => 110,
                'default_category' => 1,
                'default_comment_status' => 'open',
                'default_ping_status' => 'open',
                'default_pingback_flag' => 1,
                'posts_per_page' => 10,
                'date_format' => 'F j, Y',
                'time_format' => 'g:i a',
                'links_updated_date_format' => 'F j, Y g:i a',
                'comment_moderation' => 0,
                'moderation_notify' => 1,
                'permalink_structure' => '/%year%/%monthnum%/%day%/%postname%/',
                'rewrite_rules' => '',
                'hack_file' => 0,
                'blog_charset' => 'UTF-8',
                'moderation_keys' => '',
                'active_plugins' => [],
                'category_base' => '',
                'ping_sites' => 'http://rpc.pingomatic.com/',
                'comment_max_links' => 2,
                'gmt_offset' => 0,
                'default_email_category' => 1,
                'recently_edited' => '',
                'template' => 'twentytwentythree',
                'stylesheet' => 'twentytwentythree',
                'comment_whitelist' => 1,
                'blacklist_keys' => '',
                'comment_registration' => 0,
                'html_type' => 'text/html',
                'use_trackback' => 0,
                'default_role' => 'subscriber',
                'db_version' => 55853,
                'uploads_use_yearmonth_folders' => 1,
                'upload_path' => '',
                'blog_public' => 1,
                'default_link_category' => 2,
                'show_on_front' => 'posts',
                'tag_base' => '',
                'show_avatars' => 1,
                'avatar_rating' => 'G',
                'upload_url_path' => '',
                'thumbnail_size_w' => 150,
                'thumbnail_size_h' => 150,
                'thumbnail_crop' => 1,
                'medium_size_w' => 300,
                'medium_size_h' => 300,
                'avatar_default' => 'mystery',
                'large_size_w' => 1024,
                'large_size_h' => 1024,
                'image_default_link_type' => 'none',
                'image_default_size' => '',
                'image_default_align' => '',
                'close_comments_for_old_posts' => 0,
                'close_comments_days_old' => 14,
                'thread_comments' => 1,
                'thread_comments_depth' => 5,
                'page_comments' => 0,
                'comments_per_page' => 50,
                'default_comments_page' => 'newest',
                'comment_order' => 'asc',
                'sticky_posts' => [],
                'widget_categories' => [],
                'widget_text' => [],
                'widget_rss' => [],
                'uninstall_plugins' => [],
                'timezone_string' => '',
                'page_for_posts' => 0,
                'page_on_front' => 0,
                'default_post_format' => 0,
                'link_manager_enabled' => 0,
                'finished_splitting_shared_terms' => 1,
                'site_icon' => 0,
                'medium_large_size_w' => 768,
                'medium_large_size_h' => 0,
                'wp_page_for_privacy_policy' => 0,
                'show_comments_cookies_opt_in' => 1,
                'admin_email_lifespan' => 0,
                'disallowed_keys' => '',
                'comment_previously_approved' => 1,
                'auto_plugin_theme_update_emails' => [],
                'auto_update_core_dev' => 'enabled',
                'auto_update_core_minor' => 'enabled',
                'auto_update_core_major' => 'enabled'
            ];
        }
        
        /**
         * Clean up test data after each test
         * 
         * @return void
         */
        private function cleanup_test_data(): void {
            global $wpdb;
            
            // Clear mock database
            if ($wpdb instanceof MockWPDB) {
                $wpdb->reset();
            }
            
            // Clear static fixtures
            static::$fixtures = [];
        }
        
        /**
         * Security: Log security events for audit trail
         * 
         * @param string $event_type Type of security event
         * @param array $data Event data
         * @return void
         */
        private function log_security_event(string $event_type, array $data): void {
            // Security: Sanitize data to prevent log injection (OWASP A03:2021)
            $sanitized_data = array_map(function($value) {
                if (is_string($value)) {
                    // Security: Use modern PHP 8.3+ sanitization (OWASP A03:2021)
                    return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
                }
                return $value;
            }, $data);
            
            self::$test_audit_log[] = [
                'event_type' => $event_type,
                'data' => $sanitized_data,
                'timestamp' => microtime(true),
                'session_id' => session_id() ?: 'test_session_' . uniqid()
            ];
        }
        
        /**
         * WordPress-compatible assertion methods
         */
        
        /**
         * Assert that a WordPress action was called
         * 
         * @param string $action Action name
         * @param string $message Optional failure message
         * @return void
         */
        public function assertActionCalled(string $action, string $message = ''): void {
            $this->assertTrue(
                MockWordPressHooks::was_action_called($action),
                $message ?: "Action '$action' was not called"
            );
        }
        
        /**
         * Assert that a WordPress filter was applied
         * 
         * @param string $filter Filter name
         * @param string $message Optional failure message
         * @return void
         */
        public function assertFilterApplied(string $filter, string $message = ''): void {
            $this->assertTrue(
                MockWordPressHooks::was_filter_applied($filter),
                $message ?: "Filter '$filter' was not applied"
            );
        }
        
        /**
         * Assert that user has capability
         * 
         * @param string $capability Capability name
         * @param int|null $user_id User ID (null for current user)
         * @param string $message Optional failure message
         * @return void
         */
        public function assertUserCan(string $capability, ?int $user_id = null, string $message = ''): void {
            $can = $user_id ? user_can($user_id, $capability) : current_user_can($capability);
            $this->assertTrue(
                $can,
                $message ?: "User does not have capability '$capability'"
            );
        }
        
        /**
         * Assert that user does not have capability
         * 
         * @param string $capability Capability name
         * @param int|null $user_id User ID (null for current user)
         * @param string $message Optional failure message
         * @return void
         */
        public function assertUserCannotPerform(string $capability, ?int $user_id = null, string $message = ''): void {
            $can = $user_id ? user_can($user_id, $capability) : current_user_can($capability);
            $this->assertFalse(
                $can,
                $message ?: "User should not have capability '$capability'"
            );
        }
        
        /**
         * Assert that WordPress option has expected value
         * 
         * @param string $option Option name
         * @param mixed $expected Expected value
         * @param string $message Optional failure message
         * @return void
         */
        public function assertOptionEquals(string $option, $expected, string $message = ''): void {
            $actual = get_option($option);
            $this->assertEquals(
                $expected,
                $actual,
                $message ?: "Option '$option' does not have expected value"
            );
        }
        
        /**
         * Get security audit log for testing
         * 
         * @return array Security audit log
         */
        public static function getSecurityAuditLog(): array {
            return self::$test_audit_log;
        }
        
        /**
         * Clear security audit log
         * 
         * @return void
         */
        public static function clearSecurityAuditLog(): void {
            self::$test_audit_log = [];
        }
    }
}
