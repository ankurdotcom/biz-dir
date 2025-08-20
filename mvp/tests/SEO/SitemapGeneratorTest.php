<?php

namespace BizDir\Tests\SEO;

use BizDir\Tests\Base_Test_Case;
use BizDir\Core\SEO\Sitemap_Generator;
use BizDir\Core\Business\Business_Manager;

class SitemapGeneratorTest extends Base_Test_Case {
    private $sitemap_generator;
    private $business_manager;

    public function setUp(): void {
        parent::setUp();
        
        global $wpdb;

        // Add test town first
        $wpdb->insert($wpdb->prefix . 'biz_towns', [
            'name' => 'Test Town',
            'slug' => 'test-town',
            'region' => 'test'
        ]);
        $town_id = $wpdb->insert_id;
        
        $permission_handler = new \BizDir\Core\User\Permission_Handler();
        $this->business_manager = new Business_Manager($permission_handler);
        $this->sitemap_generator = new Sitemap_Generator($this->business_manager, $permission_handler);
        
        // Create test businesses with town_id
        for ($i = 1; $i <= 5; $i++) {
            $this->business_manager->create_business([
                'name' => "Test Business $i",
                'slug' => "test-business-$i",
                'town_id' => $town_id,
                'description' => "Test business description $i",
                'contact_info' => json_encode(['address' => "123 Test St $i"]),
                'status' => 'active',
                'is_sponsored' => 0
            ]);
        }
    }

    public function test_generate_business_sitemap() {
        $sitemap = $this->sitemap_generator->generate_business_sitemap();
        
        $this->assertIsString($sitemap);
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $sitemap);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $sitemap);
        
        // Check for business URLs
        for ($i = 1; $i <= 5; $i++) {
            $this->assertStringContainsString("test-business-$i", $sitemap);
        }
    }

    public function test_generate_category_sitemap() {
        $categories = ['restaurants', 'hotels', 'shopping'];
        foreach ($categories as $category) {
            wp_insert_term($category, 'business_category');
        }
        
        $sitemap = $this->sitemap_generator->generate_category_sitemap();
        
        $this->assertIsString($sitemap);
        foreach ($categories as $category) {
            $this->assertStringContainsString($category, $sitemap);
        }
    }

    public function test_generate_region_sitemap() {
        global $wpdb;
        
        // Add test towns
        $regions = ['north', 'south', 'east', 'west'];
        foreach ($regions as $region) {
            $wpdb->insert($wpdb->prefix . 'biz_towns', [
                'name' => "Test Town $region",
                'slug' => "test-town-$region",
                'region' => $region
            ]);
        }
        
        $sitemap = $this->sitemap_generator->generate_region_sitemap();
        
        $this->assertIsString($sitemap);
        foreach ($regions as $region) {
            $this->assertStringContainsString($region, $sitemap);
        }
    }

    public function test_generate_index_sitemap() {
        $index = $this->sitemap_generator->generate_index_sitemap();
        
        $this->assertIsString($index);
        $this->assertStringContainsString('sitemap-businesses.xml', $index);
        $this->assertStringContainsString('sitemap-categories.xml', $index);
        $this->assertStringContainsString('sitemap-regions.xml', $index);
    }

    public function test_sitemap_pagination() {
        // Add another test town for pagination
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'biz_towns', [
            'name' => 'Pagination Town',
            'slug' => 'pagination-town',
            'region' => 'test'
        ]);
        $town_id = $wpdb->insert_id;

        // Create many businesses
        for ($i = 6; $i <= 55; $i++) {
            $this->business_manager->create_business([
                'name' => "Test Business $i",
                'slug' => "test-business-$i",
                'town_id' => $town_id,
                'description' => "Test business description $i",
                'contact_info' => json_encode(['address' => "123 Test St $i"]),
                'status' => 'active',
                'is_sponsored' => 0
            ]);
        }
        
        $sitemaps = $this->sitemap_generator->generate_paginated_business_sitemap(50);
        
        $this->assertIsArray($sitemaps);
        $this->assertCount(2, $sitemaps);
        $this->assertStringContainsString('test-business-1', $sitemaps[0]);
        $this->assertStringContainsString('test-business-51', $sitemaps[1]);
    }

    public function test_sitemap_last_modified() {
        // Add test town for last modified test
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'biz_towns', [
            'name' => 'Last Modified Town',
            'slug' => 'last-modified-town',
            'region' => 'test'
        ]);
        $town_id = $wpdb->insert_id;

        $business_id = $this->business_manager->create_business([
            'name' => 'Last Modified Test',
            'slug' => 'last-modified-test',
            'town_id' => $town_id,
            'description' => 'Last modified test description',
            'contact_info' => json_encode(['address' => '123 Test St']),
            'status' => 'active',
            'is_sponsored' => 0
        ]);
        
        // Update the business
        $this->business_manager->update_business($business_id, [
            'name' => 'Updated Name'
        ]);
        
        $sitemap = $this->sitemap_generator->generate_business_sitemap();
        $this->assertStringContainsString('last-modified-test', $sitemap);
        $this->assertStringContainsString(date('Y-m-d'), $sitemap);
    }

    public function test_sitemap_priority() {
        // Add test town for priority test
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'biz_towns', [
            'name' => 'Priority Town',
            'slug' => 'priority-town',
            'region' => 'test'
        ]);
        $town_id = $wpdb->insert_id;

        $business_id = $this->business_manager->create_business([
            'name' => 'Priority Test',
            'slug' => 'priority-test',
            'town_id' => $town_id,
            'description' => 'Priority test description',
            'contact_info' => json_encode(['address' => '123 Test St']),
            'status' => 'active',
            'is_sponsored' => 1
        ]);
        
        $sitemap = $this->sitemap_generator->generate_business_sitemap();
        $this->assertStringContainsString('<priority>1.0</priority>', $sitemap);
    }

    public function test_generate_sitemap_files() {
        $result = $this->sitemap_generator->generate_sitemap_files();
        
        $this->assertTrue($result);
        $this->assertFileExists(ABSPATH . 'sitemap-index.xml');
        $this->assertFileExists(ABSPATH . 'sitemap-businesses.xml');
        $this->assertFileExists(ABSPATH . 'sitemap-categories.xml');
        $this->assertFileExists(ABSPATH . 'sitemap-regions.xml');
    }

    public function test_invalid_sitemap_type() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid sitemap type');
        
        $this->sitemap_generator->generate_sitemap('invalid_type');
    }

    public function tearDown(): void {
        parent::tearDown();
        
        // Clean up sitemap files
        $files = ['sitemap-index.xml', 'sitemap-businesses.xml', 'sitemap-categories.xml', 'sitemap-regions.xml'];
        foreach ($files as $file) {
            if (file_exists(ABSPATH . $file)) {
                unlink(ABSPATH . $file);
            }
        }
    }
}
