<?php
/**
 * Moderation Handler Class
 *
 * @package BizDir\Core\Moderation
 */

namespace BizDir\Core\Moderation;

use BizDir\Core\User\Permission_Handler;

class Moderation_Handler {
    /**
     * Queue content statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ESCALATED = 'escalated';

    /**
     * Content types
     */
    const TYPE_REVIEW = 'review';
    const TYPE_BUSINESS = 'business';
    const TYPE_TAG = 'tag';

    /**
     * @var Permission_Handler
     */
    private $permission_handler;

    /**
     * Constructor
     *
     * @param Permission_Handler $permission_handler Permission handler instance
     */
    public function __construct(Permission_Handler $permission_handler) {
        $this->permission_handler = $permission_handler;
        error_log('[BizDir Moderation] Handler constructed');
    }

    /**
     * Initialize hooks and filters
     */
    public function init() {
        error_log('[BizDir Moderation] Initializing hooks');
        add_action('wp_ajax_get_moderation_queue', [$this, 'handle_get_queue']);
        add_action('wp_ajax_moderate_content', [$this, 'handle_moderate_content']);
        add_action('transition_post_status', [$this, 'handle_post_status_transition'], 10, 3);
        add_action('init', [$this, 'register_post_statuses']);
        error_log('[BizDir Moderation] Hooks initialized');
    }

    /**
     * Register custom post statuses for moderation
     */
    public function register_post_statuses() {
        error_log('[BizDir Moderation] Registering post statuses');
        register_post_status(self::STATUS_PENDING, [
            'label' => _x('Pending Review', 'post status', 'biz-dir'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => false,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Pending Review <span class="count">(%s)</span>',
                                  'Pending Review <span class="count">(%s)</span>', 'biz-dir'),
        ]);
    }

    /**
     * Add content to moderation queue
     *
     * @param string $content_type Type of content (review, business, tag)
     * @param int    $content_id   ID of the content
     * @return bool|int Queue ID on success, false on failure
     */
    public function add_to_queue($content_type, $content_id) {
        global $wpdb;
        error_log("[BizDir Moderation] Adding to queue | type: $content_type, id: $content_id");

        $result = $wpdb->insert(
            $wpdb->prefix . 'biz_moderation_queue',
            [
                'content_type' => $content_type,
                'content_id' => $content_id,
                'status' => self::STATUS_PENDING,
            ],
            ['%s', '%d', '%s']
        );

        if ($result === false) {
            error_log('[BizDir Moderation] Failed to add to queue: ' . $wpdb->last_error);
            return false;
        }

        $queue_id = $wpdb->insert_id;
        error_log("[BizDir Moderation] Added to queue successfully | queue_id: $queue_id");
        
        do_action('biz_dir_content_queued', $queue_id, $content_type, $content_id);
        
        return $queue_id;
    }

    /**
     * Get items from moderation queue
     *
     * @param array $args Optional. Query arguments
     * @return array Array of queue items
     */
    public function get_queue($args = []) {
        global $wpdb;

        $defaults = [
            'status' => self::STATUS_PENDING,
            'content_type' => null,
            'moderator_id' => null,
            'limit' => 20,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);
        error_log('[BizDir Moderation] Getting queue | ' . json_encode($args));

        $where = ['1=1'];
        $values = [];

        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ($args['content_type']) {
            $where[] = 'content_type = %s';
            $values[] = $args['content_type'];
        }

        if ($args['moderator_id']) {
            $where[] = 'moderator_id = %d';
            $values[] = $args['moderator_id'];
        }

        $where = implode(' AND ', $where);
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_moderation_queue 
            WHERE $where 
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            array_merge($values, [$args['limit'], $args['offset']])
        );

        error_log('[BizDir Moderation] Queue query: ' . $query);
        $items = $wpdb->get_results($query);
        error_log('[BizDir Moderation] Found ' . count($items) . ' items');

