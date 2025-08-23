<?php
/**
 * Business Review Handler
 *
 * @package BizDir\Core\Business
 */

namespace BizDir\Core\Business;

use BizDir\Core\User\Permission_Handler;
use BizDir\Core\Utility\Logger;

class Review_Handler {
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
    }

    /**
     * Initialize review functionality
     */
    public function init() {
        add_action('init', [$this, 'register_review_post_type']);
        add_action('rest_api_init', [$this, 'register_review_endpoints']);
        add_action('save_post_business_review', [$this, 'update_business_rating'], 10, 3);
        add_action('before_delete_post', [$this, 'prepare_review_deletion']);
        add_action('deleted_post', [$this, 'handle_post_deletion_complete']);
    }

    /**
     * Register review post type
     */
    public function register_review_post_type() {
        register_post_type('business_review', [
            'labels' => [
                'name' => __('Reviews', 'biz-dir'),
                'singular_name' => __('Review', 'biz-dir')
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=' . Business_Manager::POST_TYPE,
            'supports' => ['title', 'editor', 'author'],
            'capabilities' => [
                'create_posts' => 'create_business_reviews',
                'edit_post' => 'edit_business_review',
                'edit_posts' => 'edit_business_reviews',
                'edit_others_posts' => 'edit_others_business_reviews',
                'delete_post' => 'delete_business_review',
                'delete_posts' => 'delete_business_reviews',
                'delete_others_posts' => 'delete_others_business_reviews',
                'read_post' => 'read_business_review',
                'read_private_posts' => 'read_private_business_reviews',
                'publish_posts' => 'publish_business_reviews'
            ]
        ]);
    }

    /**
     * Register REST API endpoints for reviews
     */
    public function register_review_endpoints() {
        register_rest_route('biz-dir/v1', '/businesses/(?P<id>\d+)/reviews', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_business_reviews'],
                'permission_callback' => '__return_true',
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ]
                ]
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_review'],
                'permission_callback' => [$this, 'can_create_review'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ],
                    'rating' => [
                        'required' => true,
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 5
                    ],
                    'content' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'wp_kses_post'
                    ]
                ]
            ]
        ]);
    }

    /**
     * Check if user can create review
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function can_create_review($request) {
        $user_id = get_current_user_id();
        Logger::debug('Checking review creation permission', [
            'user_id' => $user_id,
            'is_logged_in' => is_user_logged_in()
        ]);

        if (!is_user_logged_in()) {
            Logger::debug('Review creation denied - user not logged in');
            return new \WP_Error(
                'rest_forbidden',
                __('You must be logged in to write reviews.', 'biz-dir'),
                ['status' => 401]
            );
        }

        $business_id = $request->get_param('id');
        
        // Check if user has already reviewed this business
        $existing_review = get_posts([
            'post_type' => 'business_review',
            'author' => get_current_user_id(),
            'meta_query' => [
                [
                    'key' => '_business_id',
                    'value' => $business_id
                ]
            ],
            'posts_per_page' => 1
        ]);

        if (!empty($existing_review)) {
            Logger::debug('Review creation denied - duplicate review', [
                'user_id' => $user_id,
                'business_id' => $business_id,
                'existing_review_id' => $existing_review[0]->ID
            ]);
            return new \WP_Error(
                'rest_forbidden',
                __('You have already reviewed this business.', 'biz-dir'),
                ['status' => 403]
            );
        }

        $can_create = $this->permission_handler->can('create_business_reviews', $user_id, null);
        Logger::debug('Review creation permission check', [
            'user_id' => $user_id,
            'business_id' => $business_id,
            'has_permission' => $can_create
        ]);
        
        return $can_create;
    }

    /**
     * Get reviews for a business
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_business_reviews($request) {
        $business_id = $request->get_param('id');
        $page = $request->get_param('page') ?? 1;
        
        $reviews = get_posts([
            'post_type' => 'business_review',
            'posts_per_page' => 10,
            'paged' => $page,
            'meta_query' => [
                [
                    'key' => '_business_id',
                    'value' => $business_id
                ]
            ]
        ]);

        $data = [];
        foreach ($reviews as $review) {
            $data[] = $this->format_review_for_response($review);
        }

        return rest_ensure_response($data);
    }

    /**
     * Create a new review
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_review($request) {
        $business_id = $request->get_param('id');
        $rating = $request->get_param('rating');
        $content = $request->get_param('content');

        Logger::debug('Creating new review', [
            'business_id' => $business_id,
            'rating' => $rating,
            'user_id' => get_current_user_id()
        ]);

        $review_data = [
            'post_type' => 'business_review',
            'post_title' => sprintf(
                __('Review for %s', 'biz-dir'),
                get_the_title($business_id)
            ),
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ];

        $review_id = wp_insert_post($review_data);
        if (is_wp_error($review_id)) {
            return $review_id;
        }

        update_post_meta($review_id, '_business_id', $business_id);
        update_post_meta($review_id, '_rating', $rating);

        Logger::debug('Created review successfully', [
            'review_id' => $review_id,
            'business_id' => $business_id,
            'rating' => $rating
        ]);

        $review = get_post($review_id);
        return rest_ensure_response($this->format_review_for_response($review));
    }

    /**
     * Format review for API response
     *
     * @param \WP_Post $review
     * @return array
     */
    private function format_review_for_response($review) {
        if (!$review instanceof \WP_Post) {
            return null;
        }
        
        $author = get_user_by('id', $review->post_author);
        if (!$author) {
            $author = new \stdClass();
            $author->ID = 0;
            $author->display_name = __('Anonymous', 'biz-dir');
        }
        
        return [
            'id' => $review->ID,
            'content' => $review->post_content,
            'rating' => get_post_meta($review->ID, '_rating', true),
            'date' => mysql_to_rfc3339($review->post_date),
            'author' => [
                'id' => $author->ID,
                'name' => $author->display_name,
                'avatar' => get_avatar_url($author->ID)
            ]
        ];
    }

    /**
     * Update business rating when a review is saved
     *
     * @param int      $post_id
     * @param \WP_Post $post
     * @param bool     $update
     */
    public function update_business_rating($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $business_id = get_post_meta($post_id, '_business_id', true);
        if (!$business_id) {
            return;
        }

        $this->recalculate_business_rating($business_id);
    }

    /**
     * Prepare for review deletion by storing necessary data
     *
     * @param int $post_id
     */
    public function prepare_review_deletion($post_id) {
        static $processed = [];
        
        // Skip if we've already processed this post
        if (isset($processed[$post_id])) {
            return;
        }
        
        global $wpdb;
        
        // Use a direct query to check post type since get_post might be cached
        $post_type = $wpdb->get_var($wpdb->prepare(
            "SELECT post_type FROM {$wpdb->posts} WHERE ID = %d",
            $post_id
        ));

        if ($post_type !== 'business_review') {
            Logger::debug('Skipping non-review post deletion', [
                'post_id' => $post_id,
                'post_type' => $post_type
            ]);
            return;
        }

        // Get business ID directly from database to avoid cache issues
        $business_id = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} 
             WHERE post_id = %d AND meta_key = '_business_id'",
            $post_id
        ));

        if (!$business_id) {
            Logger::debug('Review deletion: No business ID found', [
                'review_id' => $post_id
            ]);
            return;
        }

        // Store business ID for later use
        set_transient('bizdir_review_deletion_' . $post_id, $business_id, 60);

        Logger::debug('Preparing review deletion', [
            'review_id' => $post_id,
            'business_id' => $business_id,
            'old_rating' => get_post_meta($post_id, '_rating', true)
        ]);
        
        // Mark as processed
        $processed[$post_id] = true;
    }

    /**
     * Recalculate business rating
     *
     * @param int $business_id
     */
    /**
     * Handle completion of post deletion
     *
     * @param int $post_id The ID of the deleted post
     */
    public function handle_post_deletion_complete($post_id) {
        static $processed = [];
        
        // Skip if we've already processed this post
        if (isset($processed[$post_id])) {
            return;
        }
        
        // Get the business ID we stored during prepare_review_deletion
        $business_id = get_transient('bizdir_review_deletion_' . $post_id);
        
        if (!$business_id) {
            return; // Not a review we need to handle
        }

        // Mark as processed before recalculation to prevent recursion
        $processed[$post_id] = true;

        Logger::debug('Processing review deletion completion', [
            'review_id' => $post_id,
            'business_id' => $business_id
        ]);

        // Store business ID in transient for recalculation
        set_transient('bizdir_recalc_business_' . $post_id, $business_id, 60);
        
        // Use a reusable named method for recalculation
        if (!has_action('shutdown', [$this, 'process_pending_recalculations'])) {
            add_action('shutdown', [$this, 'process_pending_recalculations'], 999);
        }
    }

    /**
     * Recalculate a business's average rating and review count
     * 
     * @param int $business_id
     */
    /**
     * Process any pending rating recalculations
     */
    public function process_pending_recalculations() {
        global $wpdb;
        
        // Get all pending recalculations
        $pending = $wpdb->get_results(
            "SELECT option_name, option_value 
             FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_bizdir_recalc_business_%'
             FOR UPDATE"
        );
        
        foreach ($pending as $row) {
            $business_id = get_transient(str_replace('_transient_', '', $row->option_name));
            if ($business_id) {
                $this->recalculate_business_rating($business_id);
                delete_transient(str_replace('_transient_', '', $row->option_name));
            }
        }
    }

    /**
     * Recalculate a business's average rating and review count
     * 
     * @param int $business_id
     */
    private function recalculate_business_rating($business_id) {
        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Ensure we have fresh data by querying directly and locking rows
            $wpdb->query('SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE');

            // Get all valid review IDs for this business (excluding deleted ones)
            $post_ids = $wpdb->get_col($wpdb->prepare("
                SELECT p.ID 
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'business_review'
                AND p.post_status = 'publish'
                AND pm.meta_key = '_business_id'
                AND pm.meta_value = %d
                FOR UPDATE
            ", $business_id));

            // Then get ratings only for valid posts
            if (!empty($post_ids)) {
                $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
                $query = $wpdb->prepare("
                    SELECT pm_rating.meta_value as rating
                    FROM {$wpdb->postmeta} pm_rating
                    WHERE pm_rating.post_id IN ($placeholders)
                    AND pm_rating.meta_key = '_rating'
                ", $post_ids);
                $ratings = $wpdb->get_results($query);
            } else {
                $ratings = [];
            }

        $total = 0.0;
        $count = count($ratings);

        foreach ($ratings as $rating) {
            // Ensure we're working with floats for precision
            $rating_value = (float) $rating->rating;
            // Round each rating to 2 decimal places to avoid floating point issues
            $rating_value = round($rating_value, 2);
            $total += $rating_value;
        }

        // Calculate average, ensuring float precision
        $average = $count > 0 ? (float) number_format($total / $count, 2, '.', '') : 0.0;

        // Update metadata within transaction
        update_post_meta($business_id, '_average_rating', $average);
        update_post_meta($business_id, '_review_count', $count);

        // Commit transaction
        $wpdb->query('COMMIT');

        Logger::debug('Updated business rating', [
            'business_id' => $business_id,
            'average_rating' => $average,
            'review_count' => $count,
            'total_rating' => $total
        ]);
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            Logger::error('Failed to update business rating', [
                'business_id' => $business_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
