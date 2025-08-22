<?php
/**
 * Header template
 *
 * @package BizDir
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'biz-dir'); ?></a>

    <header id="masthead" class="site-header">
        <div class="container">
            <div class="site-branding">
                <?php
                if (has_custom_logo()) :
                    the_custom_logo();
                else :
                    ?>
                    <h1 class="site-title">
                        <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                            <?php bloginfo('name'); ?>
                        </a>
                    </h1>
                    <?php
                endif;
                $biz_dir_description = get_bloginfo('description', 'display');
                if ($biz_dir_description || is_customize_preview()) :
                    ?>
                    <p class="site-description"><?php echo $biz_dir_description; ?></p>
                <?php endif; ?>
            </div><!-- .site-branding -->

            <nav id="site-navigation" class="main-navigation">
                <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="menu-toggle-text"><?php esc_html_e('Menu', 'biz-dir'); ?></span>
                    <span class="menu-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
                <?php
                wp_nav_menu([
                    'theme_location' => 'menu-1',
                    'menu_id'        => 'primary-menu',
                    'container_class' => 'menu-container',
                ]);
                ?>
            </nav><!-- #site-navigation -->
            
            <!-- Business Search -->
            <div class="header-search">
                <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                    <div class="search-field-container">
                        <input type="search" class="search-field" placeholder="<?php echo esc_attr_x('Search businesses, services...', 'placeholder', 'biz-dir'); ?>" value="<?php echo get_search_query(); ?>" name="s" />
                        <input type="hidden" name="post_type" value="business_listing">
                        <button type="submit" class="search-submit">
                            <span class="screen-reader-text"><?php echo _x('Search', 'submit button', 'biz-dir'); ?></span>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M7.333 12.667A5.333 5.333 0 1 0 7.333 2a5.333 5.333 0 0 0 0 10.667ZM14 14l-2.9-2.9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- User Account -->
            <div class="header-account">
                <?php if (is_user_logged_in()) : ?>
                    <div class="user-menu">
                        <button class="user-menu-toggle" aria-expanded="false">
                            <span class="user-avatar">
                                <?php echo get_avatar(get_current_user_id(), 32); ?>
                            </span>
                            <span class="user-name"><?php echo wp_get_current_user()->display_name; ?></span>
                        </button>
                        <div class="user-dropdown">
                            <a href="<?php echo esc_url(admin_url('profile.php')); ?>" class="dropdown-item">
                                <span class="item-icon">👤</span>
                                <?php esc_html_e('Profile', 'biz-dir'); ?>
                            </a>
                            <a href="<?php echo esc_url(home_url('/my-businesses/')); ?>" class="dropdown-item">
                                <span class="item-icon">🏢</span>
                                <?php esc_html_e('My Businesses', 'biz-dir'); ?>
                            </a>
                            <a href="<?php echo esc_url(home_url('/add-business/')); ?>" class="dropdown-item">
                                <span class="item-icon">➕</span>
                                <?php esc_html_e('Add Business', 'biz-dir'); ?>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo esc_url(wp_logout_url()); ?>" class="dropdown-item">
                                <span class="item-icon">🚪</span>
                                <?php esc_html_e('Logout', 'biz-dir'); ?>
                            </a>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="auth-links">
                        <a href="<?php echo esc_url(wp_login_url()); ?>" class="login-link">
                            <?php esc_html_e('Login', 'biz-dir'); ?>
                        </a>
                        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="register-link btn btn-primary">
                            <?php esc_html_e('Register', 'biz-dir'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header><!-- #masthead -->
    
    <!-- Display header ads -->
    <?php do_action('biz_dir_header_ad'); ?>

    <main id="primary" class="site-main">
        <div class="container"><?php
        // Display any admin notices or messages
        if (isset($_GET['message'])) {
            $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
            echo '<div class="notice notice-' . esc_attr($message_type) . '">';
            echo '<p>' . esc_html($_GET['message']) . '</p>';
            echo '</div>';
        }
        ?>
