<?php

namespace BizDir\Core\SEO;

class Schema_Manager {
    private $business_manager;
    
    public function __construct(\BizDir\Core\Business\Business_Manager $business_manager) {
        $this->business_manager = $business_manager;
    }
    
    public function generate_business_schema($business_id) {
        $business = $this->business_manager->get_business($business_id);
        if (!$business) {
            throw new \Exception('Business not found');
        }
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $business['name'],
            'description' => $business['description'],
            'url' => $this->generate_business_url($business),
            'address' => $this->generate_address_schema($business),
            'geo' => $this->generate_geo_schema($business),
            'openingHours' => $this->generate_hours_schema($business),
            'telephone' => $this->get_business_phone($business),
            'priceRange' => $this->get_price_range($business_id)
        ];
        
        // Add aggregate rating if available
        $rating_schema = $this->generate_aggregate_rating_schema($business_id);
        if ($rating_schema) {
            $schema['aggregateRating'] = $rating_schema;
        }
        
        return $schema;
    }
    
    public function generate_review_schema($review_id) {
        global $wpdb;
        
        $review = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}biz_reviews WHERE id = %d",
                $review_id
            ),
            ARRAY_A
        );
        
        if (!$review) {
            throw new \Exception('Review not found');
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $review['rating']
            ],
            'author' => $this->get_review_author($review),
            'reviewBody' => $review['content'],
            'datePublished' => $review['created_at']
        ];
    }
    
    public function save_schema_meta($business_id, $meta_data) {
        if (!$this->validate_schema_meta($meta_data)) {
            throw new \Exception('Invalid schema data');
        }
        
        global $wpdb;
        
        foreach ($meta_data as $key => $value) {
            $result = $wpdb->replace(
                $wpdb->prefix . 'biz_seo_meta',
                [
                    'business_id' => $business_id,
                    'meta_type' => 'schema',
                    'meta_key' => $key,
                    'meta_value' => is_array($value) ? json_encode($value) : $value
                ],
                ['%d', '%s', '%s', '%s']
            );
            
            if ($result === false) {
                return false;
            }
        }
        
        return true;
    }
    
    public function get_schema_meta($business_id) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$wpdb->prefix}biz_seo_meta 
                WHERE business_id = %d AND meta_type = 'schema'",
                $business_id
            ),
            ARRAY_A
        );
        
        if (!$results) {
            return [];
        }
        
        $meta = [];
        foreach ($results as $row) {
            $value = $row['meta_value'];
            $decoded = json_decode($value, true);
            $meta[$row['meta_key']] = $decoded !== null ? $decoded : $value;
        }
        
        return $meta;
    }
    
    public function generate_breadcrumb_schema($path) {
        $items = [];
        $position = 1;
        
        foreach ($path as $item) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'item' => [
                    '@id' => $item['url'],
                    'name' => $item['name']
                ]
            ];
            $position++;
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];
    }
    
    public function generate_aggregate_rating_schema($business_id) {
        global $wpdb;
        
        $ratings = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) as count, AVG(rating) as average 
                FROM {$wpdb->prefix}biz_reviews 
                WHERE business_id = %d AND status = 'approved'",
                $business_id
            )
        );
        
        if (!$ratings || !$ratings->count) {
            return null;
        }
        
        return [
            '@type' => 'AggregateRating',
            'ratingValue' => round($ratings->average, 1),
            'reviewCount' => (int)$ratings->count
        ];
    }
    
    public function get_schema_json($schema) {
        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    private function generate_address_schema($business) {
        $contact_info = is_string($business['contact_info']) ? json_decode($business['contact_info'], true) : $business['contact_info'];
        if (!is_array($contact_info)) {
            $contact_info = [];
        }
        
        return [
            '@type' => 'PostalAddress',
            'streetAddress' => $contact_info['address'] ?? '',
            'addressLocality' => $contact_info['city'] ?? '',
            'addressRegion' => $contact_info['state'] ?? '',
            'postalCode' => $contact_info['postal_code'] ?? '',
            'addressCountry' => $contact_info['country'] ?? 'US'
        ];
    }
    
    private function get_business_phone($business) {
        $contact_info = is_string($business['contact_info']) ? json_decode($business['contact_info'], true) : $business['contact_info'];
        if (!is_array($contact_info)) {
            return '';
        }
        return $contact_info['phone'] ?? '';
    }
    
    private function generate_business_url($business) {
        return home_url('/business/' . $business['slug']);
    }
    
    private function generate_geo_schema($business) {
        if (empty($business['latitude']) || empty($business['longitude'])) {
            return null;
        }
        
        return [
            '@type' => 'GeoCoordinates',
            'latitude' => $business['latitude'],
            'longitude' => $business['longitude']
        ];
    }
    
    private function generate_hours_schema($business) {
        if (empty($business['business_hours'])) {
            return null;
        }
        
        $hours = json_decode($business['business_hours'], true);
        if (!$hours) {
            return null;
        }
        
        $schema_hours = [];
        $days = [
            'monday' => 'Mo',
            'tuesday' => 'Tu',
            'wednesday' => 'We',
            'thursday' => 'Th',
            'friday' => 'Fr',
            'saturday' => 'Sa',
            'sunday' => 'Su'
        ];
        
        foreach ($hours as $day => $times) {
            if (isset($days[$day]) && !empty($times)) {
                foreach ($times as $time) {
                    $schema_hours[] = $days[$day] . ' ' . $time;
                }
            }
        }
        
        return $schema_hours;
    }
    
    private function get_price_range($business_id) {
        $meta = $this->get_schema_meta($business_id);
        return isset($meta['price_range']) ? $meta['price_range'] : '$$';
    }
    
    private function validate_schema_meta($meta_data) {
        $allowed_keys = ['business_type', 'cuisine', 'price_range'];
        
        foreach ($meta_data as $key => $value) {
            if (!in_array($key, $allowed_keys)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function get_review_author($review) {
        $user = get_userdata($review['user_id']);
        if (!$user) {
            return [
                '@type' => 'Person',
                'name' => 'Anonymous'
            ];
        }
        
        return [
            '@type' => 'Person',
            'name' => $user->display_name
        ];
    }
    
    public function generate_seo_meta($business_id) {
        $business = $this->business_manager->get_business($business_id);
        if (!$business) {
            throw new \Exception('Business not found');
        }
        
        $meta = [
            'title' => $business['name'],
            'description' => wp_trim_words($business['description'], 20),
            'keywords' => $this->generate_keywords($business),
            'og:type' => 'business.business',
            'og:title' => $business['name'],
            'og:description' => $business['description'],
            'og:url' => get_permalink($business_id),
            'og:site_name' => get_bloginfo('name')
        ];
        
        // Add image if available
        $image = wp_get_attachment_image_src(get_post_thumbnail_id($business_id), 'large');
        if ($image) {
            $meta['og:image'] = $image[0];
        }
        
        return $meta;
    }
    
    private function generate_keywords($business) {
        $keywords = [$business['name']];
        
        // Add categories
        $categories = wp_get_post_terms($business['id'], 'business_category');
        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $keywords[] = $category->name;
            }
        }
        
        // Add location
        if (!empty($business['city'])) {
            $keywords[] = $business['city'];
        }
        if (!empty($business['state'])) {
            $keywords[] = $business['state'];
        }
        
        return implode(', ', array_unique($keywords));
    }
}
