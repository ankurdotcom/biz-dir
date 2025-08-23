<?php
/**
 * Prevent direct access
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Permission Handler Class
 *
 * @package BizDir\Core\User
 */

namespace BizDir\Core\User;

class Permission_Handler {
    /**
     * Store capabilities that need to be registered once post type is available
     *
     * @var array
     */
    private $queued_caps = [];

    /**
     * Cache for user reputation points
     *
     * @var array
     */
    private static $reputation_cache = [];
    /**
     * Initialize permission hooks
     */
    public function init() {
        add_filter('user_has_cap', [$this, 'check_reputation_caps'], 10, 4);
        add_action('init', [$this, 'register_capabilities']);
    }

    /**
     * Register custom capabilities
     */
    public function register_capabilities() {
        // Business listing capabilities
        $this->register_post_type_caps('business_listing', [
            'edit_business_listing',
            'read_business_listing',
            'delete_business_listing',
            'edit_business_listings',
            'edit_others_business_listings',
            'publish_business_listings',
            'read_private_business_listings'
        ]);
    }

    /**
     * Check reputation-based capabilities
     *
     * @param array   $allcaps All capabilities of the user
     * @param array   $caps    Required capabilities
     * @param array   $args    [0] = Requested capability, [1] = User ID, [2] = Associated object ID
     * @param WP_User $user    User object
     * @return array Modified capabilities
     */
    public function check_reputation_caps($allcaps, $caps, $args, $user) {
        if (!isset($args[0])) {
            return $allcaps;
        }
        
        $reputation = $this->get_user_reputation($user->ID);
        $allcaps[$args[0]] = false; // Initialize capability

        // Reputation-based capabilities
        switch ($args[0]) {
            case 'publish_business_listings':
                if ($reputation >= 100) {
                    $allcaps[$args[0]] = true;
                }
                break;

            case 'moderate_reviews':
                if ($reputation >= 500) {
                    $allcaps[$args[0]] = true;
                }
                break;

            case 'manage_tags':
                if ($reputation >= 200) {
                    $allcaps[$args[0]] = true;
                }
                break;
        }

        return $allcaps;
    }

