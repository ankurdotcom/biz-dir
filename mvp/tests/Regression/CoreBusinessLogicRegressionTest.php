<?php
/**
 * Core Business Logic Regression Tests
 * Tests for business listing CRUD operations and core functionality
 */

require_once __DIR__ . '/RegressionTestCase.php';

class CoreBusinessLogicRegressionTest extends RegressionTestCase
{
    private $testBusinessIds = [];
    private $testUserIds = [];
    
    /**
     * Set up test data
     */
    public function set_up()
    {
        parent::set_up();
        
        // Create test users
        $this->testUserIds['owner'] = $this->factory->user->create([
            'user_login' => 'test_business_owner',
            'user_email' => 'owner@bizdir-test.local',
            'role' => 'subscriber'
        ]);
        
        $this->testUserIds['admin'] = $this->factory->user->create([
            'user_login' => 'test_admin',
            'user_email' => 'admin@bizdir-test.local',
            'role' => 'administrator'
        ]);
    }
    
    /**
     * Clean up test data
     */
    public function tear_down()
    {
        // Clean up test businesses
        foreach ($this->testBusinessIds as $businessId) {
            wp_delete_post($businessId, true);
        }
        
        // Clean up test users
        foreach ($this->testUserIds as $userId) {
            wp_delete_user($userId);
        }
        
        parent::tear_down();
    }
    
    /**
     * Test business creation regression
     */
    public function test_business_creation_regression()
    {
        $businessData = [
            'title' => 'Regression Test Business',
            'description' => 'Test business for regression testing',
            'address' => '123 Test Street, Test City',
            'phone' => '+1-555-0123',
            'email' => 'test@business.com',
            'website' => 'https://testbusiness.com',
            'category' => 'restaurant',
            'owner_id' => $this->testUserIds['owner']
        ];
        
        // Test business creation
        $startTime = microtime(true);
        $businessId = $this->createTestBusiness($businessData);
        $creationTime = (microtime(true) - $startTime) * 1000;
        
        // Regression assertions
        $this->assertIsInt($businessId, 'Business creation should return integer ID');
        $this->assertGreaterThan(0, $businessId, 'Business ID should be positive');
        
        // Performance regression check
        $this->assertLessThan(500, $creationTime, 'Business creation should complete within 500ms');
        
        // Data integrity regression check
        $business = $this->factory->post->create_and_get($businessData);
        $this->assertNoRegression(
            'business_creation_title',
            $businessData['title'],
            $business->post_title
        );
        
        // Security regression check (OWASP A03:2021 - Injection)
        $malicious_data = [
            'title' => '<script>alert("XSS")</script>Malicious Business',
            'description' => 'DROP TABLE businesses; --',
            'owner_id' => $this->testUserIds['owner']
        ];
        
        $malicious_business_id = $this->createTestBusiness($malicious_data);
        $malicious_business = $this->factory->post->create_and_get(['post_title' => $malicious_data['title']]);
        
        // Verify XSS protection
        $this->assertStringNotContainsString('<script>', $malicious_business->post_title, 'XSS should be prevented');
        $this->assertStringNotContainsString('alert(', $malicious_business->post_title, 'JavaScript should be stripped');
        
        $this->testBusinessIds[] = $businessId;
        $this->testBusinessIds[] = $malicious_business_id;
    }
    
    /**
     * Test business search functionality with comprehensive regression checks
     * 
     * @group search
     * @group performance  
     * @group security
     */
    public function test_business_search_comprehensive_regression()
    {
        $savedAddress = get_post_meta($businessId, '_business_address', true);
        $this->assertNoRegression(
            'business_creation_address',
            $businessData['address'],
            $savedAddress
        );
        
        $savedPhone = get_post_meta($businessId, '_business_phone', true);
        $this->assertNoRegression(
            'business_creation_phone',
            $businessData['phone'],
            $savedPhone
        );
        
        $this->testBusinessIds[] = $businessId;
        
        $this->debug('Business creation regression test passed', [
            'business_id' => $businessId,
            'creation_time_ms' => $creationTime
        ]);
    }
    
