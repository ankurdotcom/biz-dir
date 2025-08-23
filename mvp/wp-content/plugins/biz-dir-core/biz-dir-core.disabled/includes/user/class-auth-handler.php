<?php
/**
 * Prevent direct access
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Authentication Handler Class
 *
 * @package BizDir\Core\User
 */

namespace BizDir\Core\User;

class Auth_Handler {
    /**
     * Rate limiter instance
     *
     * @var Auth_Rate_Limiter
     */
    private $rate_limiter;

    /**
     * Constructor
     */
    public function __construct() {
        $this->rate_limiter = new Auth_Rate_Limiter();
    }

    /**
     * Initialize authentication hooks
     */
    public function init() {
        add_action('wp_ajax_nopriv_biz_dir_login', [$this, 'handle_login']);
        add_action('wp_ajax_nopriv_biz_dir_register', [$this, 'handle_registration']);
        add_action('wp_ajax_nopriv_biz_dir_forgot_password', [$this, 'handle_forgot_password']);
        add_action('wp_ajax_biz_dir_verify_email', [$this, 'handle_email_verification']);
        add_action('wp_ajax_nopriv_biz_dir_resend_verification', [$this, 'handle_resend_verification']);
        add_action('template_redirect', [$this, 'check_auth_requirements']);
    }

    /**
     * Handle resend verification email request
     */
    public function handle_resend_verification() {
        if (!check_ajax_referer('biz_dir_resend_verification', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }

        $email = sanitize_email($_POST['email']);
        $user = get_user_by('email', $email);

        if (!$user) {
            wp_send_json_error('User not found');
        }

        if (get_user_meta($user->ID, 'biz_dir_email_verified', true)) {
            wp_send_json_error('Email already verified');
        }

        // Check rate limit for resend
        if (!$this->rate_limiter->check_rate_limit('resend_' . $email)) {
            wp_send_json_error('Please wait before requesting another verification email');
        }

        $this->send_verification_email($user->ID, $email);

        wp_send_json_success([
            'message' => __('Verification email has been resent.', 'biz-dir')
        ]);
    }

    /**
     * Handle user login
     */
    public function handle_login() {
        if (!check_ajax_referer('biz_dir_login', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }

        $username = sanitize_text_field($_POST['username']);

        // Check rate limit
        if (!$this->rate_limiter->check_rate_limit($username)) {
            wp_send_json_error('Too many login attempts. Please try again later.');
        }

        $creds = array(
            'user_login'    => $username,
            'user_password' => $_POST['password'],
            'remember'      => isset($_POST['remember'])
        );

        $user = wp_signon($creds, is_ssl());

        if (is_wp_error($user)) {
            wp_send_json_error($user->get_error_message());
        }

        // Reset rate limit on successful login
        $this->rate_limiter->reset_rate_limit($username);

        wp_send_json_success([
            'redirect_url' => home_url('/dashboard/')
        ]);
    }

    /**
     * Handle user registration
     */
    public function handle_registration() {
        if (!check_ajax_referer('biz_dir_register', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }

        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }

        // Set default role
        $user = new \WP_User($user_id);
        $user->set_role(User_Manager::ROLE_CONTRIBUTOR);

        // Send verification email
        $this->send_verification_email($user_id, $email);

        wp_send_json_success([
            'message' => __('Registration successful. Please check your email to verify your account.', 'biz-dir')
        ]);
    }

    /**
     * Send verification email
     *
     * @param int    $user_id User ID
     * @param string $email   User email
     */
    private function send_verification_email($user_id, $email) {
        $code = wp_generate_password(20, false);
        update_user_meta($user_id, 'biz_dir_email_verification_code', $code);

        $verify_link = add_query_arg([
            'action' => 'verify_email',
            'user' => $user_id,
            'code' => $code
        ], home_url());

        $subject = __('Verify your email address', 'biz-dir');
        $message = sprintf(
            __('Click the following link to verify your email address: %s', 'biz-dir'),
            esc_url($verify_link)
        );

        wp_mail($email, $subject, $message);
    }

    /**
     * Handle email verification
     */
    public function handle_email_verification() {
        $user_id = intval($_GET['user']);
        $code = sanitize_text_field($_GET['code']);

        $stored_code = get_user_meta($user_id, 'biz_dir_email_verification_code', true);

        if ($code === $stored_code) {
            update_user_meta($user_id, 'biz_dir_email_verified', true);
            delete_user_meta($user_id, 'biz_dir_email_verification_code');
            
            wp_safe_redirect(add_query_arg(
                'verified', 'true',
                home_url('/login/')
            ));
            exit;
        }

        wp_safe_redirect(add_query_arg(
            'error', 'invalid_code',
            home_url('/login/')
        ));
        exit;
    }

    /**
     * Handle forgot password requests
     */
    public function handle_forgot_password() {
        if (!check_ajax_referer('biz_dir_forgot_password', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }

        $email = sanitize_email($_POST['email']);
        $user = get_user_by('email', $email);

        if (!$user) {
            wp_send_json_error('User not found');
        }

        $key = get_password_reset_key($user);
        $reset_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');

        $subject = __('Password Reset Request', 'biz-dir');
        $message = sprintf(
            __('Click the following link to reset your password: %s', 'biz-dir'),
            esc_url($reset_link)
        );

        wp_mail($email, $subject, $message);

        wp_send_json_success([
            'message' => __('Password reset instructions have been sent to your email.', 'biz-dir')
        ]);
    }

    /**
     * Check authentication requirements for protected pages
     */
    public function check_auth_requirements() {
        // Protected paths that require authentication
        $protected_paths = [
            'dashboard',
            'submit-business',
            'edit-business',
            'moderate'
        ];

        $current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $path_parts = explode('/', $current_path);

        if (in_array($path_parts[0], $protected_paths) && !is_user_logged_in()) {
            wp_safe_redirect(home_url('/login/'));
            exit;
        }

        // Check moderator area access
        if ($path_parts[0] === 'moderate') {
            $user = wp_get_current_user();
            if (!in_array(User_Manager::ROLE_MODERATOR, $user->roles) && 
                !in_array(User_Manager::ROLE_ADMIN, $user->roles)) {
                wp_die(__('You do not have permission to access this area.', 'biz-dir'));
            }
        }
    }
}
