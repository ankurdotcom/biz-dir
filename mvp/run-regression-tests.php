<?php
/**
 * Regression Test Runner Script
 * Executes comprehensive regression testing and generates reports
 */

require_once __DIR__ . '/tests/bootstrap.php';

class RegressionTestRunner
{
    private $config;
    private $results = [];
    private $startTime;
    private $testSuites = [
        'CoreBusinessLogicRegressionTest',
        'AuthSecurityRegressionTest', 
        'DatabasePerformanceRegressionTest',
    ];
    
    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->config = [
            'output_dir' => __DIR__ . '/tests/results',
            'coverage_dir' => __DIR__ . '/tests/coverage',
            'log_file' => __DIR__ . '/tests/logs/regression.log',
            'performance_threshold_ms' => 2000,
            'memory_threshold_mb' => 256,
        ];
        
        $this->ensureDirectories();
        $this->initializeLogging();
    }
    
    /**
     * Run all regression tests
     */
    public function runAll()
    {
        $this->log("Starting comprehensive regression testing suite");
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🔄 BizDir Platform Regression Testing Suite\n";
        echo str_repeat("=", 80) . "\n\n";
        
        // Run individual test suites
        foreach ($this->testSuites as $testSuite) {
            $this->runTestSuite($testSuite);
        }
        
        // Generate comprehensive reports
        $this->generateReports();
        
        // Display summary
        $this->displaySummary();
        
        $this->log("Regression testing completed");
    }
    
    /**
     * Run individual test suite
     */
    private function runTestSuite($testSuite)
    {
        echo "🧪 Running $testSuite...\n";
        
        $startTime = microtime(true);
        
        try {
            // Execute PHPUnit for specific test suite
            $command = sprintf(
                'cd %s && ./vendor/bin/phpunit --configuration phpunit-regression.xml --testsuite=%s --log-junit %s/junit_%s.xml',
                __DIR__,
                $this->getTestSuiteName($testSuite),
                $this->config['output_dir'],
                strtolower($testSuite)
            );
            
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $this->results[$testSuite] = [
                'status' => $returnCode === 0 ? 'PASSED' : 'FAILED',
                'execution_time_ms' => $executionTime,
                'output' => implode("\n", $output),
                'return_code' => $returnCode,
            ];
            
            $status = $returnCode === 0 ? '✅ PASSED' : '❌ FAILED';
            echo "   $status (%.2fms)\n";
            
            if ($returnCode !== 0) {
                echo "   ⚠️  Error output:\n";
                echo "   " . implode("\n   ", array_slice($output, -5)) . "\n";
            }
            
        } catch (Exception $e) {
            $this->results[$testSuite] = [
                'status' => 'ERROR',
                'execution_time_ms' => 0,
                'output' => $e->getMessage(),
                'return_code' => -1,
            ];
            
            echo "   ❌ ERROR: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Generate comprehensive reports
     */
    private function generateReports()
    {
        echo "📊 Generating regression test reports...\n";
        
        // Generate JUnit XML report
        $this->generateJUnitReport();
        
        // Generate HTML report
        $this->generateHTMLReport();
        
        // Generate JSON report
        $this->generateJSONReport();
        
        // Generate performance report
        $this->generatePerformanceReport();
        
        // Generate text summary
        $this->generateTextSummary();
        
        echo "   ✅ Reports generated in " . $this->config['output_dir'] . "\n\n";
    }
    
    /**
     * Generate JUnit XML report
     */
    private function generateJUnitReport()
    {
        $totalTests = count($this->testSuites);
        $failures = 0;
        $errors = 0;
        $totalTime = 0;
        
        foreach ($this->results as $result) {
            if ($result['status'] === 'FAILED') $failures++;
            if ($result['status'] === 'ERROR') $errors++;
            $totalTime += $result['execution_time_ms'] / 1000;
        }
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $testsuites = $xml->createElement('testsuites');
        $testsuites->setAttribute('name', 'BizDir Regression Tests');
        $testsuites->setAttribute('tests', $totalTests);
        $testsuites->setAttribute('failures', $failures);
        $testsuites->setAttribute('errors', $errors);
        $testsuites->setAttribute('time', number_format($totalTime, 3));
        
        foreach ($this->results as $suiteName => $result) {
            $testsuite = $xml->createElement('testsuite');
            $testsuite->setAttribute('name', $suiteName);
            $testsuite->setAttribute('tests', '1');
            $testsuite->setAttribute('failures', $result['status'] === 'FAILED' ? '1' : '0');
            $testsuite->setAttribute('errors', $result['status'] === 'ERROR' ? '1' : '0');
            $testsuite->setAttribute('time', number_format($result['execution_time_ms'] / 1000, 3));
            
            $testcase = $xml->createElement('testcase');
            $testcase->setAttribute('name', $suiteName);
            $testcase->setAttribute('classname', $suiteName);
            $testcase->setAttribute('time', number_format($result['execution_time_ms'] / 1000, 3));
            
            if ($result['status'] !== 'PASSED') {
                $failure = $xml->createElement('failure', htmlspecialchars($result['output']));
                $failure->setAttribute('type', $result['status']);
                $testcase->appendChild($failure);
            }
            
            $testsuite->appendChild($testcase);
            $testsuites->appendChild($testsuite);
        }
        
        $xml->appendChild($testsuites);
        $xml->save($this->config['output_dir'] . '/regression_junit.xml');
    }
    
    /**
     * Generate HTML report
     */
    private function generateHTMLReport()
    {
        $totalTests = count($this->testSuites);
        $passed = count(array_filter($this->results, fn($r) => $r['status'] === 'PASSED'));
        $failed = $totalTests - $passed;
        $totalTime = array_sum(array_column($this->results, 'execution_time_ms'));
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizDir Regression Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #007bff; }
        .stat-card.passed { border-left-color: #28a745; }
        .stat-card.failed { border-left-color: #dc3545; }
        .stat-number { font-size: 2em; font-weight: bold; margin-bottom: 5px; }
        .stat-label { color: #666; }
        .test-results { margin-top: 30px; }
        .test-item { background: #f8f9fa; margin-bottom: 15px; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; }
        .test-item.failed { border-left-color: #dc3545; }
        .test-item.error { border-left-color: #ffc107; }
        .test-header { display: flex; justify-content: between; align-items: center; margin-bottom: 10px; }
        .test-name { font-weight: bold; font-size: 1.1em; }
        .test-status { padding: 4px 12px; border-radius: 4px; color: white; font-size: 0.9em; }
        .status-passed { background: #28a745; }
        .status-failed { background: #dc3545; }
        .status-error { background: #ffc107; }
        .test-details { color: #666; font-size: 0.9em; }
        .test-output { background: #f1f1f1; padding: 10px; border-radius: 4px; margin-top: 10px; font-family: monospace; font-size: 0.8em; max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 BizDir Platform Regression Test Report</h1>
            <p>Generated on ' . date('Y-m-d H:i:s') . '</p>
        </div>
        
        <div class="summary">
            <div class="stat-card">
                <div class="stat-number">' . $totalTests . '</div>
                <div class="stat-label">Total Tests</div>
            </div>
            <div class="stat-card passed">
                <div class="stat-number">' . $passed . '</div>
                <div class="stat-label">Passed</div>
            </div>
            <div class="stat-card failed">
                <div class="stat-number">' . $failed . '</div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . number_format($totalTime) . 'ms</div>
                <div class="stat-label">Total Time</div>
            </div>
        </div>
        
        <div class="test-results">
            <h2>Test Suite Results</h2>';
        
        foreach ($this->results as $testSuite => $result) {
            $statusClass = strtolower($result['status']);
            $html .= '
            <div class="test-item ' . $statusClass . '">
                <div class="test-header">
                    <span class="test-name">' . $testSuite . '</span>
                    <span class="test-status status-' . $statusClass . '">' . $result['status'] . '</span>
                </div>
                <div class="test-details">
                    Execution Time: ' . number_format($result['execution_time_ms']) . 'ms | 
                    Return Code: ' . $result['return_code'] . '
                </div>';
            
            if ($result['status'] !== 'PASSED') {
                $html .= '<div class="test-output">' . htmlspecialchars($result['output']) . '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '
        </div>
    </div>
</body>
</html>';
        
        file_put_contents($this->config['output_dir'] . '/regression_report.html', $html);
    }
    
    /**
     * Generate JSON report
     */
    private function generateJSONReport()
    {
        $report = [
            'generated_at' => date('c'),
            'execution_time_ms' => (microtime(true) - $this->startTime) * 1000,
            'summary' => [
                'total_tests' => count($this->testSuites),
                'passed' => count(array_filter($this->results, fn($r) => $r['status'] === 'PASSED')),
                'failed' => count(array_filter($this->results, fn($r) => $r['status'] === 'FAILED')),
                'errors' => count(array_filter($this->results, fn($r) => $r['status'] === 'ERROR')),
                'total_execution_time_ms' => array_sum(array_column($this->results, 'execution_time_ms')),
            ],
            'test_results' => $this->results,
            'configuration' => $this->config,
        ];
        
        file_put_contents(
            $this->config['output_dir'] . '/regression_report.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * Generate performance report
     */
    private function generatePerformanceReport()
    {
        $performanceData = [];
        
        foreach ($this->results as $testSuite => $result) {
            $performanceData[$testSuite] = [
                'execution_time_ms' => $result['execution_time_ms'],
                'performance_grade' => $this->getPerformanceGrade($result['execution_time_ms']),
                'threshold_met' => $result['execution_time_ms'] <= $this->config['performance_threshold_ms'],
            ];
        }
        
        $report = [
            'generated_at' => date('c'),
            'performance_threshold_ms' => $this->config['performance_threshold_ms'],
            'memory_threshold_mb' => $this->config['memory_threshold_mb'],
            'test_performance' => $performanceData,
            'overall_performance' => [
                'average_execution_time_ms' => array_sum(array_column($performanceData, 'execution_time_ms')) / count($performanceData),
                'slowest_test' => array_keys($performanceData, max($performanceData))[0] ?? null,
                'fastest_test' => array_keys($performanceData, min($performanceData))[0] ?? null,
            ],
        ];
        
        file_put_contents(
            $this->config['output_dir'] . '/performance_report.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * Generate text summary
     */
    private function generateTextSummary()
    {
        $totalTests = count($this->testSuites);
        $passed = count(array_filter($this->results, fn($r) => $r['status'] === 'PASSED'));
        $failed = $totalTests - $passed;
        $totalTime = array_sum(array_column($this->results, 'execution_time_ms'));
        
        $summary = "BizDir Platform Regression Test Summary\n";
        $summary .= str_repeat("=", 50) . "\n\n";
        $summary .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $summary .= "Total Tests: $totalTests\n";
        $summary .= "Passed: $passed\n";
        $summary .= "Failed: $failed\n";
        $summary .= "Total Time: " . number_format($totalTime) . "ms\n\n";
        
        $summary .= "Test Suite Results:\n";
        $summary .= str_repeat("-", 30) . "\n";
        
        foreach ($this->results as $testSuite => $result) {
            $status = str_pad($result['status'], 8);
            $time = str_pad(number_format($result['execution_time_ms']) . 'ms', 10);
            $summary .= "$status $time $testSuite\n";
        }
        
        $summary .= "\n";
        
        if ($failed > 0) {
            $summary .= "Failed Tests Details:\n";
            $summary .= str_repeat("-", 20) . "\n";
            
            foreach ($this->results as $testSuite => $result) {
                if ($result['status'] !== 'PASSED') {
                    $summary .= "\n$testSuite:\n";
                    $summary .= "Status: " . $result['status'] . "\n";
                    $summary .= "Return Code: " . $result['return_code'] . "\n";
                    $summary .= "Output: " . substr($result['output'], 0, 500) . "\n";
                }
            }
        }
        
        file_put_contents($this->config['output_dir'] . '/regression_summary.txt', $summary);
    }
    
    /**
     * Display summary to console
     */
    private function displaySummary()
    {
        $totalTests = count($this->testSuites);
        $passed = count(array_filter($this->results, fn($r) => $r['status'] === 'PASSED'));
        $failed = $totalTests - $passed;
        $totalTime = (microtime(true) - $this->startTime);
        
        echo str_repeat("=", 80) . "\n";
        echo "📊 Regression Testing Summary\n";
        echo str_repeat("=", 80) . "\n";
        
        echo sprintf("Total Tests: %d\n", $totalTests);
        echo sprintf("✅ Passed: %d\n", $passed);
        echo sprintf("❌ Failed: %d\n", $failed);
        echo sprintf("⏱️  Total Time: %.2fs\n", $totalTime);
        echo sprintf("📁 Reports: %s\n", $this->config['output_dir']);
        
        if ($failed === 0) {
            echo "\n🎉 All regression tests passed! Platform is stable.\n";
        } else {
            echo "\n⚠️  Some tests failed. Please review the detailed reports.\n";
        }
        
        echo str_repeat("=", 80) . "\n";
    }
    
    /**
     * Get test suite name for PHPUnit
     */
    private function getTestSuiteName($className)
    {
        $mapping = [
            'CoreBusinessLogicRegressionTest' => 'BusinessLogic',
            'AuthSecurityRegressionTest' => 'Security',
            'DatabasePerformanceRegressionTest' => 'Performance',
        ];
        
        return $mapping[$className] ?? $className;
    }
    
    /**
     * Get performance grade based on execution time
     */
    private function getPerformanceGrade($timeMs)
    {
        if ($timeMs <= 500) return 'A';
        if ($timeMs <= 1000) return 'B';
        if ($timeMs <= 2000) return 'C';
        if ($timeMs <= 5000) return 'D';
        return 'F';
    }
    
    /**
     * Ensure output directories exist
     */
    private function ensureDirectories()
    {
        $directories = [
            $this->config['output_dir'],
            $this->config['coverage_dir'],
            dirname($this->config['log_file']),
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Initialize logging
     */
    private function initializeLogging()
    {
        $logDir = dirname($this->config['log_file']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Log message
     */
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->config['log_file'], $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Run regression tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $runner = new RegressionTestRunner();
    $runner->runAll();
}
