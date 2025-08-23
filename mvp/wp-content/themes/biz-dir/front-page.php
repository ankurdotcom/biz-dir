<?php
/**
 * Front Page Template - Business Directory Homepage
 * 
 * Displays all businesses publicly without login requirement
 *
 * @package BizDir
 */

get_header();
?>

<main id="primary" class="site-main business-directory-home">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title"><?php esc_html_e('Discover Local Businesses', 'biz-dir'); ?></h1>
                <p class="hero-description"><?php esc_html_e('Find the best local businesses in your area. Browse categories, read reviews, and connect with quality service providers.', 'biz-dir'); ?></p>
                
                <!-- Search Form -->
                <div class="business-search-form">
                    <form id="businessSearchForm" class="search-form">
                        <div class="search-inputs">
                            <input type="text" id="businessQuery" name="query" placeholder="<?php esc_attr_e('Search businesses...', 'biz-dir'); ?>" class="search-input">
                            <select id="businessCategory" name="category" class="search-select">
                                <option value=""><?php esc_html_e('All Categories', 'biz-dir'); ?></option>
                                <?php
                                // Get all categories
                                $categories = get_categories([
                                    'hide_empty' => false,
                                    'orderby' => 'name',
                                    'order' => 'ASC'
                                ]);
                                
                                foreach ($categories as $category) {
                                    if ($category->slug !== 'uncategorized') {
                                        echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <button type="submit" class="search-button">
                                <span class="search-icon">🔍</span>
                                <?php esc_html_e('Search', 'biz-dir'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Categories -->
    <section class="featured-categories">
        <div class="container">
            <h2 class="section-title"><?php esc_html_e('Browse by Category', 'biz-dir'); ?></h2>
            <div class="categories-grid">
                <?php
                // Get categories with business count
                $featured_categories = get_categories([
                    'hide_empty' => true,
                    'number' => 8,
                    'orderby' => 'count',
                    'order' => 'DESC'
                ]);
                
                foreach ($featured_categories as $category) {
                    if ($category->slug === 'uncategorized') continue;
                    
                    $category_url = get_category_link($category->term_id);
                    ?>
                    <div class="category-card">
                        <a href="<?php echo esc_url($category_url); ?>" class="category-link">
                            <div class="category-icon">
                                <?php echo biz_dir_get_category_icon($category->slug); ?>
                            </div>
                            <h3 class="category-name"><?php echo esc_html($category->name); ?></h3>
                            <span class="category-count"><?php echo sprintf(_n('%d business', '%d businesses', $category->count, 'biz-dir'), $category->count); ?></span>
                        </a>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Latest Businesses -->
    <section class="latest-businesses">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php esc_html_e('Latest Business Listings', 'biz-dir'); ?></h2>
                <a href="<?php echo esc_url(home_url('/all-businesses/')); ?>" class="view-all-link">
                    <?php esc_html_e('View All Businesses', 'biz-dir'); ?> →
                </a>
            </div>
            
            <div class="businesses-grid" id="businessesGrid">
                <?php
                // Get latest businesses from posts
                $latest_businesses = new WP_Query([
                    'post_type' => 'post',
                    'posts_per_page' => 8,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'meta_query' => [
                        [
                            'key' => '_business_phone',
                            'compare' => 'EXISTS'
                        ]
                    ]
                ]);
                
                if ($latest_businesses->have_posts()) :
                    while ($latest_businesses->have_posts()) : $latest_businesses->the_post();
                        biz_dir_display_business_card(get_the_ID());
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <div class="no-businesses">
                        <p><?php esc_html_e('No businesses found. Be the first to list your business!', 'biz-dir'); ?></p>
                        <?php if (!is_user_logged_in()) : ?>
                            <div class="registration-prompt">
                                <h3><?php esc_html_e('Want to list your business?', 'biz-dir'); ?></h3>
                                <p><?php esc_html_e('Register now to add your business listing and connect with customers.', 'biz-dir'); ?></p>
                                <div class="auth-buttons">
                                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn btn-primary">
                                        <?php esc_html_e('Register Now', 'biz-dir'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(wp_login_url()); ?>" class="btn btn-secondary">
                                        <?php esc_html_e('Login', 'biz-dir'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                endif;
                ?>
            </div>
        </div>
    </section>

    <!-- Registration Call-to-Action (for non-logged-in users) -->
    <?php if (!is_user_logged_in()) : ?>
    <section class="registration-cta">
        <div class="container">
            <div class="cta-content">
                <h2><?php esc_html_e('Join Our Business Community', 'biz-dir'); ?></h2>
                <p><?php esc_html_e('Register to add reviews, save favorite businesses, and list your own business.', 'biz-dir'); ?></p>
                <div class="cta-benefits">
                    <div class="benefit">
                        <span class="benefit-icon">⭐</span>
                        <span><?php esc_html_e('Write Reviews', 'biz-dir'); ?></span>
                    </div>
                    <div class="benefit">
                        <span class="benefit-icon">💼</span>
                        <span><?php esc_html_e('List Your Business', 'biz-dir'); ?></span>
                    </div>
                    <div class="benefit">
                        <span class="benefit-icon">❤️</span>
                        <span><?php esc_html_e('Save Favorites', 'biz-dir'); ?></span>
                    </div>
                    <div class="benefit">
                        <span class="benefit-icon">📈</span>
                        <span><?php esc_html_e('Business Analytics', 'biz-dir'); ?></span>
                    </div>
                </div>
                <div class="cta-buttons">
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn btn-primary btn-large">
                        <?php esc_html_e('Get Started - Free Registration', 'biz-dir'); ?>
                    </a>
                    <a href="<?php echo esc_url(wp_login_url()); ?>" class="btn btn-outline">
                        <?php esc_html_e('Already have an account? Login', 'biz-dir'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Search Results Container (for AJAX) -->
    <section id="searchResults" class="search-results" style="display: none;">
        <div class="container">
            <h2 class="section-title"><?php esc_html_e('Search Results', 'biz-dir'); ?></h2>
            <div id="searchResultsGrid" class="businesses-grid">
                <!-- AJAX results will be loaded here -->
            </div>
            <div id="searchPagination" class="pagination-container">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </section>
</main>

<?php
get_footer();
