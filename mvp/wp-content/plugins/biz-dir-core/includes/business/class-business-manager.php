<?php
/**
 * Business Manager Class
 *
 * @package BizDir\Core\Business
 */

namespace BizDir\Core\Business;

use BizDir\Core\User\Permission_Handler;

class Business_Manager {
    /**
     * Post type name
     */
    const POST_TYPE = 'business_listing';

    /**
     * @var Search_Handler
     */
    private $search_handler;

    /**
     * @var Review_Handler
     */
    private $review_handler;

    /**
     * @var Permission_Handler
     */
    private $permission_handler;

    /**
     * Constructor
     * 
     * @param Permission_Handler $permission_handler
     */
    public function __construct(Permission_Handler $permission_handler) {
        $this->permission_handler = $permission_handler;
        $this->search_handler = new Search_Handler();
        $this->review_handler = new Review_Handler($permission_handler);
        error_log('[BizDir Business] Business Manager constructed with dependencies');
    }

    /**
     * Initialize the business manager
     */
    public function init() {
        error_log('[BizDir Business] Starting Business Manager initialization');

        // Register post type and taxonomies
        error_log('[BizDir Business] Registering post type and taxonomies');
        $this->register_post_type();
        $this->register_taxonomies();

        error_log('[BizDir Business] Setting up meta boxes and actions');
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_business_meta'], 10, 2);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_custom_columns'], 10, 2);

        // Initialize search and review handlers
        error_log('[BizDir Business] Initializing search handler');
        $this->search_handler->init();

        error_log('[BizDir Business] Initializing review handler');
        $this->review_handler->init();

        error_log('[BizDir Business] Business Manager initialization complete');
    }

    /**
     * Register business-related taxonomies
     */
    public function register_taxonomies() {
        error_log('[BizDir Business] Registering business taxonomies');
        register_taxonomy('business_category', self::POST_TYPE, [
            'labels' => [
                'name' => __('Categories', 'biz-dir'),
                'singular_name' => __('Category', 'biz-dir'),
                'menu_name' => __('Categories', 'biz-dir'),
                'all_items' => __('All Categories', 'biz-dir'),
                'edit_item' => __('Edit Category', 'biz-dir'),
                'view_item' => __('View Category', 'biz-dir'),
                'update_item' => __('Update Category', 'biz-dir'),
                'add_new_item' => __('Add New Category', 'biz-dir'),
                'new_item_name' => __('New Category Name', 'biz-dir'),
                'search_items' => __('Search Categories', 'biz-dir'),
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'business-category'],
            'show_in_rest' => true,
        ]);
        error_log('[BizDir Business] Business taxonomies registered successfully');
    }

    /**
     * Register the business listing post type
     */
    public function register_post_type() {
        error_log('[BizDir Business] Registering business post type');
        $labels = [
            'name'               => __('Businesses', 'biz-dir'),
            'singular_name'      => __('Business', 'biz-dir'),
            'menu_name'         => __('Businesses', 'biz-dir'),
            'add_new'           => __('Add New', 'biz-dir'),
            'add_new_item'      => __('Add New Business', 'biz-dir'),
            'edit_item'         => __('Edit Business', 'biz-dir'),
            'new_item'          => __('New Business', 'biz-dir'),
            'view_item'         => __('View Business', 'biz-dir'),
            'search_items'      => __('Search Businesses', 'biz-dir'),
            'not_found'         => __('No businesses found', 'biz-dir'),
            'not_found_in_trash'=> __('No businesses found in trash', 'biz-dir'),
        ];

        $args = [
            'labels'              => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'business'],
            'capability_type'    => 'business_listing',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-store',
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'show_in_rest'       => true,
            'map_meta_cap'       => true,
        ];

        register_post_type(self::POST_TYPE, $args);

        // Register additional capabilities
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('edit_business_listing');
            $role->add_cap('edit_business_listings');
            $role->add_cap('edit_others_business_listings');
            $role->add_cap('publish_business_listings');
            $role->add_cap('read_private_business_listings');
            $role->add_cap('delete_business_listings');
        }

        $contributor_role = get_role('biz_contributor');
        if ($contributor_role) {
            $contributor_role->add_cap('edit_business_listing');
            $contributor_role->add_cap('edit_business_listings');
            $contributor_role->add_cap('publish_business_listings');
        }
    }

    /**
     * Register meta boxes for business listings
     */
    public function register_meta_boxes() {
        add_meta_box(
            'business_details',
            __('Business Details', 'biz-dir'),
            [$this, 'render_business_details_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'business_location',
            __('Business Location', 'biz-dir'),
            [$this, 'render_business_location_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * Render business details meta box
     *
     * @param \WP_Post $post Current post object
     */
    public function render_business_details_meta_box($post) {
        wp_nonce_field('business_details_meta_box', 'business_details_meta_box_nonce');

        $contact_info = get_post_meta($post->ID, '_contact_info', true);
        $is_sponsored = get_post_meta($post->ID, '_is_sponsored', true);
        
        include dirname(__FILE__) . '/views/meta-box-business-details.php';
    }

    /**
     * Render business location meta box
     *
     * @param \WP_Post $post Current post object
     */
    public function render_business_location_meta_box($post) {
        wp_nonce_field('business_location_meta_box', 'business_location_meta_box_nonce');

        $town_id = get_post_meta($post->ID, '_town_id', true);
        $location = get_post_meta($post->ID, '_location', true);
        
        include dirname(__FILE__) . '/views/meta-box-business-location.php';
    }

    /**
     * Save business meta data
     *
     * @param int      $post_id Post ID
     * @param \WP_Post $post    Post object
     */
    public function save_business_meta($post_id, $post) {
        // Check if our nonce is set and verify it
        if (!isset($_POST['business_details_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['business_details_meta_box_nonce'], 'business_details_meta_box') ||
            !isset($_POST['business_location_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['business_location_meta_box_nonce'], 'business_location_meta_box')) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (!Permission_Handler::can('edit_business', get_current_user_id(), $post_id)) {
            error_log('[BizDir Business] User cannot edit business');
            return;
        }

        // Update contact info
        if (isset($_POST['contact_info'])) {
            update_post_meta($post_id, '_contact_info', sanitize_text_field($_POST['contact_info']));
        }

        // Update sponsored status
        $is_sponsored = isset($_POST['is_sponsored']) ? '1' : '0';
        update_post_meta($post_id, '_is_sponsored', $is_sponsored);

        // Update town
        if (isset($_POST['town_id'])) {
            update_post_meta($post_id, '_town_id', intval($_POST['town_id']));
        }

        // Update location
        if (isset($_POST['location'])) {
            update_post_meta($post_id, '_location', sanitize_text_field($_POST['location']));
        }
    }

    /**
     * Set custom columns for business listings
     *
     * @param array $columns Array of column names
     * @return array Modified columns
     */
    public function set_custom_columns($columns) {
        $new_columns = [];

        foreach ($columns as $key => $value) {
            if ($key === 'date') {
                $new_columns['town'] = __('Town', 'biz-dir');
                $new_columns['sponsored'] = __('Sponsored', 'biz-dir');
            }
            $new_columns[$key] = $value;
        }

        return $new_columns;
    }

    /**
     * Render custom column content
     *
     * @param string $column  Column name
     * @param int    $post_id Post ID
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'town':
                $town_id = get_post_meta($post_id, '_town_id', true);
                if ($town_id) {
                    global $wpdb;
                    $town_name = $wpdb->get_var($wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}biz_towns WHERE id = %d",
                        $town_id
                    ));
                    echo esc_html($town_name);
                }
                break;

            case 'sponsored':
                $is_sponsored = get_post_meta($post_id, '_is_sponsored', true);
                echo $is_sponsored ? '✓' : '✗';
                break;
        }
    }
}
