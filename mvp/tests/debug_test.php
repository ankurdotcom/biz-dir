<?php
/**
 * Debug Test to identify regression test issues
 */

// Load the mock test framework
require_once __DIR__ . '/bootstrap-mock.php';
require_once __DIR__ . '/Regression/RegressionTestCase.php';

class debug_test extends WP_UnitTestCase
{
    public function test_basic_functionality()
    {
        echo "Testing basic functionality...\n";
        $this->assertTrue(true, 'Basic assertion should pass');
    }
    
    public function test_wordpress_functions()
    {
        echo "Testing WordPress functions...\n";
        
        // Test user creation
        $user_id = $this->factory->user->create([
            'user_login' => 'debug_test_user',
            'user_email' => 'debug@test.local',
            'user_pass' => 'TestPassword123!'
        ]);
        
        $this->assertIsInt($user_id, 'User creation should return integer ID');
        
        // Test user authentication
        $auth_result = wp_authenticate('debug_test_user', 'TestPassword123!');
        $this->assertIsObject($auth_result, 'Authentication should return object');
        $this->assertTrue(property_exists($auth_result, 'ID'), 'Auth result should have ID property');
        
        echo "WordPress functions test passed!\n";
    }
    
    public function test_regression_base_class()
    {
        echo "Testing if we can instantiate RegressionTestCase...\n";
        
        $testCase = new class extends RegressionTestCase {
            public function test_dummy() {
                $this->assertTrue(true);
            }
        };
        
        $this->assertInstanceOf('RegressionTestCase', $testCase, 'Should be able to instantiate RegressionTestCase');
        echo "RegressionTestCase instantiation test passed!\n";
    }
}
