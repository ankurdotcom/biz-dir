<?php
/**
 * Regression Test Suite Base Class
 * Provides foundation for comprehensive regression testing
 * Updated to use mock WordPress framework for test isolation
 */

require_once dirname(__DIR__) . '/mocks/WP_UnitTestCase.php';

/**
 * Base class for all regression tests
 * Implements comprehensive testing patterns and utilities
 * Following OWASP security guidelines and IEEE testing standards
 */
abstract class RegressionTestCase extends WP_UnitTestCase
{
    /**
     * Regression test data storage
     */
    protected static $regressionData = [];
    
    /**
     * Performance benchmarks
     */
    protected static $performanceBenchmarks = [];
    
    /**
     * Memory usage tracking
     */
    protected $initialMemory;
    protected $memoryThreshold;
    
    /**
     * Time tracking
     */
    protected $startTime;
    protected $timeThreshold;
    
    /**
     * Test environment snapshot
     */
    protected $environmentSnapshot = [];
    
    /**
     * Set up regression test environment
     */
    public function set_up()
    {
        // No parent::set_up() call needed - handled by PHPUnit setUp() bridge pattern
        
        // Initialize performance tracking
        $this->startTime = microtime(true);
        $this->initialMemory = memory_get_usage(true);
        
        // Set thresholds from environment or defaults
        $this->timeThreshold = (float)($_ENV['PERFORMANCE_THRESHOLD_MS'] ?? 2000);
        $this->memoryThreshold = (float)($_ENV['MEMORY_THRESHOLD_MB'] ?? 256) * 1024 * 1024;
        
        // Create environment snapshot
        $this->createEnvironmentSnapshot();
        
        // Initialize regression data directory
        $this->ensureRegressionDataDirectory();
        
        $this->debug('Regression test setup completed', [
            'test_method' => $this->getName(),
            'memory_limit' => $this->memoryThreshold,
            'time_limit' => $this->timeThreshold
        ]);
    }
    
    /**
     * Tear down and validate regression test
     */
    public function tear_down()
    {
        // Validate performance metrics
        $this->validatePerformanceMetrics();
        
        // Validate environment integrity
        $this->validateEnvironmentIntegrity();
        
        // Store regression data
        $this->storeRegressionData();
        
        // No parent::tear_down() call needed - handled by PHPUnit tearDown() bridge pattern
    }
    
    /**
     * Create comprehensive environment snapshot
     */
    protected function createEnvironmentSnapshot()
    {
        global $wpdb, $wp_roles;
        
        $this->environmentSnapshot = [
            'database' => [
                'tables' => $wpdb->get_col("SHOW TABLES"),
                'options_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options}"),
                'posts_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}"),
                'users_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
            ],
            'wordpress' => [
                'active_plugins' => get_option('active_plugins', []),
                'current_theme' => get_option('stylesheet'),
                'wp_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
            ],
            'system' => [
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'error_reporting' => error_reporting(),
            ],
            'bizdir' => [
                'businesses_count' => $this->getBusinessCount(),
                'users_count' => $this->getBizDirUserCount(),
                'reviews_count' => $this->getReviewCount(),
                'analytics_records' => $this->getAnalyticsRecordCount(),
            ]
        ];
    }
    
