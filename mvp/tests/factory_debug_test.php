<?php
/**
 * Factory Debug Test
 */

// Load the mock test framework
require_once __DIR__ . '/bootstrap-mock.php';

class factory_debug_test extends WP_UnitTestCase
{
    public function test_user_factory()
    {
        echo "Testing user factory creation...\n";
        
        $user_id = $this->factory->user->create([
            'user_login' => 'test_admin_user',
            'user_email' => 'admin@bizdir-test.local',
            'user_pass' => 'TestAdmin123!',
            'role' => 'administrator'
        ]);
        
        echo "Factory returned user ID: " . var_export($user_id, true) . "\n";
        echo "User ID type: " . gettype($user_id) . "\n";
        
        $this->assertIsInt($user_id, 'Factory should return integer user ID');
        $this->assertGreaterThan(0, $user_id, 'User ID should be positive');
        
        // Test getting user data
        $user = get_user_by('id', $user_id);
        echo "Retrieved user: " . var_export($user, true) . "\n";
        
        echo "Factory test completed successfully!\n";
    }
}
