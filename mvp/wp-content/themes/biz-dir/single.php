<?php
/**
 * Single Business Post Template
 * 
 * Displays detailed business information publicly
 *
 * @package BizDir
 */

get_header();
?>

<main id="primary" class="site-main business-single">
    <?php
    while (have_posts()) :
        the_post();
        
        // Get business metadata
        $phone = get_post_meta(get_the_ID(), '_business_phone', true);
        $address = get_post_meta(get_the_ID(), '_business_address', true);
        $email = get_post_meta(get_the_ID(), '_business_email', true);
        $website = get_post_meta(get_the_ID(), '_business_website', true);
        $hours = get_post_meta(get_the_ID(), '_business_hours', true);
        $rating = get_post_meta(get_the_ID(), '_business_rating', true);
        $price_range = get_post_meta(get_the_ID(), '_business_price_range', true);
        $features = get_post_meta(get_the_ID(), '_business_features', true);
        
        $categories = get_the_category();
        $primary_category = !empty($categories) ? $categories[0] : null;
        ?>
        
        <!-- Breadcrumbs -->
        <?php biz_dir_breadcrumbs(); ?>
        
        <article id="post-<?php the_ID(); ?>" <?php post_class('business-listing'); ?>>
            <div class="container">
                <div class="business-layout">
                    <!-- Main Business Information -->
                    <div class="business-main">
                        <header class="business-header">
                            <div class="business-title-section">
                                <h1 class="business-title"><?php the_title(); ?></h1>
                                
                                <div class="business-meta-header">
                                    <?php if ($primary_category) : ?>
                                        <span class="business-category">
                                            <a href="<?php echo esc_url(get_category_link($primary_category->term_id)); ?>">
                                                <?php echo biz_dir_get_category_icon($primary_category->slug); ?>
                                                <?php echo esc_html($primary_category->name); ?>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($rating) : ?>
                                        <div class="business-rating-header">
                                            <?php biz_dir_display_rating($rating, true); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($price_range) : ?>
                                        <span class="price-range-header"><?php echo esc_html($price_range); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Business Actions -->
                            <div class="business-actions-header">
                                <?php if (!is_user_logged_in()) : ?>
                                    <button class="btn btn-primary login-prompt" data-action="review">
                                        <?php esc_html_e('Login to Write Review', 'biz-dir'); ?>
                                    </button>
                                    <button class="btn btn-outline login-prompt" data-action="save">
                                        <?php esc_html_e('Login to Save', 'biz-dir'); ?>
                                    </button>
                                <?php else : ?>
                                    <button class="btn btn-primary" id="writeReviewBtn">
                                        <?php esc_html_e('Write Review', 'biz-dir'); ?>
                                    </button>
                                    <button class="btn btn-outline" id="saveFavoriteBtn">
                                        <?php esc_html_e('Save to Favorites', 'biz-dir'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </header>
                        
                        <!-- Business Images -->
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="business-featured-image">
                                <?php the_post_thumbnail('business-featured', ['alt' => get_the_title()]); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Business Description -->
                        <div class="business-description">
                            <h2><?php esc_html_e('About This Business', 'biz-dir'); ?></h2>
                            <div class="business-content">
                                <?php the_content(); ?>
                            </div>
                            
                            <?php if ($features) : ?>
                                <div class="business-features">
                                    <h3><?php esc_html_e('Features & Services', 'biz-dir'); ?></h3>
                                    <p><?php echo esc_html($features); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Reviews Section -->
                        <div class="business-reviews">
                            <h2><?php esc_html_e('Customer Reviews', 'biz-dir'); ?></h2>
                            
                            <?php if (!is_user_logged_in()) : ?>
                                <div class="review-login-prompt">
                                    <p><?php esc_html_e('Login to read and write reviews for this business.', 'biz-dir'); ?></p>
                                    <div class="auth-buttons">
                                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-primary">
                                            <?php esc_html_e('Login', 'biz-dir'); ?>
                                        </a>
                                        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn btn-outline">
                                            <?php esc_html_e('Register', 'biz-dir'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php else : ?>
                                <!-- Reviews will be loaded via AJAX for logged-in users -->
                                <div id="reviewsContainer">
                                    <div class="loading-reviews">
                                        <?php esc_html_e('Loading reviews...', 'biz-dir'); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Business Sidebar -->
                    <aside class="business-sidebar">
                        <!-- Contact Information -->
                        <div class="business-contact-card">
                            <h3><?php esc_html_e('Contact Information', 'biz-dir'); ?></h3>
                            
                            <div class="contact-info">
                                <?php if ($phone) : ?>
                                    <div class="contact-item">
                                        <span class="contact-icon">📞</span>
                                        <div class="contact-details">
                                            <strong><?php esc_html_e('Phone', 'biz-dir'); ?></strong>
                                            <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($email) : ?>
                                    <div class="contact-item">
                                        <span class="contact-icon">✉️</span>
                                        <div class="contact-details">
                                            <strong><?php esc_html_e('Email', 'biz-dir'); ?></strong>
                                            <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($website) : ?>
                                    <div class="contact-item">
                                        <span class="contact-icon">🌐</span>
                                        <div class="contact-details">
                                            <strong><?php esc_html_e('Website', 'biz-dir'); ?></strong>
                                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener">
                                                <?php echo esc_html(parse_url($website, PHP_URL_HOST)); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($address) : ?>
                                    <div class="contact-item">
                                        <span class="contact-icon">📍</span>
                                        <div class="contact-details">
                                            <strong><?php esc_html_e('Address', 'biz-dir'); ?></strong>
                                            <span><?php echo esc_html($address); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($hours) : ?>
                                    <div class="contact-item">
                                        <span class="contact-icon">🕒</span>
                                        <div class="contact-details">
                                            <strong><?php esc_html_e('Hours', 'biz-dir'); ?></strong>
                                            <span><?php echo esc_html($hours); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Business Stats -->
                        <?php if ($rating) : ?>
                            <div class="business-stats-card">
                                <h3><?php esc_html_e('Business Stats', 'biz-dir'); ?></h3>
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo esc_html(number_format($rating, 1)); ?></div>
                                        <div class="stat-label"><?php esc_html_e('Rating', 'biz-dir'); ?></div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo esc_html(get_comments_number()); ?></div>
                                        <div class="stat-label"><?php esc_html_e('Reviews', 'biz-dir'); ?></div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo esc_html(get_post_meta(get_the_ID(), '_business_views', true) ?: '0'); ?></div>
                                        <div class="stat-label"><?php esc_html_e('Views', 'biz-dir'); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Related Businesses -->
                        <?php if ($primary_category) : ?>
                            <div class="related-businesses-card">
                                <h3><?php esc_html_e('Similar Businesses', 'biz-dir'); ?></h3>
                                <?php
                                $related_businesses = new WP_Query([
                                    'post_type' => 'post',
                                    'posts_per_page' => 3,
                                    'post__not_in' => [get_the_ID()],
                                    'category__in' => [$primary_category->term_id],
                                    'meta_query' => [
                                        [
                                            'key' => '_business_phone',
                                            'compare' => 'EXISTS'
                                        ]
                                    ]
                                ]);
                                
                                if ($related_businesses->have_posts()) :
                                    echo '<div class="related-businesses-list">';
                                    while ($related_businesses->have_posts()) : $related_businesses->the_post();
                                        ?>
                                        <div class="related-business-item">
                                            <a href="<?php the_permalink(); ?>" class="related-business-link">
                                                <?php if (has_post_thumbnail()) : ?>
                                                    <div class="related-business-image">
                                                        <?php the_post_thumbnail('thumbnail'); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="related-business-info">
                                                    <h4><?php the_title(); ?></h4>
                                                    <p><?php echo wp_trim_words(get_the_excerpt(), 10); ?></p>
                                                </div>
                                            </a>
                                        </div>
                                        <?php
                                    endwhile;
                                    echo '</div>';
                                    wp_reset_postdata();
                                else :
                                    echo '<p>' . esc_html__('No similar businesses found.', 'biz-dir') . '</p>';
                                endif;
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Share Business -->
                        <div class="share-business-card">
                            <h3><?php esc_html_e('Share This Business', 'biz-dir'); ?></h3>
                            <div class="share-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                                   target="_blank" rel="noopener" class="share-btn facebook">
                                    Facebook
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                                   target="_blank" rel="noopener" class="share-btn twitter">
                                    Twitter
                                </a>
                                <a href="https://wa.me/?text=<?php echo urlencode(get_the_title() . ' - ' . get_permalink()); ?>" 
                                   target="_blank" rel="noopener" class="share-btn whatsapp">
                                    WhatsApp
                                </a>
                                <button class="share-btn copy-link" data-url="<?php echo esc_url(get_permalink()); ?>">
                                    Copy Link
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </article>
        
        <?php
    endwhile;
    ?>
</main>

<!-- Login Modal for Non-Logged-In Users -->
<?php if (!is_user_logged_in()) : ?>
<div id="loginModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php esc_html_e('Login Required', 'biz-dir'); ?></h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p><?php esc_html_e('Please login or register to access this feature.', 'biz-dir'); ?></p>
            <div class="modal-buttons">
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-primary">
                    <?php esc_html_e('Login', 'biz-dir'); ?>
                </a>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn btn-outline">
                    <?php esc_html_e('Register', 'biz-dir'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
get_footer();
