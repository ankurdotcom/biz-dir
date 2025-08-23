<?php
/**
 * Prevent direct access
 */
if (!defined('ABSPATH')) {
    exit;
}


namespace BizDir\Core\SEO;

use BizDir\Core\Business\Business_Manager;
use BizDir\Core\User\Permission_Handler;

class Meta_Manager {
    private $business_manager;
    
    public function __construct() {
        $permission_handler = new Permission_Handler();
        $this->business_manager = new Business_Manager($permission_handler);
    }
    
    public function generate_meta_tags($business_id) {
        $business = $this->business_manager->get_business($business_id);
        if (!$business) {
            throw new \Exception('Business not found');
        }
        
        $meta_tags = [
            'title' => $this->generate_title($business),
            'description' => $this->generate_description($business),
            'keywords' => $this->generate_keywords($business),
            'robots' => $this->generate_robots_meta($business_id)
        ];
        
        // Add custom meta if exists
        $custom_meta = $this->get_custom_meta($business_id);
        if ($custom_meta) {
            $meta_tags = array_merge($meta_tags, $custom_meta);
        }
        
        return $meta_tags;
    }
    
    public function generate_og_tags($business_id) {
        $business = $this->business_manager->get_business($business_id);
        if (!$business) {
            throw new \Exception('Business not found');
        }
        
        return [
            'og:title' => $business['name'],
            'og:description' => $business['description'],
            'og:type' => 'business',
            'og:url' => $this->generate_canonical_url($business_id),
            'og:image' => $this->get_business_image($business_id)
        ];
    }
    
    public function generate_twitter_cards($business_id) {
        $business = $this->business_manager->get_business($business_id);
        if (!$business) {
            throw new \Exception('Business not found');
        }
        
        return [
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $business['name'],
            'twitter:description' => $business['description'],
            'twitter:image' => $this->get_business_image($business_id)
        ];
    }
    
    public function save_custom_meta($business_id, $meta_data) {
        global $wpdb;
        
        if (!$this->validate_meta_data($meta_data)) {
            throw new \Exception('Invalid meta data');
        }
        
        foreach ($meta_data as $key => $value) {
            $result = $wpdb->replace(
                $wpdb->prefix . 'biz_seo_meta',
                [
                    'business_id' => $business_id,
                    'meta_type' => 'custom',
                    'meta_key' => $key,
                    'meta_value' => $value
                ],
                ['%d', '%s', '%s', '%s']
            );
            
            if ($result === false) {
                return false;
            }
        }
        
        return true;
    }
    
    public function get_custom_meta($business_id) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$wpdb->prefix}biz_seo_meta 
                WHERE business_id = %d AND meta_type = 'custom'",
                $business_id
            ),
            ARRAY_A
        );
        
        if (!$results) {
            return [];
        }
        
        $meta = [];
        foreach ($results as $row) {
            $meta[$row['meta_key']] = $row['meta_value'];
        }
        
        return $meta;
    }
    
    public function generate_canonical_url($business_id) {
        $business = $this->business_manager->get_business($business_id);
        if (!$business) {
            throw new \Exception('Business not found');
        }

        global $wpdb;
        $town = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT name, slug FROM {$wpdb->prefix}biz_towns WHERE id = %d",
                $business['town_id']
            )
        );
        
        $business_slug = sanitize_title($business['name']); // Use the sanitized name instead of ID-based slug
        
        $url_parts = ['business'];
        if ($town && $town->slug) {
            $url_parts[] = $town->slug;
        }
        $url_parts[] = $business_slug;
        
        return home_url('/' . implode('/', $url_parts));
    }
    
    public function sanitize_meta_data($meta_data) {
        $sanitized = [];
        
        foreach ($meta_data as $key => $value) {
            $sanitized[$key] = wp_strip_all_tags($value);
            $sanitized[$key] = str_replace(["\n", "\r"], ' ', $sanitized[$key]);
            $sanitized[$key] = preg_replace('/\s+/', ' ', $sanitized[$key]);
            $sanitized[$key] = trim($sanitized[$key]);
        }
        
        return $sanitized;
    }
    
    public function validate_meta_lengths($meta_data) {
        $validated = [];
        
        foreach ($meta_data as $key => $value) {
            switch ($key) {
                case 'title':
                    $validated[$key] = substr($value, 0, 60);
                    break;
                case 'description':
                    $validated[$key] = substr($value, 0, 160);
                    break;
                default:
                    $validated[$key] = $value;
            }
        }
        
        return $validated;
    }
    
    public function generate_robots_meta($business_id) {
        $business = $this->business_manager->get_business($business_id);
        
        if (!$business || $business['status'] === 'private') {
            return 'noindex,nofollow';
        }
        
        return 'index,follow';
    }
    
    private function validate_meta_data($meta_data) {
        $allowed_keys = ['custom_title', 'custom_description', 'custom_keywords'];
        
        foreach ($meta_data as $key => $value) {
            if (!in_array($key, $allowed_keys)) {
                return false;
            }
            
            if (empty($value) || !is_string($value)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function generate_title($business) {
        $custom_meta = $this->get_custom_meta($business['id']);
        if (isset($custom_meta['custom_title'])) {
            return $custom_meta['custom_title'];
        }
        
        $site_name = get_bloginfo('name');
        return $business['name'] . ($site_name ? ' - ' . $site_name : '');
    }
    
    private function generate_description($business) {
        $custom_meta = $this->get_custom_meta($business['id']);
        if (isset($custom_meta['custom_description'])) {
            return $custom_meta['custom_description'];
        }
        
        $description = $business['description'];
        return is_string($description) ? wp_trim_words($description, 20) : '';
    }
    
    private function generate_keywords($business) {
        $custom_meta = $this->get_custom_meta($business['id']);
        if (isset($custom_meta['custom_keywords'])) {
            return $custom_meta['custom_keywords'];
        }
        
        $keywords = [];
        $contact_info = $business['contact_info'];
        
        if (is_array($contact_info) && isset($contact_info['keywords'])) {
            if (is_array($contact_info['keywords'])) {
                $keywords = array_merge($keywords, $contact_info['keywords']);
            }
        }
        
        // Add business categories
        $categories = wp_get_post_terms($business['id'], 'business_category');
        if (!is_wp_error($categories) && $categories) {
            foreach ($categories as $category) {
                $keywords[] = $category->name;
            }
        }
        
        // Add business tags
        $tags = wp_get_post_terms($business['id'], 'business_tag');
        if (!is_wp_error($tags) && $tags) {
            foreach ($tags as $tag) {
                $keywords[] = $tag->name;
            }
        }
        
        return implode(',', array_unique(array_filter($keywords)));
    }
    
    private function get_business_image($business_id) {
        $image_id = get_post_thumbnail_id($business_id);
        if (!$image_id) {
            return '';
        }
        
        $image = wp_get_attachment_image_src($image_id, 'large');
        return $image ? $image[0] : '';
    }
    
    public function render_meta_tags($meta_tags) {
        $html = '';
        
        foreach ($meta_tags as $name => $content) {
            $html .= sprintf(
                '<meta name="%s" content="%s">' . PHP_EOL,
                esc_attr($name),
                esc_attr($content)
            );
        }
        
        return $html;
    }
}
