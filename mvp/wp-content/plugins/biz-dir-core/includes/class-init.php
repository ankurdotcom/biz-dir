<?php
/**
 * Core Initialization Class
 *
 * @package BizDir\Core
 */

namespace BizDir\Core;

class Init {
    /**
     * Initialize core functionality
     */
    public function __construct() {
        // Constructor is kept empty to allow delayed initialization
    }

    /**
     * Initialize core plugin functionality
     *
     * @return void
     */
    public function init() {
        // Initialize core components
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
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
