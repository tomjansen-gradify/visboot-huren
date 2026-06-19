<?php
/**
 * Image + text block — ACF render template.
 */

$image      = get_field('image');
$position   = (string) get_field('image_position') ?: 'left';
$heading    = (string) get_field('heading');
$subheading = (string) get_field('subheading');
$content    = (string) get_field('content');
$show_lines = (bool) get_field('show_accent_lines');

$image_url = '';
$image_alt = '';
if (is_array($image)) {
    $image_url = $image['sizes']['large'] ?? ($image['url'] ?? '');
    $image_alt = $image['alt'] ?? '';
} elseif (is_string($image) && $image !== '') {
    $image_url = $image;
}

$classes = ['image-text-block', 'image-text-block--' . $position];
if ($show_lines) {
    $classes[] = 'image-text-block--with-lines';
}
if (!$image_url) {
    $classes[] = 'image-text-block--no-image';
}
?>
<section class="<?php echo esc_attr(implode(' ', $classes)); ?>">
    <div class="image-text-block__container">
        <?php if ($image_url) : ?>
            <div class="image-text-block__image-wrap">
                <img class="image-text-block__image"
                     src="<?php echo esc_url($image_url); ?>"
                     alt="<?php echo esc_attr($image_alt); ?>"
                     loading="lazy">
            </div>
        <?php endif; ?>
        <div class="image-text-block__text-wrap">
            <?php if ($show_lines) : ?>
                <span class="image-text-block__line image-text-block__line--top" aria-hidden="true"></span>
            <?php endif; ?>
            <div class="image-text-block__text">
                <?php if ($heading !== '') : ?>
                    <h2 class="image-text-block__heading"><?php echo esc_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($subheading !== '') : ?>
                    <p class="image-text-block__subheading"><?php echo esc_html($subheading); ?></p>
                <?php endif; ?>
                <?php if ($content !== '') : ?>
                    <div class="image-text-block__body"><?php echo wp_kses_post($content); ?></div>
                <?php endif; ?>
            </div>
            <?php if ($show_lines) : ?>
                <span class="image-text-block__line image-text-block__line--bottom" aria-hidden="true"></span>
            <?php endif; ?>
        </div>
    </div>
</section>