    /**
     * Test business search regression
     */
    public function test_business_search_regression()
    {
        // Create test businesses for search
        $businesses = [
            [
                'title' => 'Italian Restaurant Regression',
                'description' => 'Authentic Italian cuisine',
                'category' => 'restaurant',
            ],
            [
                'title' => 'Coffee Shop Regression',
                'description' => 'Artisan coffee and pastries',
                'category' => 'food',
            ],
            [
                'title' => 'Auto Repair Regression',
                'description' => 'Professional auto repair services',
                'category' => 'automotive',
            ]
        ];
        
        foreach ($businesses as $businessData) {
            $businessData['owner_id'] = $this->testUserIds['owner'];
            $this->testBusinessIds[] = $this->createTestBusiness($businessData);
        }
        
        // Test search functionality
        $searchQueries = [
            ['query' => 'restaurant', 'expected_min' => 1],
            ['query' => 'coffee', 'expected_min' => 1],
            ['query' => 'auto', 'expected_min' => 1],
            ['query' => 'nonexistent', 'expected_min' => 0],
        ];
        
        foreach ($searchQueries as $searchTest) {
            $startTime = microtime(true);
            $results = $this->performBusinessSearch($searchTest['query']);
            $searchTime = (microtime(true) - $startTime) * 1000;
            
            // Performance regression check
            $this->assertLessThan(200, $searchTime, 'Search should complete within 200ms');
            
            // Functionality regression check
            $this->assertGreaterThanOrEqual(
                $searchTest['expected_min'],
                count($results),
                sprintf('Search for "%s" should return at least %d results', 
                    $searchTest['query'], 
                    $searchTest['expected_min']
                )
            );
            
            $this->debug('Search regression test passed', [
                'query' => $searchTest['query'],
                'results_count' => count($results),
                'search_time_ms' => $searchTime
            ]);
        }
    }
    
    /**
     * Test business update regression
     */
    public function test_business_update_regression()
    {
        // Create test business
        $originalData = [
            'title' => 'Original Business Name',
            'description' => 'Original description',
            'address' => '123 Original Street',
            'phone' => '+1-555-0001',
            'owner_id' => $this->testUserIds['owner']
        ];
        
        $businessId = $this->createTestBusiness($originalData);
        $this->testBusinessIds[] = $businessId;
        
        // Update business data
        $updatedData = [
            'title' => 'Updated Business Name',
            'description' => 'Updated description',
            'address' => '456 Updated Avenue',
            'phone' => '+1-555-0002',
        ];
        
        $startTime = microtime(true);
        $updateResult = $this->updateTestBusiness($businessId, $updatedData);
        $updateTime = (microtime(true) - $startTime) * 1000;
        
        // Performance regression check
        $this->assertLessThan(300, $updateTime, 'Business update should complete within 300ms');
        
        // Functionality regression check
        $this->assertTrue($updateResult, 'Business update should return true on success');
        
        // Data integrity regression check
        $updatedBusiness = get_post($businessId);
        $this->assertNoRegression(
            'business_update_title',
            $updatedData['title'],
            $updatedBusiness->post_title
        );
        
        $this->assertNoRegression(
            'business_update_content',
            $updatedData['description'],
            $updatedBusiness->post_content
        );
        
        $updatedAddress = get_post_meta($businessId, '_business_address', true);
        $this->assertNoRegression(
            'business_update_address',
            $updatedData['address'],
            $updatedAddress
        );
        
        $this->debug('Business update regression test passed', [
            'business_id' => $businessId,
            'update_time_ms' => $updateTime
        ]);
    }
    
    /**
     * Test business deletion regression
     */
    public function test_business_deletion_regression()
    {
        // Create test business
        $businessData = [
            'title' => 'Business to Delete',
            'description' => 'This business will be deleted',
            'owner_id' => $this->testUserIds['owner']
        ];
        
        $businessId = $this->createTestBusiness($businessData);
        
        // Verify business exists
        $business = get_post($businessId);
        $this->assertNotNull($business, 'Business should exist before deletion');
        
        // Delete business
        $startTime = microtime(true);
        $deleteResult = wp_delete_post($businessId, true);
        $deleteTime = (microtime(true) - $startTime) * 1000;
        
        // Performance regression check
        $this->assertLessThan(200, $deleteTime, 'Business deletion should complete within 200ms');
        
        // Functionality regression check
        $this->assertNotFalse($deleteResult, 'Business deletion should not return false');
        
        // Data integrity regression check
        $deletedBusiness = get_post($businessId);
        $this->assertNoRegression(
            'business_deletion_cleanup',
            null,
            $deletedBusiness
        );
        
        // Check meta cleanup
        $metaExists = get_post_meta($businessId);
        $this->assertEmpty($metaExists, 'Post meta should be cleaned up after deletion');
        
        $this->debug('Business deletion regression test passed', [
            'business_id' => $businessId,
            'delete_time_ms' => $deleteTime
        ]);
    }
    
