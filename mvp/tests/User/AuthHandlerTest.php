<?php
/**
 * Auth Handler Test Case
 */

namespace BizDir\Tests\User;

use BizDir\Core\User\Auth_Handler;
use BizDir\Core\User\User_Manager;
use WP_UnitTestCase;

class AuthHandlerTest extends WP_UnitTestCase {
    private $auth_handler;
    private $test_user_id;

    public function setUp(): void {
        parent::setUp();
        $this->auth_handler = new Auth_Handler();
        
        // Create test user
        $this->test_user_id = $this->factory->user->create([
            'role' => User_Manager::ROLE_CONTRIBUTOR,
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => wp_hash_password('password123')
        ]);
    }

    public function tearDown(): void {
        wp_delete_user($this->test_user_id);
        parent::tearDown();
    }

    public function test_login_handler() {
        $_POST['username'] = 'testuser';
        $_POST['password'] = 'password123';
        $_POST['nonce'] = wp_create_nonce('biz_dir_login');

        ob_start();
        $this->auth_handler->handle_login();
        $response = json_decode(ob_get_clean());

        $this->assertTrue($response->success);
        $this->assertEquals(home_url('/dashboard/'), $response->data->redirect_url);
    }

    public function test_registration_handler() {
        $_POST['username'] = 'newuser';
        $_POST['email'] = 'newuser@example.com';
        $_POST['password'] = 'newpassword123';
        $_POST['nonce'] = wp_create_nonce('biz_dir_register');

        ob_start();
        $this->auth_handler->handle_registration();
        $response = json_decode(ob_get_clean());

        $this->assertTrue($response->success);
        
        // Verify user was created
        $user = get_user_by('email', 'newuser@example.com');
        $this->assertNotFalse($user);
        $this->assertTrue(in_array(User_Manager::ROLE_CONTRIBUTOR, $user->roles));

        // Verify verification code was set
        $code = get_user_meta($user->ID, 'biz_dir_email_verification_code', true);
        $this->assertNotEmpty($code);

        wp_delete_user($user->ID);
    }

    public function test_email_verification() {
        // Set up verification code
        $code = wp_generate_password(20, false);
        update_user_meta($this->test_user_id, 'biz_dir_email_verification_code', $code);

        $_GET['user'] = $this->test_user_id;
        $_GET['code'] = $code;

        // Test verification
        ob_start();
        $this->auth_handler->handle_email_verification();
        ob_end_clean();

        $this->assertTrue(get_user_meta($this->test_user_id, 'biz_dir_email_verified', true));
        $this->assertEmpty(get_user_meta($this->test_user_id, 'biz_dir_email_verification_code', true));
    }

    public function test_auth_requirements() {
        // Test protected path redirect
        $_SERVER['REQUEST_URI'] = '/dashboard/';
        
        ob_start();
        $this->auth_handler->check_auth_requirements();
        ob_end_clean();

        $this->assertTrue(did_action('wp_safe_redirect'));

        // Test moderator area access
        $_SERVER['REQUEST_URI'] = '/moderate/';
        wp_set_current_user($this->test_user_id);

        $this->expectException('WPDieException');
        $this->auth_handler->check_auth_requirements();
    }
}
