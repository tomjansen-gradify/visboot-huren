<?php
/**
 * Text block — ACF render template.
 * Available: $block, $content, $is_preview, $post_id
 */

$content = get_field('content');
$align   = get_field('align') ?: 'left';
?>
<section class="text-block text-block--align-<?php echo esc_attr($align); ?>">
    <div class="text-block__container">
        <?php if ($content) : ?>
            <div class="text-block__content"><?php echo wp_kses_post($content); ?></div>
        <?php elseif ($is_preview ?? false) : ?>
            <p class="text-block__placeholder"><em>Voeg in de zijbalk je tekst toe.</em></p>
        <?php endif; ?>
    </div>
</section>
