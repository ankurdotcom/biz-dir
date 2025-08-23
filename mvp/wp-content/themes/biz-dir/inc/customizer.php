<?php
/**
 * Customizer additions
 * Theme customization options
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function biz_dir_customize_register($wp_customize) {
    
    // Site Identity
    $wp_customize->get_setting('blogname')->transport = 'postMessage';
    $wp_customize->get_setting('blogdescription')->transport = 'postMessage';
    
    // Add Business Directory Section
    $wp_customize->add_section('biz_dir_options', array(
        'title' => __('Business Directory Options', 'biz-dir'),
        'priority' => 120,
    ));
    
    // Hero Section Title
    $wp_customize->add_setting('biz_dir_hero_title', array(
        'default' => 'Find Local Businesses',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('biz_dir_hero_title', array(
        'label' => __('Hero Section Title', 'biz-dir'),
        'section' => 'biz_dir_options',
        'type' => 'text',
    ));
    
    // Hero Section Subtitle
    $wp_customize->add_setting('biz_dir_hero_subtitle', array(
        'default' => 'Discover and connect with local businesses in your area',
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
    
    $wp_customize->add_control('biz_dir_hero_subtitle', array(
        'label' => __('Hero Section Subtitle', 'biz-dir'),
        'section' => 'biz_dir_options',
        'type' => 'textarea',
    ));
    
    // Primary Color
    $wp_customize->add_setting('biz_dir_primary_color', array(
        'default' => '#007cba',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'biz_dir_primary_color', array(
        'label' => __('Primary Color', 'biz-dir'),
        'section' => 'biz_dir_options',
    )));
    
    // Show Featured Businesses
    $wp_customize->add_setting('biz_dir_show_featured', array(
        'default' => true,
        'sanitize_callback' => 'biz_dir_sanitize_checkbox',
    ));
    
    $wp_customize->add_control('biz_dir_show_featured', array(
        'label' => __('Show Featured Businesses', 'biz-dir'),
        'section' => 'biz_dir_options',
        'type' => 'checkbox',
    ));
}
add_action('customize_register', 'biz_dir_customize_register');

/**
 * Sanitize checkbox
 */
function biz_dir_sanitize_checkbox($checked) {
    return ((isset($checked) && true == $checked) ? true : false);
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function biz_dir_customize_preview_js() {
    wp_enqueue_script('biz-dir-customizer', get_template_directory_uri() . '/js/customizer.js', array('customize-preview'), '1.0.0', true);
}
add_action('customize_preview_init', 'biz_dir_customize_preview_js');
