<?php
/**
 * Moderation Handler Test Case
 *
 * @package BizDir\Tests\Moderation
 */

namespace BizDir\Tests\Moderation;

use BizDir\Core\Moderation\Moderation_Handler;
use BizDir\Core\User\User_Manager;
use BizDir\Tests\Base_Test_Case;

class ModerationHandlerTest extends Base_Test_Case {
    private $moderation_handler;
    private $test_moderator_id;
    private $test_user_id;
    private $test_business_id;
    private $test_review_id;

    public function setUp(): void {
        parent::setUp();

        $permission_handler = new \BizDir\Core\User\Permission_Handler();
        $this->moderation_handler = new Moderation_Handler($permission_handler);
        $this->moderation_handler->init();

        // Create test moderator
        $this->test_moderator_id = $this->factory->user->create([
            'role' => User_Manager::ROLE_MODERATOR
        ]);
        
        // Create test user
        $this->test_user_id = $this->factory->user->create([
            'role' => User_Manager::ROLE_CONTRIBUTOR
        ]);

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
        $test_town_id = $wpdb->insert_id;

        // Create test business
        $this->test_business_id = wp_insert_post([
            'post_type' => 'business_listing',
            'post_title' => 'Test Business',
            'post_author' => $this->test_user_id,
            'post_status' => 'publish'
        ]);

        update_post_meta($this->test_business_id, '_town_id', $test_town_id);

        // Create test review
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'biz_reviews',
            [
                'business_id' => $this->test_business_id,
                'user_id' => $this->test_user_id,
                'rating' => 4.0,
                'comment' => 'Test review comment',
                'status' => 'pending'
            ]
        );
        $this->test_review_id = $wpdb->insert_id;
    }

    public function tearDown(): void {
        wp_delete_post($this->test_business_id, true);
        wp_delete_user($this->test_moderator_id);
        wp_delete_user($this->test_user_id);
        
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'biz_reviews', ['id' => $this->test_review_id]);
        
        parent::tearDown();
    }

    public function test_add_to_queue() {
        $result = $this->moderation_handler->add_to_queue(
            Moderation_Handler::TYPE_REVIEW,
            $this->test_review_id
        );

        $this->assertNotFalse($result);
        $this->assertIsInt($result);

        global $wpdb;
        $queue_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_moderation_queue WHERE id = %d",
            $result
        ));

        $this->assertNotNull($queue_item);
        $this->assertEquals(Moderation_Handler::TYPE_REVIEW, $queue_item->content_type);
        $this->assertEquals($this->test_review_id, $queue_item->content_id);
        $this->assertEquals(Moderation_Handler::STATUS_PENDING, $queue_item->status);
    }

    public function test_get_queue() {
        // Add test items to queue
        $review_queue_id = $this->moderation_handler->add_to_queue(
            Moderation_Handler::TYPE_REVIEW,
            $this->test_review_id
        );

        $business_queue_id = $this->moderation_handler->add_to_queue(
            Moderation_Handler::TYPE_BUSINESS,
            $this->test_business_id
        );

        // Test getting all pending items
        $items = $this->moderation_handler->get_queue([
            'status' => Moderation_Handler::STATUS_PENDING
        ]);

        $this->assertIsArray($items);
        $this->assertGreaterThanOrEqual(2, count($items));

        // Test filtering by content type
        $review_items = $this->moderation_handler->get_queue([
            'content_type' => Moderation_Handler::TYPE_REVIEW
        ]);

        $this->assertIsArray($review_items);
        $this->assertGreaterThanOrEqual(1, count($review_items));
        $this->assertEquals(Moderation_Handler::TYPE_REVIEW, $review_items[0]['content_type']);
    }

    public function test_moderate_item() {
        // Add review to queue
        $queue_id = $this->moderation_handler->add_to_queue(
            Moderation_Handler::TYPE_REVIEW,
            $this->test_review_id
        );

        // Set current user as moderator
        wp_set_current_user($this->test_moderator_id);

        // Test approving review
        $result = $this->moderation_handler->moderate_item(
            $queue_id,
            'approve',
            'Approved test review'
        );

        $this->assertTrue($result);

        global $wpdb;
        
        // Check queue item status
        $queue_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_moderation_queue WHERE id = %d",
            $queue_id
        ));

        $this->assertEquals(Moderation_Handler::STATUS_APPROVED, $queue_item->status);
        $this->assertEquals($this->test_moderator_id, $queue_item->moderator_id);

        // Check review status
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_reviews WHERE id = %d",
            $this->test_review_id
        ));

        $this->assertEquals('published', $review->status);
    }

    public function test_reject_item() {
        // Add review to queue
        $queue_id = $this->moderation_handler->add_to_queue(
            Moderation_Handler::TYPE_REVIEW,
            $this->test_review_id
        );

        // Set current user as moderator
        wp_set_current_user($this->test_moderator_id);

        // Test rejecting review
        $result = $this->moderation_handler->moderate_item(
            $queue_id,
            'reject',
            'Rejected test review'
        );

        $this->assertTrue($result);

        global $wpdb;
        
        // Check queue item status
        $queue_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_moderation_queue WHERE id = %d",
            $queue_id
        ));

        $this->assertEquals(Moderation_Handler::STATUS_REJECTED, $queue_item->status);
        $this->assertEquals($this->test_moderator_id, $queue_item->moderator_id);

        // Check review status
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_reviews WHERE id = %d",
            $this->test_review_id
        ));

        $this->assertEquals('rejected', $review->status);
    }

    public function test_unauthorized_moderation() {
        // Add review to queue
        $queue_id = $this->moderation_handler->add_to_queue(
            Moderation_Handler::TYPE_REVIEW,
            $this->test_review_id
        );

        // Set current user as regular user (not moderator)
        wp_set_current_user($this->test_user_id);

        // Test moderating as unauthorized user
        $result = $this->moderation_handler->moderate_item(
            $queue_id,
            'approve',
            'Trying to approve'
        );

        $this->assertFalse($result);

        global $wpdb;
        
        // Check queue item status (should remain pending)
        $queue_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_moderation_queue WHERE id = %d",
            $queue_id
        ));

        $this->assertEquals(Moderation_Handler::STATUS_PENDING, $queue_item->status);
        $this->assertNull($queue_item->moderator_id);

        // Check review status (should remain pending)
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_reviews WHERE id = %d",
            $this->test_review_id
        ));

        $this->assertEquals('pending', $review->status);
    }

    public function test_invalid_queue_item() {
        wp_set_current_user($this->test_moderator_id);

        // Test moderating non-existent queue item
        $result = $this->moderation_handler->moderate_item(999999, 'approve', 'Testing invalid ID');
        $this->assertFalse($result);
    }

    public function test_invalid_action() {
        $queue_id = $this->moderation_handler->add_to_queue(
            Moderation_Handler::TYPE_REVIEW,
            $this->test_review_id
        );

        wp_set_current_user($this->test_moderator_id);

        // Test invalid moderation action
        $result = $this->moderation_handler->moderate_item($queue_id, 'invalid_action', 'Testing invalid action');
        $this->assertFalse($result);

        global $wpdb;
        $queue_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_moderation_queue WHERE id = %d",
            $queue_id
        ));

        // Status should remain pending
        $this->assertEquals(Moderation_Handler::STATUS_PENDING, $queue_item->status);
    }

    public function test_escalate_item() {
        $queue_id = $this->moderation_handler->add_to_queue(
            Moderation_Handler::TYPE_REVIEW,
            $this->test_review_id
        );

        wp_set_current_user($this->test_moderator_id);

        // Test escalating review
        $result = $this->moderation_handler->moderate_item(
            $queue_id,
            'escalate',
            'Needs admin review'
        );

        $this->assertTrue($result);

        global $wpdb;
        $queue_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_moderation_queue WHERE id = %d",
            $queue_id
        ));

        $this->assertEquals(Moderation_Handler::STATUS_ESCALATED, $queue_item->status);
        $this->assertEquals($this->test_moderator_id, $queue_item->moderator_id);

        // Review status should remain pending when escalated
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_reviews WHERE id = %d",
            $this->test_review_id
        ));

        $this->assertEquals('pending', $review->status);
    }

    public function test_queue_pagination() {
        // Add multiple items to queue
        for ($i = 0; $i < 25; $i++) {
            $this->moderation_handler->add_to_queue(
                Moderation_Handler::TYPE_REVIEW,
                $this->test_review_id
            );
        }

        // Test first page (default limit is 20)
        $page1 = $this->moderation_handler->get_queue([
            'offset' => 0
        ]);
        $this->assertCount(20, $page1);

        // Test second page
        $page2 = $this->moderation_handler->get_queue([
            'offset' => 20
        ]);
        $this->assertGreaterThanOrEqual(5, count($page2));
    }
}
