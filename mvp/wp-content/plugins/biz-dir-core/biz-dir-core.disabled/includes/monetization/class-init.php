<?php
/**
 * Monetization Module Initialization
 * 
 * Coordinates all monetization features including payments, subscriptions, and ads
 */

namespace BizDir\Core\Monetization;

if (!defined('ABSPATH')) {
    exit;
}

class Init {
    private $payment_handler;
    private $subscription_manager;
    private $ad_manager;
    private $analytics_handler;
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menus']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Initialize components
        $this->payment_handler = new Payment_Handler();
        $this->subscription_manager = new Subscription_Manager();
        $this->ad_manager = new Ad_Manager();
        $this->analytics_handler = new Analytics_Handler();
    }
    
    public function init() {
        // Add custom post meta for sponsored businesses
        add_action('add_meta_boxes', [$this, 'add_business_monetization_meta_boxes']);
        add_action('save_post', [$this, 'save_business_monetization_meta']);
        
        // Add sponsored badge to business listings
        add_filter('biz_dir_business_listing_classes', [$this, 'add_sponsored_class']);
        add_action('biz_dir_after_business_title', [$this, 'display_sponsored_badge']);
        
        // Modify search results to prioritize sponsored listings
        add_filter('biz_dir_business_search_results', [$this, 'prioritize_sponsored_results']);
        
        // Add monetization shortcodes
        add_shortcode('biz_dir_payment_form', [$this, 'payment_form_shortcode']);
        add_shortcode('biz_dir_subscription_plans', [$this, 'subscription_plans_shortcode']);
        add_shortcode('biz_dir_ad_placement_form', [$this, 'ad_placement_form_shortcode']);
    }
    
    /**
     * Add admin menus for monetization
     */
    public function add_admin_menus() {
        add_submenu_page(
            'biz-dir-core',
            'Monetization',
            'Monetization',
            'manage_options',
            'biz-dir-monetization',
            [$this, 'monetization_admin_page']
        );
        
        add_submenu_page(
            'biz-dir-core',
            'Payments',
            'Payments',
            'manage_options',
            'biz-dir-payments',
            [$this, 'payments_admin_page']
        );
        
        add_submenu_page(
            'biz-dir-core',
            'Ad Management',
            'Ad Management',
            'manage_options',
            'biz-dir-ads',
            [$this, 'ads_admin_page']
        );
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'biz-dir-monetization',
            plugins_url('assets/js/monetization.js', dirname(dirname(__FILE__))),
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('biz-dir-monetization', 'bizDirMonetization', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('biz_dir_monetization_nonce'),
            'currency' => 'INR',
            'currencySymbol' => '₹'
        ]);
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'biz-dir') === false) {
            return;
        }
        
        wp_enqueue_script(
            'biz-dir-admin-monetization',
            plugins_url('assets/js/admin-monetization.js', dirname(dirname(__FILE__))),
            ['jquery', 'wp-util'],
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'biz-dir-admin-monetization',
            plugins_url('assets/css/admin-monetization.css', dirname(dirname(__FILE__))),
            [],
            '1.0.0'
        );
    }
    
    /**
     * Add business monetization meta boxes
     */
    public function add_business_monetization_meta_boxes() {
        add_meta_box(
            'biz-dir-sponsorship',
            'Sponsorship Status',
            [$this, 'sponsorship_meta_box'],
            'business_listing',
            'side',
            'high'
        );
    }
    
    /**
     * Sponsorship meta box content
     */
    public function sponsorship_meta_box($post) {
        global $wpdb;
        
        // Get current sponsorship status
        $business = $wpdb->get_row($wpdb->prepare(
            "SELECT is_sponsored, sponsored_until, sponsored_plan 
             FROM {$wpdb->prefix}biz_businesses 
             WHERE id = %d",
            $post->ID
        ));
        
        wp_nonce_field('biz_dir_sponsorship_meta', 'biz_dir_sponsorship_nonce');
        
        ?>
        <div class="sponsorship-status">
            <?php if ($business && $business->is_sponsored): ?>
                <div class="sponsored-active">
                    <h4 style="color: #46b450;">✓ Active Sponsorship</h4>
                    <p><strong>Plan:</strong> <?php echo esc_html($business->sponsored_plan); ?></p>
                    <p><strong>Expires:</strong> <?php echo esc_html(date('M j, Y', strtotime($business->sponsored_until))); ?></p>
                </div>
            <?php else: ?>
                <div class="sponsored-inactive">
                    <h4 style="color: #dc3232;">Not Sponsored</h4>
                    <p>This business is not currently sponsored.</p>
                </div>
            <?php endif; ?>
            
            <div class="sponsorship-actions">
                <h4>Admin Actions:</h4>
                <label>
                    <input type="checkbox" name="force_sponsored" value="1" 
                           <?php checked($business && $business->is_sponsored); ?>>
                    Force Sponsored Status
                </label>
                <br><br>
                
                <label for="sponsored_until">Sponsored Until:</label>
                <input type="date" name="sponsored_until" id="sponsored_until" 
                       value="<?php echo $business ? esc_attr(substr($business->sponsored_until, 0, 10)) : ''; ?>">
                <br><br>
                
                <label for="sponsored_plan">Sponsored Plan:</label>
                <select name="sponsored_plan" id="sponsored_plan">
                    <option value="">Select Plan</option>
                    <option value="sponsored_basic" <?php selected($business ? $business->sponsored_plan : '', 'sponsored_basic'); ?>>Basic</option>
                    <option value="sponsored_premium" <?php selected($business ? $business->sponsored_plan : '', 'sponsored_premium'); ?>>Premium</option>
                    <option value="sponsored_enterprise" <?php selected($business ? $business->sponsored_plan : '', 'sponsored_enterprise'); ?>>Enterprise</option>
                </select>
            </div>
        </div>
        
        <style>
        .sponsored-active { padding: 10px; background: #f0f8f0; border: 1px solid #46b450; border-radius: 3px; }
        .sponsored-inactive { padding: 10px; background: #fdf0f0; border: 1px solid #dc3232; border-radius: 3px; }
        .sponsorship-actions { margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; }
        .sponsorship-actions label { font-weight: bold; }
        .sponsorship-actions input, .sponsorship-actions select { margin: 5px 0; width: 100%; }
        </style>
        <?php
    }
    
    /**
     * Save business monetization meta
     */
    public function save_business_monetization_meta($post_id) {
        if (!isset($_POST['biz_dir_sponsorship_nonce']) || 
            !wp_verify_nonce($_POST['biz_dir_sponsorship_nonce'], 'biz_dir_sponsorship_meta')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        global $wpdb;
        
        $force_sponsored = isset($_POST['force_sponsored']) ? 1 : 0;
        $sponsored_until = sanitize_text_field($_POST['sponsored_until']);
        $sponsored_plan = sanitize_text_field($_POST['sponsored_plan']);
        
        // Update business table
        $wpdb->update(
            $wpdb->prefix . 'biz_businesses',
            [
                'is_sponsored' => $force_sponsored,
                'sponsored_until' => $sponsored_until ? $sponsored_until . ' 23:59:59' : null,
                'sponsored_plan' => $sponsored_plan ?: null,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $post_id],
            ['%d', '%s', '%s', '%s'],
            ['%d']
        );
    }
    
    /**
     * Add sponsored class to business listings
     */
    public function add_sponsored_class($classes) {
        global $post;
        
        if ($this->is_business_sponsored($post->ID)) {
            $classes[] = 'sponsored-business';
        }
        
        return $classes;
    }
    
    /**
     * Display sponsored badge
     */
    public function display_sponsored_badge() {
        global $post;
        
        if ($this->is_business_sponsored($post->ID)) {
            echo '<span class="sponsored-badge">Sponsored</span>';
        }
    }
    
    /**
     * Prioritize sponsored results in search
     */
    public function prioritize_sponsored_results($results) {
        if (empty($results)) {
            return $results;
        }
        
        $sponsored = [];
        $regular = [];
        
        foreach ($results as $result) {
            if ($this->is_business_sponsored($result->ID)) {
                $sponsored[] = $result;
            } else {
                $regular[] = $result;
            }
        }
        
        return array_merge($sponsored, $regular);
    }
    
    /**
     * Check if business is sponsored
     */
    private function is_business_sponsored($business_id) {
        global $wpdb;
        
        $sponsored = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}biz_businesses 
             WHERE id = %d AND is_sponsored = 1 AND sponsored_until > NOW()",
            $business_id
        ));
        
        return $sponsored > 0;
    }
    
    /**
     * Payment form shortcode
     */
    public function payment_form_shortcode($atts) {
        $atts = shortcode_atts([
            'business_id' => 0,
            'plan' => 'sponsored_basic'
        ], $atts);
        
        if (!$atts['business_id']) {
            return '<p>Error: Business ID required</p>';
        }
        
        $plans = $this->payment_handler->get_payment_plans();
        $selected_plan = $plans[$atts['plan']] ?? $plans['sponsored_basic'];
        
        ob_start();
        ?>
        <div class="biz-dir-payment-form">
            <h3>Sponsor Your Business</h3>
            <div class="plan-details">
                <h4><?php echo esc_html($selected_plan['name']); ?></h4>
                <p class="plan-price">₹<?php echo number_format($selected_plan['price']); ?> for <?php echo $selected_plan['duration_months']; ?> month(s)</p>
                
                <ul class="plan-features">
                    <?php foreach ($selected_plan['features'] as $feature): ?>
                        <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <form id="biz-dir-payment-form" data-business-id="<?php echo esc_attr($atts['business_id']); ?>" data-plan="<?php echo esc_attr($atts['plan']); ?>">
                <div class="payment-gateway-selection">
                    <h4>Choose Payment Method:</h4>
                    <label><input type="radio" name="gateway" value="razorpay" checked> Razorpay</label>
                    <label><input type="radio" name="gateway" value="payu"> PayU</label>
                    <label><input type="radio" name="gateway" value="stripe"> Stripe</label>
                </div>
                
                <button type="submit" class="payment-submit-btn">
                    Pay ₹<?php echo number_format($selected_plan['price']); ?>
                </button>
            </form>
        </div>
        
        <style>
        .biz-dir-payment-form { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .plan-details { margin-bottom: 20px; }
        .plan-price { font-size: 24px; font-weight: bold; color: #007cba; }
        .plan-features { list-style: none; padding: 0; }
        .plan-features li { padding: 5px 0; }
        .plan-features li:before { content: "✓ "; color: #46b450; font-weight: bold; }
        .payment-gateway-selection label { display: block; margin: 5px 0; }
        .payment-submit-btn { width: 100%; padding: 12px; background: #007cba; color: white; border: none; border-radius: 3px; font-size: 16px; cursor: pointer; }
        .payment-submit-btn:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Subscription plans shortcode
     */
    public function subscription_plans_shortcode($atts) {
        $plans = $this->payment_handler->get_payment_plans();
        
        ob_start();
        ?>
        <div class="biz-dir-subscription-plans">
            <h3>Choose Your Sponsorship Plan</h3>
            <div class="plans-grid">
                <?php foreach ($plans as $plan_id => $plan): ?>
                    <div class="plan-card">
                        <h4><?php echo esc_html($plan['name']); ?></h4>
                        <div class="plan-price">₹<?php echo number_format($plan['price']); ?></div>
                        <div class="plan-duration"><?php echo $plan['duration_months']; ?> month(s)</div>
                        
                        <ul class="plan-features">
                            <?php foreach ($plan['features'] as $feature): ?>
                                <li><?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <a href="#" class="select-plan-btn" data-plan="<?php echo esc_attr($plan_id); ?>">
                            Select Plan
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .biz-dir-subscription-plans { margin: 20px 0; }
        .plans-grid { display: flex; gap: 20px; flex-wrap: wrap; }
        .plan-card { flex: 1; min-width: 250px; padding: 20px; border: 2px solid #ddd; border-radius: 8px; text-align: center; }
        .plan-card:hover { border-color: #007cba; }
        .plan-price { font-size: 32px; font-weight: bold; color: #007cba; margin: 10px 0; }
        .plan-duration { color: #666; margin-bottom: 15px; }
        .plan-features { list-style: none; padding: 0; text-align: left; margin: 20px 0; }
        .plan-features li { padding: 8px 0; border-bottom: 1px solid #eee; }
        .plan-features li:before { content: "✓ "; color: #46b450; font-weight: bold; }
        .select-plan-btn { display: inline-block; padding: 12px 24px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; }
        .select-plan-btn:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Ad placement form shortcode
     */
    public function ad_placement_form_shortcode($atts) {
        $slots = $this->ad_manager->get_available_slots();
        
        ob_start();
        ?>
        <div class="biz-dir-ad-placement-form">
            <h3>Place Your Advertisement</h3>
            <form id="biz-dir-ad-form">
                <div class="form-group">
                    <label for="ad_slot">Ad Placement:</label>
                    <select name="ad_slot_id" id="ad_slot" required>
                        <option value="">Select Ad Placement</option>
                        <?php foreach ($slots as $slot): ?>
                            <option value="<?php echo esc_attr($slot['id']); ?>" data-price="<?php echo esc_attr($slot['price_per_month']); ?>">
                                <?php echo esc_html($slot['slot_name']); ?> - ₹<?php echo number_format($slot['price_per_month']); ?>/month
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ad_title">Ad Title:</label>
                    <input type="text" name="ad_title" id="ad_title" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="ad_description">Description:</label>
                    <textarea name="ad_description" id="ad_description" rows="3" maxlength="200"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="ad_image_url">Image URL:</label>
                    <input type="url" name="ad_image_url" id="ad_image_url">
                </div>
                
                <div class="form-group">
                    <label for="ad_link_url">Link URL:</label>
                    <input type="url" name="ad_link_url" id="ad_link_url" required>
                </div>
                
                <div class="form-group">
                    <label for="ad_cta_text">Call-to-Action Text:</label>
                    <input type="text" name="ad_cta_text" id="ad_cta_text" placeholder="Learn More" maxlength="20">
                </div>
                
                <div class="form-group">
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" required>
                </div>
                
                <div class="cost-calculator">
                    <p>Total Cost: <span id="total-cost">₹0</span></p>
                </div>
                
                <button type="submit">Create Ad Placement</button>
            </form>
        </div>
        
        <style>
        .biz-dir-ad-placement-form { max-width: 500px; margin: 20px 0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        .cost-calculator { padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 3px; margin: 15px 0; }
        .cost-calculator p { margin: 0; font-size: 18px; font-weight: bold; }
        button[type="submit"] { width: 100%; padding: 12px; background: #007cba; color: white; border: none; border-radius: 3px; font-size: 16px; cursor: pointer; }
        button[type="submit"]:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Monetization admin page
     */
    public function monetization_admin_page() {
        ?>
        <div class="wrap">
            <h1>Monetization Overview</h1>
            
            <div class="monetization-dashboard">
                <?php $this->render_monetization_stats(); ?>
                <?php $this->render_payment_gateway_settings(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Payments admin page
     */
    public function payments_admin_page() {
        ?>
        <div class="wrap">
            <h1>Payment Management</h1>
            <?php $this->render_payments_table(); ?>
        </div>
        <?php
    }
    
    /**
     * Ads admin page
     */
    public function ads_admin_page() {
        ?>
        <div class="wrap">
            <h1>Advertisement Management</h1>
            <?php $this->render_ads_management(); ?>
        </div>
        <?php
    }
    
    /**
     * Render monetization statistics
     */
    private function render_monetization_stats() {
        global $wpdb;
        
        $stats = [
            'total_revenue' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}biz_payments WHERE status = 'completed'"),
            'active_sponsorships' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}biz_businesses WHERE is_sponsored = 1 AND sponsored_until > NOW()"),
            'active_ads' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}biz_ad_placements WHERE status = 'active' AND end_date > NOW()"),
            'monthly_revenue' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}biz_payments WHERE status = 'completed' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")
        ];
        
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <p class="stat-number">₹<?php echo number_format($stats['total_revenue'] ?: 0); ?></p>
            </div>
            <div class="stat-card">
                <h3>Active Sponsorships</h3>
                <p class="stat-number"><?php echo intval($stats['active_sponsorships']); ?></p>
            </div>
            <div class="stat-card">
                <h3>Active Ads</h3>
                <p class="stat-number"><?php echo intval($stats['active_ads']); ?></p>
            </div>
            <div class="stat-card">
                <h3>Monthly Revenue</h3>
                <p class="stat-number">₹<?php echo number_format($stats['monthly_revenue'] ?: 0); ?></p>
            </div>
        </div>
        
        <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { padding: 20px; background: white; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .stat-card h3 { margin: 0 0 10px 0; color: #666; }
        .stat-number { font-size: 32px; font-weight: bold; color: #007cba; margin: 0; }
        </style>
        <?php
    }
    
    /**
     * Render payment gateway settings
     */
    private function render_payment_gateway_settings() {
        ?>
        <div class="payment-gateway-settings">
            <h2>Payment Gateway Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('biz_dir_payment_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Razorpay</th>
                        <td>
                            <label>
                                <input type="checkbox" name="biz_dir_razorpay_enabled" value="1" <?php checked(get_option('biz_dir_razorpay_enabled')); ?>>
                                Enable Razorpay
                            </label>
                            <br><br>
                            <input type="text" name="biz_dir_razorpay_key_id" value="<?php echo esc_attr(get_option('biz_dir_razorpay_key_id')); ?>" placeholder="Razorpay Key ID" style="width: 300px;">
                            <br><br>
                            <input type="text" name="biz_dir_razorpay_key_secret" value="<?php echo esc_attr(get_option('biz_dir_razorpay_key_secret')); ?>" placeholder="Razorpay Key Secret" style="width: 300px;">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">PayU</th>
                        <td>
                            <label>
                                <input type="checkbox" name="biz_dir_payu_enabled" value="1" <?php checked(get_option('biz_dir_payu_enabled')); ?>>
                                Enable PayU
                            </label>
                            <br><br>
                            <input type="text" name="biz_dir_payu_merchant_key" value="<?php echo esc_attr(get_option('biz_dir_payu_merchant_key')); ?>" placeholder="PayU Merchant Key" style="width: 300px;">
                            <br><br>
                            <input type="text" name="biz_dir_payu_salt" value="<?php echo esc_attr(get_option('biz_dir_payu_salt')); ?>" placeholder="PayU Salt" style="width: 300px;">
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render payments table
     */
    private function render_payments_table() {
        global $wpdb;
        
        $payments = $wpdb->get_results(
            "SELECT p.*, b.name as business_name, u.display_name as user_name
             FROM {$wpdb->prefix}biz_payments p
             LEFT JOIN {$wpdb->prefix}biz_businesses b ON p.business_id = b.id
             LEFT JOIN {$wpdb->prefix}users u ON p.user_id = u.ID
             ORDER BY p.created_at DESC
             LIMIT 50"
        );
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Business</th>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo esc_html($payment->id); ?></td>
                        <td><?php echo esc_html($payment->business_name); ?></td>
                        <td><?php echo esc_html($payment->user_name); ?></td>
                        <td><?php echo esc_html($payment->plan_type); ?></td>
                        <td>₹<?php echo number_format($payment->amount); ?></td>
                        <td>
                            <span class="status-<?php echo esc_attr($payment->status); ?>">
                                <?php echo esc_html(ucfirst($payment->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date('M j, Y', strtotime($payment->created_at))); ?></td>
                        <td>
                            <a href="#" class="button">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <style>
        .status-completed { color: #46b450; font-weight: bold; }
        .status-pending { color: #ffb900; font-weight: bold; }
        .status-failed { color: #dc3232; font-weight: bold; }
        </style>
        <?php
    }
    
    /**
     * Render ads management
     */
    private function render_ads_management() {
        global $wpdb;
        
        $ad_placements = $wpdb->get_results(
            "SELECT ap.*, s.slot_name, s.position, b.name as business_name
             FROM {$wpdb->prefix}biz_ad_placements ap
             LEFT JOIN {$wpdb->prefix}biz_ad_slots s ON ap.ad_slot_id = s.id
             LEFT JOIN {$wpdb->prefix}biz_businesses b ON ap.business_id = b.id
             ORDER BY ap.created_at DESC
             LIMIT 50"
        );
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Business</th>
                    <th>Ad Slot</th>
                    <th>Position</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Impressions</th>
                    <th>Clicks</th>
                    <th>CTR</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ad_placements as $placement): ?>
                    <?php $ctr = $placement->impressions > 0 ? ($placement->clicks / $placement->impressions) * 100 : 0; ?>
                    <tr>
                        <td><?php echo esc_html($placement->id); ?></td>
                        <td><?php echo esc_html($placement->business_name); ?></td>
                        <td><?php echo esc_html($placement->slot_name); ?></td>
                        <td><?php echo esc_html($placement->position); ?></td>
                        <td><?php echo esc_html(date('M j, Y', strtotime($placement->start_date))); ?></td>
                        <td><?php echo esc_html(date('M j, Y', strtotime($placement->end_date))); ?></td>
                        <td><?php echo number_format($placement->impressions); ?></td>
                        <td><?php echo number_format($placement->clicks); ?></td>
                        <td><?php echo number_format($ctr, 2); ?>%</td>
                        <td>
                            <span class="status-<?php echo esc_attr($placement->status); ?>">
                                <?php echo esc_html(ucfirst($placement->status)); ?>
                            </span>
                        </td>
                        <td>
                            <a href="#" class="button">Edit</a>
                            <a href="#" class="button">Analytics</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
