<?php

namespace BizDir\Tests\Monetization;

use BizDir\Tests\Base_Test_Case;

class SubscriptionManagerTest extends Base_Test_Case {
    private $subscription_manager;
    private $user_id;
    private $business_id;
    
    public function setUp(): void {
        parent::setUp();
        $this->subscription_manager = new \BizDir\Core\Monetization\Subscription_Manager();
        
        // Create test user
        $this->user_id = wp_create_user('testuser', 'testpass', 'test@example.com');
        
        // Create test town first 
        $town_data = $this->setup_helper->create_test_town();
        if (!$town_data['success'] || !$town_data['id']) {
            throw new \RuntimeException('Failed to create test town');
        }
        
        // Create test business with town id
        $business_data = $this->setup_helper->create_test_business([
            'town_id' => $town_data['id'],
            'owner_id' => $this->user_id
        ]);
        if (!$business_data['success'] || !$business_data['id']) {
            throw new \RuntimeException('Failed to create test business: ' . ($business_data['error'] ?? 'Unknown error'));
        }
        $this->business_id = $business_data['id'];
    }
    
    public function test_create_subscription() {
        $subscription_data = [
            'plan_id' => 'premium_monthly',
            'payment_provider' => 'stripe',
            'payment_id' => 'test_payment_' . uniqid(),
            'amount' => 29.99,
            'currency' => 'USD',
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s', strtotime('+1 month'))
        ];
        
        $subscription = $this->subscription_manager->create_subscription(
            $this->user_id, 
            $this->business_id, 
            $subscription_data
        );
        
        $this->assertNotNull($subscription);
        $this->assertEquals($this->user_id, $subscription->user_id);
        $this->assertEquals($this->business_id, $subscription->business_id);
        $this->assertEquals('active', $subscription->status);
    }
    
    public function test_get_active_subscription() {
        // First create a subscription
        $this->test_create_subscription();
        
        $subscription = $this->subscription_manager->get_active_subscription($this->business_id);
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
    }
    
    public function test_cancel_subscription() {
        // First create a subscription
        $this->test_create_subscription();
        
        $result = $this->subscription_manager->cancel_subscription($this->business_id);
        $this->assertTrue($result);
        
        $subscription = $this->subscription_manager->get_active_subscription($this->business_id);
        $this->assertNull($subscription);
    }
    
    public function test_subscription_expiry() {
        $subscription_data = [
            'plan_id' => 'premium_monthly',
            'payment_provider' => 'stripe',
            'payment_id' => 'test_payment_' . uniqid(),
            'amount' => 29.99,
            'currency' => 'USD',
            'start_date' => date('Y-m-d H:i:s', strtotime('-2 months')),
            'end_date' => date('Y-m-d H:i:s', strtotime('-1 month'))
        ];
        
        $subscription = $this->subscription_manager->create_subscription(
            $this->user_id, 
            $this->business_id, 
            $subscription_data
        );
        
        $this->assertFalse($this->subscription_manager->is_subscription_active($this->business_id));
    }
    
    public function test_subscription_features() {
        $features = [
            'featured_listing' => 'true',
            'max_photos' => '10',
            'analytics_access' => 'true'
        ];
        
        $plan_id = 'premium_monthly';
        
        foreach ($features as $key => $value) {
            $this->subscription_manager->add_plan_feature($plan_id, $key, $value);
        }
        
        $saved_features = $this->subscription_manager->get_plan_features($plan_id);
        
        $this->assertCount(3, $saved_features);
        $this->assertEquals($features['max_photos'], $saved_features['max_photos']);
    }
    
    public function test_validate_subscription_data() {
        $invalid_data = [
            'plan_id' => '',
            'payment_provider' => 'invalid_provider',
            'payment_id' => '',
            'amount' => -10,
            'currency' => 'INVALID'
        ];
        
        $this->expectException(\InvalidArgumentException::class);
        $this->subscription_manager->create_subscription(
            $this->user_id,
            $this->business_id,
            $invalid_data
        );
    }
}
