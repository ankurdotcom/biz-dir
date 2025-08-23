<?php
/**
 * SEO Regression Tests
 * Tests structured data, meta tags, schema markup, and search optimization
 */

require_once __DIR__ . '/RegressionTestCase.php';

class SEORegressionTest extends RegressionTestCase
{
    private $sampleBusinessData;
    private $metaTagsToTest;
    private $schemaTypes;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sampleBusinessData = [
            'name' => 'Test Restaurant',
            'description' => 'A test restaurant for SEO testing',
            'address' => '123 Test Street, Test City, TC 12345',
            'phone' => '+1-555-123-4567',
            'category' => 'restaurant',
            'rating' => 4.5,
            'review_count' => 150,
            'hours' => '9:00 AM - 10:00 PM',
            'price_range' => '$$',
        ];
        
        $this->metaTagsToTest = [
            'title',
            'description', 
            'keywords',
            'og:title',
            'og:description',
            'og:type',
            'og:url',
            'og:image',
            'twitter:card',
            'twitter:title',
            'twitter:description',
        ];
        
        $this->schemaTypes = [
            'LocalBusiness',
            'Restaurant',
            'Review',
            'AggregateRating',
            'PostalAddress',
            'OpeningHoursSpecification',
        ];
    }
    
    /**
     * Test structured data generation for business listings
     */
    public function testStructuredDataGeneration()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test basic business schema
        $schema = $this->generateBusinessSchema($this->sampleBusinessData);
        
        $this->assertIsArray($schema, 'Schema should be an array');
        $this->assertEquals('LocalBusiness', $schema['@type']);
        $this->assertEquals($this->sampleBusinessData['name'], $schema['name']);
        $this->assertArrayHasKey('address', $schema);
        $this->assertArrayHasKey('telephone', $schema);
        
        // Test restaurant-specific schema
        $restaurantSchema = $this->generateRestaurantSchema($this->sampleBusinessData);
        $this->assertEquals('Restaurant', $restaurantSchema['@type']);
        $this->assertArrayHasKey('servesCuisine', $restaurantSchema);
        $this->assertArrayHasKey('priceRange', $restaurantSchema);
        
        // Test review schema
        $reviewSchema = $this->generateReviewSchema($this->sampleBusinessData);
        $this->assertEquals('Review', $reviewSchema['@type']);
        $this->assertArrayHasKey('reviewRating', $reviewSchema);
        $this->assertArrayHasKey('author', $reviewSchema);
        
        // Test aggregate rating schema
        $ratingSchema = $this->generateAggregateRatingSchema($this->sampleBusinessData);
        $this->assertEquals('AggregateRating', $ratingSchema['@type']);
        $this->assertEquals($this->sampleBusinessData['rating'], $ratingSchema['ratingValue']);
        $this->assertEquals($this->sampleBusinessData['review_count'], $ratingSchema['reviewCount']);
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test meta tag generation for SEO
     */
    public function testMetaTagGeneration()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        $metaTags = $this->generateMetaTags($this->sampleBusinessData);
        
        // Test required meta tags
        foreach ($this->metaTagsToTest as $tag) {
            $this->assertArrayHasKey($tag, $metaTags, "Meta tag '$tag' should be present");
            $this->assertNotEmpty($metaTags[$tag], "Meta tag '$tag' should not be empty");
        }
        
        // Test meta tag length limits
        $this->assertLessThanOrEqual(60, strlen($metaTags['title']), 'Title should be under 60 characters');
        $this->assertLessThanOrEqual(160, strlen($metaTags['description']), 'Description should be under 160 characters');
        
        // Test Open Graph tags
        $this->assertStringContainsString('business', $metaTags['og:type']);
        $this->assertStringStartsWith('http', $metaTags['og:url']);
        $this->assertStringStartsWith('http', $metaTags['og:image']);
        
        // Test Twitter Card tags
        $this->assertEquals('summary_large_image', $metaTags['twitter:card']);
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test sitemap generation and validation
     */
    public function testSitemapGeneration()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Generate sitemap
        $sitemap = $this->generateSitemap();
        
        $this->assertIsString($sitemap, 'Sitemap should be a string');
        $this->assertStringContainsString('<?xml', $sitemap, 'Sitemap should be valid XML');
        $this->assertStringContainsString('<urlset', $sitemap, 'Sitemap should contain urlset element');
        
        // Validate XML structure
        $xml = simplexml_load_string($sitemap);
        $this->assertNotFalse($xml, 'Sitemap should be valid XML');
        
        // Test sitemap URLs
        $urls = $xml->xpath('//url/loc');
        $this->assertGreaterThan(0, count($urls), 'Sitemap should contain URLs');
        
        foreach ($urls as $url) {
            $urlString = (string)$url;
            $this->assertStringStartsWith('http', $urlString, 'URLs should be absolute');
            $this->assertStringContainsString('bizdir', $urlString, 'URLs should contain domain');
        }
        
        // Test priority and changefreq
        $priorities = $xml->xpath('//url/priority');
        $changefreqs = $xml->xpath('//url/changefreq');
        
        $this->assertGreaterThan(0, count($priorities), 'Sitemap should contain priorities');
        $this->assertGreaterThan(0, count($changefreqs), 'Sitemap should contain changefreq');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test robots.txt generation
     */
    public function testRobotsTxtGeneration()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        $robotsTxt = $this->generateRobotsTxt();
        
        $this->assertIsString($robotsTxt, 'robots.txt should be a string');
        $this->assertStringContainsString('User-agent:', $robotsTxt, 'robots.txt should contain user-agent');
        $this->assertStringContainsString('Sitemap:', $robotsTxt, 'robots.txt should contain sitemap reference');
        
        // Test basic rules
        $this->assertStringContainsString('Allow: /', $robotsTxt, 'Should allow crawling of root');
        $this->assertStringContainsString('Disallow: /admin', $robotsTxt, 'Should disallow admin areas');
        $this->assertStringContainsString('Disallow: /wp-admin', $robotsTxt, 'Should disallow wp-admin');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test URL structure optimization
     */
    public function testURLStructureOptimization()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        $testCases = [
            'Test Restaurant' => 'test-restaurant',
            'Joe\'s Pizza & Pasta' => 'joes-pizza-pasta',
            'Best Café in Town!' => 'best-cafe-in-town',
            'Multi   Space   Title' => 'multi-space-title',
        ];
        
        foreach ($testCases as $title => $expectedSlug) {
            $slug = $this->generateSEOSlug($title);
            $this->assertEquals($expectedSlug, $slug, "Slug for '$title' should be '$expectedSlug'");
            
            // Test slug characteristics
            $this->assertDoesNotMatchRegularExpression('/[^a-z0-9\-]/', $slug, 'Slug should only contain lowercase letters, numbers, and hyphens');
            $this->assertStringStartsNotWith('-', $slug, 'Slug should not start with hyphen');
            $this->assertStringEndsNotWith('-', $slug, 'Slug should not end with hyphen');
            $this->assertDoesNotMatchRegularExpression('/--+/', $slug, 'Slug should not contain consecutive hyphens');
        }
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test breadcrumb generation
     */
    public function testBreadcrumbGeneration()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        $breadcrumbs = $this->generateBreadcrumbs('/category/restaurants/pizza/joes-pizza');
        
        $this->assertIsArray($breadcrumbs, 'Breadcrumbs should be an array');
        $this->assertGreaterThan(1, count($breadcrumbs), 'Should have multiple breadcrumb items');
        
        // Test breadcrumb structure
        foreach ($breadcrumbs as $breadcrumb) {
            $this->assertArrayHasKey('name', $breadcrumb, 'Breadcrumb should have name');
            $this->assertArrayHasKey('url', $breadcrumb, 'Breadcrumb should have URL');
            $this->assertNotEmpty($breadcrumb['name'], 'Breadcrumb name should not be empty');
        }
        
        // Test first and last items
        $this->assertEquals('Home', $breadcrumbs[0]['name'], 'First breadcrumb should be Home');
        $this->assertEquals('/', $breadcrumbs[0]['url'], 'Home breadcrumb should link to root');
        
        // Test breadcrumb schema
        $schema = $this->generateBreadcrumbSchema($breadcrumbs);
        $this->assertEquals('BreadcrumbList', $schema['@type']);
        $this->assertArrayHasKey('itemListElement', $schema);
        $this->assertIsArray($schema['itemListElement']);
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test canonical URL generation
     */
    public function testCanonicalURLGeneration()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        $testURLs = [
            '/business/test-restaurant',
            '/business/test-restaurant?utm_source=google',
            '/business/test-restaurant?page=2',
            '/business/test-restaurant?sort=rating&page=1',
        ];
        
        foreach ($testURLs as $url) {
            $canonical = $this->generateCanonicalURL($url);
            
            $this->assertStringStartsWith('https://', $canonical, 'Canonical URL should use HTTPS');
            $this->assertStringContainsString('bizdir', $canonical, 'Should contain domain');
            $this->assertStringNotContainsString('utm_', $canonical, 'Should remove UTM parameters');
            $this->assertStringNotContainsString('?page=1', $canonical, 'Should remove default page parameter');
        }
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test image optimization for SEO
     */
    public function testImageSEOOptimization()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        $imageData = [
            'src' => '/uploads/business/test-restaurant.jpg',
            'alt' => '',
            'title' => 'Test Restaurant Interior',
        ];
        
        $optimizedImage = $this->optimizeImageForSEO($imageData, $this->sampleBusinessData);
        
        // Test alt text generation
        $this->assertNotEmpty($optimizedImage['alt'], 'Alt text should be generated');
        $this->assertStringContainsString($this->sampleBusinessData['name'], $optimizedImage['alt']);
        
        // Test responsive images
        $this->assertArrayHasKey('srcset', $optimizedImage, 'Should include srcset for responsive images');
        $this->assertArrayHasKey('sizes', $optimizedImage, 'Should include sizes attribute');
        
        // Test lazy loading
        $this->assertArrayHasKey('loading', $optimizedImage, 'Should include loading attribute');
        $this->assertEquals('lazy', $optimizedImage['loading']);
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test page speed optimization elements
     */
    public function testPageSpeedOptimization()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        // Test critical CSS identification
        $criticalCSS = $this->identifyCriticalCSS();
        $this->assertIsString($criticalCSS, 'Critical CSS should be a string');
        $this->assertStringContainsString('body', $criticalCSS, 'Should contain basic body styles');
        
        // Test resource preloading
        $preloadResources = $this->generatePreloadHeaders();
        $this->assertIsArray($preloadResources, 'Preload resources should be an array');
        
        foreach ($preloadResources as $resource) {
            $this->assertArrayHasKey('href', $resource, 'Resource should have href');
            $this->assertArrayHasKey('as', $resource, 'Resource should have as attribute');
            $this->assertContains($resource['as'], ['style', 'script', 'font', 'image']);
        }
        
        // Test minification
        $cssMinified = $this->minifyCSS('body { margin: 0; padding: 10px; }');
        $this->assertEquals('body{margin:0;padding:10px}', $cssMinified);
        
        $jsMinified = $this->minifyJS('function test() { console.log("test"); }');
        $this->assertStringNotContainsString('  ', $jsMinified, 'JS should be minified');
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    /**
     * Test local SEO optimization
     */
    public function testLocalSEOOptimization()
    {
        $this->startPerformanceTracking(__FUNCTION__);
        
        $localSEOData = $this->generateLocalSEOData($this->sampleBusinessData);
        
        // Test NAP (Name, Address, Phone) consistency
        $this->assertArrayHasKey('name', $localSEOData);
        $this->assertArrayHasKey('address', $localSEOData);
        $this->assertArrayHasKey('phone', $localSEOData);
        
        // Test local schema markup
        $localBusinessSchema = $localSEOData['schema'];
        $this->assertEquals('LocalBusiness', $localBusinessSchema['@type']);
        $this->assertArrayHasKey('geo', $localBusinessSchema);
        $this->assertArrayHasKey('areaServed', $localBusinessSchema);
        
        // Test opening hours format
        $this->assertArrayHasKey('openingHours', $localBusinessSchema);
        $this->assertIsArray($localBusinessSchema['openingHours']);
        
        // Test review schema
        if (isset($localBusinessSchema['review'])) {
            foreach ($localBusinessSchema['review'] as $review) {
                $this->assertEquals('Review', $review['@type']);
                $this->assertArrayHasKey('reviewRating', $review);
                $this->assertArrayHasKey('author', $review);
            }
        }
        
        $this->endPerformanceTracking(__FUNCTION__);
    }
    
    // Helper methods for generating SEO data
    
    private function generateBusinessSchema($businessData)
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $businessData['name'],
            'description' => $businessData['description'],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $businessData['address'],
            ],
            'telephone' => $businessData['phone'],
            'url' => 'https://bizdir.local/business/' . $this->generateSEOSlug($businessData['name']),
        ];
    }
    
    private function generateRestaurantSchema($businessData)
    {
        $schema = $this->generateBusinessSchema($businessData);
        $schema['@type'] = 'Restaurant';
        $schema['servesCuisine'] = $businessData['category'];
        $schema['priceRange'] = $businessData['price_range'];
        return $schema;
    }
    
    private function generateReviewSchema($businessData)
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'itemReviewed' => [
                '@type' => 'LocalBusiness',
                'name' => $businessData['name'],
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $businessData['rating'],
                'bestRating' => 5,
            ],
            'author' => [
                '@type' => 'Person',
                'name' => 'Test Reviewer',
            ],
        ];
    }
    
    private function generateAggregateRatingSchema($businessData)
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'AggregateRating',
            'ratingValue' => $businessData['rating'],
            'reviewCount' => $businessData['review_count'],
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }
    
    private function generateMetaTags($businessData)
    {
        return [
            'title' => $businessData['name'] . ' - Local Business Directory',
            'description' => $businessData['description'] . ' Find reviews, hours, and contact info.',
            'keywords' => $businessData['category'] . ', local business, directory',
            'og:title' => $businessData['name'],
            'og:description' => $businessData['description'],
            'og:type' => 'business.business',
            'og:url' => 'https://bizdir.local/business/' . $this->generateSEOSlug($businessData['name']),
            'og:image' => 'https://bizdir.local/images/business-default.jpg',
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $businessData['name'],
            'twitter:description' => substr($businessData['description'], 0, 120),
        ];
    }
    
    private function generateSitemap()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://bizdir.local/</loc>
    <priority>1.0</priority>
    <changefreq>daily</changefreq>
  </url>
  <url>
    <loc>https://bizdir.local/businesses</loc>
    <priority>0.8</priority>
    <changefreq>weekly</changefreq>
  </url>
