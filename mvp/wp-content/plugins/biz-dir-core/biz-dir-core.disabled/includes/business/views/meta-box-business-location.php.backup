<?php
/**
 * Business Location Meta Box View
 * 
 * @var WP_Post $post Current post object
 * @var int     $town_id Town ID
 * @var array   $location Location details
 */

global $wpdb;
$towns = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}biz_towns ORDER BY name ASC");
?>
<div class="business-location-meta">
    <p>
        <label for="town_id"><?php _e('Town', 'biz-dir'); ?></label>
        <select id="town_id" name="town_id" class="widefat">
            <option value=""><?php _e('Select a town', 'biz-dir'); ?></option>
            <?php foreach ($towns as $town) : ?>
                <option value="<?php echo esc_attr($town->id); ?>" <?php selected($town_id, $town->id); ?>>
                    <?php echo esc_html($town->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label for="location"><?php _e('Address', 'biz-dir'); ?></label>
        <textarea id="location" name="location" class="widefat"><?php echo esc_textarea($location); ?></textarea>
        <span class="description"><?php _e('Enter the full address of the business', 'biz-dir'); ?></span>
    </p>
</div>
