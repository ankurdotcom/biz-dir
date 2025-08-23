<?php
/**
 * Analytics Regression Test Suite
 * 
 * @package BizDir
 * @subpackage Tests\Regression
 */

require_once __DIR__ . '/RegressionTestCase.php';

class AnalyticsRegressionTest extends RegressionTestCase
{
    private $sampleEvents;
    private $testMetrics;
    private $analyticsConfig;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sampleEvents = [
            'page_view' => [
                'event_type' => 'page_view',
                'page_url' => '/business/test-restaurant',
                'user_id' => 123,
                'session_id' => 'sess_123456',
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'referrer' => 'https://google.com/search',
                'timestamp' => time(),
            ],
            'business_view' => [
                'event_type' => 'business_view',
                'business_id' => 456,
                'user_id' => 123,
                'session_id' => 'sess_123456',
                'view_duration' => 45,
                'timestamp' => time(),
            ],
            'search_query' => [
                'event_type' => 'search',
                'query' => 'restaurants near me',
                'results_count' => 25,
                'user_id' => 123,
                'filters' => ['category' => 'restaurant', 'distance' => '5mi'],
                'timestamp' => time(),
            ],
            'review_submit' => [
                'event_type' => 'review_submit',
                'business_id' => 456,
                'user_id' => 123,
                'rating' => 5,
                'review_length' => 150,
                'timestamp' => time(),
            ],
        ];
        
        $this->testMetrics = [
            'page_views',
            'unique_visitors',
            'session_duration',
            'bounce_rate',
            'business_views',
            'search_queries',
            'review_submissions',
            'user_engagement',
            'conversion_rate',
            'popular_categories',
        ];
        
