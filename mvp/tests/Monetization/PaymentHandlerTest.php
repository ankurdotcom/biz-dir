<?php

namespace BizDir\Tests\Monetization;

use BizDir\Tests\Base_Test_Case;
use BizDir\Core\Monetization\Payment_Handler;
use BizDir\Core\Business\Business_Manager;
use BizDir\Core\User\User_Manager;

class PaymentHandlerTest extends Base_Test_Case {
    private $payment_handler;
    private $business_manager;
    private $user_manager;
    private $test_user_id;
    private $test_business_id;

    public function setUp(): void {
        parent::setUp();
        
        $this->payment_handler = new Payment_Handler();
        $this->business_manager = new Business_Manager();
        $this->user_manager = new User_Manager();
        
        // Create test user
        $this->test_user_id = $this->factory()->user->create([
            'role' => 'business_owner'
        ]);
        
        // Create test business
        $this->test_business_id = $this->business_manager->create_business([
            'name' => 'Test Business',
            'owner_id' => $this->test_user_id
        ]);
    }

    public function test_create_subscription() {
        $subscription_data = [
            'user_id' => $this->test_user_id,
            'business_id' => $this->test_business_id,
            'plan_id' => 'premium_monthly',
            'payment_provider' => 'stripe',
            'payment_id' => 'test_payment_' . time(),
            'amount' => 29.99,
            'currency' => 'USD',
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s', strtotime('+1 month'))
        ];

        $subscription_id = $this->payment_handler->create_subscription($subscription_data);
        $this->assertIsInt($subscription_id);
        $this->assertGreaterThan(0, $subscription_id);

        $subscription = $this->payment_handler->get_subscription($subscription_id);
        $this->assertEquals($subscription_data['plan_id'], $subscription['plan_id']);
        $this->assertEquals($subscription_data['amount'], $subscription['amount']);
    }

    public function test_update_subscription_status() {
        // Create initial subscription
        $subscription_id = $this->payment_handler->create_subscription([
            'user_id' => $this->test_user_id,
            'business_id' => $this->test_business_id,
            'plan_id' => 'basic_monthly',
            'payment_provider' => 'stripe',
            'payment_id' => 'test_payment_' . time(),
            'amount' => 19.99,
            'currency' => 'USD',
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s', strtotime('+1 month'))
        ]);

        // Update status
        $result = $this->payment_handler->update_subscription_status($subscription_id, 'cancelled');
        $this->assertTrue($result);

        $subscription = $this->payment_handler->get_subscription($subscription_id);
        $this->assertEquals('cancelled', $subscription['status']);
    }

    public function test_process_payment() {
        $payment_data = [
            'subscription_id' => 1,
            'amount' => 29.99,
            'currency' => 'USD',
            'payment_method' => 'card',
            'provider_ref' => 'test_payment_' . time()
        ];

        $payment_id = $this->payment_handler->process_payment($payment_data);
        $this->assertIsInt($payment_id);
        $this->assertGreaterThan(0, $payment_id);

        $payment = $this->payment_handler->get_payment($payment_id);
        $this->assertEquals($payment_data['amount'], $payment['amount']);
        $this->assertEquals('completed', $payment['status']);
    }

    public function test_subscription_features() {
        $features = [
            'featured_listing' => 'true',
            'max_photos' => '10',
            'analytics_access' => 'basic'
        ];

        foreach ($features as $key => $value) {
            $result = $this->payment_handler->set_plan_feature('premium_monthly', $key, $value);
            $this->assertTrue($result);
        }

        $plan_features = $this->payment_handler->get_plan_features('premium_monthly');
        $this->assertIsArray($plan_features);
        $this->assertEquals($features, $plan_features);
    }

    public function test_validate_subscription() {
        $subscription_id = $this->payment_handler->create_subscription([
            'user_id' => $this->test_user_id,
            'business_id' => $this->test_business_id,
            'plan_id' => 'premium_monthly',
            'payment_provider' => 'stripe',
            'payment_id' => 'test_payment_' . time(),
            'amount' => 29.99,
            'currency' => 'USD',
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s', strtotime('+1 month'))
        ]);

        $is_valid = $this->payment_handler->validate_subscription($this->test_business_id);
        $this->assertTrue($is_valid);

        // Test expired subscription
        $this->payment_handler->update_subscription_end_date($subscription_id, date('Y-m-d H:i:s', strtotime('-1 day')));
        $is_valid = $this->payment_handler->validate_subscription($this->test_business_id);
        $this->assertFalse($is_valid);
    }

    public function test_subscription_history() {
        // Create multiple subscriptions
        $subscription_ids = [];
        $plans = ['basic_monthly', 'premium_monthly', 'premium_yearly'];
        
        foreach ($plans as $plan) {
            $subscription_ids[] = $this->payment_handler->create_subscription([
                'user_id' => $this->test_user_id,
                'business_id' => $this->test_business_id,
                'plan_id' => $plan,
                'payment_provider' => 'stripe',
                'payment_id' => 'test_payment_' . time(),
                'amount' => 29.99,
                'currency' => 'USD',
                'start_date' => date('Y-m-d H:i:s'),
                'end_date' => date('Y-m-d H:i:s', strtotime('+1 month'))
            ]);
        }

        $history = $this->payment_handler->get_subscription_history($this->test_business_id);
        $this->assertIsArray($history);
        $this->assertCount(3, $history);
        
        // Verify chronological order
        $this->assertEquals($plans[2], $history[0]['plan_id']);
        $this->assertEquals($plans[0], $history[2]['plan_id']);
    }

    public function test_payment_error_handling() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid payment data');
        
        $this->payment_handler->process_payment([
            'amount' => -10.00,
            'currency' => 'INVALID'
        ]);
    }

    public function test_subscription_validation() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid subscription data');
        
        $this->payment_handler->create_subscription([
            'user_id' => 999999, // Non-existent user
            'business_id' => $this->test_business_id,
            'plan_id' => 'invalid_plan'
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();
    }
}