    /**
     * Validate performance metrics against baselines
     */
    protected function validatePerformanceMetrics()
    {
        $endTime = microtime(true);
        $executionTime = ($endTime - $this->startTime) * 1000; // Convert to milliseconds
        $memoryUsage = memory_get_usage(true) - $this->initialMemory;
        $peakMemory = memory_get_peak_usage(true);
        
        // Log performance metrics
        $this->debug('Performance metrics', [
            'execution_time_ms' => $executionTime,
            'memory_used_bytes' => $memoryUsage,
            'peak_memory_bytes' => $peakMemory,
            'time_threshold_ms' => $this->timeThreshold,
            'memory_threshold_bytes' => $this->memoryThreshold
        ]);
        
        // Assert performance requirements
        $this->assertLessThan(
            $this->timeThreshold,
            $executionTime,
            sprintf(
                'Test execution time (%.2fms) exceeded threshold (%.2fms)',
                $executionTime,
                $this->timeThreshold
            )
        );
        
        $this->assertLessThan(
            $this->memoryThreshold,
            $peakMemory,
            sprintf(
                'Peak memory usage (%.2fMB) exceeded threshold (%.2fMB)',
                $peakMemory / 1024 / 1024,
                $this->memoryThreshold / 1024 / 1024
            )
        );
        
        // Store performance data for trend analysis
        $this->storePerformanceData($executionTime, $memoryUsage, $peakMemory);
    }
    
    /**
     * Validate environment integrity after test
     */
    protected function validateEnvironmentIntegrity()
    {
        global $wpdb;
        
        // Check database integrity
        $currentTables = $wpdb->get_col("SHOW TABLES");
        $this->assertEquals(
            $this->environmentSnapshot['database']['tables'],
            $currentTables,
            'Database tables modified during test execution'
        );
        
        // Check plugin activation state
        $currentPlugins = get_option('active_plugins', []);
        $this->assertEquals(
            $this->environmentSnapshot['wordpress']['active_plugins'],
            $currentPlugins,
            'Plugin activation state changed during test'
        );
        
        // Check for orphaned data
        $this->validateNoOrphanedData();
    }
    
    /**
     * Store regression test data for historical comparison
     */
    protected function storeRegressionData()
    {
        $testName = $this->getName();
        $timestamp = date('Y-m-d H:i:s');
        
        $regressionData = [
            'test_name' => $testName,
            'timestamp' => $timestamp,
            'environment_snapshot' => $this->environmentSnapshot,
            'assertions_count' => $this->getNumAssertions(),
            'test_status' => 'passed', // Will be overridden if test fails
        ];
        
        // Store in file for historical tracking
        $dataFile = $this->getRegressionDataPath() . '/' . date('Y-m-d') . '_regression_data.json';
        $existingData = [];
        
        if (file_exists($dataFile)) {
            $existingData = json_decode(file_get_contents($dataFile), true) ?: [];
        }
        
        $existingData[] = $regressionData;
        file_put_contents($dataFile, json_encode($existingData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Assert that specific functionality hasn't regressed
     */
    protected function assertNoRegression($functionality, $expectedResult, $actualResult, $context = [])
    {
        $message = sprintf(
            'Regression detected in %s functionality. Expected: %s, Actual: %s',
            $functionality,
            json_encode($expectedResult),
            json_encode($actualResult)
        );
        
        if (!empty($context)) {
            $message .= ' Context: ' . json_encode($context);
        }
        
        $this->assertEquals($expectedResult, $actualResult, $message);
        
        // Log successful regression check
        $this->debug('Regression check passed', [
            'functionality' => $functionality,
            'context' => $context
        ]);
    }
    
    /**
     * Test database performance against baseline
     */
    protected function assertDatabasePerformance($query, $maxExecutionTime = 100)
    {
        global $wpdb;
        
        $startTime = microtime(true);
        $result = $wpdb->get_results($query);
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        $this->assertLessThan(
            $maxExecutionTime,
            $executionTime,
            sprintf(
                'Database query execution time (%.2fms) exceeded threshold (%dms). Query: %s',
                $executionTime,
                $maxExecutionTime,
                $query
            )
        );
        
        return $result;
    }
    
    /**
     * Ensure regression data directory exists
     */
    protected function ensureRegressionDataDirectory()
    {
        $dataPath = $this->getRegressionDataPath();
        if (!is_dir($dataPath)) {
            wp_mkdir_p($dataPath);
        }
    }
    
    /**
     * Get regression data storage path
     */
    protected function getRegressionDataPath()
    {
        return $_ENV['REGRESSION_DATA_DIR'] ?? (dirname(__DIR__) . '/fixtures/regression');
    }
    
    /**
     * Store performance data for trend analysis
     */
    protected function storePerformanceData($executionTime, $memoryUsage, $peakMemory)
    {
        $testName = $this->getName();
        $performanceData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'execution_time_ms' => $executionTime,
            'memory_usage_bytes' => $memoryUsage,
            'peak_memory_bytes' => $peakMemory,
        ];
        
        // Store in class property for batch processing
        if (!isset(self::$performanceBenchmarks[$testName])) {
            self::$performanceBenchmarks[$testName] = [];
        }
        
        self::$performanceBenchmarks[$testName][] = $performanceData;
    }
    
    /**
     * Get count of businesses for integrity checking
     */
    protected function getBusinessCount()
    {
        global $wpdb;
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'business'");
    }
    
    /**
     * Get count of BizDir users for integrity checking
     */
    protected function getBizDirUserCount()
    {
        global $wpdb;
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
    }
    
    /**
     * Get count of reviews for integrity checking
     */
    protected function getReviewCount()
    {
        global $wpdb;
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'business_review'");
    }
    
    /**
     * Get count of analytics records for integrity checking
     */
    protected function getAnalyticsRecordCount()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bizdir_analytics';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            return (int)$wpdb->get_var("SELECT COUNT(*) FROM $table");
        }
        return 0;
    }
    
