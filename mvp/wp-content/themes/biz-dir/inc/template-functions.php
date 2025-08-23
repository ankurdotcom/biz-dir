<?php
/**
 * Template Functions
 * Additional functions for the BizDir theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Additional template functions for business directory
 * Note: Core functions are in functions.php to avoid conflicts
 */

/**
 * Helper function for template includes
 */
function biz_dir_template_loaded() {
    // This function confirms template-functions.php is loaded
    return true;
}

/**
 * Custom body classes for business directory
 */
function biz_dir_body_classes($classes) {
    // Add business directory specific classes
    $classes[] = 'bizdir-theme';
    
    if (is_home() || is_front_page()) {
        $classes[] = 'bizdir-home';
    }
    
    return $classes;
}
add_filter('body_class', 'biz_dir_body_classes');
