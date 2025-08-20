<?php
/**
 * Business Manager Test Case
 */

namespace BizDir\Tests\Business;

use BizDir\Core\Business\Business_Manager;
use BizDir\Core\User\User_Manager;
use BizDir\Tests\Base_Test_Case;

class BusinessManagerTest extends Base_Test_Case {
    private $business_manager;
    private $permission_handler;
    private $test_user_id;
    private $test_business_id;
    private $test_town_id;

    public function setUp(): void {
        parent::setUp();
        $this->permission_handler = $this->getMockBuilder(\BizDir\Core\User\Permission_Handler::class)
            ->disableOriginalConstructor()
            ->addMethods(['__call'])
            ->getMock();
        $this->business_manager = new Business_Manager($this->permission_handler);
        $this->business_manager->init();

        // Create test user with required capabilities
        $this->test_user_id = $this->factory->user->create([
            'role' => User_Manager::ROLE_CONTRIBUTOR
        ]);
        $user = new \WP_User($this->test_user_id);
        $user->add_cap('edit_business_listing');
        $user->add_cap('edit_business_listings');

        // Add test town
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'biz_towns',
            [
                'name' => 'Test Town',
                'slug' => 'test-town',
                'region' => 'Test Region'
            ]
        );
        $this->test_town_id = $wpdb->insert_id;

        // Create test business
        $this->test_business_id = wp_insert_post([
            'post_type' => Business_Manager::POST_TYPE,
            'post_title' => 'Test Business',
            'post_author' => $this->test_user_id,
            'post_status' => 'publish'
        ]);

        update_post_meta($this->test_business_id, '_town_id', $this->test_town_id);
        update_post_meta($this->test_business_id, '_contact_info', 'new@example.com');
        update_post_meta($this->test_business_id, '_is_sponsored', '0');
        update_post_meta($this->test_business_id, '_location', '123 Test St');
    }

    public function tearDown(): void {
        wp_delete_post($this->test_business_id, true);
        wp_delete_user($this->test_user_id);
        
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'biz_towns', ['id' => $this->test_town_id]);
        
        parent::tearDown();
    }

    public function test_post_type_registration() {
        $post_type_object = get_post_type_object(Business_Manager::POST_TYPE);
        $this->assertNotNull($post_type_object);
        $this->assertEquals('business_listing', $post_type_object->name);
        $this->assertTrue($post_type_object->public);
        $this->assertTrue($post_type_object->show_in_rest);
    }

    public function test_meta_boxes_registration() {
        global $wp_meta_boxes;
        
        do_action('add_meta_boxes', Business_Manager::POST_TYPE);
        
        $this->assertArrayHasKey('business_details', $wp_meta_boxes[Business_Manager::POST_TYPE]['normal']['high']);
        $this->assertArrayHasKey('business_location', $wp_meta_boxes[Business_Manager::POST_TYPE]['normal']['high']);
    }

    public function test_business_meta_saving() {
        // Set current user for test
        wp_set_current_user($this->test_user_id);
        
        // Define we're in testing mode
        if (!defined('DOING_TESTS')) {
            define('DOING_TESTS', true);
        }

        // Set permission handler expectations
        $this->permission_handler
            ->expects($this->once())
            ->method('can')
            ->with('edit_business', $this->test_user_id, $this->test_business_id)
            ->willReturn(true);
        
        // Setup POST data
        $_POST['business_details_meta_box_nonce'] = wp_create_nonce('business_details_meta_box');
        $_POST['business_location_meta_box_nonce'] = wp_create_nonce('business_location_meta_box');
        $_POST['contact_info'] = 'new@example.com';
        $_POST['is_sponsored'] = '1';
        $_POST['town_id'] = $this->test_town_id;
        $_POST['location'] = '456 New St';

        // Trigger save
        $post = get_post($this->test_business_id);
        $this->business_manager->save_business_meta($this->test_business_id, $post);

        // Verify saved data
        $this->assertEquals('new@example.com', get_post_meta($this->test_business_id, '_contact_info', true));
        $this->assertEquals('1', get_post_meta($this->test_business_id, '_is_sponsored', true));
        $this->assertEquals($this->test_town_id, get_post_meta($this->test_business_id, '_town_id', true));
        $this->assertEquals('456 New St', get_post_meta($this->test_business_id, '_location', true));
    }

    public function test_custom_columns() {
        $columns = $this->business_manager->set_custom_columns(['title' => 'Title', 'date' => 'Date']);
        
        $this->assertArrayHasKey('town', $columns);
        $this->assertArrayHasKey('sponsored', $columns);
        
        ob_start();
        $this->business_manager->render_custom_columns('town', $this->test_business_id);
        $town_output = ob_get_clean();
        
        ob_start();
        $this->business_manager->render_custom_columns('sponsored', $this->test_business_id);
        $sponsored_output = ob_get_clean();
        
        $this->assertStringContainsString('Test Town', $town_output);
        $this->assertStringContainsString('✗', $sponsored_output);
    }
}