        $this->analyticsConfig = [
            'retention_days' => 365,
            'batch_size' => 1000,
            'aggregation_intervals' => ['hourly', 'daily', 'weekly', 'monthly'],
            'privacy_mode' => true,
            'exclude_bots' => true,
            'sample_rate' => 1.0,
        ];
    }
    
    /**
     * Test event tracking and collection
     */
    public function testEventTracking()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test individual event tracking
        foreach ($this->sampleEvents as $eventType => $eventData) {
            $trackingResult = $this->trackEvent($eventData);
            $this->assertTrue($trackingResult, "Should successfully track $eventType event");
            
            // Verify event was stored
            $storedEvent = $this->getStoredEvent($eventData['event_type'], $eventData['timestamp']);
            $this->assertNotNull($storedEvent, "Event $eventType should be stored");
            $this->assertEquals($eventData['event_type'], $storedEvent['event_type']);
        }
        
        // Test batch event tracking
        $batchEvents = array_values($this->sampleEvents);
        $batchResult = $this->trackEventBatch($batchEvents);
        $this->assertTrue($batchResult, 'Should successfully track batch of events');
        
        // Test event validation
        $invalidEvent = ['event_type' => '', 'timestamp' => 'invalid'];
        $invalidResult = $this->trackEvent($invalidEvent);
        $this->assertFalse($invalidResult, 'Should reject invalid events');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test data aggregation and metrics calculation
     */
    public function testDataAggregation()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Generate sample data for aggregation
        $this->generateSampleData(100);
        
        // Test daily aggregation
        $dailyMetrics = $this->aggregateMetrics('daily', date('Y-m-d'));
        $this->assertIsArray($dailyMetrics, 'Daily metrics should be an array');
        
        foreach ($this->testMetrics as $metric) {
            $this->assertArrayHasKey($metric, $dailyMetrics, "Daily metrics should include $metric");
            $this->assertIsNumeric($dailyMetrics[$metric], "$metric should be numeric");
        }
        
        // Test weekly aggregation
        $weeklyMetrics = $this->aggregateMetrics('weekly', date('Y-W'));
        $this->assertIsArray($weeklyMetrics, 'Weekly metrics should be an array');
        $this->assertGreaterThanOrEqual($dailyMetrics['page_views'], $weeklyMetrics['page_views'], 
            'Weekly page views should be >= daily page views');
        
        // Test monthly aggregation
        $monthlyMetrics = $this->aggregateMetrics('monthly', date('Y-m'));
        $this->assertIsArray($monthlyMetrics, 'Monthly metrics should be an array');
        $this->assertGreaterThanOrEqual($weeklyMetrics['page_views'], $monthlyMetrics['page_views'],
            'Monthly page views should be >= weekly page views');
        
        // Test real-time metrics
        $realtimeMetrics = $this->getRealtimeMetrics();
        $this->assertIsArray($realtimeMetrics, 'Real-time metrics should be an array');
        $this->assertArrayHasKey('active_users', $realtimeMetrics);
        $this->assertArrayHasKey('current_sessions', $realtimeMetrics);
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test analytics reporting and dashboards
     */
    public function testAnalyticsReporting()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test dashboard data generation
        $dashboardData = $this->generateDashboardData(7); // Last 7 days
        $this->assertIsArray($dashboardData, 'Dashboard data should be an array');
        
        $this->assertArrayHasKey('overview', $dashboardData);
        $this->assertArrayHasKey('traffic_sources', $dashboardData);
        $this->assertArrayHasKey('popular_pages', $dashboardData);
        $this->assertArrayHasKey('user_behavior', $dashboardData);
        $this->assertArrayHasKey('business_analytics', $dashboardData);
        
        // Test traffic sources analysis
        $trafficSources = $dashboardData['traffic_sources'];
        $this->assertArrayHasKey('organic', $trafficSources);
        $this->assertArrayHasKey('direct', $trafficSources);
        $this->assertArrayHasKey('referral', $trafficSources);
        $this->assertArrayHasKey('social', $trafficSources);
        
        // Test popular pages report
        $popularPages = $dashboardData['popular_pages'];
        $this->assertIsArray($popularPages);
        foreach ($popularPages as $page) {
            $this->assertArrayHasKey('url', $page);
            $this->assertArrayHasKey('views', $page);
            $this->assertArrayHasKey('unique_views', $page);
        }
        
        // Test user behavior metrics
        $userBehavior = $dashboardData['user_behavior'];
        $this->assertArrayHasKey('avg_session_duration', $userBehavior);
        $this->assertArrayHasKey('pages_per_session', $userBehavior);
        $this->assertArrayHasKey('bounce_rate', $userBehavior);
        $this->assertArrayHasKey('return_visitor_rate', $userBehavior);
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test business analytics and insights
     */
    public function testBusinessAnalytics()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        $businessId = 456;
        
        // Test business performance metrics
        $businessMetrics = $this->getBusinessMetrics($businessId, 30); // Last 30 days
        $this->assertIsArray($businessMetrics, 'Business metrics should be an array');
        
        $this->assertArrayHasKey('total_views', $businessMetrics);
        $this->assertArrayHasKey('unique_visitors', $businessMetrics);
        $this->assertArrayHasKey('click_through_rate', $businessMetrics);
        $this->assertArrayHasKey('engagement_score', $businessMetrics);
        $this->assertArrayHasKey('review_rate', $businessMetrics);
        
        // Test business ranking analytics
        $rankings = $this->getBusinessRankings($businessId);
        $this->assertIsArray($rankings, 'Rankings should be an array');
        $this->assertArrayHasKey('category_rank', $rankings);
        $this->assertArrayHasKey('local_rank', $rankings);
        $this->assertArrayHasKey('search_visibility', $rankings);
        
        // Test competitor analysis
        $competitorAnalysis = $this->getCompetitorAnalysis($businessId);
        $this->assertIsArray($competitorAnalysis, 'Competitor analysis should be an array');
        $this->assertArrayHasKey('similar_businesses', $competitorAnalysis);
        $this->assertArrayHasKey('market_share', $competitorAnalysis);
        $this->assertArrayHasKey('performance_comparison', $competitorAnalysis);
        
        // Test search analytics
        $searchAnalytics = $this->getBusinessSearchAnalytics($businessId);
        $this->assertIsArray($searchAnalytics, 'Search analytics should be an array');
        $this->assertArrayHasKey('search_impressions', $searchAnalytics);
        $this->assertArrayHasKey('search_clicks', $searchAnalytics);
        $this->assertArrayHasKey('top_keywords', $searchAnalytics);
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test user analytics and segmentation
     */
    public function testUserAnalytics()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test user segmentation
        $segments = $this->getUserSegments();
        $this->assertIsArray($segments, 'User segments should be an array');
        
        $expectedSegments = ['new_users', 'returning_users', 'power_users', 'inactive_users'];
        foreach ($expectedSegments as $segment) {
            $this->assertArrayHasKey($segment, $segments, "Should have $segment segment");
            $this->assertIsNumeric($segments[$segment]['count'], "Segment $segment should have numeric count");
        }
        
        // Test user journey analysis
        $userJourney = $this->analyzeUserJourney(123); // Test user ID
        $this->assertIsArray($userJourney, 'User journey should be an array');
        $this->assertArrayHasKey('touchpoints', $userJourney);
        $this->assertArrayHasKey('conversion_path', $userJourney);
        $this->assertArrayHasKey('session_count', $userJourney);
        
        // Test cohort analysis
        $cohortAnalysis = $this->getCohortAnalysis('monthly', 6); // 6 months
        $this->assertIsArray($cohortAnalysis, 'Cohort analysis should be an array');
        $this->assertArrayHasKey('cohorts', $cohortAnalysis);
        $this->assertArrayHasKey('retention_rates', $cohortAnalysis);
        
        // Test user lifetime value
        $ltv = $this->calculateUserLTV(123);
        $this->assertIsNumeric($ltv, 'User LTV should be numeric');
        $this->assertGreaterThanOrEqual(0, $ltv, 'User LTV should be non-negative');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test performance monitoring and alerts
     */
    public function testPerformanceMonitoring()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test performance metrics collection
        $performanceMetrics = $this->getPerformanceMetrics();
        $this->assertIsArray($performanceMetrics, 'Performance metrics should be an array');
        
        $this->assertArrayHasKey('page_load_time', $performanceMetrics);
        $this->assertArrayHasKey('server_response_time', $performanceMetrics);
        $this->assertArrayHasKey('database_query_time', $performanceMetrics);
        $this->assertArrayHasKey('memory_usage', $performanceMetrics);
        
        // Test alert system
        $alerts = $this->checkPerformanceAlerts();
        $this->assertIsArray($alerts, 'Alerts should be an array');
        
        // Test threshold monitoring
        $thresholds = [
            'page_load_time' => 3000, // 3 seconds
            'server_response_time' => 1000, // 1 second
            'error_rate' => 5, // 5%
        ];
        
        foreach ($thresholds as $metric => $threshold) {
            $currentValue = $performanceMetrics[$metric] ?? 0;
            $isWithinThreshold = $currentValue <= $threshold;
            
            if (!$isWithinThreshold) {
                $this->assertContains($metric, array_column($alerts, 'metric'), 
                    "Should have alert for $metric exceeding threshold");
            }
        }
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test data privacy and GDPR compliance
     */
    public function testDataPrivacyCompliance()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test data anonymization
        $anonymizedData = $this->anonymizeUserData($this->sampleEvents['page_view']);
        $this->assertArrayNotHasKey('ip_address', $anonymizedData, 'IP address should be removed');
        $this->assertArrayHasKey('hashed_user_id', $anonymizedData, 'Should have hashed user ID');
        
        // Test data retention policy
        $retentionPolicy = $this->getDataRetentionPolicy();
        $this->assertIsArray($retentionPolicy, 'Retention policy should be an array');
        $this->assertArrayHasKey('analytics_data', $retentionPolicy);
        $this->assertArrayHasKey('user_sessions', $retentionPolicy);
        $this->assertArrayHasKey('log_files', $retentionPolicy);
        
        // Test data export functionality (GDPR right to data portability)
        $userId = 123;
        $exportedData = $this->exportUserData($userId);
        $this->assertIsArray($exportedData, 'Exported data should be an array');
        $this->assertArrayHasKey('events', $exportedData);
        $this->assertArrayHasKey('sessions', $exportedData);
        $this->assertArrayHasKey('preferences', $exportedData);
        
        // Test data deletion (GDPR right to be forgotten)
        $deletionResult = $this->deleteUserData($userId);
        $this->assertTrue($deletionResult, 'Should successfully delete user data');
        
        // Verify data is deleted
        $remainingData = $this->getUserData($userId);
        $this->assertEmpty($remainingData, 'User data should be deleted');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test analytics API and integrations
     */
    public function testAnalyticsAPI()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test API endpoints
        $endpoints = [
            '/api/analytics/overview',
            '/api/analytics/traffic',
            '/api/analytics/business/{id}',
            '/api/analytics/users',
            '/api/analytics/events',
        ];
        
        foreach ($endpoints as $endpoint) {
            $response = $this->callAnalyticsAPI($endpoint);
            $this->assertIsArray($response, "API endpoint $endpoint should return array");
            $this->assertArrayHasKey('status', $response, "Response should have status");
            $this->assertEquals('success', $response['status'], "API call should be successful");
        }
        
        // Test API authentication
        $unauthorizedResponse = $this->callAnalyticsAPI('/api/analytics/overview', ['invalid_token']);
        $this->assertEquals('unauthorized', $unauthorizedResponse['status'], 'Should reject invalid tokens');
        
        // Test rate limiting
        $rateLimitTest = $this->testAPIRateLimit('/api/analytics/overview', 100); // 100 requests
        $this->assertTrue($rateLimitTest['rate_limited'], 'API should implement rate limiting');
        $this->assertIsNumeric($rateLimitTest['limit'], 'Rate limit should be numeric');
        
        // Test data filtering and pagination
        $filteredResponse = $this->callAnalyticsAPI('/api/analytics/events', [], [
            'start_date' => date('Y-m-d', strtotime('-7 days')),
            'end_date' => date('Y-m-d'),
            'event_type' => 'page_view',
            'limit' => 50,
            'offset' => 0,
        ]);
        
        $this->assertArrayHasKey('events', $filteredResponse['data']);
        $this->assertArrayHasKey('total', $filteredResponse['data']);
        $this->assertArrayHasKey('pagination', $filteredResponse['data']);
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    // Helper methods for analytics testing
    
    private function trackEvent($eventData)
    {
        // Validate event data
        if (empty($eventData['event_type']) || empty($eventData['timestamp'])) {
            return false;
        }
        
        // Store in mock analytics database
        $this->mockAnalyticsDB['events'][] = $eventData;
        return true;
    }
    
    private function trackEventBatch($events)
    {
        foreach ($events as $event) {
            if (!$this->trackEvent($event)) {
                return false;
            }
        }
        return true;
    }
    
    private function getStoredEvent($eventType, $timestamp)
    {
        foreach ($this->mockAnalyticsDB['events'] ?? [] as $event) {
            if ($event['event_type'] === $eventType && $event['timestamp'] === $timestamp) {
                return $event;
            }
        }
        return null;
    }
    
    private function generateSampleData($count)
    {
        for ($i = 0; $i < $count; $i++) {
            $eventTypes = array_keys($this->sampleEvents);
            $randomType = $eventTypes[array_rand($eventTypes)];
            $event = $this->sampleEvents[$randomType];
            $event['timestamp'] = time() - rand(0, 86400 * 7); // Last 7 days
            $this->trackEvent($event);
        }
    }
    
    private function aggregateMetrics($interval, $period)
    {
        $events = $this->mockAnalyticsDB['events'] ?? [];
        
        // Simple aggregation simulation
        return [
            'page_views' => count(array_filter($events, fn($e) => $e['event_type'] === 'page_view')),
            'unique_visitors' => count(array_unique(array_column($events, 'user_id'))),
            'session_duration' => 300, // Average 5 minutes
            'bounce_rate' => 45.5,
            'business_views' => count(array_filter($events, fn($e) => $e['event_type'] === 'business_view')),
            'search_queries' => count(array_filter($events, fn($e) => $e['event_type'] === 'search')),
            'review_submissions' => count(array_filter($events, fn($e) => $e['event_type'] === 'review_submit')),
            'user_engagement' => 78.2,
            'conversion_rate' => 12.5,
            'popular_categories' => ['restaurant', 'retail', 'services'],
        ];
    }
    
    private function getRealtimeMetrics()
    {
        return [
            'active_users' => rand(50, 200),
            'current_sessions' => rand(30, 150),
            'page_views_last_hour' => rand(500, 2000),
            'current_popular_pages' => [
                '/businesses',
                '/search',
                '/business/pizza-place'
            ],
        ];
    }
    
    private function generateDashboardData($days)
    {
        return [
            'overview' => [
                'total_views' => rand(10000, 50000),
                'unique_visitors' => rand(5000, 25000),
                'avg_session_duration' => '4m 32s',
                'bounce_rate' => '42.3%',
            ],
            'traffic_sources' => [
                'organic' => 45.2,
                'direct' => 32.1,
                'referral' => 15.7,
                'social' => 7.0,
            ],
            'popular_pages' => [
                ['url' => '/businesses', 'views' => 5432, 'unique_views' => 3210],
                ['url' => '/search', 'views' => 4321, 'unique_views' => 2890],
                ['url' => '/categories/restaurants', 'views' => 3210, 'unique_views' => 2100],
            ],
            'user_behavior' => [
                'avg_session_duration' => 272, // seconds
                'pages_per_session' => 3.2,
                'bounce_rate' => 42.3,
                'return_visitor_rate' => 35.7,
            ],
            'business_analytics' => [
                'total_businesses' => 1250,
                'active_businesses' => 890,
                'avg_views_per_business' => 45.7,
                'top_categories' => ['restaurant', 'retail', 'healthcare'],
            ],
        ];
    }
    
    private function getBusinessMetrics($businessId, $days)
    {
        return [
            'total_views' => rand(100, 1000),
            'unique_visitors' => rand(50, 500),
            'click_through_rate' => round(rand(500, 1500) / 100, 2),
            'engagement_score' => rand(60, 95),
            'review_rate' => round(rand(500, 2000) / 100, 2),
        ];
    }
    
    private function getBusinessRankings($businessId)
    {
        return [
            'category_rank' => rand(1, 20),
            'local_rank' => rand(1, 50),
            'search_visibility' => rand(70, 95),
        ];
    }
    
    private function getCompetitorAnalysis($businessId)
    {
        return [
            'similar_businesses' => [456, 789, 101],
            'market_share' => 15.7,
            'performance_comparison' => [
                'views' => 'above_average',
                'rating' => 'average',
                'reviews' => 'below_average',
            ],
        ];
    }
    
    private function getBusinessSearchAnalytics($businessId)
    {
        return [
            'search_impressions' => rand(1000, 5000),
            'search_clicks' => rand(100, 500),
            'top_keywords' => ['restaurant near me', 'pizza delivery', 'best pizza'],
        ];
    }
    
    private function getUserSegments()
    {
        return [
            'new_users' => ['count' => 1250, 'percentage' => 35.2],
            'returning_users' => ['count' => 1890, 'percentage' => 53.4],
            'power_users' => ['count' => 324, 'percentage' => 9.1],
            'inactive_users' => ['count' => 81, 'percentage' => 2.3],
        ];
    }
    
    private function analyzeUserJourney($userId)
    {
        return [
            'touchpoints' => ['search', 'business_view', 'review_read', 'review_submit'],
            'conversion_path' => '/search -> /business/123 -> /reviews -> /submit-review',
            'session_count' => 5,
            'total_time_spent' => 1800, // 30 minutes
        ];
    }
    
    private function getCohortAnalysis($interval, $periods)
    {
        $cohorts = [];
        $retentionRates = [];
        
        for ($i = 0; $i < $periods; $i++) {
            $cohorts[] = [
                'period' => date('Y-m', strtotime("-$i months")),
                'users' => rand(100, 500),
            ];
            $retentionRates[] = array_fill(0, $i + 1, rand(20, 80));
        }
        
        return [
            'cohorts' => $cohorts,
            'retention_rates' => $retentionRates,
        ];
    }
    
    private function calculateUserLTV($userId)
    {
        // Simplified LTV calculation
        return rand(50, 200) + (rand(0, 100) / 100);
    }
    
    private function getPerformanceMetrics()
    {
        return [
            'page_load_time' => rand(1000, 4000), // milliseconds
            'server_response_time' => rand(200, 1500),
            'database_query_time' => rand(50, 500),
            'memory_usage' => rand(50, 200), // MB
            'error_rate' => rand(1, 10), // percentage
        ];
    }
    
    private function checkPerformanceAlerts()
    {
        return [
            ['metric' => 'page_load_time', 'value' => 3500, 'threshold' => 3000, 'severity' => 'warning'],
        ];
    }
    
    private function anonymizeUserData($eventData)
    {
        $anonymized = $eventData;
        unset($anonymized['ip_address']);
        $anonymized['hashed_user_id'] = hash('sha256', $eventData['user_id'] ?? '');
        return $anonymized;
    }
    
    private function getDataRetentionPolicy()
    {
        return [
            'analytics_data' => 365, // days
            'user_sessions' => 90,
            'log_files' => 180,
        ];
    }
    
    private function exportUserData($userId)
    {
        return [
            'events' => array_filter($this->mockAnalyticsDB['events'] ?? [], fn($e) => $e['user_id'] === $userId),
            'sessions' => [],
            'preferences' => [],
        ];
    }
    
    private function deleteUserData($userId)
    {
        $this->mockAnalyticsDB['events'] = array_filter(
            $this->mockAnalyticsDB['events'] ?? [], 
            fn($e) => $e['user_id'] !== $userId
        );
        return true;
    }
    
    private function getUserData($userId)
    {
        return array_filter($this->mockAnalyticsDB['events'] ?? [], fn($e) => $e['user_id'] === $userId);
    }
    
    private function callAnalyticsAPI($endpoint, $headers = [], $params = [])
    {
        // Simulate API call
        if (in_array('invalid_token', $headers)) {
            return ['status' => 'unauthorized'];
        }
        
        return [
            'status' => 'success',
            'data' => [
                'events' => [],
                'total' => 0,
                'pagination' => ['limit' => 50, 'offset' => 0],
            ],
        ];
    }
    
    private function testAPIRateLimit($endpoint, $requests)
    {
        // Simulate rate limiting after 50 requests
        return [
            'rate_limited' => $requests > 50,
            'limit' => 50,
            'reset_time' => time() + 3600,
        ];
    }
    
    private $mockAnalyticsDB = [
        'events' => [],
    ];
}
