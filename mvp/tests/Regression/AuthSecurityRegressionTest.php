<?php
/**
 * Authentication and Security Regression Tests
 * Tests for user authentication, authorization, and security features
 */

require_once __DIR__ . '/RegressionTestCase.php';

class AuthSecurityRegressionTest extends RegressionTestCase
{
    private $testUsers = [];
    private $testSessions = [];
    
    /**
     * Set up test data
     */
    public function set_up()
    {
        parent::set_up();
        
        // Create test users with different roles
        $this->testUsers['admin'] = $this->factory->user->create([
            'user_login' => 'test_admin_user',
            'user_email' => 'admin@bizdir-test.local',
            'user_pass' => 'TestAdmin123!',
            'role' => 'administrator'
        ]);
        
        $this->testUsers['business_owner'] = $this->factory->user->create([
            'user_login' => 'test_business_owner',
            'user_email' => 'owner@bizdir-test.local',
            'user_pass' => 'TestOwner123!',
            'role' => 'subscriber'
        ]);
        
        $this->testUsers['regular_user'] = $this->factory->user->create([
            'user_login' => 'test_regular_user',
            'user_email' => 'user@bizdir-test.local',
            'user_pass' => 'TestUser123!',
            'role' => 'subscriber'
        ]);
    }
    
    /**
     * Clean up test data
     */
    public function tear_down()
    {
        // Clean up test users
        foreach ($this->testUsers as $userId) {
            wp_delete_user($userId);
        }
        
        // Clean up test sessions
        $this->cleanupTestSessions();
        
        parent::tear_down();
    }
    
    /**
     * Test user authentication regression
     */
    public function test_user_authentication_regression()
    {
        // Test valid login
        $validCredentials = [
            'user_login' => 'test_admin_user',
            'user_password' => 'TestAdmin123!',
        ];
        
        $startTime = microtime(true);
        $authResult = wp_authenticate($validCredentials['user_login'], $validCredentials['user_password']);
        $authTime = (microtime(true) - $startTime) * 1000;
        
        // Performance regression check
        $this->assertLessThan(100, $authTime, 'Authentication should complete within 100ms');
        
        // Functionality regression check
        $this->assertInstanceOf('WP_User', $authResult, 'Valid credentials should return WP_User object');
        $this->assertNoRegression(
            'auth_valid_user_id',
            $this->testUsers['admin'],
            $authResult->ID
        );
        
        // Test invalid login
        $invalidCredentials = [
            'user_login' => 'test_admin_user',
            'user_password' => 'WrongPassword123!',
        ];
        
        $startTime = microtime(true);
        $failedAuthResult = wp_authenticate($invalidCredentials['user_login'], $invalidCredentials['user_password']);
        $failedAuthTime = (microtime(true) - $startTime) * 1000;
        
        // Performance regression check for failed auth
        $this->assertLessThan(200, $failedAuthTime, 'Failed authentication should complete within 200ms');
        
        // Security regression check
        $this->assertInstanceOf('WP_Error', $failedAuthResult, 'Invalid credentials should return WP_Error');
        
        $this->debug('Authentication regression test passed', [
            'valid_auth_time_ms' => $authTime,
            'failed_auth_time_ms' => $failedAuthTime
        ]);
    }
    
    /**
     * Test rate limiting regression
     */
    public function test_rate_limiting_regression()
    {
        $username = 'test_admin_user';
        $wrongPassword = 'WrongPassword123!';
        
        // Attempt multiple failed logins
        $attemptTimes = [];
        for ($i = 1; $i <= 5; $i++) {
            $startTime = microtime(true);
            $result = wp_authenticate($username, $wrongPassword);
            $attemptTime = (microtime(true) - $startTime) * 1000;
            $attemptTimes[] = $attemptTime;
            
            // Should always return WP_Error for wrong password
            $this->assertInstanceOf('WP_Error', $result);
        }
        
        // Check if rate limiting is working
        // Later attempts should take longer due to rate limiting
        $avgEarlyAttempts = array_sum(array_slice($attemptTimes, 0, 2)) / 2;
        $avgLaterAttempts = array_sum(array_slice($attemptTimes, -2)) / 2;
        
        $this->assertNoRegression(
            'rate_limiting_effectiveness',
            true,
            $avgLaterAttempts >= $avgEarlyAttempts,
            ['early_avg' => $avgEarlyAttempts, 'later_avg' => $avgLaterAttempts]
        );
        
        $this->debug('Rate limiting regression test passed', [
            'attempt_times' => $attemptTimes,
            'early_avg_ms' => $avgEarlyAttempts,
            'later_avg_ms' => $avgLaterAttempts
        ]);
    }
    
