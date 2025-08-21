<?php
/**
 * Subscription Manager Class
 *
 * Handles all subscription-related operations including creation, management,
 * and feature validation for business listings.
 *
 * @package BizDir\Core\Monetization
 */

namespace BizDir\Core\Monetization;

use BizDir\Core\Utility\Logger;

class Subscription_Manager {
    private $logger;
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->logger = new Logger('subscription');
    }
    
    /**
     * Creates a new subscription for a business
     *
     * @param int $user_id The user ID
     * @param int $business_id The business ID
     * @param array $data Subscription data
     * @return object|null Subscription object or null on failure
     */
    public function create_subscription($user_id, $business_id, array $data) {
        $this->logger->info('Creating new subscription', [
            'user_id' => $user_id,
            'business_id' => $business_id,
            'data' => $data
        ]);
        
        try {
            $this->validate_subscription_data($data);
            
            $subscription_data = [
                'user_id' => $user_id,
                'business_id' => $business_id,
                'plan_id' => $data['plan_id'],
                'payment_provider' => $data['payment_provider'],
                'payment_id' => $data['payment_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'status' => 'active',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date']
            ];
            
            $result = $this->db->insert(
                $this->db->prefix . 'biz_subscriptions',
                $subscription_data
            );
            
            if ($result === false) {
                throw new \Exception('Failed to insert subscription: ' . $this->db->last_error);
            }
            
            $subscription_id = $this->db->insert_id;
            $this->logger->info('Subscription created successfully', ['id' => $subscription_id]);
            
            return $this->get_subscription($subscription_id);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create subscription', [
                'error' => $e->getMessage(),
                'user_id' => $user_id,
                'business_id' => $business_id
            ]);
            throw $e;
        }
    }
    
    /**
     * Gets the active subscription for a business
     *
     * @param int $business_id The business ID
     * @return object|null Subscription object or null if none found
     */
    public function get_active_subscription($business_id) {
        $this->logger->debug('Getting active subscription', ['business_id' => $business_id]);
        
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->db->prefix}biz_subscriptions 
             WHERE business_id = %d 
             AND status = 'active' 
             AND end_date > NOW()",
            $business_id
        );
        
        return $this->db->get_row($sql);
    }
    
    /**
     * Cancels a subscription
     *
     * @param int $business_id The business ID
     * @return bool True on success, false on failure
     */
    public function cancel_subscription($business_id) {
        $this->logger->info('Canceling subscription', ['business_id' => $business_id]);
        
        try {
            $subscription = $this->get_active_subscription($business_id);
            if (!$subscription) {
                throw new \Exception('No active subscription found');
            }
            
            $result = $this->db->update(
                $this->db->prefix . 'biz_subscriptions',
                ['status' => 'cancelled'],
                ['id' => $subscription->id]
            );
            
            if ($result === false) {
                throw new \Exception('Failed to cancel subscription: ' . $this->db->last_error);
            }
            
            $this->logger->info('Subscription cancelled successfully', ['id' => $subscription->id]);
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel subscription', [
                'error' => $e->getMessage(),
                'business_id' => $business_id
            ]);
            return false;
        }
    }
    
    /**
     * Checks if a subscription is active
     *
     * @param int $business_id The business ID
     * @return bool True if active, false otherwise
     */
    public function is_subscription_active($business_id) {
        $subscription = $this->get_active_subscription($business_id);
        return $subscription !== null;
    }
    
    /**
     * Adds a feature to a subscription plan
     *
     * @param string $plan_id The plan ID
     * @param string $key Feature key
     * @param string $value Feature value
     * @return bool True on success, false on failure
     */
    public function add_plan_feature($plan_id, $key, $value) {
        $this->logger->debug('Adding plan feature', [
            'plan_id' => $plan_id,
            'key' => $key,
            'value' => $value
        ]);
        
        try {
            $data = [
                'plan_id' => $plan_id,
                'feature_key' => $key,
                'feature_value' => $value
            ];
            
            $result = $this->db->insert(
                $this->db->prefix . 'biz_subscription_features',
                $data
            );
            
            if ($result === false) {
                throw new \Exception('Failed to add plan feature: ' . $this->db->last_error);
            }
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to add plan feature', [
                'error' => $e->getMessage(),
                'plan_id' => $plan_id,
                'key' => $key
            ]);
            return false;
        }
    }
    
    /**
     * Gets all features for a subscription plan
     *
     * @param string $plan_id The plan ID
     * @return array Array of features
     */
    public function get_plan_features($plan_id) {
        $sql = $this->db->prepare(
            "SELECT feature_key, feature_value 
             FROM {$this->db->prefix}biz_subscription_features 
             WHERE plan_id = %s",
            $plan_id
        );
        
        $results = $this->db->get_results($sql);
        $features = [];
        
        foreach ($results as $row) {
            $features[$row->feature_key] = $row->feature_value;
        }
        
        return $features;
    }
    
    /**
     * Gets a subscription by ID
     *
     * @param int $subscription_id The subscription ID
     * @return object|null Subscription object or null if not found
     */
    private function get_subscription($subscription_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}biz_subscriptions WHERE id = %d",
                $subscription_id
            )
        );
    }
    
    /**
     * Validates subscription data
     *
     * @param array $data The subscription data to validate
     * @throws \InvalidArgumentException If data is invalid
     */
    private function validate_subscription_data($data) {
        $required_fields = ['plan_id', 'payment_provider', 'payment_id', 'amount', 'currency'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
        
        if ($data['amount'] <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        
        if (!in_array($data['currency'], ['USD', 'EUR', 'GBP'])) {
            throw new \InvalidArgumentException('Invalid currency');
        }
        
        if (!in_array($data['payment_provider'], ['stripe', 'paypal'])) {
            throw new \InvalidArgumentException('Invalid payment provider');
        }
    }
}
