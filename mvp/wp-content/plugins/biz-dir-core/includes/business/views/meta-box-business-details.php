<?php
/**
 * Business Details Meta Box View
 * 
 * @var WP_Post $post Current post object
 * @var array   $contact_info Contact information
 * @var string  $is_sponsored Whether business is sponsored
 */
?>
<div class="business-details-meta">
    <p>
        <label for="contact_info"><?php _e('Contact Information', 'biz-dir'); ?></label>
        <textarea id="contact_info" name="contact_info" class="widefat"><?php echo esc_textarea($contact_info); ?></textarea>
        <span class="description"><?php _e('Enter business contact details like phone, email, website etc.', 'biz-dir'); ?></span>
    </p>

    <p>
        <label>
            <input type="checkbox" name="is_sponsored" value="1" <?php checked($is_sponsored, '1'); ?>>
            <?php _e('This is a sponsored business listing', 'biz-dir'); ?>
        </label>
    </p>
</div>
