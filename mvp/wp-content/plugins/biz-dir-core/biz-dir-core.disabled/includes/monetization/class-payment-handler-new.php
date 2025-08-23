<?php
/**
 * Enhanced Payment Handler Class
 * 
 * Handles payment processing for sponsored listings and premium features
 */

namespace BizDir\Core\Monetization;

if (!defined('ABSPATH')) {
    exit;
}

class Payment_Handler {
    private $db;
    private $payment_gateways = [];
    private $currency = 'INR';
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        add_action('init', [$this, 'init']);
        add_action('wp_ajax_biz_dir_process_payment', [$this, 'process_payment_ajax']);
        add_action('wp_ajax_nopriv_biz_dir_process_payment', [$this, 'process_payment_ajax']);
        
        // Register payment gateways
        $this->register_payment_gateways();
    }
    
    public function init() {
        $this->setup_payment_hooks();
    }
    
    /**
     * Register available payment gateways
     */
    private function register_payment_gateways() {
        $this->payment_gateways = [
            'razorpay' => [
                'name' => 'Razorpay',
                'enabled' => get_option('biz_dir_razorpay_enabled', false),
                'key_id' => get_option('biz_dir_razorpay_key_id', ''),
                'key_secret' => get_option('biz_dir_razorpay_key_secret', ''),
            ],
            'payu' => [
                'name' => 'PayU',
                'enabled' => get_option('biz_dir_payu_enabled', false),
                'merchant_key' => get_option('biz_dir_payu_merchant_key', ''),
                'salt' => get_option('biz_dir_payu_salt', ''),
            ],
            'stripe' => [
                'name' => 'Stripe',
                'enabled' => get_option('biz_dir_stripe_enabled', false),
                'public_key' => get_option('biz_dir_stripe_public_key', ''),
                'secret_key' => get_option('biz_dir_stripe_secret_key', ''),
            ]
        ];
    }
    
    /**
     * Create a payment intent for sponsored listing
     */
    public function create_payment_intent($business_id, $plan_type, $amount, $duration_months = 1) {
        // Validate inputs
        if (!$business_id || !$plan_type || !$amount) {
            return new \WP_Error('invalid_params', 'Invalid payment parameters');
        }
        
        // Create payment record
        $payment_data = [
            'business_id' => $business_id,
            'user_id' => get_current_user_id(),
            'plan_type' => $plan_type,
            'amount' => $amount,
            'currency' => $this->currency,
            'duration_months' => $duration_months,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        ];
        
        $payment_id = $this->db->insert(
            $this->db->prefix . 'biz_payments',
            $payment_data
        );
        
        if (!$payment_id) {
            return new \WP_Error('payment_creation_failed', 'Failed to create payment record');
        }
        
        return [
            'payment_id' => $this->db->insert_id,
            'amount' => $amount,
            'currency' => $this->currency,
            'business_id' => $business_id,
            'plan_type' => $plan_type
        ];
    }
    
    /**
     * AJAX handler for payment processing
     */
    public function process_payment_ajax() {
        check_ajax_referer('biz_dir_payment_nonce', 'nonce');
        
        $payment_id = sanitize_text_field($_POST['payment_id']);
        $gateway = sanitize_text_field($_POST['gateway']);
        $transaction_data = $_POST['transaction_data'];
        
        // Verify payment with gateway
        $verification_result = $this->verify_payment_with_gateway($gateway, $transaction_data);
        
        if (is_wp_error($verification_result)) {
            wp_send_json_error(['message' => $verification_result->get_error_message()]);
            return;
        }
        
        // Update payment status
        $this->update_payment_status($payment_id, 'completed', $verification_result);
        
        // Activate sponsored features
        $this->activate_sponsored_features($payment_id);
        
        wp_send_json_success(['message' => 'Payment processed successfully']);
    }
    
    /**
     * Get available payment plans
     */
    public function get_payment_plans() {
        return [
            'sponsored_basic' => [
                'name' => 'Sponsored Listing - Basic',
                'price' => 500,
                'duration_months' => 1,
                'features' => [
                    'Top placement in search results',
                    'Sponsored badge',
                    'Priority listing in town pages'
                ]
            ],
            'sponsored_premium' => [
                'name' => 'Sponsored Listing - Premium', 
                'price' => 1200,
                'duration_months' => 3,
                'features' => [
                    'Top placement in search results',
                    'Sponsored badge',
                    'Priority listing in town pages',
                    'Featured images',
                    'Extended business description',
                    'Contact form integration'
                ]
            ],
            'sponsored_enterprise' => [
                'name' => 'Sponsored Listing - Enterprise',
                'price' => 4000,
                'duration_months' => 12,
                'features' => [
                    'All Premium features',
                    'Custom business page design',
                    'Analytics dashboard',
                    'Priority customer support',
                    'Social media integration',
                    'Advanced SEO features'
                ]
            ]
        ];
    }
    
    /**
     * Verify payment with specific gateway
     */
    private function verify_payment_with_gateway($gateway, $transaction_data) {
        switch ($gateway) {
            case 'razorpay':
                return $this->verify_razorpay_payment($transaction_data);
            case 'payu':
                return $this->verify_payu_payment($transaction_data);
            case 'stripe':
                return $this->verify_stripe_payment($transaction_data);
            default:
                return new \WP_Error('invalid_gateway', 'Invalid payment gateway');
        }
    }
    
    /**
     * Verify Razorpay payment
     */
    private function verify_razorpay_payment($transaction_data) {
        $razorpay_config = $this->payment_gateways['razorpay'];
        
        if (!$razorpay_config['enabled']) {
            return new \WP_Error('gateway_disabled', 'Razorpay is not enabled');
        }
        
        // Verify signature
        $expected_signature = hash_hmac('sha256', 
            $transaction_data['order_id'] . '|' . $transaction_data['payment_id'], 
            $razorpay_config['key_secret']
        );
        
        if (!hash_equals($expected_signature, $transaction_data['signature'])) {
            return new \WP_Error('signature_mismatch', 'Payment signature verification failed');
        }
        
        return [
            'gateway_transaction_id' => $transaction_data['payment_id'],
            'gateway_order_id' => $transaction_data['order_id'],
            'verified_at' => current_time('mysql')
        ];
    }
    
    /**
     * Update payment status
     */
    private function update_payment_status($payment_id, $status, $transaction_data = []) {
        $update_data = [
            'status' => $status,
            'updated_at' => current_time('mysql')
        ];
        
        if (!empty($transaction_data)) {
            $update_data['gateway_transaction_id'] = $transaction_data['gateway_transaction_id'];
            $update_data['gateway_order_id'] = $transaction_data['gateway_order_id'];
            $update_data['verified_at'] = $transaction_data['verified_at'];
        }
        
        return $this->db->update(
            $this->db->prefix . 'biz_payments',
            $update_data,
            ['id' => $payment_id],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );
    }
    
    /**
     * Activate sponsored features for business
     */
    private function activate_sponsored_features($payment_id) {
        // Get payment details
        $payment = $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->db->prefix}biz_payments WHERE id = %d",
            $payment_id
        ));
        
        if (!$payment) {
            return false;
        }
        
        // Calculate expiry date
        $expiry_date = date('Y-m-d H:i:s', strtotime("+{$payment->duration_months} months"));
        
        // Update business sponsored status
        $this->db->update(
            $this->db->prefix . 'biz_businesses',
            [
                'is_sponsored' => 1,
                'sponsored_until' => $expiry_date,
                'sponsored_plan' => $payment->plan_type,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $payment->business_id],
            ['%d', '%s', '%s', '%s'],
            ['%d']
        );
        
        // Log the sponsorship activation
        do_action('biz_dir_sponsorship_activated', $payment->business_id, $payment->plan_type, $expiry_date);
        
        return true;
    }
    
    /**
     * Setup payment hooks
     */
    private function setup_payment_hooks() {
        // Hook for expired sponsorships
        add_action('biz_dir_check_expired_sponsorships', [$this, 'check_expired_sponsorships']);
        
        // Schedule daily check if not already scheduled
        if (!wp_next_scheduled('biz_dir_check_expired_sponsorships')) {
            wp_schedule_event(time(), 'daily', 'biz_dir_check_expired_sponsorships');
        }
    }
    
    /**
     * Check and deactivate expired sponsorships
     */
    public function check_expired_sponsorships() {
        $expired_businesses = $this->db->get_results(
            "SELECT id FROM {$this->db->prefix}biz_businesses 
             WHERE is_sponsored = 1 
             AND sponsored_until < NOW()"
        );
        
        foreach ($expired_businesses as $business) {
            $this->db->update(
                $this->db->prefix . 'biz_businesses',
                [
                    'is_sponsored' => 0,
                    'sponsored_until' => null,
                    'sponsored_plan' => null,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $business->id],
                ['%d', '%s', '%s', '%s'],
                ['%d']
            );
            
            do_action('biz_dir_sponsorship_expired', $business->id);
        }
    }
    
    /**
     * Process payment via legacy method
     */
    public function process_payment($data) {
        if (!$this->validate_payment_data($data)) {
            throw new \Exception('Invalid payment data');
        }
        
        // Process payment with provider
        $payment_result = $this->process_with_provider($data);
        
        if (!$payment_result) {
            throw new \Exception('Payment processing failed');
        }
        
        // Record payment
        $result = $this->db->insert(
            $this->db->prefix . 'biz_payments',
            [
                'business_id' => $data['business_id'],
                'user_id' => $data['user_id'],
                'plan_type' => $data['plan_type'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'status' => 'completed',
                'gateway_transaction_id' => $data['provider_ref'],
                'payment_method' => $data['payment_method']
            ],
            ['%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s']
        );
        
        if (!$result) {
            throw new \Exception('Failed to record payment');
        }
        
        return $this->db->insert_id;
    }
    
    /**
     * Get payment by ID
     */
    public function get_payment($payment_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}biz_payments WHERE id = %d",
                $payment_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Validate payment data
     */
    private function validate_payment_data($data) {
        $required_fields = [
            'business_id',
            'user_id', 
            'amount',
            'currency',
            'payment_method',
            'provider_ref'
        ];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        
        if ($data['amount'] <= 0) {
            return false;
        }
        
        if (!in_array($data['currency'], ['INR', 'USD', 'EUR', 'GBP'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Process with payment provider
     */
    private function process_with_provider($data) {
        // This is a placeholder for actual payment processing
        // In a real implementation, this would integrate with Razorpay, PayU, Stripe, etc.
        return true;
    }
}
