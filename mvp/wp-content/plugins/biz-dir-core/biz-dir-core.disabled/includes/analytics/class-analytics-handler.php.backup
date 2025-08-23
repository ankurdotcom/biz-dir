<?php
/**
 * Analytics Handler Class
 *
 * @package BizDir\Core\Analytics
 */

namespace BizDir\Core\Analytics;

use BizDir\Core\User\Permission_Handler;

class Analytics_Handler {
    const METRIC_VIEWS = 'views';
    const METRIC_SEARCHES = 'searches';
    const METRIC_REVIEWS = 'reviews';
    const METRIC_INQUIRIES = 'inquiries';
    const METRIC_CLICKS = 'clicks';

    const PERIOD_DAY = 'day';
    const PERIOD_WEEK = 'week';
    const PERIOD_MONTH = 'month';
    const PERIOD_YEAR = 'year';

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
    }

    /**
     * Initialize hooks and filters
     */
    public function init() {
        error_log('[BizDir Analytics] Initializing hooks');
        add_action('wp_ajax_get_analytics', [$this, 'handle_get_analytics']);
        add_action('template_redirect', [$this, 'track_page_view']);
        add_action('biz_dir_search_performed', [$this, 'track_search'], 10, 2);
        add_action('biz_dir_review_submitted', [$this, 'track_review'], 10, 2);
        add_action('biz_dir_inquiry_sent', [$this, 'track_inquiry'], 10, 2);
        add_action('wp_ajax_track_click', [$this, 'handle_track_click']);
        add_action('wp_ajax_nopriv_track_click', [$this, 'handle_track_click']);
        error_log('[BizDir Analytics] Hooks initialized');
    }

    /**
     * Track page view
     */
    public function track_page_view() {
        if (!is_singular('business_listing')) {
            return;
        }

        $business_id = get_the_ID();
        $this->track_metric(self::METRIC_VIEWS, $business_id);
        error_log("[BizDir Analytics] Tracked view for business: $business_id");
    }

    /**
     * Track search
     *
     * @param array  $params Search parameters
     * @param int    $results_count Number of results
     */
    public function track_search($params, $results_count) {
        global $wpdb;
        error_log('[BizDir Analytics] Tracking search | ' . json_encode($params));

        $wpdb->insert(
            $wpdb->prefix . 'biz_analytics_searches',
            [
                'query' => isset($params['q']) ? $params['q'] : '',
                'filters' => json_encode($params),
                'results' => $results_count,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%s']
        );
    }

    /**
     * Track metric
     *
     * @param string $metric Metric type
     * @param int    $business_id Business ID
     * @param array  $meta Additional metadata
     */
    private function track_metric($metric, $business_id, $meta = []) {
        global $wpdb;
        error_log("[BizDir Analytics] Tracking metric | type: $metric, business: $business_id");

        $wpdb->insert(
            $wpdb->prefix . 'biz_analytics_metrics',
            [
                'metric' => $metric,
                'business_id' => $business_id,
                'meta' => $meta ? json_encode($meta) : null,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%d', '%s', '%s']
        );
    }

    /**
     * Get analytics data
     *
     * @param string $metric Metric type
     * @param string $period Time period
     * @param int    $business_id Optional. Business ID
     * @return array Analytics data
     */
    public function get_analytics($metric, $period, $business_id = null) {
        global $wpdb;
        error_log("[BizDir Analytics] Getting analytics | metric: $metric, period: $period, business: $business_id");

        $where = ['metric = %s'];
        $values = [$metric];

        if ($business_id) {
            $where[] = 'business_id = %d';
            $values[] = $business_id;
        }

        $date_clause = $this->get_period_clause($period);
        if ($date_clause) {
            $where[] = $date_clause;
        }

        $where = implode(' AND ', $where);
        $query = $wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                GROUP_CONCAT(meta) as meta_data
            FROM {$wpdb->prefix}biz_analytics_metrics 
            WHERE $where 
            GROUP BY DATE(created_at)
            ORDER BY date DESC",
            $values
        );

        return $wpdb->get_results($query);
    }

    /**
     * Get period SQL clause
     *
     * @param string $period Time period
     * @return string SQL clause
     */
    private function get_period_clause($period) {
        switch ($period) {
            case self::PERIOD_DAY:
                return 'DATE(created_at) = CURDATE()';
            case self::PERIOD_WEEK:
                return 'created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
            case self::PERIOD_MONTH:
                return 'created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
            case self::PERIOD_YEAR:
                return 'created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
            default:
                return '';
        }
    }

    /**
     * Get search analytics
     *
     * @param string $period Time period
     * @return array Search analytics data
     */
    public function get_search_analytics($period) {
        global $wpdb;
        error_log("[BizDir Analytics] Getting search analytics | period: $period");

        $date_clause = $this->get_period_clause($period);
        $where = $date_clause ? "WHERE $date_clause" : '';

        return $wpdb->get_results(
            "SELECT 
                query,
                COUNT(*) as count,
                AVG(results) as avg_results,
                GROUP_CONCAT(DISTINCT filters) as filters
            FROM {$wpdb->prefix}biz_analytics_searches
            $where
            GROUP BY query
            ORDER BY count DESC
            LIMIT 100"
        );
    }

    /**
     * AJAX handler for getting analytics
     */
    public function handle_get_analytics() {
        if (!Permission_Handler::can('view_analytics')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $metric = isset($_GET['metric']) ? sanitize_text_field($_GET['metric']) : self::METRIC_VIEWS;
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : self::PERIOD_MONTH;
        $business_id = isset($_GET['business_id']) ? intval($_GET['business_id']) : null;

        $data = $this->get_analytics($metric, $period, $business_id);
        wp_send_json_success($data);
    }

    /**
     * AJAX handler for tracking clicks
     */
    public function handle_track_click() {
        check_ajax_referer('track_click', 'nonce');

        $business_id = isset($_POST['business_id']) ? intval($_POST['business_id']) : 0;
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

        if (!$business_id) {
            wp_send_json_error(['message' => 'Invalid business ID']);
            return;
        }

        $this->track_metric(self::METRIC_CLICKS, $business_id, ['type' => $type]);
        wp_send_json_success();
    }
}
