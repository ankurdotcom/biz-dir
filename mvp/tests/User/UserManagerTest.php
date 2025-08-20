<?php
/**
 * User Manager Test Case
 */

namespace BizDir\Tests\User;

use BizDir\Core\User\User_Manager;
use BizDir\Tests\Base_Test_Case;

class UserManagerTest extends Base_Test_Case {
    private $user_manager;
    private $test_user_id;

    public function setUp(): void {
        parent::setUp();
        $this->user_manager = new User_Manager();
        
        // Create test user
        $this->test_user_id = $this->factory->user->create([
            'role' => User_Manager::ROLE_CONTRIBUTOR
        ]);
    }

    public function tearDown(): void {
        wp_delete_user($this->test_user_id);
        parent::tearDown();
    }

    public function test_register_roles() {
        $this->user_manager->register_roles();

        // Test contributor role
        $contributor_role = get_role(User_Manager::ROLE_CONTRIBUTOR);
        $this->assertNotNull($contributor_role);
        $this->assertTrue($contributor_role->has_cap('manage_business_listings'));
        $this->assertFalse($contributor_role->has_cap('moderate_reviews'));

        // Test moderator role
        $moderator_role = get_role(User_Manager::ROLE_MODERATOR);
        $this->assertNotNull($moderator_role);
        $this->assertTrue($moderator_role->has_cap('moderate_reviews'));
        $this->assertTrue($moderator_role->has_cap('manage_tags'));

        // Test admin capabilities
        $admin_role = get_role('administrator');
        $this->assertTrue($admin_role->has_cap('manage_business_settings'));
    }

    public function test_setup_new_user() {
        $this->user_manager->setup_new_user($this->test_user_id);

        global $wpdb;
        
        // Check reputation entry
        $reputation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_user_reputation WHERE user_id = %d",
            $this->test_user_id
        ));
        
        $this->assertNotNull($reputation);
        $this->assertEquals(0, $reputation->reputation_points);
        $this->assertEquals('contributor', $reputation->level);

        // Check notification preferences
        $notifications = get_user_meta($this->test_user_id, 'biz_dir_notifications', true);
        $this->assertIsArray($notifications);
        $this->assertTrue($notifications['review_replies']);
        $this->assertTrue($notifications['business_updates']);
        $this->assertTrue($notifications['moderation_status']);
    }

    public function test_cleanup_user_data() {
        // Setup test data
        $this->user_manager->setup_new_user($this->test_user_id);
        
        // Test cleanup
        $this->user_manager->cleanup_user_data($this->test_user_id);

        global $wpdb;
        
        // Verify reputation removed
        $reputation = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}biz_user_reputation WHERE user_id = %d",
            $this->test_user_id
        ));
        
        $this->assertEquals(0, $reputation);
    }
}
