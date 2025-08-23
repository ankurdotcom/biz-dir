<?php
/**
 * Business Directory Theme Functions
 * 
 * @package BizDir
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme setup
 */
function biz_dir_setup() {
    // Make theme available for translation
    load_theme_textdomain('biz-dir', get_template_directory() . '/languages');

    // Add default posts and comments RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages
    add_theme_support('post-thumbnails');
    
    // Add custom image sizes for business listings
    add_image_size('business-thumbnail', 300, 200, true);
    add_image_size('business-featured', 800, 400, true);
    add_image_size('business-gallery', 400, 300, true);

    // Register navigation menus
    register_nav_menus([
        'menu-1' => esc_html__('Primary', 'biz-dir'),
        'footer-menu' => esc_html__('Footer Menu', 'biz-dir'),
    ]);

    // Switch default core markup to output valid HTML5
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);

    // Set up the WordPress core custom background feature
    add_theme_support('custom-background', [
        'default-color' => 'ffffff',
        'default-image' => '',
    ]);

    // Add theme support for selective refresh for widgets
    add_theme_support('customize-selective-refresh-widgets');

    // Add support for core custom logo
    add_theme_support('custom-logo', [
        'height'      => 250,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ]);

    // Add support for responsive embeds
    add_theme_support('responsive-embeds');

    // Add support for block styles
    add_theme_support('wp-block-styles');

    // Add support for wide alignment
    add_theme_support('align-wide');
}
add_action('after_setup_theme', 'biz_dir_setup');

/**
 * Set the content width in pixels, based on the theme's design and stylesheet
 */
function biz_dir_content_width() {
    $GLOBALS['content_width'] = apply_filters('biz_dir_content_width', 1200);
}
add_action('after_setup_theme', 'biz_dir_content_width', 0);

/**
 * Register widget area
 */
