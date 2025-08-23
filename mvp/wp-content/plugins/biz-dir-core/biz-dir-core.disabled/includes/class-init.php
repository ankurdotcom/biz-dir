<?php
/**
 * Prevent direct access
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core Initialization Class
 *
 * @package BizDir\Core
 */

namespace BizDir\Core;

class Init {
    private $user_init;
    private $business_init;

    /**
     * Initialize core functionality
     */
    public function __construct() {
        $permission_handler = new User\Permission_Handler();
        $business_manager = new Business\Business_Manager($permission_handler);
        
        $this->user_init = new User\Init();
        $this->business_init = new Business\Init($business_manager);
    }

    /**
     * Initialize core plugin functionality
     *
     * @return void
     */
    public function init() {
        // Initialize core components
        $this->user_init->init();
        $this->business_init->init();
    }

    /**
     * Register custom post types
     *
     * @return void
     */
    public function register_post_types() {
        // To be implemented
    }

    /**
     * Register custom taxonomies
     *
     * @return void
     */
    public function register_taxonomies() {
        // To be implemented
    }
}
