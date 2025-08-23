<?php
/**
 * Mock WordPress Unit Test Factory
 * 
 * Industry-standard factory pattern implementation for WordPress test objects
 * Following OWASP A04:2021 - Insecure Design prevention guidelines
 * 
 * @package BizDir
 * @subpackage Tests\Mocks
 * @since 1.0.0
 */

if (!class_exists('WP_UnitTest_Factory_Mock')) {
    /**
     * WordPress Test Factory Mock
     * 
     * Provides factory methods for creating test objects
     * with security validation and data integrity checks
     */
    class WP_UnitTest_Factory_Mock {
        
        /**
         * User factory
         * 
         * @var WP_UnitTest_Factory_For_User_Mock
         */
        public $user;
        
        /**
         * Post factory
         * 
         * @var WP_UnitTest_Factory_For_Post_Mock
         */
        public $post;
        
        /**
         * Comment factory
         * 
         * @var WP_UnitTest_Factory_For_Comment_Mock
         */
        public $comment;
        
        /**
         * Term factory
         * 
         * @var WP_UnitTest_Factory_For_Term_Mock
         */
        public $term;
        
        /**
         * Attachment factory
         * 
         * @var WP_UnitTest_Factory_For_Attachment_Mock
         */
        public $attachment;
        
        /**
         * Security: Track created objects for cleanup
         * 
         * @var array
         */
        private $created_objects = [];
        
        /**
         * Constructor
         */
        public function __construct() {
            $this->user = new WP_UnitTest_Factory_For_User_Mock($this);
            $this->post = new WP_UnitTest_Factory_For_Post_Mock($this);
            $this->comment = new WP_UnitTest_Factory_For_Comment_Mock($this);
            $this->term = new WP_UnitTest_Factory_For_Term_Mock($this);
            $this->attachment = new WP_UnitTest_Factory_For_Attachment_Mock($this);
        }
        
        /**
         * Track created object for cleanup
         * 
         * @param string $type Object type
         * @param int $id Object ID
         * @return void
         */
        public function track_object(string $type, int $id): void {
            $this->created_objects[$type][] = $id;
        }
        
        /**
         * Get tracked objects by type
         * 
         * @param string $type Object type
         * @return array Object IDs
         */
        public function get_tracked_objects(string $type): array {
            return $this->created_objects[$type] ?? [];
        }
        
        /**
         * Clean up all created objects
         * 
         * @return void
         */
        public function cleanup(): void {
            $this->created_objects = [];
        }
    }
}

if (!class_exists('WP_UnitTest_Factory_For_User_Mock')) {
    /**
     * User Factory Mock
     */
    class WP_UnitTest_Factory_For_User_Mock {
        
        /**
         * Parent factory
         * 
         * @var WP_UnitTest_Factory_Mock
         */
        private $factory;
        
        /**
         * Counter for unique IDs
         * 
         * @var int
         */
        private static $id_counter = 1;
        
        /**
         * Constructor
         * 
         * @param WP_UnitTest_Factory_Mock $factory Parent factory
         */
        public function __construct(WP_UnitTest_Factory_Mock $factory) {
            $this->factory = $factory;
        }
        
        /**
         * Create a test user
         * 
         * @param array $args User arguments
         * @return int User ID
         */
        public function create(array $args = []): int {
            $user_id = self::$id_counter++;
            
            $defaults = [
                'user_login' => 'testuser' . $user_id,
                'user_email' => 'testuser' . $user_id . '@test.local',
                'user_pass' => 'test_password_' . wp_generate_password(12, true, true),
                'user_nicename' => 'testuser-' . $user_id,
                'user_registered' => current_time('mysql'),
                'user_activation_key' => '',
                'user_status' => 0,
                'display_name' => 'Test User ' . $user_id,
                'role' => 'subscriber'
            ];
            
            $user_data = array_merge($defaults, $args);
            
            // Security: Validate email format (OWASP A03:2021 - Injection)
            if (!filter_var($user_data['user_email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email format provided');
            }
            
            // Security: Validate user login (alphanumeric + underscore only)
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $user_data['user_login'])) {
                throw new InvalidArgumentException('Invalid user login format');
            }
            
            // Store user in both global databases for compatibility
            global $wp_users, $mock_users_db;
            if (!isset($wp_users)) {
                $wp_users = [];
            }
            if (!isset($mock_users_db)) {
                $mock_users_db = [];
            }
            
            // Store in legacy global
            $wp_users[$user_id] = (object) array_merge($user_data, ['ID' => $user_id]);
            
            // Store in new role-aware global for permission testing
            $mock_users_db[$user_id] = $user_data;
            
            $this->factory->track_object('user', $user_id);
            
            return $user_id;
        }
        
        /**
         * Create and return user object
         * 
         * @param array $args User arguments
         * @return object User object
         */
        public function create_and_get(array $args = []): object {
            $user_id = $this->create($args);
            global $wp_users;
            return $wp_users[$user_id];
        }
    }
}