function biz_dir_widgets_init() {
    register_sidebar([
        'name'          => esc_html__('Sidebar', 'biz-dir'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Add widgets here.', 'biz-dir'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ]);

    register_sidebar([
        'name'          => esc_html__('Footer 1', 'biz-dir'),
        'id'            => 'footer-1',
        'description'   => esc_html__('Footer widget area 1.', 'biz-dir'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ]);

    register_sidebar([
        'name'          => esc_html__('Footer 2', 'biz-dir'),
        'id'            => 'footer-2',
        'description'   => esc_html__('Footer widget area 2.', 'biz-dir'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ]);

    register_sidebar([
        'name'          => esc_html__('Footer 3', 'biz-dir'),
        'id'            => 'footer-3',
        'description'   => esc_html__('Footer widget area 3.', 'biz-dir'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ]);
}
add_action('widgets_init', 'biz_dir_widgets_init');

/**
 * Enqueue scripts and styles
 */
function biz_dir_scripts() {
    // Enqueue theme stylesheet
    wp_enqueue_style('biz-dir-style', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
    
    // Enqueue theme JavaScript
    wp_enqueue_script('biz-dir-theme', get_template_directory_uri() . '/js/theme.js', ['jquery'], wp_get_theme()->get('Version'), true);
    
    // Localize script for AJAX
    wp_localize_script('biz-dir-theme', 'bizDirTheme', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('biz_dir_theme_nonce'),
        'strings' => [
            'loading' => esc_html__('Loading...', 'biz-dir'),
            'error' => esc_html__('An error occurred. Please try again.', 'biz-dir'),
            'noResults' => esc_html__('No results found.', 'biz-dir'),
        ]
    ]);

    // Enqueue comment reply script on singular posts/pages
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'biz_dir_scripts');

/**
 * Custom template tags for this theme
 */

/**
 * Prints HTML with meta information for the current post-date/time
 */
function biz_dir_posted_on() {
    $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
    if (get_the_time('U') !== get_the_modified_time('U')) {
        $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
    }

    $time_string = sprintf($time_string,
        esc_attr(get_the_date(DATE_W3C)),
        esc_html(get_the_date()),
        esc_attr(get_the_modified_date(DATE_W3C)),
        esc_html(get_the_modified_date())
    );

    $posted_on = sprintf(
        esc_html_x('Posted on %s', 'post date', 'biz-dir'),
        '<a href="' . esc_url(get_permalink()) . '" rel="bookmark">' . $time_string . '</a>'
    );

    echo '<span class="posted-on">' . $posted_on . '</span>';
}

/**
 * Prints HTML with meta information for the current author
 */
function biz_dir_posted_by() {
    $byline = sprintf(
        esc_html_x('by %s', 'post author', 'biz-dir'),
        '<span class="author vcard"><a class="url fn n" href="' . esc_url(get_author_posts_url(get_the_author_meta('ID'))) . '">' . esc_html(get_the_author()) . '</a></span>'
    );

    echo '<span class="byline"> ' . $byline . '</span>';
}

/**
 * Display business rating stars
 */
function biz_dir_display_rating($rating, $show_text = true) {
    $rating = floatval($rating);
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    
    echo '<div class="business-rating">';
    echo '<div class="stars">';
    
    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        echo '<span class="star">★</span>';
    }
    
    // Half star
    if ($half_star) {
        echo '<span class="star half">★</span>';
    }
    
    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        echo '<span class="star empty">☆</span>';
    }
    
    echo '</div>';
    
    if ($show_text) {
        echo '<span class="rating-text">' . number_format($rating, 1) . '</span>';
    }
    
    echo '</div>';
}

/**
 * Display business card for listings
 */
function biz_dir_display_business_card($post_id) {
    $phone = get_post_meta($post_id, '_business_phone', true);
    $address = get_post_meta($post_id, '_business_address', true);
    $email = get_post_meta($post_id, '_business_email', true);
    $website = get_post_meta($post_id, '_business_website', true);
    $hours = get_post_meta($post_id, '_business_hours', true);
    $rating = get_post_meta($post_id, '_business_rating', true);
    $price_range = get_post_meta($post_id, '_business_price_range', true);
    $features = get_post_meta($post_id, '_business_features', true);
    
    $categories = get_the_category($post_id);
    $primary_category = !empty($categories) ? $categories[0] : null;
    ?>
    <div class="business-card" data-business-id="<?php echo esc_attr($post_id); ?>">
        <?php if (has_post_thumbnail($post_id)) : ?>
            <div class="business-image">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                    <?php echo get_the_post_thumbnail($post_id, 'business-thumbnail', ['alt' => get_the_title($post_id)]); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <div class="business-content">
            <div class="business-header">
                <h3 class="business-title">
                    <a href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a>
                </h3>
                
                <?php if ($primary_category) : ?>
                    <span class="business-category">
                        <a href="<?php echo esc_url(get_category_link($primary_category->term_id)); ?>">
                            <?php echo esc_html($primary_category->name); ?>
                        </a>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="business-excerpt">
                <?php echo wp_trim_words(get_the_excerpt($post_id), 20, '...'); ?>
            </div>
            
            <div class="business-meta">
                <?php if ($rating) : ?>
                    <div class="business-rating-small">
                        <?php biz_dir_display_rating($rating, true); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($price_range) : ?>
                    <span class="price-range"><?php echo esc_html($price_range); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="business-contact-preview">
                <?php if ($phone) : ?>
                    <div class="contact-item">
                        <span class="contact-icon">📞</span>
                        <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                    </div>
                <?php endif; ?>
                
                <?php if ($address) : ?>
                    <div class="contact-item">
                        <span class="contact-icon">📍</span>
                        <span><?php echo esc_html(wp_trim_words($address, 6, '...')); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($features) : ?>
                <div class="business-features">
                    <small><?php echo esc_html($features); ?></small>
                </div>
            <?php endif; ?>
            
            <div class="business-actions">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="btn btn-primary btn-small">
                    <?php esc_html_e('View Details', 'biz-dir'); ?>
                </a>
                
                <?php if (!is_user_logged_in()) : ?>
                    <button class="btn btn-outline btn-small login-prompt" data-action="review">
                        <?php esc_html_e('Login to Review', 'biz-dir'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Get category icon based on category slug
 */
function biz_dir_get_category_icon($category_slug) {
    $icons = [
        'restaurants-food' => '🍽️',
        'cloud-kitchen' => '👨‍🍳',
        'street-food' => '🍜',
        'fine-dining' => '🥂',
        'fast-food' => '🍔',
        'catering-services' => '🎉',
        'health-fitness' => '💪',
        'gym-fitness' => '🏋️',
        'yoga-center' => '🧘',
        'medical-clinic' => '🏥',
        'pharmacy' => '💊',
        'dental-clinic' => '🦷',
        'education-training' => '📚',
        'tuition-teacher' => '👨‍🏫',
        'coaching-center' => '📖',
        'computer-training' => '💻',
        'language-classes' => '🗣️',
        'music-classes' => '🎵',
        'home-services' => '🏠',
        'electrician' => '⚡',
        'carpenter' => '🔨',
        'plumber' => '🔧',
        'house-cleaning' => '🧹',
        'security-service' => '🛡️',
        'kabadi-wala' => '♻️',
        'shopping-retail' => '🛍️',
        'furniture-store' => '🛋️',
        'home-decor' => '🎨',
        'electronics-shop' => '📱',
        'sabzi-wala' => '🥬',
        'grocery-store' => '🛒',
        'professional-services' => '💼',
        'automotive' => '🚗',
        'beauty-wellness' => '💄',
        'travel-tourism' => '✈️',
        'real-estate' => '🏘️'
    ];
    
    return isset($icons[$category_slug]) ? $icons[$category_slug] : '🏪';
}

/**
 * Display business contact information
 */
function biz_dir_display_contact_info($business_id) {
    global $wpdb;
    
    $business = $wpdb->get_row($wpdb->prepare(
        "SELECT contact_info FROM {$wpdb->prefix}biz_businesses WHERE id = %d",
        $business_id
    ));
    
    if (!$business || !$business->contact_info) {
        return;
    }
    
    $contact_info = json_decode($business->contact_info, true);
    
    echo '<div class="business-contact">';
    
    if (!empty($contact_info['phone'])) {
        echo '<div class="contact-item">';
        echo '<span class="contact-icon">📞</span>';
        echo '<a href="tel:' . esc_attr($contact_info['phone']) . '">' . esc_html($contact_info['phone']) . '</a>';
        echo '</div>';
    }
    
    if (!empty($contact_info['email'])) {
        echo '<div class="contact-item">';
        echo '<span class="contact-icon">✉️</span>';
        echo '<a href="mailto:' . esc_attr($contact_info['email']) . '">' . esc_html($contact_info['email']) . '</a>';
        echo '</div>';
    }
    
    if (!empty($contact_info['website'])) {
        echo '<div class="contact-item">';
        echo '<span class="contact-icon">🌐</span>';
        echo '<a href="' . esc_url($contact_info['website']) . '" target="_blank" rel="noopener">' . esc_html($contact_info['website']) . '</a>';
        echo '</div>';
    }
    
    if (!empty($contact_info['address'])) {
        echo '<div class="contact-item">';
        echo '<span class="contact-icon">📍</span>';
        echo '<span>' . esc_html($contact_info['address']) . '</span>';
        echo '</div>';
    }
    
    echo '</div>';
}

/**
 * Display business tags
 */
function biz_dir_display_business_tags($business_id) {
    global $wpdb;
    
    $tags = $wpdb->get_results($wpdb->prepare(
        "SELECT tag, weight FROM {$wpdb->prefix}biz_tags WHERE business_id = %d ORDER BY weight DESC LIMIT 10",
        $business_id
    ));
    
    if (!$tags) {
        return;
    }
    
    echo '<div class="business-tags">';
    echo '<h4>' . esc_html__('Tags', 'biz-dir') . '</h4>';
    echo '<div class="tag-cloud">';
    
    foreach ($tags as $tag) {
        $weight_class = '';
        if ($tag->weight > 0.8) {
            $weight_class = 'tag-large';
        } elseif ($tag->weight > 0.5) {
            $weight_class = 'tag-medium';
        } else {
            $weight_class = 'tag-small';
        }
        
        echo '<span class="business-tag ' . esc_attr($weight_class) . '">' . esc_html($tag->tag) . '</span>';
    }
    
    echo '</div>';
    echo '</div>';
}

/**
 * Get business location info
 */
function biz_dir_get_business_location($business_id) {
    global $wpdb;
    
    $location = $wpdb->get_row($wpdb->prepare(
        "SELECT t.name as town_name, t.slug as town_slug, t.region 
         FROM {$wpdb->prefix}biz_businesses b
         JOIN {$wpdb->prefix}biz_towns t ON b.town_id = t.id
         WHERE b.id = %d",
        $business_id
    ));
    
    return $location;
}

/**
 * Display breadcrumbs
 */
function biz_dir_breadcrumbs() {
    if (is_front_page()) {
        return;
    }
    
    echo '<nav class="breadcrumbs" aria-label="Breadcrumb">';
    echo '<ol>';
    
    // Home link
    echo '<li><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'biz-dir') . '</a></li>';
    
    if (is_single() && get_post_type() === 'business_listing') {
        // Business listing breadcrumb
        $business_id = get_the_ID();
        $location = biz_dir_get_business_location($business_id);
        
        if ($location) {
            echo '<li><a href="' . esc_url(home_url('/' . $location->town_slug . '/')) . '">' . esc_html($location->town_name) . '</a></li>';
        }
        
        echo '<li aria-current="page">' . esc_html(get_the_title()) . '</li>';
    } elseif (is_search()) {
        echo '<li aria-current="page">' . esc_html__('Search Results', 'biz-dir') . '</li>';
    } elseif (is_404()) {
        echo '<li aria-current="page">' . esc_html__('404 Error', 'biz-dir') . '</li>';
    } else {
        echo '<li aria-current="page">' . esc_html(get_the_title()) . '</li>';
    }
    
    echo '</ol>';
    echo '</nav>';
}

/**
 * Custom pagination
 */
function biz_dir_pagination() {
    $pagination = paginate_links([
        'type' => 'array',
        'prev_text' => esc_html__('Previous', 'biz-dir'),
        'next_text' => esc_html__('Next', 'biz-dir'),
    ]);
    
    if ($pagination) {
        echo '<nav class="pagination" aria-label="Pagination">';
        echo '<ul>';
        foreach ($pagination as $page) {
            echo '<li>' . $page . '</li>';
        }
        echo '</ul>';
        echo '</nav>';
    }
}

/**
 * Add custom classes to business listings
 */
function biz_dir_business_listing_classes($classes) {
    if (is_singular('business_listing')) {
        global $wpdb;
        
        $business_id = get_the_ID();
        $is_sponsored = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}biz_businesses 
             WHERE id = %d AND is_sponsored = 1 AND sponsored_until > NOW()",
            $business_id
        ));
        
        if ($is_sponsored) {
            $classes[] = 'sponsored-business';
        }
    }
    
    return $classes;
}
add_filter('body_class', 'biz_dir_business_listing_classes');

/**
 * Customize excerpt length
 */
function biz_dir_excerpt_length($length) {
    return 25;
}
add_filter('excerpt_length', 'biz_dir_excerpt_length');

/**
 * Customize excerpt more text
 */
function biz_dir_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'biz_dir_excerpt_more');

/**
 * Add schema.org markup for businesses
 */
function biz_dir_business_schema() {
    if (!is_singular('business_listing')) {
        return;
    }
    
    global $wpdb;
    $business_id = get_the_ID();
    
    $business = $wpdb->get_row($wpdb->prepare(
        "SELECT b.*, t.name as town_name 
         FROM {$wpdb->prefix}biz_businesses b
         LEFT JOIN {$wpdb->prefix}biz_towns t ON b.town_id = t.id
         WHERE b.id = %d",
        $business_id
    ));
    
    if (!$business) {
        return;
    }
    
    $contact_info = json_decode($business->contact_info, true) ?: [];
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => get_the_title(),
        'description' => get_the_excerpt(),
        'url' => get_permalink(),
    ];
    
    if ($business->town_name) {
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'addressLocality' => $business->town_name,
        ];
        
        if (!empty($contact_info['address'])) {
            $schema['address']['streetAddress'] = $contact_info['address'];
        }
    }
    
    if (!empty($contact_info['phone'])) {
        $schema['telephone'] = $contact_info['phone'];
    }
    
    if (!empty($contact_info['email'])) {
        $schema['email'] = $contact_info['email'];
    }
    
    if (!empty($contact_info['website'])) {
        $schema['url'] = $contact_info['website'];
    }
    
    // Get average rating
    $avg_rating = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(rating) FROM {$wpdb->prefix}biz_reviews WHERE business_id = %d AND status = 'approved'",
        $business_id
    ));
    
    $review_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}biz_reviews WHERE business_id = %d AND status = 'approved'",
        $business_id
    ));
    
    if ($avg_rating && $review_count) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format($avg_rating, 1),
            'reviewCount' => intval($review_count),
            'bestRating' => '5',
            'worstRating' => '1'
        ];
    }
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
}
add_action('wp_head', 'biz_dir_business_schema');

