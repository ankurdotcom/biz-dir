<?php

namespace BizDir\Core\SEO;

class Sitemap_Generator {
    private $business_manager;
    private $permission_handler;
    
    public function __construct(\BizDir\Core\Business\Business_Manager $business_manager, \BizDir\Core\User\Permission_Handler $permission_handler) {
        $this->business_manager = $business_manager;
        $this->permission_handler = $permission_handler;
    }
    
    public function generate_business_sitemap() {
        $businesses = $this->get_all_businesses();
        
        $xml = $this->get_sitemap_header();
        
        foreach ($businesses as $business) {
                            $xml .= $this->get_url_entry(
                    home_url('/business/' . $business['slug']),
                    isset($business['updated_at']) ? $business['updated_at'] : date('c'),
                    'weekly',
                    $business['is_sponsored'] ? '1.0' : '0.8'
                );
        }
        
        $xml .= $this->get_sitemap_footer();
        
        return $xml;
    }
    
    public function generate_category_sitemap() {
        $categories = get_terms([
            'taxonomy' => 'business_category',
            'hide_empty' => false
        ]);
        
        $xml = $this->get_sitemap_header();
        
        foreach ($categories as $category) {
            $xml .= $this->get_url_entry(
                get_term_link($category),
                date('c'),
                'weekly',
                '0.7'
            );
        }
        
        $xml .= $this->get_sitemap_footer();
        
        return $xml;
    }
    
    public function generate_region_sitemap() {
        global $wpdb;
        
        $regions = $wpdb->get_results(
            "SELECT DISTINCT region FROM {$wpdb->prefix}biz_towns 
            ORDER BY region",
            ARRAY_A
        );
        
        $xml = $this->get_sitemap_header();
        
        foreach ($regions as $region) {
            $xml .= $this->get_url_entry(
                home_url('/region/' . sanitize_title($region['region'])),
                date('c'),
                'weekly',
                '0.6'
            );
        }
        
        $xml .= $this->get_sitemap_footer();
        
        return $xml;
    }
    
    public function generate_index_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        
        // Add business sitemap
        $xml .= $this->get_sitemap_entry('sitemap-businesses.xml');
        
        // Add category sitemap
        $xml .= $this->get_sitemap_entry('sitemap-categories.xml');
        
        // Add region sitemap
        $xml .= $this->get_sitemap_entry('sitemap-regions.xml');
        
        $xml .= '</sitemapindex>';
        
        return $xml;
    }
    
    public function generate_paginated_business_sitemap($per_page = 50) {
        global $wpdb;
        
        // Get total count of active businesses
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}biz_businesses WHERE status = 'active'");
        $sitemaps = [];
        $pages = ceil($total / $per_page);
        
        for ($page = 0; $page < $pages; $page++) {
            $offset = $page * $per_page;
            
            // Get businesses for this page with proper ordering
            $sql = $wpdb->prepare(
                "SELECT b.*, t.slug as town_slug 
                FROM {$wpdb->prefix}biz_businesses b 
                LEFT JOIN {$wpdb->prefix}biz_towns t ON b.town_id = t.id 
                WHERE b.status = 'active'
                ORDER BY b.id ASC
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            );
            
            $businesses = $wpdb->get_results($sql, ARRAY_A);
            
            $xml = $this->get_sitemap_header();
            foreach ($businesses as $business) {
                // Use town name in URL if available
                $location = $business['town_slug'] ? 
                    home_url('/business/' . $business['town_slug'] . '/' . $business['slug']) :
                    home_url('/business/' . $business['slug']);
                    
                $xml .= $this->get_url_entry(
                    $location,
                    $business['updated_at'] ?: date('c'),
                    'weekly',
                    $business['is_sponsored'] ? '1.0' : '0.8'
                );
            }
            
            $xml .= $this->get_sitemap_footer();
            $sitemaps[] = $xml;
        }
        
        return $sitemaps;
    }
    
    public function generate_sitemap_files() {
        $base_path = ABSPATH;
        
        // Generate and save index sitemap
        $index_content = $this->generate_index_sitemap();
        file_put_contents($base_path . 'sitemap-index.xml', $index_content);
        
        // Generate and save business sitemap
        $business_content = $this->generate_business_sitemap();
        file_put_contents($base_path . 'sitemap-businesses.xml', $business_content);
        
        // Generate and save category sitemap
        $category_content = $this->generate_category_sitemap();
        file_put_contents($base_path . 'sitemap-categories.xml', $category_content);
        
        // Generate and save region sitemap
        $region_content = $this->generate_region_sitemap();
        file_put_contents($base_path . 'sitemap-regions.xml', $region_content);
        
        return true;
    }
    
    private function get_all_businesses($limit = null, $offset = null) {
        global $wpdb;
        
        $sql = "SELECT b.*, t.slug as town_slug 
                FROM {$wpdb->prefix}biz_businesses b 
                LEFT JOIN {$wpdb->prefix}biz_towns t ON b.town_id = t.id 
                WHERE b.status = 'active'
                ORDER BY b.id ASC";
                
        if ($limit !== null) {
            $sql .= $wpdb->prepare(" LIMIT %d", $limit);
            if ($offset !== null) {
                $sql .= $wpdb->prepare(" OFFSET %d", $offset);
            }
        }
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    private function get_sitemap_header() {
        return '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
               '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    }
    
    private function get_sitemap_footer() {
        return '</urlset>';
    }
    
    private function get_url_entry($loc, $lastmod, $changefreq, $priority) {
        return "\t<url>\n" .
               "\t\t<loc>" . esc_url($loc) . "</loc>\n" .
               "\t\t<lastmod>" . esc_html(date('c', strtotime($lastmod))) . "</lastmod>\n" .
               "\t\t<changefreq>" . esc_html($changefreq) . "</changefreq>\n" .
               "\t\t<priority>" . esc_html($priority) . "</priority>\n" .
               "\t</url>\n";
    }
    
    private function get_sitemap_entry($filename) {
        return "\t<sitemap>\n" .
               "\t\t<loc>" . esc_url(home_url('/' . $filename)) . "</loc>\n" .
               "\t\t<lastmod>" . esc_html(date('c')) . "</lastmod>\n" .
               "\t</sitemap>\n";
    }
    
    public function generate_sitemap($type = 'index') {
        switch ($type) {
            case 'index':
                return $this->generate_index_sitemap();
            case 'businesses':
                return $this->generate_business_sitemap();
            case 'categories':
                return $this->generate_category_sitemap();
            case 'regions':
                return $this->generate_region_sitemap();
            default:
                throw new \Exception('Invalid sitemap type');
        }
    }
}
