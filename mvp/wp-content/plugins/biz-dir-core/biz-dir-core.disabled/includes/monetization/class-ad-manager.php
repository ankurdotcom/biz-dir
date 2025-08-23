<?php
/**
 * Ad Manager Class
 * 
 * Handles ad slot management and placement for monetization
 */

namespace BizDir\Core\Monetization;

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Manager {
    private $db;
    private $ad_slots = [];
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        add_action('init', [$this, 'init']);
        add_action('wp_ajax_biz_dir_create_ad_placement', [$this, 'create_ad_placement_ajax']);
        add_action('wp_ajax_biz_dir_track_ad_click', [$this, 'track_ad_click']);
        add_action('wp_ajax_nopriv_biz_dir_track_ad_click', [$this, 'track_ad_click']);
        
        // Register ad display hooks
        add_action('wp_head', [$this, 'inject_ad_css']);
        add_action('wp_footer', [$this, 'inject_ad_tracking_js']);
    }
    
    public function init() {
        $this->load_ad_slots();
        $this->register_ad_display_hooks();
    }
    
    /**
     * Load available ad slots from database
     */
    private function load_ad_slots() {
        $slots = $this->db->get_results(
            "SELECT * FROM {$this->db->prefix}biz_ad_slots WHERE is_active = 1",
            ARRAY_A
        );
        
        foreach ($slots as $slot) {
            $this->ad_slots[$slot['slot_name']] = $slot;
        }
    }
    
    /**
     * Register hooks for ad display
     */
    private function register_ad_display_hooks() {
        // Header banner
        add_action('wp_head', [$this, 'display_header_ad'], 20);
        
        // Sidebar ads
        add_action('dynamic_sidebar_before', [$this, 'display_sidebar_top_ad']);
        add_action('dynamic_sidebar_after', [$this, 'display_sidebar_bottom_ad']);
        
        // Footer banner
        add_action('wp_footer', [$this, 'display_footer_ad'], 10);
        
        // Content ads (for business listing pages)
        add_filter('the_content', [$this, 'inject_content_ads']);
    }
    
    /**
     * Create a new ad placement
     */
    public function create_ad_placement($data) {
        // Validate required fields
        if (!isset($data['ad_slot_id']) || !isset($data['business_id']) || !isset($data['ad_content'])) {
            return new \WP_Error('missing_fields', 'Required fields missing');
        }
        
        // Validate slot availability
        if (!$this->is_slot_available($data['ad_slot_id'], $data['start_date'], $data['end_date'])) {
            return new \WP_Error('slot_unavailable', 'Ad slot not available for requested dates');
        }
        
        // Calculate cost
        $slot = $this->get_ad_slot($data['ad_slot_id']);
        $duration_months = $this->calculate_duration_months($data['start_date'], $data['end_date']);
        $total_cost = $slot['price_per_month'] * $duration_months;
        
        // Create placement record
        $placement_data = [
            'ad_slot_id' => $data['ad_slot_id'],
            'business_id' => $data['business_id'],
            'user_id' => get_current_user_id(),
            'ad_content' => wp_json_encode($data['ad_content']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => 'pending_payment',
            'total_cost' => $total_cost,
            'created_at' => current_time('mysql')
        ];
        
        $result = $this->db->insert(
            $this->db->prefix . 'biz_ad_placements',
            $placement_data,
            ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%s']
        );
        
        if (!$result) {
            return new \WP_Error('creation_failed', 'Failed to create ad placement');
        }
        
        return [
            'placement_id' => $this->db->insert_id,
            'total_cost' => $total_cost,
            'duration_months' => $duration_months
        ];
    }
    
    /**
     * AJAX handler for creating ad placement
     */
    public function create_ad_placement_ajax() {
        check_ajax_referer('biz_dir_ad_nonce', 'nonce');
        
        $data = [
            'ad_slot_id' => intval($_POST['ad_slot_id']),
            'business_id' => intval($_POST['business_id']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => sanitize_text_field($_POST['end_date']),
            'ad_content' => [
                'title' => sanitize_text_field($_POST['ad_title']),
                'description' => sanitize_textarea_field($_POST['ad_description']),
                'image_url' => esc_url_raw($_POST['ad_image_url']),
                'link_url' => esc_url_raw($_POST['ad_link_url']),
                'cta_text' => sanitize_text_field($_POST['ad_cta_text'])
            ]
        ];
        
        $result = $this->create_ad_placement($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Display header ad
     */
    public function display_header_ad() {
        if (!$this->should_display_ads()) {
            return;
        }
        
        $ad = $this->get_active_ad('header_banner');
        if ($ad) {
            echo $this->render_ad($ad, 'header');
        }
    }
    
    /**
     * Display sidebar top ad
     */
    public function display_sidebar_top_ad() {
        if (!$this->should_display_ads()) {
            return;
        }
        
        $ad = $this->get_active_ad('sidebar_top');
        if ($ad) {
            echo $this->render_ad($ad, 'sidebar-top');
        }
    }
    
    /**
     * Display sidebar bottom ad
     */
    public function display_sidebar_bottom_ad() {
        if (!$this->should_display_ads()) {
            return;
        }
        
        $ad = $this->get_active_ad('sidebar_bottom');
        if ($ad) {
            echo $this->render_ad($ad, 'sidebar-bottom');
        }
    }
    
    /**
     * Display footer ad
     */
    public function display_footer_ad() {
        if (!$this->should_display_ads()) {
            return;
        }
        
        $ad = $this->get_active_ad('footer_banner');
        if ($ad) {
            echo $this->render_ad($ad, 'footer');
        }
    }
    
    /**
     * Inject ads into content
     */
    public function inject_content_ads($content) {
        if (!is_single() || !$this->should_display_ads()) {
            return $content;
        }
        
        // Add ad after first paragraph for business listings
        if (get_post_type() === 'business_listing') {
            $ad = $this->get_active_ad('content_inline');
            if ($ad) {
                $ad_html = $this->render_ad($ad, 'content-inline');
                $content = $this->insert_after_paragraph($content, 1, $ad_html);
            }
        }
        
        return $content;
    }
    
    /**
     * Get active ad for a specific slot
     */
    private function get_active_ad($slot_name) {
        $current_date = current_time('mysql');
        
        $ad = $this->db->get_row($this->db->prepare(
            "SELECT p.*, s.position, s.dimensions 
             FROM {$this->db->prefix}biz_ad_placements p 
             JOIN {$this->db->prefix}biz_ad_slots s ON p.ad_slot_id = s.id 
             WHERE s.slot_name = %s 
             AND p.status = 'active' 
             AND p.start_date <= %s 
             AND p.end_date >= %s 
             ORDER BY p.created_at ASC 
             LIMIT 1",
            $slot_name,
            $current_date,
            $current_date
        ), ARRAY_A);
        
        if ($ad) {
            // Track impression
            $this->track_ad_impression($ad['id']);
            
            // Decode ad content
            $ad['ad_content'] = json_decode($ad['ad_content'], true);
        }
        
        return $ad;
    }
    
    /**
     * Render ad HTML
     */
    private function render_ad($ad, $position) {
        $ad_content = $ad['ad_content'];
        $placement_id = $ad['id'];
        
        $html = '<div class="biz-dir-ad biz-dir-ad-' . esc_attr($position) . '" data-placement-id="' . esc_attr($placement_id) . '">';
        $html .= '<div class="ad-content">';
        
        if (!empty($ad_content['image_url'])) {
            $html .= '<img src="' . esc_url($ad_content['image_url']) . '" alt="' . esc_attr($ad_content['title']) . '" class="ad-image">';
        }
        
        $html .= '<div class="ad-text">';
        $html .= '<h4 class="ad-title">' . esc_html($ad_content['title']) . '</h4>';
        
        if (!empty($ad_content['description'])) {
            $html .= '<p class="ad-description">' . esc_html($ad_content['description']) . '</p>';
        }
        
        if (!empty($ad_content['link_url']) && !empty($ad_content['cta_text'])) {
            $html .= '<a href="' . esc_url($ad_content['link_url']) . '" class="ad-cta" target="_blank" rel="noopener">';
            $html .= esc_html($ad_content['cta_text']);
            $html .= '</a>';
        }
        
        $html .= '</div>'; // ad-text
        $html .= '</div>'; // ad-content
        $html .= '<span class="ad-label">Sponsored</span>';
        $html .= '</div>'; // biz-dir-ad
        
        return $html;
    }
    
    /**
     * Track ad impression
     */
    private function track_ad_impression($placement_id) {
        $this->db->query($this->db->prepare(
            "UPDATE {$this->db->prefix}biz_ad_placements 
             SET impressions = impressions + 1 
             WHERE id = %d",
            $placement_id
        ));
    }
    
    /**
     * Track ad click
     */
    public function track_ad_click() {
        $placement_id = intval($_POST['placement_id']);
        
        if (!$placement_id) {
            wp_send_json_error('Invalid placement ID');
            return;
        }
        
        $this->db->query($this->db->prepare(
            "UPDATE {$this->db->prefix}biz_ad_placements 
             SET clicks = clicks + 1 
             WHERE id = %d",
            $placement_id
        ));
        
        wp_send_json_success('Click tracked');
    }
    
    /**
     * Check if ads should be displayed
     */
    private function should_display_ads() {
        // Don't show ads to admins in admin area
        if (is_admin()) {
            return false;
        }
        
        // Don't show ads on login/register pages
        if (is_page(['login', 'register', 'wp-login'])) {
            return false;
        }
        
        // Allow filtering
        return apply_filters('biz_dir_should_display_ads', true);
    }
    
    /**
     * Check if ad slot is available for given dates
     */
    private function is_slot_available($slot_id, $start_date, $end_date) {
        $conflicting_placements = $this->db->get_var($this->db->prepare(
            "SELECT COUNT(*) 
             FROM {$this->db->prefix}biz_ad_placements 
             WHERE ad_slot_id = %d 
             AND status IN ('active', 'pending_payment') 
             AND (
                 (start_date <= %s AND end_date >= %s) OR 
                 (start_date <= %s AND end_date >= %s) OR 
                 (start_date >= %s AND end_date <= %s)
             )",
            $slot_id,
            $start_date, $start_date,
            $end_date, $end_date,
            $start_date, $end_date
        ));
        
        return $conflicting_placements == 0;
    }
    
    /**
     * Get ad slot by ID
     */
    private function get_ad_slot($slot_id) {
        return $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->db->prefix}biz_ad_slots WHERE id = %d",
            $slot_id
        ), ARRAY_A);
    }
    
    /**
     * Calculate duration in months between two dates
     */
    private function calculate_duration_months($start_date, $end_date) {
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $interval = $start->diff($end);
        
        return max(1, $interval->m + ($interval->y * 12));
    }
    
    /**
     * Insert content after specific paragraph
     */
    private function insert_after_paragraph($content, $paragraph_number, $insert_content) {
        $paragraphs = explode('</p>', $content);
        
        if (count($paragraphs) > $paragraph_number) {
            $paragraphs[$paragraph_number] .= $insert_content;
        }
        
        return implode('</p>', $paragraphs);
    }
    
    /**
     * Inject ad CSS
     */
    public function inject_ad_css() {
        ?>
        <style>
        .biz-dir-ad {
            position: relative;
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .biz-dir-ad .ad-content {
            display: flex;
            align-items: center;
        }
        
        .biz-dir-ad .ad-image {
            max-width: 100px;
            margin-right: 15px;
            border-radius: 3px;
        }
        
        .biz-dir-ad .ad-title {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #333;
        }
        
        .biz-dir-ad .ad-description {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        
        .biz-dir-ad .ad-cta {
            display: inline-block;
            padding: 8px 16px;
            background: #007cba;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .biz-dir-ad .ad-cta:hover {
            background: #005a87;
        }
        
        .biz-dir-ad .ad-label {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 10px;
            color: #999;
            text-transform: uppercase;
        }
        
        .biz-dir-ad-header {
            text-align: center;
        }
        
        .biz-dir-ad-footer {
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .biz-dir-ad .ad-content {
                flex-direction: column;
                text-align: center;
            }
            
            .biz-dir-ad .ad-image {
                margin: 0 0 10px 0;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Inject ad tracking JavaScript
     */
    public function inject_ad_tracking_js() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Track ad clicks
            document.querySelectorAll('.biz-dir-ad .ad-cta').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    var ad = this.closest('.biz-dir-ad');
                    var placementId = ad.getAttribute('data-placement-id');
                    
                    if (placementId) {
                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=biz_dir_track_ad_click&placement_id=' + placementId
                        });
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get ad slots for admin
     */
    public function get_available_slots() {
        return $this->db->get_results(
            "SELECT * FROM {$this->db->prefix}biz_ad_slots WHERE is_active = 1 ORDER BY position",
            ARRAY_A
        );
    }
    
    /**
     * Get ad placement analytics
     */
    public function get_placement_analytics($placement_id) {
        $placement = $this->db->get_row($this->db->prepare(
            "SELECT *, 
             CASE WHEN impressions > 0 THEN (clicks / impressions) * 100 ELSE 0 END as ctr
             FROM {$this->db->prefix}biz_ad_placements 
             WHERE id = %d",
            $placement_id
        ), ARRAY_A);
        
        return $placement;
    }
}
