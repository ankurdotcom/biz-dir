<?php

namespace BizDir\Tests;

/**
 * Test Setup Helper Functions
 */
class Setup_Helper {
    /**
     * Create a test town record
     * 
     * @return int|false Town ID if successful, false otherwise
     */
    public static function create_test_town() {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'biz_towns',
            array(
                'name' => 'Test Town',
                'slug' => 'test-town',
                'region' => 'Test Region'
            ),
            array('%s', '%s', '%s')
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Create a test business record
     * 
     * @param int $town_id Town ID for the business
     * @param array $data Optional additional data
     * @return int|false Business ID if successful, false otherwise
     */
    public static function create_test_business($town_id, $data = []) {
        global $wpdb;
        
        $defaults = array(
            'name' => 'Test Business',
            'slug' => 'test-business-' . uniqid(),
            'town_id' => $town_id,
            'owner_id' => 1,
            'category' => 'test',
            'description' => 'Test business description',
            'contact_info' => json_encode(['email' => 'test@example.com']),
            'status' => 'active',
            'is_sponsored' => 0
        );

        $data = array_merge($defaults, $data);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'biz_businesses',
            $data,
            array(
                '%s', // name
                '%s', // slug
                '%d', // town_id
                '%d', // owner_id
                '%s', // category
                '%s', // description
                '%s', // contact_info
                '%s', // status
                '%d'  // is_sponsored
            )
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }
}
