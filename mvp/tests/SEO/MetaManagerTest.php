<?php

namespace BizDir\Tests\SEO;

use BizDir\Tests\Base_Test_Case;
use BizDir\Tests\Setup_Helper;
use BizDir\Core\SEO\Meta_Manager;
use BizDir\Core\Business\Business_Manager;

class MetaManagerTest extends Base_Test_Case {
    private $meta_manager;
    private $business_manager;
    private $test_business_id;

    public function setUp(): void {
        parent::setUp();
        
        $this->meta_manager = new Meta_Manager();
        $this->business_manager = new Business_Manager($this->getMockBuilder('BizDir\Core\User\Permission_Handler')->getMock());
        
        // Create test town and business
        $setup_helper = new Setup_Helper();
        $town = $setup_helper->create_test_town();
        if (!$town) {
            throw new \RuntimeException('Failed to create test town');
        }

        $business = $setup_helper->create_test_business([
            'town_id' => $town['id'],
            'name' => 'Test Business Meta',
            'description' => 'A test business for meta tags',
            'contact_info' => json_encode([
                'email' => 'test@example.com',
                'keywords' => ['test', 'business', 'meta']
            ])
        ]);
        $this->test_business_id = $business['id'];
    }

    public function test_generate_meta_tags() {
        $meta_tags = $this->meta_manager->generate_meta_tags($this->test_business_id);
        
        $this->assertIsArray($meta_tags);
        $this->assertArrayHasKey('title', $meta_tags);
        $this->assertArrayHasKey('description', $meta_tags);
        $this->assertArrayHasKey('keywords', $meta_tags);
        
        $this->assertStringContainsString('Test Business Meta', $meta_tags['title']);
        $this->assertEquals('A test business for meta tags', $meta_tags['description']);
    }

    public function test_generate_og_tags() {
        $og_tags = $this->meta_manager->generate_og_tags($this->test_business_id);
        
        $this->assertIsArray($og_tags);
        $this->assertEquals('business', $og_tags['og:type']);
        $this->assertArrayHasKey('og:title', $og_tags);
        $this->assertArrayHasKey('og:description', $og_tags);
        $this->assertArrayHasKey('og:url', $og_tags);
    }

    public function test_generate_twitter_cards() {
        $twitter_tags = $this->meta_manager->generate_twitter_cards($this->test_business_id);
        
        $this->assertIsArray($twitter_tags);
        $this->assertEquals('summary_large_image', $twitter_tags['twitter:card']);
        $this->assertArrayHasKey('twitter:title', $twitter_tags);
        $this->assertArrayHasKey('twitter:description', $twitter_tags);
    }

    public function test_save_custom_meta() {
        $custom_meta = [
            'custom_title' => 'Custom Business Title',
            'custom_description' => 'Custom business description for testing',
            'custom_keywords' => 'test,custom,meta'
        ];
        
        $result = $this->meta_manager->save_custom_meta($this->test_business_id, $custom_meta);
        $this->assertTrue($result);
        
        $saved_meta = $this->meta_manager->get_custom_meta($this->test_business_id);
        $this->assertEquals($custom_meta, $saved_meta);
    }

    public function test_generate_canonical_url() {
        $canonical = $this->meta_manager->generate_canonical_url($this->test_business_id);
        
        $this->assertIsString($canonical);
        $this->assertStringContainsString('test-business-meta', $canonical);
    }

    public function test_meta_sanitization() {
        $meta_data = [
            'title' => 'Test <script>alert("xss")</script> Business',
            'description' => "Test description with\nmultiple\nlines"
        ];
        
        $sanitized = $this->meta_manager->sanitize_meta_data($meta_data);
        
        $this->assertStringNotContainsString('<script>', $sanitized['title']);
        $this->assertStringNotContainsString("\n", $sanitized['description']);
    }

    public function test_meta_length_validation() {
        $long_title = str_repeat('a', 100);
        $long_description = str_repeat('b', 500);
        
        $meta_data = [
            'title' => $long_title,
            'description' => $long_description
        ];
        
        $validated = $this->meta_manager->validate_meta_lengths($meta_data);
        
        $this->assertLessThanOrEqual(60, strlen($validated['title']));
        $this->assertLessThanOrEqual(160, strlen($validated['description']));
    }

    public function test_generate_robots_meta() {
        // Test public business
        $robots_public = $this->meta_manager->generate_robots_meta($this->test_business_id);
        $this->assertEquals('index,follow', $robots_public);
        
        // Test private business
        $setup_helper = new Setup_Helper();
        $private_business = $setup_helper->create_test_business([
            'name' => 'Private Business',
            'status' => 'private'
        ]);
        
        $robots_private = $this->meta_manager->generate_robots_meta($private_business['id']);
        $this->assertEquals('noindex,nofollow', $robots_private);
    }

    public function test_invalid_meta_data() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid meta data');
        
        $this->meta_manager->save_custom_meta($this->test_business_id, ['invalid_key' => '']);
    }

    public function test_meta_tag_output() {
        $meta_tags = $this->meta_manager->generate_meta_tags($this->test_business_id);
        $html = $this->meta_manager->render_meta_tags($meta_tags);
        
        $this->assertIsString($html);
        $this->assertStringContainsString('<meta', $html);
        $this->assertStringContainsString('name=', $html);
        $this->assertStringContainsString('content=', $html);
    }

    public function tearDown(): void {
        parent::tearDown();
    }
}
