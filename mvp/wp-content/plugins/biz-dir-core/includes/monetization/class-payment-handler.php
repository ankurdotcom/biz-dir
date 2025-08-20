<?php

namespace BizDir\Core\Monetization;

class Payment_Handler {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }
    
    public function create_subscription($data) {
        if (!$this->validate_subscription_data($data)) {
            throw new \Exception('Invalid subscription data');
        }
        
        $result = $this->db->insert(
            $this->db->prefix . 'biz_subscriptions',
            [
                'user_id' => $data['user_id'],
                'business_id' => $data['business_id'],
                'plan_id' => $data['plan_id'],
                'payment_provider' => $data['payment_provider'],
                'payment_id' => $data['payment_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'active'
            ],
            ['%d', '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s']
        );
        
        if (!$result) {
            throw new \Exception('Failed to create subscription');
        }
        
        return $this->db->insert_id;
    }
    
    public function get_subscription($subscription_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}biz_subscriptions WHERE id = %d",
                $subscription_id
            ),
            ARRAY_A
        );
    }
    
    public function update_subscription_status($subscription_id, $status) {
        $result = $this->db->update(
            $this->db->prefix . 'biz_subscriptions',
            ['status' => $status],
            ['id' => $subscription_id],
            ['%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
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
                'subscription_id' => $data['subscription_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'status' => 'completed',
                'provider_ref' => $data['provider_ref'],
                'payment_method' => $data['payment_method']
            ],
            ['%d', '%f', '%s', '%s', '%s', '%s']
        );
        
        if (!$result) {
            throw new \Exception('Failed to record payment');
        }
        
        return $this->db->insert_id;
    }
    
    public function get_payment($payment_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}biz_payments WHERE id = %d",
                $payment_id
            ),
            ARRAY_A
        );
    }
    
    public function set_plan_feature($plan_id, $feature_key, $feature_value) {
        $result = $this->db->replace(
            $this->db->prefix . 'biz_subscription_features',
            [
                'plan_id' => $plan_id,
                'feature_key' => $feature_key,
                'feature_value' => $feature_value
            ],
            ['%s', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    public function get_plan_features($plan_id) {
        $results = $this->db->get_results(
            $this->db->prepare(
                "SELECT feature_key, feature_value 
                FROM {$this->db->prefix}biz_subscription_features 
                WHERE plan_id = %s",
                $plan_id
            ),
            ARRAY_A
        );
        
        if (!$results) {
            return [];
        }
        
        $features = [];
        foreach ($results as $row) {
            $features[$row['feature_key']] = $row['feature_value'];
        }
        
        return $features;
    }
    
    public function validate_subscription($business_id) {
        $subscription = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}biz_subscriptions 
                WHERE business_id = %d AND status = 'active' 
                ORDER BY end_date DESC 
                LIMIT 1",
                $business_id
            )
        );
        
        if (!$subscription) {
            return false;
        }
        
        return strtotime($subscription->end_date) > time();
    }
    
    public function update_subscription_end_date($subscription_id, $end_date) {
        return $this->db->update(
            $this->db->prefix . 'biz_subscriptions',
            ['end_date' => $end_date],
            ['id' => $subscription_id],
            ['%s'],
            ['%d']
        );
    }
    
    public function get_subscription_history($business_id) {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}biz_subscriptions 
                WHERE business_id = %d 
                ORDER BY created_at DESC",
                $business_id
            ),
            ARRAY_A
        );
    }
    
    private function validate_subscription_data($data) {
        $required_fields = [
            'user_id',
            'business_id',
            'plan_id',
            'payment_provider',
            'payment_id',
            'amount',
            'currency',
            'start_date',
            'end_date'
        ];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        
        // Validate user exists
        $user = get_user_by('id', $data['user_id']);
        if (!$user) {
            return false;
        }
        
        // Validate business exists
        $business = get_post($data['business_id']);
        if (!$business || $business->post_type !== 'business_listing') {
            return false;
        }
        
        return true;
    }
    
    private function validate_payment_data($data) {
        $required_fields = [
            'subscription_id',
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
        
        if (!in_array($data['currency'], ['USD', 'EUR', 'GBP'])) {
            return false;
        }
        
        return true;
    }
    
    private function process_with_provider($data) {
        // This is a placeholder for actual payment processing
        // In a real implementation, this would integrate with Stripe, PayPal, etc.
        return true;
    }
}
