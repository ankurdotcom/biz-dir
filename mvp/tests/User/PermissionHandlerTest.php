<?php
/**
 class PermissionHandlerTest extends Base_Test_Case {
    private $permission_handler;
    private $test_user_id;
    private $test_business_id;
    private $wpdb;rmission Handler Test Case
 */

namespace BizDir\Tests\User;

use BizDir\Core\User\Permission_Handler;
use BizDir\Core\User\User_Manager;
use WP_UnitTestCase;

class PermissionHandlerTest extends WP_UnitTestCase {
    private $permission_handler;
    private $test_user_id;
    private $test_business_id;

    public function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->permission_handler = new Permission_Handler();
        
        /** @var \wpdb $wpdb */
        $this->wpdb = $wpdb;
        
        // Clean up reputation table before each test
        $this->wpdb->query("TRUNCATE TABLE {$this->wpdb->prefix}biz_user_reputation");
        
        // Create test user
        $this->test_user_id = $this->factory->user->create([
            'role' => User_Manager::ROLE_CONTRIBUTOR
        ]);

        // Create test business
        $this->test_business_id = $this->factory->post->create([
            'post_type' => 'business_listing',
            'post_author' => $this->test_user_id
        ]);

        // Set up reputation
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}biz_user_reputation");
        $wpdb->insert(
            $wpdb->prefix . 'biz_user_reputation',
            [
                'user_id' => $this->test_user_id,
                'reputation_points' => 100,
                'level' => 'contributor'
            ],
            ['%d', '%d', '%s']
        );
    }

    public function tearDown(): void {
        // Clean up reputation table after each test
        $this->wpdb->query("TRUNCATE TABLE {$this->wpdb->prefix}biz_user_reputation");
        wp_delete_post($this->test_business_id, true);
        wp_delete_user($this->test_user_id);
        parent::tearDown();
    }

    public function test_reputation_based_capabilities() {
        $user = get_user_by('id', $this->test_user_id);

        // Test publish capability with 100 reputation
        $allcaps = [];
        $allcaps = $this->permission_handler->check_reputation_caps(
            $allcaps,
            ['publish_business_listings'],
            ['publish_business_listings', $this->test_user_id],
            $user
        );
        $this->assertTrue($allcaps['publish_business_listings']);

        // Test moderate capability with insufficient reputation
        $allcaps = $this->permission_handler->check_reputation_caps(
            [],
            ['moderate_reviews'],
            ['moderate_reviews', $this->test_user_id],
            $user
        );
        $this->assertFalse($allcaps['moderate_reviews']);
    }



    public function test_business_editing_permissions() {
        // Test owner can edit their own business
        $this->assertTrue($this->permission_handler->can_edit_business($this->test_user_id, $this->test_business_id), 'Owner should be able to edit their own business');

        // Test non-owner cannot edit business
        $other_user_id = $this->factory->user->create(['role' => User_Manager::ROLE_CONTRIBUTOR]);
        global $wpdb;
        $this->wpdb->replace(
            $this->wpdb->prefix . 'biz_user_reputation',
            [
                'user_id' => $other_user_id,
                'reputation_points' => 50,
                'level' => 'contributor'
            ],
            ['%d', '%d', '%s']
        );
        $this->assertFalse($this->permission_handler->can_edit_business($other_user_id, $this->test_business_id), 'Non-owner should not be able to edit business');

        // Test admin can edit any business
        $admin_id = $this->factory->user->create(['role' => 'administrator']);
        $admin = new \WP_User($admin_id);
        $admin->add_cap('edit_others_business_listings');
        $this->wpdb->replace(
            $this->wpdb->prefix . 'biz_user_reputation',
            [
                'user_id' => $admin_id,
                'reputation_points' => 150,
                'level' => 'admin'
            ],
            ['%d', '%d', '%s']
        );
        $this->assertTrue($this->permission_handler->can_edit_business($admin_id, $this->test_business_id), 'Admin should be able to edit any business');
    }

    public function test_can_moderate() {
        // Test contributor cannot moderate
        $this->assertFalse(
            Permission_Handler::can('moderate_content', $this->test_user_id)
        );

        // Test moderator can moderate
        $moderator_id = $this->factory->user->create([
            'role' => User_Manager::ROLE_MODERATOR
        ]);
        $this->assertTrue(
            Permission_Handler::can('moderate_content', $moderator_id)
        );

        wp_delete_user($moderator_id);
    }
}