    /**
     * Validate no orphaned data exists
     */
    protected function validateNoOrphanedData()
    {
        global $wpdb;
        
        // Check for orphaned post meta
        $orphanedPostMeta = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        $this->assertEquals(0, $orphanedPostMeta, 'Orphaned post meta detected');
        
        // Check for orphaned user meta
        $orphanedUserMeta = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->usermeta} um
            LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
            WHERE u.ID IS NULL
        ");
        
        $this->assertEquals(0, $orphanedUserMeta, 'Orphaned user meta detected');
        
        // Check for orphaned comments
        $orphanedComments = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->comments} c
            LEFT JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
            WHERE p.ID IS NULL AND c.comment_post_ID != 0
        ");
        
        $this->assertEquals(0, $orphanedComments, 'Orphaned comments detected');
    }
    
    /**
     * Enhanced debug logging for regression tests
     */
    protected function debug($message, $context = [])
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $logMessage = sprintf(
                '[REGRESSION][%s][%s] %s',
                get_class($this),
                $this->getName(),
                $message
            );
            
            if (!empty($context)) {
                $logMessage .= ' | Context: ' . json_encode($context);
            }
            
            error_log($logMessage);
        }
    }
    
    /**
     * Generate performance report for all tests
     */
    public static function generatePerformanceReport()
    {
        if (empty(self::$performanceBenchmarks)) {
            return;
        }
        
        $reportPath = dirname(__DIR__) . '/results/performance_report.json';
        $reportData = [
            'generated_at' => date('Y-m-d H:i:s'),
            'test_results' => self::$performanceBenchmarks,
            'summary' => [
                'total_tests' => count(self::$performanceBenchmarks),
                'average_execution_time' => 0,
                'average_memory_usage' => 0,
            ]
        ];
        
        // Calculate averages
        $totalTime = $totalMemory = 0;
        $testCount = 0;
        
        foreach (self::$performanceBenchmarks as $testData) {
            foreach ($testData as $run) {
                $totalTime += $run['execution_time_ms'];
                $totalMemory += $run['peak_memory_bytes'];
                $testCount++;
            }
        }
        
        if ($testCount > 0) {
            $reportData['summary']['average_execution_time'] = $totalTime / $testCount;
            $reportData['summary']['average_memory_usage'] = $totalMemory / $testCount;
        }
        
        // Ensure directory exists
        wp_mkdir_p(dirname($reportPath));
        
        // Write report
        file_put_contents($reportPath, json_encode($reportData, JSON_PRETTY_PRINT));
    }
}