    /**
     * Get user's reputation
     *
     * @param int $user_id User ID
     * @return int User's reputation points
     */
    private function get_user_reputation($user_id) {
        if (isset(self::$reputation_cache[$user_id])) {
            error_log('[BizDir Permission] Using cached reputation for user ' . $user_id);
            return self::$reputation_cache[$user_id];
        }

        global $wpdb;
        // First try to get existing
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT reputation_points FROM {$wpdb->prefix}biz_user_reputation WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing !== null) {
            self::$reputation_cache[$user_id] = (int) $existing;
            return self::$reputation_cache[$user_id];
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'biz_user_reputation';
        $query = $wpdb->prepare(
            "SELECT reputation_points FROM {$table_name} WHERE user_id = %d",
            $user_id
        );

        error_log('[BizDir Permission] Checking reputation for user ' . $user_id);
        error_log('[BizDir Permission] Using table: ' . $table_name);
        error_log('[BizDir Permission] Query: ' . $query);

        // Check if table exists
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table_name
            )
        );

        if (!$table_exists) {
            error_log('[BizDir Permission] ERROR: Reputation table does not exist!');
            error_log('[BizDir Permission] Available tables: ' . implode(', ', $wpdb->get_col("SHOW TABLES")));
            return 0;
        }

        $reputation = $wpdb->get_var($query);
        
        if ($wpdb->last_error) {
            error_log('[BizDir Permission] Database error: ' . $wpdb->last_error);
        }

        error_log('[BizDir Permission] Retrieved reputation: ' . var_export($reputation, true));
        
        // Cache the result
        self::$reputation_cache[$user_id] = (int) $reputation;
        
        return self::$reputation_cache[$user_id];
    }

    /**
     * Register post type capabilities
     *
     * @param string $post_type Post type name
     * @param array  $caps      Array of capabilities to register
     */
    private function register_post_type_caps($post_type, $caps) {
        global $wp_post_types;
        
        if (!isset($wp_post_types[$post_type])) {
            // Post type not registered yet, save caps for later
            $this->queued_caps[$post_type] = $caps;
            add_action('registered_post_type', function($registered_type) use ($post_type, $caps) {
                if ($registered_type === $post_type) {
                    $this->apply_post_type_caps($post_type, $caps);
                }
            });
            return;
        }
        
        $this->apply_post_type_caps($post_type, $caps);
    }
    
    /**
     * Apply capabilities to a registered post type
     *
     * @param string $post_type Post type name
     * @param array  $caps      Array of capabilities to register
     */
    private function apply_post_type_caps($post_type, $caps) {
        global $wp_post_types;
        
        if (!isset($wp_post_types[$post_type])) {
            return;
        }
        
        if (!isset($wp_post_types[$post_type]->cap)) {
            $wp_post_types[$post_type]->cap = new \stdClass();
        }
        
        foreach ($caps as $cap) {
            if (!isset($wp_post_types[$post_type]->cap->$cap)) {
                $wp_post_types[$post_type]->cap->$cap = $cap;
            }
        }
    }

    /**
     * Check if user can perform specific action
     *
     * @param string $action   Action to check
     * @param int    $user_id  User ID (optional)
     * @param int    $obj_id   Object ID (optional)
     * @return bool Whether user can perform action
     */
    public static function can($action, $user_id = null, $obj_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        switch ($action) {
            case 'edit_business':
                return self::can_edit_business($user_id, $obj_id);

            case 'moderate_content':
                return self::can_moderate($user_id);

            case 'manage_settings':
                return user_can($user_id, 'manage_business_settings');

            case 'create_business_reviews':
                return is_user_logged_in() && get_current_user_id() == $user_id;

            case 'delete_business_reviews':
                return $obj_id ? self::can_delete_review($user_id, $obj_id) : false;

            default:
                return user_can($user_id, $action);
        }
    }

    /**
     * Check if user can edit a business
     *
     * @param int $user_id User ID
     * @param int $business_id Business ID
     * @return bool
     */
    public static function can_edit_business($user_id, $business_id) {
        error_log("[BizDir Permission] Checking edit permission for user $user_id on business $business_id");
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            error_log("[BizDir Permission] User not found");
            return false;
        }

        error_log("[BizDir Permission] User roles: " . implode(', ', $user->roles));
        error_log("[BizDir Permission] User caps: " . implode(', ', array_keys($user->allcaps)));

        // If user is an administrator or has edit_others_business_listings capability
        if (in_array('administrator', $user->roles)) {
            error_log("[BizDir Permission] User is administrator");
            return true;
        }

        if (user_can($user_id, 'edit_others_business_listings')) {
            error_log("[BizDir Permission] User has edit_others_business_listings capability");
            return true;
        }

        if (!$business_id) {
            error_log("[BizDir Permission] No business ID provided");
            return false;
        }

        global $wpdb;
        $owner_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_author FROM {$wpdb->posts} WHERE ID = %d",
            $business_id
        ));

        error_log("[BizDir Permission] Business owner: $owner_id, Current user: $user_id");
        return $owner_id == $user_id;
    }

    /**
     * Check if user can moderate content
     *
     * @param int $user_id User ID
     * @return bool
     */
    private static function can_moderate($user_id) {
        $user = get_user_by('id', $user_id);
        return in_array(User_Manager::ROLE_MODERATOR, $user->roles) || 
               in_array(User_Manager::ROLE_ADMIN, $user->roles);
    }

    /**
     * Check if user can delete a review
     *
     * @param int $user_id User ID
     * @param int $review_id Review ID
     * @return bool
     */
    private static function can_delete_review($user_id, $review_id) {
        $review = get_post($review_id);
        if (!$review || $review->post_type !== 'business_review') {
            return false;
        }

        // Allow review author to delete their own review
        if ($review->post_author == $user_id) {
            return true;
        }

        // Allow moderators and admins to delete any review
        return self::can_moderate($user_id);
    }
}
