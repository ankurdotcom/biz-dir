<?php
/**
 * Activate BizDir Theme and Plugin
 * Sets up the complete business directory environment
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== BizDir Setup Script ===\n";

// 1. Activate BizDir Theme
echo "1. Activating BizDir Theme...\n";
$theme = 'biz-dir';
switch_theme($theme);

$current_theme = wp_get_theme();
echo "✅ Active Theme: " . $current_theme->get('Name') . "\n";

// 2. Activate BizDir Core Plugin
echo "\n2. Activating BizDir Core Plugin...\n";
$plugin = 'biz-dir-core/biz-dir-core.php';

if (!is_plugin_active($plugin)) {
    $result = activate_plugin($plugin);
    if (is_wp_error($result)) {
        echo "❌ Plugin activation error: " . $result->get_error_message() . "\n";
    } else {
        echo "✅ BizDir Core Plugin activated successfully!\n";
    }
} else {
    echo "✅ BizDir Core Plugin already active\n";
}

// 3. Check plugin status
echo "\n3. Checking active plugins...\n";
$active_plugins = get_option('active_plugins');
foreach ($active_plugins as $plugin_path) {
    if (strpos($plugin_path, 'biz-dir') !== false) {
        echo "✅ Active: $plugin_path\n";
    }
}

// 4. Set up some basic options for demo
echo "\n4. Setting up basic demo configuration...\n";

// Update site title and tagline
update_option('blogname', 'BizDir - Business Directory Demo');
update_option('blogdescription', 'Your Local Business Directory Platform');

// Set permalink structure for SEO-friendly URLs
global $wp_rewrite;
$wp_rewrite->set_permalink_structure('/%postname%/');
flush_rewrite_rules();

echo "✅ Site title and permalinks configured\n";

// 5. Create sample pages if they don't exist
echo "\n5. Creating essential pages...\n";

$pages = [
    'Business Directory' => 'Browse all local businesses in our directory.',
    'Submit Business' => 'Add your business to our directory.',
    'Contact' => 'Get in touch with us.',
    'About' => 'Learn more about our business directory platform.'
];

foreach ($pages as $title => $content) {
    $existing = get_page_by_title($title);
    if (!$existing) {
        $page_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'page'
        ]);
        echo "✅ Created page: $title (ID: $page_id)\n";
    } else {
        echo "✅ Page already exists: $title\n";
    }
}

echo "\n=== BizDir Setup Complete! ===\n";
echo "🌐 Frontend: http://localhost:8888\n";
echo "🔧 Admin: http://localhost:8888/wp-admin/\n";
echo "📁 Theme: BizDir\n";
echo "🔌 Plugin: BizDir Core\n";
echo "\nYour business directory is now ready for testing!\n";

?>
