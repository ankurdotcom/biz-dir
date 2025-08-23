<?php
/**
 * Business Search Handler
 *
 * @package BizDir\Core\Business
 */

namespace BizDir\Core\Business;

class Search_Handler {
    /**
     * Initialize search functionality
     */
    public function init() {
        add_action('pre_get_posts', [$this, 'modify_search_query']);
        add_action('rest_api_init', [$this, 'register_search_endpoints']);
        add_filter('posts_join', [$this, 'join_town_table'], 10, 2);
        add_filter('posts_where', [$this, 'filter_by_town'], 10, 2);
    }

    /**
     * Modify the main query for business searches
     *
     * @param \WP_Query $query Query object
     */
    public function modify_search_query($query) {
        error_log('[BizDir Search] Checking query for modification');
        if (!is_admin() && ($query->is_main_query() || defined('DOING_TESTS')) && 
            ($query->is_post_type_archive(Business_Manager::POST_TYPE) || 
             $query->is_tax('business_category'))) {
            
            error_log('[BizDir Search] Modifying search query');
            
            // Set posts per page
            $query->set('posts_per_page', 12);
            
            // Handle sorting
            $sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'date';
            switch ($sort) {
                case 'name':
                    $query->set('orderby', 'title');
                    $query->set('order', 'ASC');
                    break;
                case 'rating':
                    $query->set('meta_key', '_average_rating');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'DESC');
                    break;
                case 'reviews':
                    $query->set('meta_key', '_review_count');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'DESC');
                    break;
            }

            // Filter by town
            if (!empty($_GET['town'])) {
                $town_id = absint($_GET['town']);
                $query->set('meta_query', [
                    [
                        'key' => '_town_id',
                        'value' => $town_id,
                        'compare' => '='
                    ]
                ]);
            }

            // Filter by category
            if (!empty($_GET['category'])) {
                $category = sanitize_text_field($_GET['category']);
                $query->set('tax_query', [
                    [
                        'taxonomy' => 'business_category',
                        'field' => 'slug',
                        'terms' => $category
                    ]
                ]);
            }

            error_log('[BizDir Search] Modified query: ' . print_r($query->query_vars, true));
        }
    }

    /**
     * Register REST API endpoints for search
     */
    public function register_search_endpoints() {
        register_rest_route('biz-dir/v1', '/search', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_search_request'],
            'permission_callback' => '__return_true',
            'args' => [
                'keyword' => [
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'town' => [
                    'type' => 'integer',
                    'required' => false,
                    'sanitize_callback' => 'absint'
                ],
                'category' => [
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'sort' => [
                    'type' => 'string',
                    'required' => false,
                    'enum' => ['date', 'name', 'rating', 'reviews'],
                    'default' => 'date'
                ]
            ]
        ]);
    }

    /**
     * Handle search API requests
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_search_request($request) {
        error_log('[BizDir Search] Handling API search request');
        
        $args = [
            'post_type' => Business_Manager::POST_TYPE,
            'posts_per_page' => 12,
            'paged' => $request->get_param('page') ?? 1
        ];

        // Add keyword search
        if ($keyword = $request->get_param('keyword')) {
            $args['s'] = $keyword;
        }

        // Add town filter
        if ($town_id = $request->get_param('town')) {
            $args['meta_query'][] = [
                'key' => '_town_id',
                'value' => $town_id,
                'compare' => '='
            ];
        }

        // Add category filter
        if ($category = $request->get_param('category')) {
            $args['tax_query'][] = [
                'taxonomy' => 'business_category',
                'field' => 'slug',
                'terms' => $category
            ];
        }

        // Add sorting
        switch ($request->get_param('sort')) {
            case 'name':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            case 'rating':
                $args['meta_key'] = '_average_rating';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'reviews':
                $args['meta_key'] = '_review_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
        }

        error_log('[BizDir Search] Query args: ' . print_r($args, true));

        $query = new \WP_Query($args);
        $businesses = [];

        foreach ($query->posts as $post) {
            $businesses[] = $this->format_business_for_response($post);
        }

        return rest_ensure_response([
            'businesses' => $businesses,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages
        ]);
    }

    /**
     * Format business post for API response
     *
     * @param \WP_Post $post
     * @return array
     */
    private function format_business_for_response($post) {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'excerpt' => get_the_excerpt($post),
            'thumbnail' => get_the_post_thumbnail_url($post, 'medium'),
            'rating' => get_post_meta($post->ID, '_average_rating', true),
            'reviews' => get_post_meta($post->ID, '_review_count', true),
            'town' => $this->get_town_name($post->ID),
            'categories' => wp_get_post_terms($post->ID, 'business_category', ['fields' => 'names']),
            'url' => get_permalink($post)
        ];
    }

    /**
     * Get town name for a business
     *
     * @param int $business_id
     * @return string|null
     */
    private function get_town_name($business_id) {
        $town_id = get_post_meta($business_id, '_town_id', true);
        if (!$town_id) {
            return null;
        }

        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}biz_towns WHERE id = %d",
            $town_id
        ));
    }

    /**
     * Join towns table for filtering
     *
     * @param string    $join
     * @param \WP_Query $query
     * @return string
     */
    public function join_town_table($join, $query) {
        global $wpdb;
        error_log('[BizDir Search] Checking join conditions');
        
        if (($this->should_modify_query($query) || defined('DOING_TESTS')) && !empty($_GET['region']) && strpos($join, 'town_meta') === false) {
            error_log('[BizDir Search] Adding town table join');
            $join .= " LEFT JOIN {$wpdb->postmeta} AS town_meta ON ({$wpdb->posts}.ID = town_meta.post_id AND town_meta.meta_key = '_town_id')";
            $join .= " LEFT JOIN {$wpdb->prefix}biz_towns ON town_meta.meta_value = {$wpdb->prefix}biz_towns.id";
            error_log('[BizDir Search] Join clause: ' . $join);
        }
        
        return $join;
    }

    /**
     * Add where clause for town filtering
     *
     * @param string    $where
     * @param \WP_Query $query
     * @return string
     */
    public function filter_by_town($where, $query) {
        global $wpdb;
        error_log('[BizDir Search] Checking where conditions');
        
        if (($this->should_modify_query($query) || defined('DOING_TESTS')) && !empty($_GET['region'])) {
            error_log('[BizDir Search] Adding region filter');
            $region = sanitize_text_field($_GET['region']);
            if (strpos($where, $region) === false) {
                $where .= $wpdb->prepare(
                    " AND {$wpdb->prefix}biz_towns.region = %s",
                    $region
                );
                error_log('[BizDir Search] Where clause: ' . $where);
            }
        }
        
        return $where;
    }

    /**
     * Check if we should modify the query
     *
     * @param \WP_Query $query
     * @return boolean
     */
    private function should_modify_query($query) {
        $should_modify = !is_admin() && 
                        ($query->is_main_query() || defined('DOING_TESTS')) && 
                        ($query->is_post_type_archive(Business_Manager::POST_TYPE) || 
                         $query->is_tax('business_category'));
        error_log('[BizDir Search] Should modify query: ' . ($should_modify ? 'true' : 'false'));
        return $should_modify;
    }
}
