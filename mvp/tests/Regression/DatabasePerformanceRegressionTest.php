<?php
/**
 * Database Performance Regression Tests
 * Tests for database query performance and optimization
 */

require_once __DIR__ . '/RegressionTestCase.php';

class DatabasePerformanceRegressionTest extends RegressionTestCase
{
    private $testDataIds = [];
    
    /**
     * Set up test data
     */
    public function set_up()
    {
        parent::set_up();
        
        // Create sample data for performance testing
        $this->createSampleData();
    }
    
    /**
     * Clean up test data
     */
    public function tear_down()
    {
        $this->cleanupSampleData();
        parent::tear_down();
    }
    
    /**
     * Test business listing query performance regression
     */
    public function test_business_listing_query_performance()
    {
        global $wpdb;
        
        // Test basic business listing query
        $query = $wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_content, p.post_date
            FROM {$wpdb->posts} p
            WHERE p.post_type = %s 
            AND p.post_status = %s
            ORDER BY p.post_date DESC
            LIMIT %d
        ", 'business', 'publish', 20);
        
        $results = $this->assertDatabasePerformance($query, 100);
        
        // Functionality regression check
        $this->assertIsArray($results, 'Business listing query should return array');
        $this->assertLessThanOrEqual(20, count($results), 'Should not exceed limit');
        
        $this->debug('Business listing query performance test passed', [
            'results_count' => count($results)
        ]);
    }
    
