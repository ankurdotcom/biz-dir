<?php

namespace BizDir\Tests\SEO;

use BizDir\Tests\Base_Test_Case;
use BizDir\Core\SEO\Schema_Manager;
use BizDir\Core\Business\Business_Manager;

class SchemaManagerTest extends Base_Test_Case {
    private $schema_manager;
    private $business_manager;
    private $test_business_id;

    public function setUp(): void {
        parent::setUp();
        
        global $wpdb;
        
        // Create a test town first
        $wpdb->insert($wpdb->prefix . 'biz_towns', [
            'name' => 'Test Town',
            'slug' => 'test-town',
            'region' => 'test'
        ]);
        $town_id = $wpdb->insert_id;

        $permission_handler = new \BizDir\Core\User\Permission_Handler();
        $this->business_manager = new Business_Manager($permission_handler);
        $this->schema_manager = new Schema_Manager($this->business_manager);
        
        // Create test business with proper contact info
        $contact_info = [
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'postal_code' => '12345',
            'phone' => '555-1234',
            'website' => 'https://test.com',
            'business_hours' => [
                'monday' => ['09:00-17:00'],
                'tuesday' => ['09:00-17:00']
            ]
        ];
        
        // Create test business with town_id
        $this->test_business_id = $this->business_manager->create_business([
            'name' => 'Test Business',
            'slug' => 'test-business',
            'description' => 'A test business description',
            'town_id' => $town_id,
            'contact_info' => json_encode($contact_info),
            'status' => 'active',
            'is_sponsored' => 0
        ]);
    }

    public function test_generate_business_schema() {
        $schema = $this->schema_manager->generate_business_schema($this->test_business_id);
        
        $this->assertIsArray($schema);
        $this->assertEquals('LocalBusiness', $schema['@type']);
        $this->assertArrayHasKey('name', $schema);
        $this->assertArrayHasKey('description', $schema);
        $this->assertArrayHasKey('address', $schema);
        $this->assertArrayHasKey('openingHours', $schema);
    }

    public function test_generate_review_schema() {
        // Add a test review
        $review_data = [
            'business_id' => $this->test_business_id,
            'rating' => 4,
            'content' => 'Great service!',
            'author' => 'Test User'
        ];
        
        $review_id = $this->business_manager->add_review($review_data);
        
        $schema = $this->schema_manager->generate_review_schema($review_id);
        
        $this->assertIsArray($schema);
        $this->assertEquals('Review', $schema['@type']);
        $this->assertArrayHasKey('reviewRating', $schema);
        $this->assertArrayHasKey('author', $schema);
        $this->assertArrayHasKey('reviewBody', $schema);
    }

    public function test_save_schema_meta() {
        $meta_data = [
            'business_type' => 'Restaurant',
            'cuisine' => ['Italian', 'Pizza'],
            'price_range' => '$$'
        ];
        
        $result = $this->schema_manager->save_schema_meta($this->test_business_id, $meta_data);
        $this->assertTrue($result);
        
        $saved_meta = $this->schema_manager->get_schema_meta($this->test_business_id);
        $this->assertEquals($meta_data, $saved_meta);
    }

    public function test_generate_breadcrumb_schema() {
        $path = [
            ['name' => 'Restaurants', 'url' => '/restaurants'],
            ['name' => 'Italian', 'url' => '/restaurants/italian'],
            ['name' => 'Test Business', 'url' => '/restaurants/italian/test-business']
        ];
        
        $schema = $this->schema_manager->generate_breadcrumb_schema($path);
        
        $this->assertIsArray($schema);
        $this->assertEquals('BreadcrumbList', $schema['@type']);
        $this->assertArrayHasKey('itemListElement', $schema);
        $this->assertCount(3, $schema['itemListElement']);
    }

    public function test_generate_aggregate_rating_schema() {
        // Add multiple reviews
        $ratings = [5, 4, 4, 3, 5];
        foreach ($ratings as $rating) {
            $this->business_manager->add_review([
                'business_id' => $this->test_business_id,
                'rating' => $rating,
                'content' => 'Test review',
                'author' => 'Test User'
            ]);
        }
        
        $schema = $this->schema_manager->generate_aggregate_rating_schema($this->test_business_id);
        
        $this->assertIsArray($schema);
        $this->assertEquals('AggregateRating', $schema['@type']);
        $this->assertEquals(4.2, $schema['ratingValue']);
        $this->assertEquals(5, $schema['reviewCount']);
    }

    public function test_schema_validation() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid schema data');
        
        $this->schema_manager->save_schema_meta($this->test_business_id, ['invalid_key' => []]);
    }

    public function test_generate_seo_meta() {
        $meta = $this->schema_manager->generate_seo_meta($this->test_business_id);
        
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('description', $meta);
        $this->assertArrayHasKey('keywords', $meta);
        $this->assertArrayHasKey('og:type', $meta);
        $this->assertArrayHasKey('og:title', $meta);
    }

    public function test_invalid_business() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Business not found');
        
        $this->schema_manager->generate_business_schema(999999);
    }

    public function test_schema_output_format() {
        $schema = $this->schema_manager->generate_business_schema($this->test_business_id);
        $json = $this->schema_manager->get_schema_json($schema);
        
        $this->assertIsString($json);
        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('@context', $decoded);
        $this->assertEquals('https://schema.org', $decoded['@context']);
    }

    public function tearDown(): void {
        parent::tearDown();
    }
}
