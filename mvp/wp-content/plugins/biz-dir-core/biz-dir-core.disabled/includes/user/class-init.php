<?php
/**
 * Prevent direct access
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Module Initialization
 *
 * @package BizDir\Core\User
 */

namespace BizDir\Core\User;

class Init {
    /**
     * @var User_Manager
     */
    private $user_manager;

    /**
     * @var Auth_Handler
     */
    private $auth_handler;

    /**
     * @var Permission_Handler
     */
    private $permission_handler;

    /**
     * Initialize the user module
     */
    public function __construct() {
        error_log('[BizDir User] Initializing User Module');
        $this->user_manager = new User_Manager();
        $this->auth_handler = new Auth_Handler();
        $this->permission_handler = new Permission_Handler();

        $this->init();
    }

    public function init() {
        error_log('[BizDir User] Setting up User Module hooks');
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize components
        $this->user_manager->init();
        $this->auth_handler->init();
        $this->permission_handler->init();

        // Additional module hooks
        add_action('rest_api_init', [$this, 'register_endpoints']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Register REST API endpoints
     */
    public function register_endpoints() {
        register_rest_route('biz-dir/v1', '/user/profile', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user_profile'],
            'permission_callback' => function () {
                return is_user_logged_in();
            }
        ]);

        register_rest_route('biz-dir/v1', '/user/profile', [
            'methods' => 'POST',
            'callback' => [$this, 'update_user_profile'],
            'permission_callback' => function () {
                return is_user_logged_in();
            }
        ]);
    }

    /**
     * Get user profile data
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_user_profile($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!$user) {
            return new \WP_Error(
                'user_not_found',
                __('User not found', 'biz-dir'),
                ['status' => 404]
            );
        }

        return rest_ensure_response([
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'reputation' => $this->get_user_reputation($user_id),
            'notifications' => get_user_meta($user_id, 'biz_dir_notifications', true),
            'capabilities' => array_keys($user->allcaps)
        ]);
    }

    /**
     * Update user profile
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_user_profile($request) {
        $user_id = get_current_user_id();
        $params = $request->get_params();

        // Update basic user data
        $user_data = [
            'ID' => $user_id
        ];

        if (isset($params['display_name'])) {
            $user_data['display_name'] = sanitize_text_field($params['display_name']);
        }

        if (!empty($user_data)) {
            wp_update_user($user_data);
        }

        // Update notification preferences
        if (isset($params['notifications'])) {
            update_user_meta(
                $user_id,
                'biz_dir_notifications',
                array_map('rest_sanitize_boolean', $params['notifications'])
            );
        }

        return rest_ensure_response([
            'status' => 'success',
            'message' => __('Profile updated successfully', 'biz-dir')
        ]);
    }

    /**
     * Enqueue user module scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'biz-dir-user',
            plugins_url('assets/js/user.js', BIZ_DIR_PLUGIN_DIR),
            ['jquery'],
            BIZ_DIR_VERSION,
            true
        );

        wp_localize_script('biz-dir-user', 'bizDirUser', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => get_rest_url(null, 'biz-dir/v1'),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
    }

    /**
     * Get user reputation
     *
     * @param int $user_id
     * @return int
     */
    private function get_user_reputation($user_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT reputation_points FROM {$wpdb->prefix}biz_user_reputation WHERE user_id = %d",
            $user_id
        ));
    }
}