    /**
     * Test business listing pagination regression
     */
    public function test_business_listing_pagination_regression()
    {
        // Create multiple test businesses
        for ($i = 1; $i <= 15; $i++) {
            $businessData = [
                'title' => "Pagination Test Business $i",
                'description' => "Test business $i for pagination testing",
                'owner_id' => $this->testUserIds['owner']
            ];
            $this->testBusinessIds[] = $this->createTestBusiness($businessData);
        }
        
        // Test pagination
        $perPage = 5;
        $pages = [1, 2, 3];
        
        foreach ($pages as $page) {
            $startTime = microtime(true);
            $results = $this->getBusinessListingPage($page, $perPage);
            $queryTime = (microtime(true) - $startTime) * 1000;
            
            // Performance regression check
            $this->assertLessThan(150, $queryTime, 'Pagination query should complete within 150ms');
            
            // Functionality regression check
            $expectedCount = ($page <= 3) ? $perPage : 0;
            $this->assertNoRegression(
                "pagination_page_{$page}_count",
                $expectedCount,
                count($results)
            );
            
            $this->debug('Pagination regression test passed', [
                'page' => $page,
                'per_page' => $perPage,
                'results_count' => count($results),
                'query_time_ms' => $queryTime
            ]);
        }
    }
    
    /**
     * Test business category filtering regression
     */
    public function test_business_category_filtering_regression()
    {
        $categories = ['restaurant', 'retail', 'service'];
        $businessesByCategory = [];
        
        // Create businesses in different categories
        foreach ($categories as $category) {
            for ($i = 1; $i <= 3; $i++) {
                $businessData = [
                    'title' => "Category Test Business $category $i",
                    'description' => "Test business for $category category",
                    'category' => $category,
                    'owner_id' => $this->testUserIds['owner']
                ];
                $businessId = $this->createTestBusiness($businessData);
                $this->testBusinessIds[] = $businessId;
                $businessesByCategory[$category][] = $businessId;
            }
        }
        
        // Test category filtering
        foreach ($categories as $category) {
            $startTime = microtime(true);
            $results = $this->getBusinessesByCategory($category);
            $filterTime = (microtime(true) - $startTime) * 1000;
            
            // Performance regression check
            $this->assertLessThan(100, $filterTime, 'Category filtering should complete within 100ms');
            
            // Functionality regression check
            $this->assertNoRegression(
                "category_filter_{$category}_count",
                3, // We created 3 businesses per category
                count($results)
            );
            
            $this->debug('Category filtering regression test passed', [
                'category' => $category,
                'results_count' => count($results),
                'filter_time_ms' => $filterTime
            ]);
        }
    }
    
    /**
     * Helper method to create test business
     */
    private function createTestBusiness($data)
    {
        $postData = [
            'post_title' => $data['title'],
            'post_content' => $data['description'] ?? '',
            'post_type' => 'business',
            'post_status' => 'publish',
            'post_author' => $data['owner_id'] ?? 1,
        ];
        
        $businessId = wp_insert_post($postData);
        
        if ($businessId && !is_wp_error($businessId)) {
            // Add meta data
            if (isset($data['address'])) {
                update_post_meta($businessId, '_business_address', $data['address']);
            }
            if (isset($data['phone'])) {
                update_post_meta($businessId, '_business_phone', $data['phone']);
            }
            if (isset($data['email'])) {
                update_post_meta($businessId, '_business_email', $data['email']);
            }
            if (isset($data['website'])) {
                update_post_meta($businessId, '_business_website', $data['website']);
            }
            if (isset($data['category'])) {
                wp_set_post_terms($businessId, [$data['category']], 'business_category');
            }
        }
        
        return $businessId;
    }
    
    /**
     * Helper method to update test business
     */
    private function updateTestBusiness($businessId, $data)
    {
        $postData = [
            'ID' => $businessId,
        ];
        
        if (isset($data['title'])) {
            $postData['post_title'] = $data['title'];
        }
        if (isset($data['description'])) {
            $postData['post_content'] = $data['description'];
        }
        
        $result = wp_update_post($postData);
        
        if ($result && !is_wp_error($result)) {
            // Update meta data
            if (isset($data['address'])) {
                update_post_meta($businessId, '_business_address', $data['address']);
            }
            if (isset($data['phone'])) {
                update_post_meta($businessId, '_business_phone', $data['phone']);
            }
        }
        
        return !is_wp_error($result);
    }
    
    /**
     * Helper method to perform business search
     */
    private function performBusinessSearch($query)
    {
        $searchArgs = [
            'post_type' => 'business',
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => -1,
        ];
        
        $searchQuery = new WP_Query($searchArgs);
        return $searchQuery->posts;
    }
    
    /**
     * Helper method to get business listing page
     */
    private function getBusinessListingPage($page, $perPage)
    {
        $args = [
            'post_type' => 'business',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'orderby' => 'title',
            'order' => 'ASC',
        ];
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Helper method to get businesses by category
     */
    private function getBusinessesByCategory($category)
    {
        $args = [
            'post_type' => 'business',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'business_category',
                    'field' => 'slug',
                    'terms' => $category,
                ],
            ],
        ];
        
        $query = new WP_Query($args);
        return $query->posts;
    }
}
