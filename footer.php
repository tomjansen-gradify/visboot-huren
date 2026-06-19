    </main>
<?php
$vbh_footer_intro     = function_exists('get_field') ? (string) get_field('footer_intro', 'option') : '';
$vbh_footer_bg_image  = function_exists('get_field') ? get_field('footer_bg_image', 'option') : null;
$vbh_footer_bg_color  = function_exists('get_field') ? (string) get_field('footer_bg_color', 'option') : '';
$vbh_footer_copyright = function_exists('get_field') ? (string) get_field('footer_copyright', 'option') : '';
if ($vbh_footer_bg_color === '') {
    $vbh_footer_bg_color = '#1e2d44';
}
if ($vbh_footer_copyright === '') {
    $vbh_footer_copyright = '© {year} Visboot-huren.nl';
}
$vbh_footer_copyright = str_replace('{year}', date('Y'), $vbh_footer_copyright);

$vbh_footer_bg_url = '';
if (is_array($vbh_footer_bg_image) && !empty($vbh_footer_bg_image['url'])) {
    $vbh_footer_bg_url = $vbh_footer_bg_image['url'];
}

$vbh_footer_style_parts = ['background-color: ' . $vbh_footer_bg_color];
if ($vbh_footer_bg_url) {
    $vbh_footer_style_parts[] = sprintf(
        'background-image: linear-gradient(180deg, rgba(30,45,68,0.4) 0%%, %s 90%%), url(%s)',
        $vbh_footer_bg_color,
        esc_url($vbh_footer_bg_url)
    );
}
$vbh_footer_style = implode('; ', $vbh_footer_style_parts);
?>
    <footer id="site-footer" class="site-footer" style="<?php echo esc_attr($vbh_footer_style); ?>">
        <?php if ($vbh_footer_intro !== '') : ?>
            <div class="site-footer__intro">
                <div class="site-footer__container">
                    <p><?php echo esc_html($vbh_footer_intro); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="site-footer__bottom">
            <div class="site-footer__container">
                <?php
                $has_footer_menu = has_nav_menu('footer-bottom');
                if ($has_footer_menu) :
                ?>
                    <nav class="site-footer__menu" aria-label="<?php esc_attr_e('Footer menu', 'visboothuren'); ?>">
                        <?php
                        wp_nav_menu([
                            'theme_location' => 'footer-bottom',
                            'container'      => false,
                            'menu_class'     => 'site-footer__menu-list',
                            'fallback_cb'    => '__return_false',
                            'depth'          => 1,
                        ]);
                        ?>
                    </nav>
                <?php endif; ?>

                <p class="site-footer__part-of">Onderdeel van Visboot-Huren B.V</p>

                <p class="site-footer__copyright"><?php echo esc_html($vbh_footer_copyright); ?></p>
            </div>
        </div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
