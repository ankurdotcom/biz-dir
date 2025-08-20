<?php
/**
 * Plugin Name: Business Directory Core
 * Description: Core functionality for the Community Business Directory Platform
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: biz-dir
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BIZ_DIR_VERSION', '1.0.0');
define('BIZ_DIR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BIZ_DIR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Debug log constants for testing
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[BizDir Core] Plugin constants defined:');
    error_log('[BizDir Core] BIZ_DIR_VERSION = ' . BIZ_DIR_VERSION);
    error_log('[BizDir Core] BIZ_DIR_PLUGIN_DIR = ' . BIZ_DIR_PLUGIN_DIR);
    error_log('[BizDir Core] BIZ_DIR_PLUGIN_URL = ' . BIZ_DIR_PLUGIN_URL);
}

// Initialize autoloader immediately
require_once BIZ_DIR_PLUGIN_DIR . 'includes/class-autoloader.php';
$loader = new \BizDir\Core\Autoloader();
$loader->register();

// Initialize plugin
function biz_dir_init() {
    // Initialize core components
    if (!class_exists('BizDir\Core\Init')) {
        throw new \RuntimeException('Core Init class not found. Autoloader may not be working.');
    }
    
    $core = new \BizDir\Core\Init();
    $core->init();
    
    // Initialize user module
    new \BizDir\Core\User\Init();
}
add_action('plugins_loaded', 'biz_dir_init', 10);