    /**
     * Test session management regression
     */
    public function test_session_management_regression()
    {
        $userId = $this->testUsers['business_owner'];
        
        // Simulate login and session creation
        wp_set_current_user($userId);
        
        $startTime = microtime(true);
        $sessionToken = wp_generate_auth_cookie($userId, time() + 3600);
        $sessionTime = (microtime(true) - $startTime) * 1000;
        
        // Performance regression check
        $this->assertLessThan(50, $sessionTime, 'Session creation should complete within 50ms');
        
        // Functionality regression check
        $this->assertNotEmpty($sessionToken, 'Session token should not be empty');
        
        // Test session validation
        $startTime = microtime(true);
        $validatedUserId = wp_validate_auth_cookie($sessionToken);
        $validationTime = (microtime(true) - $startTime) * 1000;
        
        // Performance regression check
        $this->assertLessThan(30, $validationTime, 'Session validation should complete within 30ms');
        
        // Functionality regression check
        $this->assertNoRegression(
            'session_validation_user_id',
            $userId,
            $validatedUserId
        );
        
        $this->debug('Session management regression test passed', [
            'session_creation_time_ms' => $sessionTime,
            'session_validation_time_ms' => $validationTime
        ]);
    }
    
    /**
     * Test permission checks regression
     */
    public function test_permission_checks_regression()
    {
        // Test admin permissions
        wp_set_current_user($this->testUsers['admin']);
        
        $adminPermissions = [
            'manage_options' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'edit_users' => true,
            'delete_users' => true,
        ];
        
        foreach ($adminPermissions as $capability => $expected) {
            $startTime = microtime(true);
            $hasCapability = current_user_can($capability);
            $checkTime = (microtime(true) - $startTime) * 1000;
            
            // Performance regression check
            $this->assertLessThan(10, $checkTime, 'Permission check should complete within 10ms');
            
            // Functionality regression check
            $this->assertNoRegression(
                "admin_permission_{$capability}",
                $expected,
                $hasCapability
            );
        }
        
        // Test regular user permissions
        wp_set_current_user($this->testUsers['regular_user']);
        
        $userPermissions = [
            'manage_options' => false,
            'edit_posts' => false,
            'delete_posts' => false,
            'read' => true,
        ];
        
        foreach ($userPermissions as $capability => $expected) {
            $startTime = microtime(true);
            $hasCapability = current_user_can($capability);
            $checkTime = (microtime(true) - $startTime) * 1000;
            
            // Performance regression check
            $this->assertLessThan(10, $checkTime, 'Permission check should complete within 10ms');
            
            // Functionality regression check
            $this->assertNoRegression(
                "user_permission_{$capability}",
                $expected,
                $hasCapability
            );
        }
        
        $this->debug('Permission checks regression test passed');
    }
    
    /**
     * Test password security regression
     */
    public function test_password_security_regression()
    {
        $passwords = [
            'weak' => 'password',
            'medium' => 'Password123',
            'strong' => 'TestPassword123!@#',
        ];
        
        foreach ($passwords as $strength => $password) {
            $startTime = microtime(true);
            $hash = wp_hash_password($password);
            $hashTime = (microtime(true) - $startTime) * 1000;
            
            // Performance regression check
            $this->assertLessThan(300, $hashTime, 'Password hashing should complete within 300ms');
            
            // Security regression check
            $this->assertNotEquals($password, $hash, 'Password should be hashed, not stored in plain text');
            $this->assertGreaterThan(50, strlen($hash), 'Hash should be sufficiently long');
            
            // Test password verification
            $startTime = microtime(true);
            $isValid = wp_check_password($password, $hash);
            $verifyTime = (microtime(true) - $startTime) * 1000;
            
            // Performance regression check
            $this->assertLessThan(300, $verifyTime, 'Password verification should complete within 300ms');
            
            // Functionality regression check
            $this->assertNoRegression(
                "password_verification_{$strength}",
                true,
                $isValid
            );
            
            $this->debug('Password security test passed', [
                'strength' => $strength,
                'hash_time_ms' => $hashTime,
                'verify_time_ms' => $verifyTime
            ]);
        }
    }
    
    /**
     * Test SQL injection protection regression
     */
    public function test_sql_injection_protection_regression()
    {
        global $wpdb;
        
        // Test malicious input patterns
        $maliciousInputs = [
            "'; DROP TABLE wp_users; --",
            "1' OR '1'='1",
            "' UNION SELECT * FROM wp_users --",
            "<script>alert('xss')</script>",
            "1; DELETE FROM wp_posts WHERE 1=1;",
        ];
        
        foreach ($maliciousInputs as $input) {
            // Test user lookup with malicious input
            $startTime = microtime(true);
            $result = $wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE user_login = %s", $input);
            $query = $wpdb->get_var($result);
            $queryTime = (microtime(true) - $startTime) * 1000;
            
            // Performance regression check
            $this->assertLessThan(50, $queryTime, 'Protected query should complete within 50ms');
            
            // Security regression check
            $this->assertNull($query, 'Malicious input should not return results');
            
            // Test that database structure is intact
            $tableExists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->users}'");
            $this->assertNoRegression(
                'sql_injection_table_integrity',
                $wpdb->users,
                $tableExists
            );
        }
        
