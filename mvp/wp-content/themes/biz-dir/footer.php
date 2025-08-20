<?php
/**
 * Footer template
 *
 * @package BizDir
 */
?>

    <footer id="colophon" class="site-footer">
        <div class="site-info">
            <?php
            printf(
                esc_html__('Community Business Directory © %d', 'biz-dir'),
                date('Y')
            );
            ?>
        </div>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