/**
 * Security enhancements
 */

// Remove WordPress version from head
remove_action('wp_head', 'wp_generator');

// Remove Really Simple Discovery link
remove_action('wp_head', 'rsd_link');

// Remove Windows Live Writer manifest link
remove_action('wp_head', 'wlwmanifest_link');

// Disable XML-RPC
add_filter('xmlrpc_enabled', '__return_false');

// Hide login errors
function biz_dir_login_errors() {
    return esc_html__('Login failed. Please check your credentials.', 'biz-dir');
}
add_filter('login_errors', 'biz_dir_login_errors');

/**
 * Performance optimizations
 */

// Remove query strings from static resources
function biz_dir_remove_query_strings($src) {
    $parts = explode('?', $src);
    return $parts[0];
}
add_filter('script_loader_src', 'biz_dir_remove_query_strings', 15, 1);
add_filter('style_loader_src', 'biz_dir_remove_query_strings', 15, 1);

// Disable emojis for performance
function biz_dir_disable_emojis() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'biz_dir_disable_emojis');

/**
 * Custom AJAX handlers
 */

// AJAX search for businesses
function biz_dir_ajax_search() {
    check_ajax_referer('biz_dir_theme_nonce', 'nonce');
    
    $query = sanitize_text_field($_POST['query']);
    $town = sanitize_text_field($_POST['town']);
    $category = sanitize_text_field($_POST['category']);
    
    global $wpdb;
    
    $where_clauses = ["b.status = 'active'"];
    $params = [];
    
    if ($query) {
        $where_clauses[] = "(b.name LIKE %s OR b.description LIKE %s)";
        $params[] = '%' . $query . '%';
        $params[] = '%' . $query . '%';
    }
    
    if ($town) {
        $where_clauses[] = "t.slug = %s";
        $params[] = $town;
    }
    
    if ($category) {
        $where_clauses[] = "b.category = %s";
        $params[] = $category;
    }
    
    $where_sql = implode(' AND ', $where_clauses);
    
    $businesses = $wpdb->get_results($wpdb->prepare(
        "SELECT b.*, t.name as town_name 
         FROM {$wpdb->prefix}biz_businesses b
         LEFT JOIN {$wpdb->prefix}biz_towns t ON b.town_id = t.id
         WHERE {$where_sql}
         ORDER BY b.is_sponsored DESC, b.created_at DESC
         LIMIT 20",
        $params
    ));
    
    wp_send_json_success($businesses);
}
add_action('wp_ajax_biz_dir_search', 'biz_dir_ajax_search');
add_action('wp_ajax_nopriv_biz_dir_search', 'biz_dir_ajax_search');

/**
 * Include required files
 */
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file if Jetpack is active
 */
if (defined('JETPACK__VERSION')) {
    require get_template_directory() . '/inc/jetpack.php';
}