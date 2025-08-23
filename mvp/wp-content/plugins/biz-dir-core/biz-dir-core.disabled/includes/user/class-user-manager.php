<?php
/**
 * Prevent direct access
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Manager Class
 *
 * @package BizDir\Core\User
 */

namespace BizDir\Core\User;

class User_Manager {
    /**
     * User roles specific to the business directory
     */
    const ROLE_CONTRIBUTOR = 'biz_contributor';
    const ROLE_MODERATOR  = 'biz_moderator';
    const ROLE_ADMIN      = 'biz_admin';

    /**
     * Initialize user roles and capabilities
     */
    public function init() {
        add_action('init', [$this, 'register_roles']);
        add_action('user_register', [$this, 'setup_new_user']);
        add_action('delete_user', [$this, 'cleanup_user_data']);
    }

    /**
     * Register custom user roles
     */
    public function register_roles() {
        // Contributor role
        add_role(
            self::ROLE_CONTRIBUTOR,
            __('Business Contributor', 'biz-dir'),
            [
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'publish_posts' => false,
                'upload_files' => true,
                'manage_business_listings' => true,
            ]
        );

        // Moderator role
        add_role(
            self::ROLE_MODERATOR,
            __('Business Moderator', 'biz-dir'),
            [
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'publish_posts' => true,
                'upload_files' => true,
                'manage_business_listings' => true,
                'moderate_reviews' => true,
                'manage_tags' => true,
            ]
        );

        // Admin role (extends WordPress administrator)
        $admin = get_role('administrator');
        $admin->add_cap('manage_business_listings');
        $admin->add_cap('moderate_reviews');
        $admin->add_cap('manage_tags');
        $admin->add_cap('manage_business_settings');
    }

    /**
     * Setup new user reputation and metadata
     *
     * @param int $user_id New user ID
     */
    public function setup_new_user($user_id) {
        global $wpdb;

        // First check if reputation entry exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}biz_user_reputation WHERE user_id = %d",
            $user_id
        ));

        if (!$exists) {
            // Initialize user reputation
            $wpdb->insert(
                $wpdb->prefix . 'biz_user_reputation',
                [
                    'user_id' => $user_id,
                    'reputation_points' => 0,
                    'level' => 'contributor'
                ],
                ['%d', '%d', '%s']
            );
        }

        // Set default notification preferences
        update_user_meta($user_id, 'biz_dir_notifications', [
            'review_replies' => true,
            'business_updates' => true,
            'moderation_status' => true
        ]);
    }

    /**
     * Clean up user data when user is deleted
     *
     * @param int $user_id User ID being deleted
     */
    public function cleanup_user_data($user_id) {
        global $wpdb;

        // Remove user reputation
        $wpdb->delete(
            $wpdb->prefix . 'biz_user_reputation',
            ['user_id' => $user_id],
            ['%d']
        );

        // Clean up user's reviews
        $wpdb->update(
            $wpdb->prefix . 'biz_reviews',
            ['status' => 'deleted'],
            ['user_id' => $user_id],
            ['%s'],
            ['%d']
        );
    }
}
