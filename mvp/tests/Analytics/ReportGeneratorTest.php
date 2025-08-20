<?php
/**
 * Report Generator Test Case
 *
 * @package BizDir\Tests\Analytics
 */

namespace BizDir\Tests\Analytics;

use BizDir\Core\Analytics\Analytics_Handler;
use BizDir\Core\Analytics\Report_Generator;
use BizDir\Core\User\User_Manager;
use BizDir\Tests\Base_Test_Case;

class ReportGeneratorTest extends Base_Test_Case {
    private $analytics_handler;
    private $report_generator;
    private $test_admin_id;
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
        $this->report_generator = new Report_Generator($this->analytics_handler);

        $this->analytics_handler->init();
        $this->report_generator->init();

        // Create test admin
        $this->test_admin_id = $this->factory->user->create([
            'role' => User_Manager::ROLE_ADMIN
        ]);

        // Create test business
        $this->test_business_id = wp_insert_post([
            'post_type' => 'business_listing',
            'post_title' => 'Test Business',
            'post_status' => 'publish'
        ]);

        // Generate test metrics
        $this->generate_test_metrics();
    }

    public function tearDown(): void {
        wp_delete_post($this->test_business_id, true);
        wp_delete_user($this->test_admin_id);
        parent::tearDown();
    }

    private function generate_test_metrics() {
        // Add views
        for ($i = 0; $i < 5; $i++) {
            $this->analytics_handler->track_metric(
                Analytics_Handler::METRIC_VIEWS,
                $this->test_business_id
            );
        }

        // Add clicks
        for ($i = 0; $i < 3; $i++) {
            $this->analytics_handler->track_metric(
                Analytics_Handler::METRIC_CLICKS,
                $this->test_business_id,
                ['type' => 'phone']
            );
        }

        // Add searches
        $this->analytics_handler->track_search(['q' => 'test business'], 1);
        $this->analytics_handler->track_search(['q' => 'test business'], 1);
    }

    public function test_generate_business_report_json() {
        wp_set_current_user($this->test_admin_id);

        $report = $this->report_generator->generate_business_report(
            $this->test_business_id,
            Analytics_Handler::PERIOD_DAY,
            Report_Generator::FORMAT_JSON
        );

        $this->assertIsArray($report);
        $this->assertEquals($this->test_business_id, $report['business_id']);
        $this->assertArrayHasKey('metrics', $report);
        $this->assertEquals(5, $report['metrics']['views'][0]->count);
        $this->assertEquals(3, $report['metrics']['clicks'][0]->count);
    }

    public function test_generate_business_report_csv() {
        wp_set_current_user($this->test_admin_id);

        $report = $this->report_generator->generate_business_report(
            $this->test_business_id,
            Analytics_Handler::PERIOD_DAY,
            Report_Generator::FORMAT_CSV
        );

        $this->assertIsString($report);
        $this->assertStringContainsString('Date,Metric,Count', $report);
        $this->assertStringContainsString('views,5', $report);
        $this->assertStringContainsString('clicks,3', $report);
    }

    public function test_generate_search_report() {
        wp_set_current_user($this->test_admin_id);

        $report = $this->report_generator->generate_search_report(
            Analytics_Handler::PERIOD_DAY,
            Report_Generator::FORMAT_JSON
        );

        $this->assertIsArray($report);
        $this->assertArrayHasKey('trends', $report);
        $this->assertEquals('test business', $report['trends'][0]->query);
        $this->assertEquals(2, $report['trends'][0]->count);
    }

    public function test_unauthorized_report_access() {
        // Create and switch to non-admin user
        $user_id = $this->factory->user->create(['role' => 'subscriber']);
        wp_set_current_user($user_id);

        $_GET['business_id'] = $this->test_business_id;
        $_GET['period'] = Analytics_Handler::PERIOD_DAY;
        $_GET['format'] = Report_Generator::FORMAT_JSON;

        ob_start();
        $this->report_generator->handle_generate_report();
        $response = json_decode(ob_get_clean());

        $this->assertFalse($response->success);
        $this->assertEquals('Permission denied', $response->data->message);

        wp_delete_user($user_id);
    }

    public function test_daily_report_generation() {
        wp_set_current_user($this->test_admin_id);

        // Trigger daily report generation
        do_action('biz_dir_daily_report');

        // Check stored report
        $date = current_time('Y-m-d');
        $report = get_option('biz_dir_daily_report_' . $date);

        $this->assertIsArray($report);
        $this->assertEquals($date, $report['date']);
        $this->assertEquals(5, $report['metrics']['total_views']);
        $this->assertEquals(2, $report['metrics']['total_searches']);
    }

    public function test_pdf_report_format() {
        wp_set_current_user($this->test_admin_id);

        $report = $this->report_generator->generate_business_report(
            $this->test_business_id,
            Analytics_Handler::PERIOD_DAY,
            Report_Generator::FORMAT_PDF
        );

        $this->assertIsString($report);
        $this->assertStringContainsString('Business Report', $report);
        $this->assertStringContainsString('VIEWS', $report);
        $this->assertStringContainsString('CLICKS', $report);
    }
}
