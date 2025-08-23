<?php
/**
 * Category Template - Browse Businesses by Category
 *
 * @package BizDir
 */

get_header();
?>

<main id="primary" class="site-main category-page">
    <div class="container">
        <?php biz_dir_breadcrumbs(); ?>
        
        <header class="category-header">
            <h1 class="category-title">
                <?php echo biz_dir_get_category_icon(get_queried_object()->slug); ?>
                <?php single_cat_title(); ?>
            </h1>
            
            <?php
            $category_description = category_description();
            if ($category_description) :
                ?>
                <div class="category-description">
                    <?php echo $category_description; ?>
                </div>
                <?php
            endif;
            ?>
            
            <div class="category-meta">
                <span class="business-count">
                    <?php
                    $category = get_queried_object();
                    echo sprintf(_n('%d business found', '%d businesses found', $category->count, 'biz-dir'), $category->count);
                    ?>
                </span>
            </div>
        </header>
        
        <!-- Filter and Sort Options -->
        <div class="category-filters">
            <div class="filter-section">
                <div class="filter-group">
                    <label for="sortBy"><?php esc_html_e('Sort by:', 'biz-dir'); ?></label>
                    <select id="sortBy" name="sort">
                        <option value="date"><?php esc_html_e('Latest First', 'biz-dir'); ?></option>
                        <option value="title"><?php esc_html_e('Name A-Z', 'biz-dir'); ?></option>
                        <option value="rating"><?php esc_html_e('Highest Rated', 'biz-dir'); ?></option>
                        <option value="popular"><?php esc_html_e('Most Popular', 'biz-dir'); ?></option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filterRating"><?php esc_html_e('Minimum Rating:', 'biz-dir'); ?></label>
                    <select id="filterRating" name="rating">
                        <option value=""><?php esc_html_e('Any Rating', 'biz-dir'); ?></option>
                        <option value="4">4+ Stars</option>
                        <option value="3">3+ Stars</option>
                        <option value="2">2+ Stars</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filterPrice"><?php esc_html_e('Price Range:', 'biz-dir'); ?></label>
                    <select id="filterPrice" name="price">
                        <option value=""><?php esc_html_e('Any Price', 'biz-dir'); ?></option>
                        <option value="₹">₹ - Budget Friendly</option>
                        <option value="₹₹">₹₹ - Moderate</option>
                        <option value="₹₹₹">₹₹₹ - Premium</option>
                    </select>
                </div>
                
                <button id="applyFilters" class="btn btn-primary">
                    <?php esc_html_e('Apply Filters', 'biz-dir'); ?>
                </button>
            </div>
        </div>
        
        <!-- Business Listings -->
        <div class="category-businesses">
            <div id="businessesGrid" class="businesses-grid">
                <?php
                if (have_posts()) :
                    while (have_posts()) :
                        the_post();
                        
                        // Only show posts that have business metadata
                        $phone = get_post_meta(get_the_ID(), '_business_phone', true);
                        if ($phone) {
                            biz_dir_display_business_card(get_the_ID());
                        }
                    endwhile;
                else :
                    ?>
                    <div class="no-businesses-found">
                        <div class="no-businesses-content">
                            <h3><?php esc_html_e('No businesses found in this category', 'biz-dir'); ?></h3>
                            <p><?php esc_html_e('Be the first to list your business in this category!', 'biz-dir'); ?></p>
                            
                            <?php if (!is_user_logged_in()) : ?>
                                <div class="registration-prompt">
                                    <div class="auth-buttons">
                                        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn btn-primary">
                                            <?php esc_html_e('Register to List Business', 'biz-dir'); ?>
                                        </a>
                                        <a href="<?php echo esc_url(wp_login_url()); ?>" class="btn btn-outline">
                                            <?php esc_html_e('Login', 'biz-dir'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php else : ?>
                                <a href="<?php echo esc_url(admin_url('post-new.php')); ?>" class="btn btn-primary">
                                    <?php esc_html_e('Add Your Business', 'biz-dir'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                endif;
                ?>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container">
                <?php biz_dir_pagination(); ?>
            </div>
        </div>
        
        <!-- Related Categories -->
        <?php
        $current_category = get_queried_object();
        $related_categories = get_categories([
            'hide_empty' => true,
            'exclude' => [$current_category->term_id],
            'number' => 6,
            'orderby' => 'count',
            'order' => 'DESC'
        ]);
        
        if ($related_categories) :
            ?>
            <section class="related-categories">
                <h2><?php esc_html_e('Explore Other Categories', 'biz-dir'); ?></h2>
                <div class="categories-grid">
                    <?php
                    foreach ($related_categories as $category) :
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
                    endforeach;
                    ?>
                </div>
            </section>
            <?php
        endif;
        ?>
        
        <!-- Registration CTA for Non-Logged-In Users -->
        <?php if (!is_user_logged_in()) : ?>
            <section class="category-registration-cta">
                <div class="cta-content">
                    <h2><?php esc_html_e('Want to list your business here?', 'biz-dir'); ?></h2>
                    <p><?php esc_html_e('Join thousands of business owners who have grown their customer base through our platform.', 'biz-dir'); ?></p>
                    <div class="cta-buttons">
                        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn btn-primary">
                            <?php esc_html_e('Get Started Free', 'biz-dir'); ?>
                        </a>
                        <a href="<?php echo esc_url(wp_login_url()); ?>" class="btn btn-outline">
                            <?php esc_html_e('Login', 'biz-dir'); ?>
                        </a>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
