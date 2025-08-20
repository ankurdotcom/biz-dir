<?php

namespace BizDir\Tests;

class Setup_Helper {
    /**
     * Creates a test town
     *
     * @param array $data Optional town data
     * @return array The created town data
     */
    private $counter = 0;
    
    private function generate_unique_suffix() {
        $this->counter++;
        return uniqid('_' . $this->counter . '_');
    }
    
    public function create_test_town($data = []) {
        global $wpdb;

        $suffix = $this->generate_unique_suffix();
        $defaults = [
            'name' => 'Test Town ' . $suffix,
            'slug' => 'test-town' . $suffix,
            'region' => 'Test Region ' . $suffix
        ];

        $town_data = wp_parse_args($data, $defaults);
        
        $wpdb->insert($wpdb->prefix . 'biz_towns', $town_data);
        
        if ($wpdb->last_error) {
            throw new \RuntimeException("Failed to create test town: " . $wpdb->last_error);
        }
        
        $town_data['id'] = $wpdb->insert_id;
        return $town_data;
    }

    /**
     * Creates a test business
     *
     * @param array $data Optional business data
     * @return array The created business data
     */
    public function create_test_business($data = []) {
        global $wpdb;

        // Create a test town first if town_id not provided
        if (empty($data['town_id'])) {
            $town = $this->create_test_town();
            $data['town_id'] = $town['id'];
        }

        $suffix = $this->generate_unique_suffix();
        $defaults = [
            'name' => 'Test Business ' . $suffix,
            'owner_id' => 1, // Default to admin user
            'category' => 'test-category',
            'description' => 'Test business description',
            'contact_info' => json_encode([
                'phone' => '123-456-7890',
                'email' => 'test' . $suffix . '@example.com'
            ]),
            'status' => 'active',
            'is_sponsored' => 0,
            'slug' => 'test-business' . $suffix
        ];

        $business_data = wp_parse_args($data, $defaults);
        
        // Ensure we have a valid town_id reference
        if (!$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}biz_towns WHERE id = %d",
            $business_data['town_id']
        ))) {
            throw new \RuntimeException("Invalid town_id: {$business_data['town_id']}");
        }
        
        $wpdb->insert($wpdb->prefix . 'biz_businesses', $business_data);
        
        if ($wpdb->last_error) {
            throw new \RuntimeException("Failed to create test business: " . $wpdb->last_error);
        }
        
        $business_data['id'] = $wpdb->insert_id;
        return $business_data;
    }

    /**
     * Creates test SEO meta data
     *
     * @param int $business_id The business ID
     * @param array $meta_data Optional meta data
     * @return array The created meta data
     */
    public function create_test_seo_meta($business_id, $meta_data = []) {
        global $wpdb;
        
        // Verify business exists
        if (!$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}biz_businesses WHERE id = %d",
            $business_id
        ))) {
            throw new \RuntimeException("Invalid business_id: {$business_id}");
        }

        $suffix = $this->generate_unique_suffix();
        $defaults = [
            'meta_type' => 'meta',
            'meta_key' => 'description_' . $suffix,
            'meta_value' => 'Test SEO meta description ' . $suffix
        ];

        $data = wp_parse_args($meta_data, $defaults);
        $data['business_id'] = $business_id;

        $wpdb->insert($wpdb->prefix . 'biz_seo_meta', $data);
        
        if ($wpdb->last_error) {
            throw new \RuntimeException("Failed to create test SEO meta: " . $wpdb->last_error);
        }
        
        $data['id'] = $wpdb->insert_id;
        return $data;
    }

    /**
     * Cleans up test data
     *
     * @param int $business_id Optional business ID to clean up
     * @param int $town_id Optional town ID to clean up
     */
    public function cleanup_test_data($business_id = null, $town_id = null) {
        global $wpdb;

        if ($business_id) {
            $wpdb->delete($wpdb->prefix . 'biz_seo_meta', ['business_id' => $business_id]);
            $wpdb->delete($wpdb->prefix . 'biz_businesses', ['id' => $business_id]);
        }

        if ($town_id) {
            $wpdb->delete($wpdb->prefix . 'biz_towns', ['id' => $town_id]);
        }
    }
}
