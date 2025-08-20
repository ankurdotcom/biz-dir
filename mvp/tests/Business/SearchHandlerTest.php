<?php
/**
 * Test Search Handler functionality
 *
 * @package BizDir\Tests\Business
 */

namespace BizDir\Tests\Business;

use BizDir\Core\Business\Search_Handler;
use BizDir\Tests\Base_Test_Case;
use WP_REST_Request;

class SearchHandlerTest extends Base_Test_Case {
    /**
     * @var Search_Handler
     */
    private $search_handler;

    public function set_up() {
        parent::set_up();
        $this->search_handler = new Search_Handler();
        $this->search_handler->init();
    }

    public function test_modify_search_query() {
        $_GET['sort'] = 'name';
        $_GET['town'] = '1';
        $_GET['category'] = 'restaurants';

        if (!defined('DOING_TESTS')) {
            define('DOING_TESTS', true);
        }

        $query = new \WP_Query([
            'post_type' => 'business_listing'
        ]);
        $query->is_main_query = true;
        $query->is_post_type_archive = true;

        $this->search_handler->modify_search_query($query);

        $this->assertEquals('title', $query->get('orderby'));
        $this->assertEquals('ASC', $query->get('order'));
        $this->assertArrayHasKey('meta_query', $query->query_vars);
        $this->assertArrayHasKey('tax_query', $query->query_vars);
    }

    public function test_rest_search_endpoint() {
        $request = new WP_REST_Request('GET', '/biz-dir/v1/search');
        $request->set_query_params([
            'keyword' => 'test',
            'town' => 1,
            'category' => 'restaurants',
            'sort' => 'rating'
        ]);

        $response = $this->search_handler->handle_search_request($request);
        $data = $response->get_data();

        $this->assertArrayHasKey('businesses', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('pages', $data);
    }

    public function test_search_with_region_filter() {
        $_GET['region'] = 'north';

        if (!defined('DOING_TESTS')) {
            define('DOING_TESTS', true);
        }

        global $wpdb;
        $join = '';
        $where = '';
        $query = new \WP_Query([
            'post_type' => 'business_listing'
        ]);
        $query->is_main_query = true;
        $query->is_post_type_archive = true;

        $join = $this->search_handler->join_town_table($join, $query);
        $where = $this->search_handler->filter_by_town($where, $query);

        $this->assertStringContainsString('JOIN', $join);
        $this->assertStringContainsString('region', $where);
    }

    public function test_business_formatting() {
        $post = $this->factory->post->create_and_get([
            'post_type' => 'business',
            'post_title' => 'Test Business'
        ]);

        $method = new \ReflectionMethod(Search_Handler::class, 'format_business_for_response');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->search_handler, $post);

        $this->assertArrayHasKey('id', $formatted);
        $this->assertArrayHasKey('title', $formatted);
        $this->assertArrayHasKey('rating', $formatted);
        $this->assertArrayHasKey('reviews', $formatted);
    }
}
