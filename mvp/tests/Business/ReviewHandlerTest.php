<?php
/**
 * Test Review Handler functionality
 *
 * @package BizDir\Tests\Business
 */

namespace BizDir\Tests\Business;

use BizDir\Core\Business\Review_Handler;
use BizDir\Core\User\Permission_Handler;
use BizDir\Tests\Base_Test_Case;
use WP_REST_Request;

class ReviewHandlerTest extends Base_Test_Case {
    /**
     * @var Review_Handler
     */
    private $review_handler;

    /**
     * @var Permission_Handler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $permission_handler;

    public function set_up() {
        parent::set_up();
        
        $this->permission_handler = $this->createMock(Permission_Handler::class);
        $this->review_handler = new Review_Handler($this->permission_handler);
        $this->review_handler->init();
    }

    public function test_review_post_type_registration() {
        $this->assertTrue(post_type_exists('business_review'));
    }

    public function test_review_creation() {
        $user_id = $this->factory->user->create([
            'role' => 'subscriber'
        ]);
        wp_set_current_user($user_id);

        $business_id = $this->factory->post->create([
            'post_type' => 'business'
        ]);

        $this->permission_handler
            ->method('can')
            ->with('create_business_reviews', $user_id, null)
            ->willReturn(true);

        $request = new WP_REST_Request('POST', '/biz-dir/v1/businesses/' . $business_id . '/reviews');
        $request->set_param('id', $business_id);
        $request->set_param('rating', 4);
        $request->set_param('content', 'Great business!');

        $response = $this->review_handler->create_review($request);
        $data = $response->get_data();

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals(4, $data['rating']);
        $this->assertEquals('Great business!', $data['content']);
    }

    public function test_prevent_duplicate_reviews() {
        $user_id = $this->factory->user->create([
            'role' => 'subscriber'
        ]);
        wp_set_current_user($user_id);

        $business_id = $this->factory->post->create([
            'post_type' => 'business'
        ]);

        // Create first review
        $review_id = wp_insert_post([
            'post_type' => 'business_review',
            'post_author' => $user_id,
            'post_status' => 'publish'
        ]);
        update_post_meta($review_id, '_business_id', $business_id);

        $request = new WP_REST_Request('POST', '/biz-dir/v1/businesses/' . $business_id . '/reviews');
        $request->set_param('id', $business_id);

        $can_create = $this->review_handler->can_create_review($request);
        $this->assertInstanceOf('WP_Error', $can_create);
        $this->assertEquals('rest_forbidden', $can_create->get_error_code());
    }

    public function test_get_business_reviews() {
        $business_id = $this->factory->post->create([
            'post_type' => 'business'
        ]);

        // Create some reviews
        for ($i = 0; $i < 3; $i++) {
            $review_id = wp_insert_post([
                'post_type' => 'business_review',
                'post_content' => "Review {$i}",
                'post_status' => 'publish'
            ]);
            update_post_meta($review_id, '_business_id', $business_id);
            update_post_meta($review_id, '_rating', 4);
        }

        $request = new WP_REST_Request('GET', '/biz-dir/v1/businesses/' . $business_id . '/reviews');
        $request->set_param('id', $business_id);

        $response = $this->review_handler->get_business_reviews($request);
        $data = $response->get_data();

        $this->assertCount(3, $data);
        $this->assertEquals(4, $data[0]['rating']);
    }

    public function test_rating_calculation() {
        $business_id = $this->factory->post->create([
            'post_type' => 'business'
        ]);

        // Create reviews with different ratings
        $ratings = [3, 4, 5];
        foreach ($ratings as $rating) {
            $review_id = wp_insert_post([
                'post_type' => 'business_review',
                'post_status' => 'publish'
            ]);
            update_post_meta($review_id, '_business_id', $business_id);
            update_post_meta($review_id, '_rating', $rating);
        }

        // Trigger rating recalculation
        $review = get_post($review_id);
        $this->review_handler->update_business_rating($review_id, $review, true);

        $average_rating = get_post_meta($business_id, '_average_rating', true);
        $review_count = get_post_meta($business_id, '_review_count', true);

        $this->assertEquals(4.0, $average_rating);
        $this->assertEquals(3, $review_count);
    }

    public function test_review_deletion() {
        global $wpdb;
        
        // Start a new transaction for our test
        $wpdb->query('START TRANSACTION');
        
        $business_id = $this->factory->post->create([
            'post_type' => 'business'
        ]);

        // Create reviews
        $review_ids = [];
        foreach ([4, 5] as $rating) {
            $review_id = wp_insert_post([
                'post_type' => 'business_review',
                'post_author' => 1,
                'post_status' => 'publish',
                'post_content' => 'Test review'
            ]);
            update_post_meta($review_id, '_business_id', $business_id);
            update_post_meta($review_id, '_rating', $rating);
            $review_ids[] = $review_id;
            
            // Trigger rating calculation on save
            $review = get_post($review_id);
            $this->review_handler->update_business_rating($review_id, $review, false);
        }

        // Store current rating
        $initial_rating = get_post_meta($business_id, '_average_rating', true);
        $initial_count = get_post_meta($business_id, '_review_count', true);

        $this->assertEquals(4.5, (float)$initial_rating);
        $this->assertEquals(2, (int)$initial_count);

        // Delete first review (rating 4)
        wp_delete_post($review_ids[0], true);
        
        // Ensure WordPress fully processes the deletion
        clean_post_cache($review_ids[0]);
        
        // Run shutdown actions to ensure all hooks have executed
        do_action('shutdown');
        
        // Force WordPress to commit any pending database operations
        $wpdb->flush();
        
        // Commit our test transaction
        $wpdb->query('COMMIT');

        // Force a commit of any pending changes
        $wpdb->flush();
        
        // Now get the final values
        $average_rating = get_post_meta($business_id, '_average_rating', true);
        $review_count = get_post_meta($business_id, '_review_count', true);

        $this->assertEquals(5.0, (float)$average_rating);
        $this->assertEquals(1, (int)$review_count);
    }
}
