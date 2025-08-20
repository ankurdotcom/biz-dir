<?php
namespace BizDir\Tests\User;

use BizDir\Core\User\Auth_Handler;
use BizDir\Core\User\User_Manager;
use WP_UnitTestCase;

class AuthRateLimitTest extends WP_UnitTestCase {
    private $auth_handler;
    private $test_user_id;

    public function setUp(): void {
        parent::setUp();
        $this->auth_handler = new Auth_Handler();
        
        // Create test user
        $this->test_user_id = $this->factory->user->create([
            'role' => User_Manager::ROLE_CONTRIBUTOR,
            'user_login' => 'ratelimituser',
            'user_email' => 'ratelimit@example.com',
            'user_pass' => wp_hash_password('password123')
        ]);
    }

    public function tearDown(): void {
        wp_delete_user($this->test_user_id);
        parent::tearDown();
    }

    public function test_login_rate_limit() {
        $_POST['username'] = 'ratelimituser';
        $_POST['password'] = 'wrongpassword';
        $_POST['nonce'] = wp_create_nonce('biz_dir_login');

        // Make multiple failed attempts
        for ($i = 0; $i < 5; $i++) {
            ob_start();
            $this->auth_handler->handle_login();
            ob_end_clean();
        }

        // Next attempt should be rate limited
        ob_start();
        $this->auth_handler->handle_login();
        $response = json_decode(ob_get_clean());

        $this->assertFalse($response->success);
        $this->assertStringContainsString('Too many login attempts', $response->data);
    }

    public function test_rate_limit_reset() {
        $_POST['username'] = 'ratelimituser';
        $_POST['nonce'] = wp_create_nonce('biz_dir_login');

        // Make a few failed attempts
        $_POST['password'] = 'wrongpassword';
        for ($i = 0; $i < 3; $i++) {
            ob_start();
            $this->auth_handler->handle_login();
            ob_end_clean();
        }

        // Successful login should reset rate limit
        $_POST['password'] = 'password123';
        ob_start();
        $this->auth_handler->handle_login();
        $response = json_decode(ob_get_clean());

        $this->assertTrue($response->success);

        // Should be able to attempt login again
        $_POST['password'] = 'wrongpassword';
        ob_start();
        $this->auth_handler->handle_login();
        $response = json_decode(ob_get_clean());

        $this->assertFalse($response->success);
        $this->assertStringNotContainsString('Too many login attempts', $response->data);
    }
}