</urlset>';
    }
    
    private function generateRobotsTxt()
    {
        return "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /wp-admin\nSitemap: https://bizdir.local/sitemap.xml";
    }
    
    private function generateSEOSlug($title)
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
        $slug = preg_replace('/\s+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
    
    private function generateBreadcrumbs($path)
    {
        $parts = explode('/', trim($path, '/'));
        $breadcrumbs = [['name' => 'Home', 'url' => '/']];
        
        $currentPath = '';
        foreach ($parts as $part) {
            if (empty($part)) continue;
            $currentPath .= '/' . $part;
            $breadcrumbs[] = [
                'name' => ucwords(str_replace('-', ' ', $part)),
                'url' => $currentPath,
            ];
        }
        
        return $breadcrumbs;
    }
    
    private function generateBreadcrumbSchema($breadcrumbs)
    {
        $items = [];
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['name'],
                'item' => 'https://bizdir.local' . $breadcrumb['url'],
            ];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }
    
    private function generateCanonicalURL($url)
    {
        $parsedUrl = parse_url($url);
        $canonical = 'https://bizdir.local' . $parsedUrl['path'];
        
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $params);
            
            // Remove UTM parameters
            $params = array_filter($params, function($key) {
                return !str_starts_with($key, 'utm_');
            }, ARRAY_FILTER_USE_KEY);
            
            // Remove default page parameter
            if (isset($params['page']) && $params['page'] == '1') {
                unset($params['page']);
            }
            
            if (!empty($params)) {
                $canonical .= '?' . http_build_query($params);
            }
        }
        
        return $canonical;
    }
    
    private function optimizeImageForSEO($imageData, $businessData)
    {
        $optimized = $imageData;
        
        // Generate alt text if empty
        if (empty($optimized['alt'])) {
            $optimized['alt'] = $businessData['name'] . ' - ' . $businessData['category'];
        }
        
        // Add responsive images
        $optimized['srcset'] = $imageData['src'] . ' 1x, ' . str_replace('.jpg', '@2x.jpg', $imageData['src']) . ' 2x';
        $optimized['sizes'] = '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw';
        $optimized['loading'] = 'lazy';
        
        return $optimized;
    }
    
    private function identifyCriticalCSS()
    {
        return 'body{margin:0;font-family:Arial,sans-serif}.header{background:#333;color:white}';
    }
    
    private function generatePreloadHeaders()
    {
        return [
            ['href' => '/css/critical.css', 'as' => 'style'],
            ['href' => '/js/main.js', 'as' => 'script'],
            ['href' => '/fonts/main.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => true],
        ];
    }
    
    private function minifyCSS($css)
    {
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace([' {', '{ ', ' }', '; ', ': '], ['{', '{', '}', ';', ':'], $css);
        return trim($css);
    }
    
    private function minifyJS($js)
    {
        return preg_replace('/\s+/', ' ', $js);
    }
    
    private function generateLocalSEOData($businessData)
    {
        return [
            'name' => $businessData['name'],
            'address' => $businessData['address'],
            'phone' => $businessData['phone'],
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'LocalBusiness',
                'name' => $businessData['name'],
                'address' => $businessData['address'],
                'telephone' => $businessData['phone'],
                'geo' => [
                    '@type' => 'GeoCoordinates',
                    'latitude' => 40.7128,
                    'longitude' => -74.0060,
                ],
                'areaServed' => 'New York, NY',
                'openingHours' => ['Mo-Su 09:00-22:00'],
            ],
        ];
    }
}