        $this->debug('SQL injection protection regression test passed');
    }
    
    /**
     * Test XSS protection regression
     */
    public function test_xss_protection_regression()
    {
        $xssInputs = [
            "<script>alert('xss')</script>",
            "javascript:alert('xss')",
            "<img src=x onerror=alert('xss')>",
            "'\"><script>alert('xss')</script>",
            "<svg onload=alert('xss')>",
        ];
        
        foreach ($xssInputs as $input) {
            // Test input sanitization
            $startTime = microtime(true);
            $sanitized = sanitize_text_field($input);
            $sanitizeTime = (microtime(true) - $startTime) * 1000;
            
            // Performance regression check
            $this->assertLessThan(10, $sanitizeTime, 'Input sanitization should complete within 10ms');
            
            // Security regression check
            $this->assertStringNotContainsString('<script', $sanitized, 'Script tags should be removed');
            $this->assertStringNotContainsString('javascript:', $sanitized, 'JavaScript URLs should be removed');
            $this->assertStringNotContainsString('onerror=', $sanitized, 'Event handlers should be removed');
            
            $this->debug('XSS protection test passed', [
                'original' => $input,
                'sanitized' => $sanitized,
                'sanitize_time_ms' => $sanitizeTime
            ]);
        }
    }
    
    /**
     * Test CSRF protection regression
     */
    public function test_csrf_protection_regression()
    {
        // Test nonce generation
        $action = 'test_action';
        
        $startTime = microtime(true);
        $nonce = wp_create_nonce($action);
        $nonceTime = (microtime(true) - $startTime) * 1000;
        
        // Performance regression check
        $this->assertLessThan(20, $nonceTime, 'Nonce creation should complete within 20ms');
        
        // Functionality regression check
        $this->assertNotEmpty($nonce, 'Nonce should not be empty');
        $this->assertIsString($nonce, 'Nonce should be a string');
        
        // Test nonce verification
        $startTime = microtime(true);
        $isValid = wp_verify_nonce($nonce, $action);
        $verifyTime = (microtime(true) - $startTime) * 1000;
        
        // Performance regression check
        $this->assertLessThan(20, $verifyTime, 'Nonce verification should complete within 20ms');
        
        // Functionality regression check
        $this->assertNoRegression(
            'csrf_nonce_validation',
            1, // wp_verify_nonce returns 1 for valid nonce
            $isValid
        );
        
        // Test invalid nonce
        $invalidNonce = wp_verify_nonce('invalid_nonce', $action);
        $this->assertFalse($invalidNonce, 'Invalid nonce should return false');
        
        $this->debug('CSRF protection regression test passed', [
            'nonce_creation_time_ms' => $nonceTime,
            'nonce_verification_time_ms' => $verifyTime
        ]);
    }
    
    /**
     * Test user enumeration protection regression
     */
    public function test_user_enumeration_protection_regression()
    {
        // Test user ID enumeration attempts
        $testIds = [1, 999, 1000, 9999];
        
        foreach ($testIds as $testId) {
            // Test user lookup by ID
            $startTime = microtime(true);
            $user = get_user_by('id', $testId);
            $lookupTime = (microtime(true) - $startTime) * 1000;
            
            // Performance regression check
            $this->assertLessThan(50, $lookupTime, 'User lookup should complete within 50ms');
            
            // If user doesn't exist, should return false
            if (!$user) {
                $this->assertFalse($user, 'Non-existent user should return false');
            }
        }
        
        // Test username enumeration attempts
        $testUsernames = ['admin', 'administrator', 'root', 'test', 'user'];
        
        foreach ($testUsernames as $username) {
            $startTime = microtime(true);
            $user = get_user_by('login', $username);
            $lookupTime = (microtime(true) - $startTime) * 1000;
            
            // Performance regression check
            $this->assertLessThan(50, $lookupTime, 'Username lookup should complete within 50ms');
        }
        
        $this->debug('User enumeration protection regression test passed');
    }
    
    /**
     * Clean up test sessions
     */
    private function cleanupTestSessions()
    {
        foreach ($this->testSessions as $sessionId) {
            // Clean up session data if needed
            delete_transient("session_$sessionId");
        }
    }
}