if (!class_exists('WP_UnitTest_Factory_For_Post_Mock')) {
    /**
     * Post Factory Mock
     */
    class WP_UnitTest_Factory_For_Post_Mock {
        
        /**
         * Parent factory
         * 
         * @var WP_UnitTest_Factory_Mock
         */
        private $factory;
        
        /**
         * Counter for unique IDs
         * 
         * @var int
         */
        private static $id_counter = 1;
        
        /**
         * Constructor
         * 
         * @param WP_UnitTest_Factory_Mock $factory Parent factory
         */
        public function __construct(WP_UnitTest_Factory_Mock $factory) {
            $this->factory = $factory;
        }
        
        /**
         * Create a test post
         * 
         * @param array $args Post arguments
         * @return int Post ID
         */
        public function create(array $args = []): int {
            $post_id = self::$id_counter++;
            
            $defaults = [
                'post_title' => 'Test Post ' . $post_id,
                'post_content' => 'This is test content for post ' . $post_id,
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_author' => 1,
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
                'post_name' => 'test-post-' . $post_id,
                'post_excerpt' => 'Test excerpt for post ' . $post_id,
                'comment_status' => 'open',
                'ping_status' => 'open',
                'comment_count' => 0
            ];
            
            $post_data = array_merge($defaults, $args);
            
            // Security: Sanitize post content (OWASP A03:2021 - Injection)
            $post_data['post_title'] = wp_strip_all_tags($post_data['post_title']);
            $post_data['post_content'] = wp_kses_post($post_data['post_content']);
            $post_data['post_excerpt'] = wp_strip_all_tags($post_data['post_excerpt']);
            
            global $wp_posts;
            if (!isset($wp_posts)) {
                $wp_posts = [];
            }
            
            $wp_posts[$post_id] = (object) array_merge($post_data, ['ID' => $post_id]);
            
            $this->factory->track_object('post', $post_id);
            
            return $post_id;
        }
        
        /**
         * Create and return post object
         * 
         * @param array $args Post arguments
         * @return object Post object
         */
        public function create_and_get(array $args = []): object {
            $post_id = $this->create($args);
            global $wp_posts;
            return $wp_posts[$post_id];
        }
    }
}