        return array_map([$this, 'format_queue_item'], $items);
    }

    /**
     * Format a queue item with additional content details
     *
     * @param object $item Queue item from database
     * @return array Formatted queue item
     */
    private function format_queue_item($item) {
        $content = $this->get_content_details($item->content_type, $item->content_id);
        return [
            'id' => $item->id,
            'content_type' => $item->content_type,
            'content_id' => $item->content_id,
            'status' => $item->status,
            'moderator_id' => $item->moderator_id,
            'notes' => $item->notes,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'content' => $content,
        ];
    }

    /**
     * Get content details based on type
     *
     * @param string $type Content type
     * @param int    $id   Content ID
     * @return array|null Content details or null if not found
     */
    private function get_content_details($type, $id) {
        error_log("[BizDir Moderation] Getting content details | type: $type, id: $id");
        
        switch ($type) {
            case self::TYPE_REVIEW:
                return $this->get_review_details($id);
            case self::TYPE_BUSINESS:
                return $this->get_business_details($id);
            case self::TYPE_TAG:
                return $this->get_tag_details($id);
            default:
                error_log("[BizDir Moderation] Unknown content type: $type");
                return null;
        }
    }

    /**
     * Get review details
     *
     * @param int $id Review ID
     * @return array|null Review details or null if not found
     */
    private function get_review_details($id) {
        global $wpdb;
        
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, b.name as business_name, u.display_name as author_name 
            FROM {$wpdb->prefix}biz_reviews r
            LEFT JOIN {$wpdb->prefix}biz_businesses b ON r.business_id = b.id
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            WHERE r.id = %d",
            $id
        ));

        if (!$review) {
            error_log("[BizDir Moderation] Review not found | id: $id");
            return null;
        }

        return [
            'type' => 'review',
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'business_name' => $review->business_name,
            'author_name' => $review->author_name,
            'created_at' => $review->created_at,
        ];
    }

    /**
     * Get business details
     *
     * @param int $id Business ID
     * @return array|null Business details or null if not found
     */
    private function get_business_details($id) {
        global $wpdb;
        
        $business = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, t.name as town_name 
            FROM {$wpdb->prefix}biz_businesses b
            LEFT JOIN {$wpdb->prefix}biz_towns t ON b.town_id = t.id
            WHERE b.id = %d",
            $id
        ));

        if (!$business) {
            error_log("[BizDir Moderation] Business not found | id: $id");
            return null;
        }

        return [
            'type' => 'business',
            'id' => $business->id,
            'name' => $business->name,
            'description' => $business->description,
            'town' => $business->town_name,
            'category' => $business->category,
            'created_at' => $business->created_at,
        ];
    }

    /**
     * Get tag details
     *
     * @param int $id Tag ID
     * @return array|null Tag details or null if not found
     */
    private function get_tag_details($id) {
        global $wpdb;
        
        $tag = $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, b.name as business_name 
            FROM {$wpdb->prefix}biz_tags t
            LEFT JOIN {$wpdb->prefix}biz_businesses b ON t.business_id = b.id
            WHERE t.id = %d",
            $id
        ));

        if (!$tag) {
            error_log("[BizDir Moderation] Tag not found | id: $id");
            return null;
        }

        return [
            'type' => 'tag',
            'id' => $tag->id,
            'tag' => $tag->tag,
            'weight' => $tag->weight,
            'business_name' => $tag->business_name,
            'created_at' => $tag->created_at,
        ];
    }

    /**
     * Moderate a queued item
     *
     * @param int    $queue_id Queue item ID
     * @param string $action   Action to take (approve/reject/escalate)
     * @param string $notes    Optional. Moderator notes
     * @return bool True on success, false on failure
     */
    public function moderate_item($queue_id, $action, $notes = '') {
        if (!Permission_Handler::can('moderate_content')) {
            error_log('[BizDir Moderation] Permission denied for moderation');
            return false;
        }

        if (!in_array($action, ['approve', 'reject', 'escalate'], true)) {
            error_log("[BizDir Moderation] Invalid action: $action");
            return false;
        }

        global $wpdb;
        error_log("[BizDir Moderation] Moderating item | queue_id: $queue_id, action: $action");

        // Get queue item
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}biz_moderation_queue WHERE id = %d",
            $queue_id
        ));

        if (!$item) {
            error_log("[BizDir Moderation] Queue item not found | id: $queue_id");
            return false;
        }

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Update queue item
            $update_result = $wpdb->update(
                $wpdb->prefix . 'biz_moderation_queue',
                [
                    'status' => $this->get_status_for_action($action),
                    'moderator_id' => get_current_user_id(),
                    'notes' => $notes,
                    'updated_at' => current_time('mysql'),
                ],
                ['id' => $queue_id],
                ['%s', '%d', '%s', '%s'],
                ['%d']
            );

            if ($update_result === false) {
                throw new \Exception('Failed to update queue item: ' . $wpdb->last_error);
            }

            // Update content status
            $content_update = $this->update_content_status($item->content_type, $item->content_id, $action);
            if (!$content_update) {
                throw new \Exception('Failed to update content status');
            }

            $wpdb->query('COMMIT');
            error_log("[BizDir Moderation] Successfully moderated item | queue_id: $queue_id");
            
            do_action('biz_dir_content_moderated', $queue_id, $action, $item);
            
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('[BizDir Moderation] Moderation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get status string for moderation action
     *
     * @param string $action Action name
     * @return string Status
     */
    private function get_status_for_action($action) {
        switch ($action) {
            case 'approve':
                return self::STATUS_APPROVED;
            case 'reject':
                return self::STATUS_REJECTED;
            case 'escalate':
                return self::STATUS_ESCALATED;
            default:
                return self::STATUS_PENDING;
        }
    }

    /**
     * Update content status based on moderation action
     *
     * @param string $type   Content type
     * @param int    $id     Content ID
     * @param string $action Moderation action
     * @return bool Success status
     */
    private function update_content_status($type, $id, $action) {
        error_log("[BizDir Moderation] Updating content status | type: $type, id: $id, action: $action");
        
        switch ($type) {
            case self::TYPE_REVIEW:
                return $this->update_review_status($id, $action);
            case self::TYPE_BUSINESS:
                return $this->update_business_status($id, $action);
            case self::TYPE_TAG:
                return $this->update_tag_status($id, $action);
            default:
                error_log("[BizDir Moderation] Unknown content type: $type");
                return false;
        }
    }

    /**
     * Update review status
     *
     * @param int    $id     Review ID
     * @param string $action Moderation action
     * @return bool Success status
     */
    private function update_review_status($id, $action) {
        global $wpdb;
        
        switch ($action) {
            case 'approve':
                $status = 'published';
                break;
            case 'reject':
                $status = 'rejected';
                break;
            case 'escalate':
                $status = 'pending';
                break;
            default:
                error_log("[BizDir Moderation] Invalid action for review status: $action");
                return false;
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'biz_reviews',
            ['status' => $status],
            ['id' => $id],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            error_log('[BizDir Moderation] Failed to update review status: ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Update business status
     *
     * @param int    $id     Business ID
     * @param string $action Moderation action
     * @return bool Success status
     */
    private function update_business_status($id, $action) {
        $post_status = $action === 'approve' ? 'publish' : 'draft';
        
        $update = wp_update_post([
            'ID' => $id,
            'post_status' => $post_status,
        ]);

        return !is_wp_error($update);
    }

    /**
     * Update tag status
     *
     * @param int    $id     Tag ID
     * @param string $action Moderation action
     * @return bool Success status
     */
    private function update_tag_status($id, $action) {
        global $wpdb;
        
        if ($action === 'reject') {
            $result = $wpdb->delete(
                $wpdb->prefix . 'biz_tags',
                ['id' => $id],
                ['%d']
            );
        } else {
            $result = true; // Tags are approved by default
        }

        if ($result === false) {
            error_log('[BizDir Moderation] Failed to update tag status: ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Handle post status transitions
     *
     * @param string  $new_status New status
     * @param string  $old_status Old status
     * @param WP_Post $post       Post object
     */
    public function handle_post_status_transition($new_status, $old_status, $post) {
        if ($post->post_type === 'business_listing' && $old_status === 'new') {
            error_log("[BizDir Moderation] New business posted | id: {$post->ID}");
            $this->add_to_queue(self::TYPE_BUSINESS, $post->ID);
        }
    }

    /**
     * AJAX handler for getting moderation queue
     */
    public function handle_get_queue() {
        if (!Permission_Handler::can('moderate_content')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $args = [
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : self::STATUS_PENDING,
            'content_type' => isset($_GET['type']) ? sanitize_text_field($_GET['type']) : null,
            'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 20,
            'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0,
        ];

        $items = $this->get_queue($args);
        wp_send_json_success($items);
    }

    /**
     * AJAX handler for moderating content
     */
    public function handle_moderate_content() {
        if (!check_ajax_referer('moderate_content', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        if (!Permission_Handler::can('moderate_content')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $queue_id = isset($_POST['queue_id']) ? intval($_POST['queue_id']) : 0;
        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (!$queue_id || !in_array($action, ['approve', 'reject', 'escalate'])) {
            wp_send_json_error(['message' => 'Invalid parameters']);
            return;
        }

        $result = $this->moderate_item($queue_id, $action, $notes);
        
        if ($result) {
            wp_send_json_success(['message' => 'Content moderated successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to moderate content']);
        }
    }
}
