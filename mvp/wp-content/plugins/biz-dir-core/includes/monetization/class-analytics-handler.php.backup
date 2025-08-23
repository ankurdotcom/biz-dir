<?php

namespace BizDir\Core\Monetization;

class Analytics_Handler {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }
    
    public function track_business_view($business_id, $viewer_ip = null) {
        if (!$viewer_ip) {
            $viewer_ip = $this->get_client_ip();
        }
        
        // Don't track repeated views from same IP within 24 hours
        $recent_view = $this->db->get_var(
            $this->db->prepare(
                "SELECT id FROM {$this->db->prefix}biz_views 
                WHERE business_id = %d AND viewer_ip = %s 
                AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                $business_id,
                $viewer_ip
            )
        );
        
        if ($recent_view) {
            return false;
        }
        
        $result = $this->db->insert(
            $this->db->prefix . 'biz_views',
            [
                'business_id' => $business_id,
                'viewer_ip' => $viewer_ip,
                'viewed_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    public function track_search($query, $filters = [], $results_count = 0) {
        $result = $this->db->insert(
            $this->db->prefix . 'biz_searches',
            [
                'search_query' => $query,
                'filters' => maybe_serialize($filters),
                'results_count' => $results_count,
                'searched_at' => current_time('mysql'),
                'searcher_ip' => $this->get_client_ip()
            ],
            ['%s', '%s', '%d', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    public function get_business_views($business_id, $start_date = null, $end_date = null) {
        $query = "SELECT DATE(viewed_at) as date, COUNT(*) as views 
                FROM {$this->db->prefix}biz_views 
                WHERE business_id = %d";
        $params = [$business_id];
        
        if ($start_date) {
            $query .= " AND viewed_at >= %s";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $query .= " AND viewed_at <= %s";
            $params[] = $end_date;
        }
        
        $query .= " GROUP BY DATE(viewed_at) ORDER BY date DESC";
        
        return $this->db->get_results(
            $this->db->prepare($query, ...$params),
            ARRAY_A
        );
    }
    
    public function get_popular_searches($limit = 10, $days = 30) {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT search_query, COUNT(*) as search_count 
                FROM {$this->db->prefix}biz_searches 
                WHERE searched_at > DATE_SUB(NOW(), INTERVAL %d DAY) 
                GROUP BY search_query 
                ORDER BY search_count DESC 
                LIMIT %d",
                $days,
                $limit
            ),
            ARRAY_A
        );
    }
    
    public function get_search_trends($start_date = null, $end_date = null) {
        $query = "SELECT DATE(searched_at) as date, COUNT(*) as searches 
                FROM {$this->db->prefix}biz_searches WHERE 1=1";
        $params = [];
        
        if ($start_date) {
            $query .= " AND searched_at >= %s";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $query .= " AND searched_at <= %s";
            $params[] = $end_date;
        }
        
        $query .= " GROUP BY DATE(searched_at) ORDER BY date DESC";
        
        if ($params) {
            $query = $this->db->prepare($query, ...$params);
        }
        
        return $this->db->get_results($query, ARRAY_A);
    }
    
    public function get_popular_filters($days = 30) {
        $results = $this->db->get_results(
            $this->db->prepare(
                "SELECT filters 
                FROM {$this->db->prefix}biz_searches 
                WHERE searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            ),
            ARRAY_A
        );
        
        $filter_counts = [];
        foreach ($results as $row) {
            $filters = maybe_unserialize($row['filters']);
            if (is_array($filters)) {
                foreach ($filters as $key => $value) {
                    if (!isset($filter_counts[$key])) {
                        $filter_counts[$key] = [];
                    }
                    if (!isset($filter_counts[$key][$value])) {
                        $filter_counts[$key][$value] = 0;
                    }
                    $filter_counts[$key][$value]++;
                }
            }
        }
        
        return $filter_counts;
    }
    
    public function get_user_interests($user_id, $limit = 5) {
        // Get categories of businesses viewed by user
        $query = "SELECT t.name, COUNT(*) as view_count 
                FROM {$this->db->prefix}biz_views v 
                JOIN {$this->db->prefix}posts p ON v.business_id = p.ID 
                JOIN {$this->db->prefix}term_relationships tr ON p.ID = tr.object_id 
                JOIN {$this->db->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
                JOIN {$this->db->prefix}terms t ON tt.term_id = t.term_id 
                WHERE tt.taxonomy = 'business_category' 
                AND v.viewer_ip IN (
                    SELECT DISTINCT viewer_ip 
                    FROM {$this->db->prefix}biz_views 
                    WHERE user_id = %d
                ) 
                GROUP BY t.term_id 
                ORDER BY view_count DESC 
                LIMIT %d";
        
        return $this->db->get_results(
            $this->db->prepare($query, $user_id, $limit),
            ARRAY_A
        );
    }
    
    public function get_business_conversion_rate($business_id, $days = 30) {
        // Get total views
        $total_views = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(DISTINCT viewer_ip) 
                FROM {$this->db->prefix}biz_views 
                WHERE business_id = %d 
                AND viewed_at > DATE_SUB(NOW(), INTERVAL %d DAY)",
                $business_id,
                $days
            )
        );
        
        if (!$total_views) {
            return 0;
        }
        
        // Get interactions (contact form submissions, etc.)
        $total_interactions = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) 
                FROM {$this->db->prefix}biz_interactions 
                WHERE business_id = %d 
                AND interaction_type IN ('contact', 'booking') 
                AND created_at > DATE_SUB(NOW(), INTERVAL %d DAY)",
                $business_id,
                $days
            )
        );
        
        return ($total_interactions / $total_views) * 100;
    }
    
    private function get_client_ip() {
        $ip_headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '127.0.0.1';
    }
}
