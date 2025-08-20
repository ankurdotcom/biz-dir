<?php
/**
 * Analytics Handler Test Case
 *
 * @package BizDir\Tests\Analytics
 */

namespace BizDir\Tests\Analytics;

use BizDir\Core\Analytics\Analytics_Handler;
use BizDir\Core\User\User_Manager;
use BizDir\Tests\Base_Test_Case;

class AnalyticsHandlerTest extends Base_Test_Case {
    private $analytics_handler;
    private $test_admin_id;
    private $test_user_id;
    private $test_business_id;

    public function setUp(): void {
        parent::setUp();

        // Create analytics tables
        global $wpdb;
        $schema = file_get_contents(dirname(dirname(__FILE__)) . '/config/analytics_schema.sql');
        $schema = str_replace('{prefix}', $wpdb->prefix, $schema);
        $wpdb->query($schema);

        $permission_handler = new \BizDir\Core\User\Permission_Handler();
        $this->analytics_handler = new Analytics_Handler($permission_handler);
        $this->analytics_handler->init();

        // Create test admin
        $this->test_admin_id = $this->factory->user->create([
            'role' => User_Manager::ROLE_ADMIN
        ]);
        
        // Create test user
        $this->test_user_id = $this->factory->user->create([
            'role' => User_Manager::ROLE_CONTRIBUTOR
        ]);

        // Create test business
        $this->test_business_id = wp_insert_post([
            'post_type' => 'business_listing',
            'post_title' => 'Test Business',
            'post_author' => $this->test_user_id,
            'post_status' => 'publish'
        ]);
    }

    public function tearDown(): void {
        wp_delete_post($this->test_business_id, true);
        wp_delete_user($this->test_admin_id);
        wp_delete_user($this->test_user_id);
        parent::tearDown();
    }

    public function test_track_metric() {
        $this->analytics_handler->track_page_view();
        
        global $wpdb;
        $metric = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_analytics_metrics 
            WHERE business_id = %d AND metric = %s",
            $this->test_business_id,
            Analytics_Handler::METRIC_VIEWS
        ));

        $this->assertNotNull($metric);
        $this->assertEquals(Analytics_Handler::METRIC_VIEWS, $metric->metric);
    }

    public function test_track_search() {
        $params = ['q' => 'test', 'region' => 'Test Region'];
        $this->analytics_handler->track_search($params, 5);

        global $wpdb;
        $search = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}biz_analytics_searches 
            ORDER BY id DESC LIMIT 1"
        );

        $this->assertNotNull($search);
        $this->assertEquals('test', $search->query);
        $this->assertEquals(5, $search->results);
        $this->assertJson($search->filters);

        $filters = json_decode($search->filters, true);
        $this->assertEquals($params, $filters);
    }

    public function test_get_analytics() {
        // Add test metrics
        for ($i = 0; $i < 5; $i++) {
            $this->analytics_handler->track_page_view();
        }

        wp_set_current_user($this->test_admin_id);

        $data = $this->analytics_handler->get_analytics(
            Analytics_Handler::METRIC_VIEWS,
            Analytics_Handler::PERIOD_DAY
        );

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals(5, $data[0]->count);
    }

    public function test_get_search_analytics() {
        // Add test searches
        $searches = [
            ['q' => 'restaurant', 'region' => 'north'],
            ['q' => 'restaurant', 'region' => 'south'],
            ['q' => 'cafe', 'region' => 'central']
        ];

        foreach ($searches as $params) {
            $this->analytics_handler->track_search($params, rand(1, 10));
        }

        wp_set_current_user($this->test_admin_id);

        $data = $this->analytics_handler->get_search_analytics(
            Analytics_Handler::PERIOD_DAY
        );

        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(2, count($data));

        // Restaurant should be top search
        $this->assertEquals('restaurant', $data[0]->query);
        $this->assertEquals(2, $data[0]->count);
    }

    public function test_unauthorized_analytics_access() {
        wp_set_current_user($this->test_user_id);

        $_GET['metric'] = Analytics_Handler::METRIC_VIEWS;
        $_GET['period'] = Analytics_Handler::PERIOD_DAY;

        ob_start();
        $this->analytics_handler->handle_get_analytics();
        $response = json_decode(ob_get_clean());

        $this->assertFalse($response->success);
        $this->assertEquals('Permission denied', $response->data->message);
    }

    public function test_period_filtering() {
        global $wpdb;

        // Add old metric
        $wpdb->insert(
            $wpdb->prefix . 'biz_analytics_metrics',
            [
                'metric' => Analytics_Handler::METRIC_VIEWS,
                'business_id' => $this->test_business_id,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 months')),
            ],
            ['%s', '%d', '%s']
        );

        // Add recent metric
        $this->analytics_handler->track_page_view();

        wp_set_current_user($this->test_admin_id);

        // Test monthly data
        $month_data = $this->analytics_handler->get_analytics(
            Analytics_Handler::METRIC_VIEWS,
            Analytics_Handler::PERIOD_MONTH
        );

        $this->assertCount(1, $month_data);

        // Test yearly data
        $year_data = $this->analytics_handler->get_analytics(
            Analytics_Handler::METRIC_VIEWS,
            Analytics_Handler::PERIOD_YEAR
        );

        $this->assertCount(2, $year_data);
    }

    public function test_click_tracking() {
        $_POST['business_id'] = $this->test_business_id;
        $_POST['type'] = 'phone';
        $_REQUEST['nonce'] = wp_create_nonce('track_click');

        ob_start();
        $this->analytics_handler->handle_track_click();
        $response = json_decode(ob_get_clean());

        $this->assertTrue($response->success);

        global $wpdb;
        $click = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_analytics_metrics 
            WHERE business_id = %d AND metric = %s",
            $this->test_business_id,
            Analytics_Handler::METRIC_CLICKS
        ));

        $this->assertNotNull($click);
        $this->assertJson($click->meta);
        
        $meta = json_decode($click->meta, true);
        $this->assertEquals('phone', $meta['type']);
    }

    public function test_invalid_click_tracking() {
        $_POST['business_id'] = 0;
        $_REQUEST['nonce'] = wp_create_nonce('track_click');

        ob_start();
        $this->analytics_handler->handle_track_click();
        $response = json_decode(ob_get_clean());

        $this->assertFalse($response->success);
        $this->assertEquals('Invalid business ID', $response->data->message);
    }
}
