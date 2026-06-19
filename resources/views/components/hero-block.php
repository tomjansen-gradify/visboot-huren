<?php
/**
 * Hero block — ACF render template.
 * Available: $block, $content, $is_preview, $post_id
 */

$image       = get_field('background_image');
$title       = get_field('title') ?: 'Van beginner tot pro, hier vind je de juiste visboot!';
$show_cta_raw = get_field('show_cta');
$show_cta    = ($show_cta_raw === null) ? true : (bool) $show_cta_raw;
$cta_label   = get_field('cta_label') ?: 'Bekijk beschikbaarheid';
$cta_link    = get_field('cta_link') ?: home_url('/visboten/');

$fallback = get_template_directory_uri() . '/resources/images/home/hero-fishing.jpg';
$bg_url   = $fallback;
if (is_array($image) && !empty($image['url'])) {
    $bg_url = $image['url'];
} elseif (is_string($image) && $image !== '') {
    $bg_url = $image;
}

$style = sprintf(
    'background-image: linear-gradient(0deg, rgba(0,0,0,0.55), rgba(62,62,63,0.25)), url(%s);',
    esc_url($bg_url)
);
?>
<section class="hero-block" style="<?php echo esc_attr($style); ?>">
    <div class="hero-block__container">
        <div class="hero-block__content">
            <h1 class="hero-block__title">
                <em><?php echo nl2br(esc_html($title)); ?></em>
            </h1>
            <?php if ($show_cta && $cta_label && $cta_link) : ?>
                <a class="hero-block__cta" href="<?php echo esc_url($cta_link); ?>">
                    <?php echo esc_html($cta_label); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