    /**
     * Test business search query performance regression
     */
    public function test_business_search_query_performance()
    {
        global $wpdb;
        
        $searchTerms = ['restaurant', 'coffee', 'auto'];
        
        foreach ($searchTerms as $term) {
            // Test search query with LIKE
            $query = $wpdb->prepare("
                SELECT p.ID, p.post_title, p.post_content
                FROM {$wpdb->posts} p
                WHERE p.post_type = %s 
                AND p.post_status = %s
                AND (p.post_title LIKE %s OR p.post_content LIKE %s)
                ORDER BY p.post_title ASC
                LIMIT %d
            ", 'business', 'publish', "%$term%", "%$term%", 50);
            
            $results = $this->assertDatabasePerformance($query, 150);
            
            $this->assertIsArray($results, 'Search query should return array');
            
            $this->debug('Search query performance test passed', [
                'search_term' => $term,
                'results_count' => count($results)
            ]);
        }
    }
    
    /**
     * Test business meta query performance regression
     */
    public function test_business_meta_query_performance()
    {
        global $wpdb;
        
        // Test meta query with JOIN
        $query = $wpdb->prepare("
            SELECT p.ID, p.post_title, pm.meta_value as address
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
            WHERE p.post_type = %s 
            AND p.post_status = %s
            ORDER BY p.post_title ASC
            LIMIT %d
        ", '_business_address', 'business', 'publish', 30);
        
        $results = $this->assertDatabasePerformance($query, 120);
        
        $this->assertIsArray($results, 'Meta query should return array');
        
        $this->debug('Meta query performance test passed', [
            'results_count' => count($results)
        ]);
    }
    
    /**
     * Test category filtering query performance regression
     */
    public function test_category_filtering_query_performance()
    {
        global $wpdb;
        
        // Test taxonomy query with multiple JOINs
        $query = $wpdb->prepare("
            SELECT p.ID, p.post_title, t.name as category
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_type = %s 
            AND p.post_status = %s
            AND tt.taxonomy = %s
            ORDER BY p.post_title ASC
            LIMIT %d
        ", 'business', 'publish', 'business_category', 25);
        
        $results = $this->assertDatabasePerformance($query, 200);
        
        $this->assertIsArray($results, 'Category query should return array');
        
        $this->debug('Category filtering query performance test passed', [
            'results_count' => count($results)
        ]);
    }
    
    /**
     * Test user-business relationship query performance regression
     */
    public function test_user_business_relationship_query_performance()
    {
        global $wpdb;
        
        $userId = $this->testDataIds['users'][0] ?? 1;
        
        // Test query for user's businesses
        $query = $wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_date, u.display_name as owner
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
            WHERE p.post_type = %s 
            AND p.post_status = %s
            AND p.post_author = %d
            ORDER BY p.post_date DESC
        ", 'business', 'publish', $userId);
        
        $results = $this->assertDatabasePerformance($query, 80);
        
        $this->assertIsArray($results, 'User business query should return array');
        
        $this->debug('User-business relationship query performance test passed', [
            'user_id' => $userId,
            'results_count' => count($results)
        ]);
    }
    
    /**
     * Test analytics aggregation query performance regression
     */
    public function test_analytics_aggregation_query_performance()
    {
        global $wpdb;
        
        $analyticsTable = $wpdb->prefix . 'bizdir_analytics';
        
        // Check if analytics table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$analyticsTable'") !== $analyticsTable) {
            $this->markTestSkipped('Analytics table not found');
            return;
        }
        
        // Test analytics aggregation query
        $query = $wpdb->prepare("
            SELECT 
                business_id,
                COUNT(*) as view_count,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                MAX(created_at) as last_view
            FROM {$analyticsTable}
            WHERE created_at >= %s
            GROUP BY business_id
            ORDER BY view_count DESC
            LIMIT %d
        ", date('Y-m-d', strtotime('-30 days')), 20);
        
        $results = $this->assertDatabasePerformance($query, 300);
        
        $this->assertIsArray($results, 'Analytics aggregation query should return array');
        
        $this->debug('Analytics aggregation query performance test passed', [
            'results_count' => count($results)
        ]);
    }
    
    /**
     * Test review aggregation query performance regression
     */
    public function test_review_aggregation_query_performance()
    {
        global $wpdb;
        
        // Test review aggregation query
        $query = $wpdb->prepare("
            SELECT 
                c.comment_post_ID as business_id,
                COUNT(*) as review_count,
                AVG(CAST(cm.meta_value AS DECIMAL(3,2))) as avg_rating,
                MAX(c.comment_date) as latest_review
            FROM {$wpdb->comments} c
            LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = %s
            LEFT JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
            WHERE c.comment_type = %s
            AND c.comment_approved = %s
            AND p.post_type = %s
            GROUP BY c.comment_post_ID
            ORDER BY review_count DESC
            LIMIT %d
        ", 'rating', 'business_review', '1', 'business', 15);
        
        $results = $this->assertDatabasePerformance($query, 250);
        
        $this->assertIsArray($results, 'Review aggregation query should return array');
        
        $this->debug('Review aggregation query performance test passed', [
            'results_count' => count($results)
        ]);
    }
    
    /**
     * Test complex business listing with all joins performance regression
     */
    public function test_complex_business_listing_query_performance()
    {
        global $wpdb;
        
        // Test complex query with multiple JOINs and subqueries
        $query = $wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                p.post_content,
                u.display_name as owner_name,
                pm_address.meta_value as address,
                pm_phone.meta_value as phone,
                t.name as category,
                COALESCE(review_stats.review_count, 0) as review_count,
                COALESCE(review_stats.avg_rating, 0) as avg_rating
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
            LEFT JOIN {$wpdb->postmeta} pm_address ON p.ID = pm_address.post_id AND pm_address.meta_key = %s
            LEFT JOIN {$wpdb->postmeta} pm_phone ON p.ID = pm_phone.post_id AND pm_phone.meta_key = %s
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = %s
            LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            LEFT JOIN (
                SELECT 
                    comment_post_ID,
                    COUNT(*) as review_count,
                    AVG(CAST(cm.meta_value AS DECIMAL(3,2))) as avg_rating
                FROM {$wpdb->comments} c
                LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = %s
                WHERE c.comment_type = %s AND c.comment_approved = %s
                GROUP BY comment_post_ID
            ) as review_stats ON p.ID = review_stats.comment_post_ID
            WHERE p.post_type = %s 
            AND p.post_status = %s
            ORDER BY p.post_date DESC
            LIMIT %d
        ", '_business_address', '_business_phone', 'business_category', 'rating', 'business_review', '1', 'business', 'publish', 10);
        
        $results = $this->assertDatabasePerformance($query, 400);
        
        $this->assertIsArray($results, 'Complex query should return array');
        $this->assertLessThanOrEqual(10, count($results), 'Should respect LIMIT clause');
        
        // Check data integrity
        foreach ($results as $result) {
            $this->assertObjectHasAttribute('ID', $result);
            $this->assertObjectHasAttribute('post_title', $result);
            $this->assertIsNumeric($result->review_count);
        }
        
        $this->debug('Complex business listing query performance test passed', [
            'results_count' => count($results)
        ]);
    }
    
    /**
     * Test database index effectiveness regression
     */
    public function test_database_index_effectiveness()
    {
        global $wpdb;
        
        // Test queries that should benefit from indexes
        $indexTestQueries = [
            // Post type index
            [
                'query' => $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'business'),
                'max_time' => 50,
                'description' => 'post_type index'
            ],
            // Post status index
            [
                'query' => $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_status = %s", 'publish'),
                'max_time' => 50,
                'description' => 'post_status index'
            ],
            // Post author index
            [
                'query' => $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_author = %d", 1),
                'max_time' => 30,
                'description' => 'post_author index'
            ],
            // Meta key index
            [
                'query' => $wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", '_business_address'),
                'max_time' => 60,
                'description' => 'meta_key index'
            ],
        ];
        
        foreach ($indexTestQueries as $test) {
            $results = $this->assertDatabasePerformance($test['query'], $test['max_time']);
            
            $this->debug('Index effectiveness test passed', [
                'description' => $test['description'],
                'results_count' => count($results)
            ]);
        }
    }
    
    /**
     * Test transaction performance regression
     */
    public function test_transaction_performance()
    {
        global $wpdb;
        
        $startTime = microtime(true);
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Perform multiple operations
            for ($i = 1; $i <= 5; $i++) {
                $postData = [
                    'post_title' => "Transaction Test Business $i",
                    'post_content' => "Test business $i for transaction testing",
                    'post_type' => 'business',
                    'post_status' => 'publish',
                    'post_author' => 1,
                ];
                
                $postId = wp_insert_post($postData);
                $this->testDataIds['transaction_posts'][] = $postId;
                
                // Add meta data
                update_post_meta($postId, '_business_address', "123 Test Street $i");
                update_post_meta($postId, '_business_phone', "+1-555-000$i");
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            throw $e;
        }
        
        $transactionTime = (microtime(true) - $startTime) * 1000;
        
        // Performance regression check
        $this->assertLessThan(1000, $transactionTime, 'Transaction should complete within 1000ms');
        
        // Verify all data was committed
        $committedPosts = 0;
        foreach ($this->testDataIds['transaction_posts'] as $postId) {
            if (get_post($postId)) {
                $committedPosts++;
            }
        }
        
        $this->assertNoRegression(
            'transaction_commit_count',
            5,
            $committedPosts
        );
        
        $this->debug('Transaction performance test passed', [
            'transaction_time_ms' => $transactionTime,
            'committed_posts' => $committedPosts
        ]);
    }
    
    /**
     * Create sample data for testing
     */
    private function createSampleData()
    {
        // Create test users
        for ($i = 1; $i <= 3; $i++) {
            $userId = $this->factory->user->create([
                'user_login' => "perf_test_user_$i",
                'user_email' => "perftest$i@bizdir-test.local",
                'role' => 'subscriber'
            ]);
            $this->testDataIds['users'][] = $userId;
        }
        
        // Create test businesses
        $categories = ['restaurant', 'retail', 'service'];
        for ($i = 1; $i <= 20; $i++) {
            $postData = [
                'post_title' => "Performance Test Business $i",
                'post_content' => "Test business $i for performance testing with detailed description and content.",
                'post_type' => 'business',
                'post_status' => 'publish',
                'post_author' => $this->testDataIds['users'][array_rand($this->testDataIds['users'])],
            ];
            
            $businessId = wp_insert_post($postData);
            $this->testDataIds['businesses'][] = $businessId;
            
            // Add meta data
            update_post_meta($businessId, '_business_address', "123 Performance Test Street $i");
            update_post_meta($businessId, '_business_phone', "+1-555-" . str_pad($i, 4, '0', STR_PAD_LEFT));
            update_post_meta($businessId, '_business_email', "business$i@test.com");
            
            // Add category
            wp_set_post_terms($businessId, [$categories[$i % 3]], 'business_category');
        }
        
        // Create test reviews
        for ($i = 1; $i <= 50; $i++) {
            $businessId = $this->testDataIds['businesses'][array_rand($this->testDataIds['businesses'])];
            $userId = $this->testDataIds['users'][array_rand($this->testDataIds['users'])];
            
            $commentData = [
                'comment_post_ID' => $businessId,
                'comment_author' => "Test Reviewer $i",
                'comment_author_email' => "reviewer$i@test.com",
                'comment_content' => "Performance test review $i with detailed feedback content.",
                'comment_type' => 'business_review',
                'comment_approved' => 1,
                'user_id' => $userId,
            ];
            
            $commentId = wp_insert_comment($commentData);
            $this->testDataIds['reviews'][] = $commentId;
            
            // Add rating meta
            add_comment_meta($commentId, 'rating', rand(1, 5));
        }
    }
    
    /**
     * Clean up sample data
     */
    private function cleanupSampleData()
    {
        // Clean up reviews
        foreach ($this->testDataIds['reviews'] ?? [] as $reviewId) {
            wp_delete_comment($reviewId, true);
        }
        
        // Clean up businesses
        foreach ($this->testDataIds['businesses'] ?? [] as $businessId) {
            wp_delete_post($businessId, true);
        }
        
        // Clean up transaction posts
        foreach ($this->testDataIds['transaction_posts'] ?? [] as $postId) {
            wp_delete_post($postId, true);
        }
        
        // Clean up users
        foreach ($this->testDataIds['users'] ?? [] as $userId) {
            wp_delete_user($userId);
        }
    }
}