if (!class_exists('WP_UnitTest_Factory_For_Comment_Mock')) {
    /**
     * Comment Factory Mock
     */
    class WP_UnitTest_Factory_For_Comment_Mock {
        
        /**
         * Parent factory
         * 
         * @var WP_UnitTest_Factory_Mock
         */
        private $factory;
        
        /**
         * Counter for unique IDs
         * 
         * @var int
         */
        private static $id_counter = 1;
        
        /**
         * Constructor
         * 
         * @param WP_UnitTest_Factory_Mock $factory Parent factory
         */
        public function __construct(WP_UnitTest_Factory_Mock $factory) {
            $this->factory = $factory;
        }
        
        /**
         * Create a test comment
         * 
         * @param array $args Comment arguments
         * @return int Comment ID
         */
        public function create(array $args = []): int {
            $comment_id = self::$id_counter++;
            
            $defaults = [
                'comment_post_ID' => 1,
                'comment_author' => 'Test Commenter ' . $comment_id,
                'comment_author_email' => 'commenter' . $comment_id . '@test.local',
                'comment_author_url' => 'http://test.local',
                'comment_content' => 'This is test comment ' . $comment_id,
                'comment_approved' => 1,
                'comment_type' => '',
                'comment_parent' => 0,
                'user_id' => 0,
                'comment_date' => current_time('mysql'),
                'comment_date_gmt' => current_time('mysql', 1)
            ];
            
            $comment_data = array_merge($defaults, $args);
            
            // Security: Validate and sanitize comment data
            if (isset($comment_data['comment_author_email']) && 
                !filter_var($comment_data['comment_author_email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid comment author email format');
            }
            
            $comment_data['comment_content'] = wp_kses_post($comment_data['comment_content']);
            $comment_data['comment_author'] = wp_strip_all_tags($comment_data['comment_author']);
            
            global $wp_comments;
            if (!isset($wp_comments)) {
                $wp_comments = [];
            }
            
            $wp_comments[$comment_id] = (object) array_merge($comment_data, ['comment_ID' => $comment_id]);
            
            $this->factory->track_object('comment', $comment_id);
            
            return $comment_id;
        }
        
        /**
         * Create and return comment object
         * 
         * @param array $args Comment arguments
         * @return object Comment object
         */
        public function create_and_get(array $args = []): object {
            $comment_id = $this->create($args);
            global $wp_comments;
            return $wp_comments[$comment_id];
        }
    }
}

if (!class_exists('WP_UnitTest_Factory_For_Term_Mock')) {
    /**
     * Term Factory Mock
     */
    class WP_UnitTest_Factory_For_Term_Mock {
        
        /**
         * Parent factory
         * 
         * @var WP_UnitTest_Factory_Mock
         */
        private $factory;
        
        /**
         * Counter for unique IDs
         * 
         * @var int
         */
        private static $id_counter = 1;
        
        /**
         * Constructor
         * 
         * @param WP_UnitTest_Factory_Mock $factory Parent factory
         */
        public function __construct(WP_UnitTest_Factory_Mock $factory) {
            $this->factory = $factory;
        }
        
        /**
         * Create a test term
         * 
         * @param array $args Term arguments
         * @return int Term ID
         */
        public function create(array $args = []): int {
            $term_id = self::$id_counter++;
            
            $defaults = [
                'name' => 'Test Term ' . $term_id,
                'slug' => 'test-term-' . $term_id,
                'taxonomy' => 'category',
                'description' => 'Test term description ' . $term_id,
                'parent' => 0,
                'count' => 0
            ];
            
            $term_data = array_merge($defaults, $args);
            
            // Security: Sanitize term data
            $term_data['name'] = wp_strip_all_tags($term_data['name']);
            $term_data['slug'] = sanitize_title($term_data['slug']);
            $term_data['description'] = wp_kses_post($term_data['description']);
            
            global $wp_terms;
            if (!isset($wp_terms)) {
                $wp_terms = [];
            }
            
            $wp_terms[$term_id] = (object) array_merge($term_data, ['term_id' => $term_id]);
            
            $this->factory->track_object('term', $term_id);
            
            return $term_id;
        }
        
        /**
         * Create and return term object
         * 
         * @param array $args Term arguments
         * @return object Term object
         */
        public function create_and_get(array $args = []): object {
            $term_id = $this->create($args);
            global $wp_terms;
            return $wp_terms[$term_id];
        }
    }
}

if (!class_exists('WP_UnitTest_Factory_For_Attachment_Mock')) {
    /**
     * Attachment Factory Mock
     */
    class WP_UnitTest_Factory_For_Attachment_Mock {
        
        /**
         * Parent factory
         * 
         * @var WP_UnitTest_Factory_Mock
         */
        private $factory;
        
        /**
         * Counter for unique IDs
         * 
         * @var int
         */
        private static $id_counter = 1;
        
        /**
         * Constructor
         * 
         * @param WP_UnitTest_Factory_Mock $factory Parent factory
         */
        public function __construct(WP_UnitTest_Factory_Mock $factory) {
            $this->factory = $factory;
        }
        
        /**
         * Create a test attachment
         * 
         * @param array $args Attachment arguments
         * @return int Attachment ID
         */
        public function create(array $args = []): int {
            $attachment_id = self::$id_counter++;
            
            $defaults = [
                'post_title' => 'Test Attachment ' . $attachment_id,
                'post_content' => '',
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'post_author' => 1,
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
                'post_name' => 'test-attachment-' . $attachment_id,
                'post_parent' => 0,
                'guid' => 'http://test.local/wp-content/uploads/test-file-' . $attachment_id . '.jpg',
                'post_mime_type' => 'image/jpeg'
            ];
            
            $attachment_data = array_merge($defaults, $args);
            
            // Security: Validate MIME type
            $allowed_mime_types = get_allowed_mime_types();
            if (!in_array($attachment_data['post_mime_type'], $allowed_mime_types)) {
                throw new InvalidArgumentException('Invalid MIME type for attachment');
            }
            
            global $wp_posts;
            if (!isset($wp_posts)) {
                $wp_posts = [];
            }
            
            $wp_posts[$attachment_id] = (object) array_merge($attachment_data, ['ID' => $attachment_id]);
            
            $this->factory->track_object('attachment', $attachment_id);
            
            return $attachment_id;
        }
        
        /**
         * Create and return attachment object
         * 
         * @param array $args Attachment arguments
         * @return object Attachment object
         */
        public function create_and_get(array $args = []): object {
            $attachment_id = $this->create($args);
            global $wp_posts;
            return $wp_posts[$attachment_id];
        }
    }
}
