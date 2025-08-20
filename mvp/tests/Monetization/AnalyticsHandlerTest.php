<?php

namespace BizDir\Tests\Monetization;

use BizDir\Tests\Base_Test_Case;
use BizDir\Core\Monetization\Analytics_Handler;
use BizDir\Core\Business\Business_Manager;

class AnalyticsHandlerTest extends Base_Test_Case {
    private $analytics_handler;
    private $business_manager;
    private $test_business_id;

    public function setUp(): void {
        parent::setUp();
        
        $this->analytics_handler = new Analytics_Handler();
        $this->business_manager = new Business_Manager();
        
        // Create test business
        $this->test_business_id = $this->business_manager->create_business([
            'name' => 'Test Business'
        ]);
    }

    public function test_track_page_view() {
        $event_data = [
            'page' => '/business/test-business',
            'referrer' => 'https://google.com',
            'device' => 'desktop'
        ];
        
        $event_id = $this->analytics_handler->track_event($this->test_business_id, 'page_view', $event_data);
        $this->assertIsInt($event_id);
        $this->assertGreaterThan(0, $event_id);
        
        $event = $this->analytics_handler->get_event($event_id);
        $this->assertEquals('page_view', $event['event_type']);
        $this->assertEquals($event_data, json_decode($event['event_data'], true));
    }

    public function test_track_interaction() {
        $event_data = [
            'interaction_type' => 'click',
            'element' => 'phone_number',
            'position' => 'header'
        ];
        
        $event_id = $this->analytics_handler->track_event(
            $this->test_business_id,
            'user_interaction',
            $event_data,
            1, // user_id
            'test_session'
        );
        
        $this->assertIsInt($event_id);
        $event = $this->analytics_handler->get_event($event_id);
        $this->assertEquals(1, $event['user_id']);
        $this->assertEquals('test_session', $event['session_id']);
    }

    public function test_get_analytics_report() {
        // Add multiple events
        $events = [
            ['page_view', ['page' => '/page1']],
            ['page_view', ['page' => '/page2']],
            ['user_interaction', ['type' => 'click']],
            ['conversion', ['type' => 'contact']]
        ];
        
        foreach ($events as $event) {
            $this->analytics_handler->track_event(
                $this->test_business_id,
                $event[0],
                $event[1]
            );
        }
        
        $report = $this->analytics_handler->get_analytics_report(
            $this->test_business_id,
            date('Y-m-d H:i:s', strtotime('-1 day')),
            date('Y-m-d H:i:s')
        );
        
        $this->assertIsArray($report);
        $this->assertEquals(4, $report['total_events']);
        $this->assertEquals(2, $report['events_by_type']['page_view']);
    }

    public function test_get_visitor_stats() {
        // Add page views with different sessions
        for ($i = 0; $i < 5; $i++) {
            $this->analytics_handler->track_event(
                $this->test_business_id,
                'page_view',
                ['page' => '/test'],
                null,
                'session_' . $i
            );
        }
        
        $stats = $this->analytics_handler->get_visitor_stats(
            $this->test_business_id,
            date('Y-m-d H:i:s', strtotime('-1 day')),
            date('Y-m-d H:i:s')
        );
        
        $this->assertIsArray($stats);
        $this->assertEquals(5, $stats['unique_visitors']);
        $this->assertEquals(5, $stats['page_views']);
    }

    public function test_get_conversion_rate() {
        // Add page views and conversions
        for ($i = 0; $i < 10; $i++) {
            $this->analytics_handler->track_event(
                $this->test_business_id,
                'page_view',
                ['page' => '/test']
            );
            
            if ($i < 3) {
                $this->analytics_handler->track_event(
                    $this->test_business_id,
                    'conversion',
                    ['type' => 'contact']
                );
            }
        }
        
        $rate = $this->analytics_handler->get_conversion_rate(
            $this->test_business_id,
            date('Y-m-d H:i:s', strtotime('-1 day')),
            date('Y-m-d H:i:s')
        );
        
        $this->assertEquals(30.0, $rate);
    }

    public function test_get_traffic_sources() {
        $sources = [
            'google' => 5,
            'direct' => 3,
            'facebook' => 2
        ];
        
        foreach ($sources as $source => $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->analytics_handler->track_event(
                    $this->test_business_id,
                    'page_view',
                    ['referrer' => $source]
                );
            }
        }
        
        $traffic_sources = $this->analytics_handler->get_traffic_sources(
            $this->test_business_id,
            date('Y-m-d H:i:s', strtotime('-1 day')),
            date('Y-m-d H:i:s')
        );
        
        $this->assertIsArray($traffic_sources);
        $this->assertEquals(5, $traffic_sources['google']);
        $this->assertEquals(3, $traffic_sources['direct']);
        $this->assertEquals(2, $traffic_sources['facebook']);
    }

    public function test_event_validation() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid event data');
        
        $this->analytics_handler->track_event(
            $this->test_business_id,
            'invalid_event',
            ['invalid' => true]
        );
    }

    public function test_data_aggregation() {
        // Add events over multiple days
        $dates = [
            date('Y-m-d H:i:s', strtotime('-3 days')),
            date('Y-m-d H:i:s', strtotime('-2 days')),
            date('Y-m-d H:i:s', strtotime('-1 day')),
            date('Y-m-d H:i:s')
        ];
        
        foreach ($dates as $date) {
            $this->analytics_handler->track_event(
                $this->test_business_id,
                'page_view',
                ['date' => $date],
                null,
                null,
                null,
                null,
                $date
            );
        }
        
        $daily_stats = $this->analytics_handler->get_daily_stats(
            $this->test_business_id,
            date('Y-m-d H:i:s', strtotime('-3 days')),
            date('Y-m-d H:i:s')
        );
        
        $this->assertIsArray($daily_stats);
        $this->assertCount(4, $daily_stats);
    }

    public function tearDown(): void {
        parent::tearDown();
    }
}
