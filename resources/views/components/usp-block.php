<?php
/**
 * USP block — ACF render template.
 * Available: $block, $content, $is_preview, $post_id
 */

$items = get_field('usps');
if (!is_array($items) || empty($items)) {
    $items = [
        ['icon' => 'fa-solid fa-location-dot',  'text' => 'Vis op je droomlocatie'],
        ['icon' => 'fa-solid fa-calendar-days', 'text' => 'Huur een dag, weekend of week'],
        ['icon' => 'fa-solid fa-ship',          'text' => 'Met of zonder vaarbewijs!'],
    ];
}
?>
<section class="usp-block">
    <div class="usp-block__container">
        <ul class="usp-block__list">
            <?php foreach ($items as $item) :
                $icon = isset($item['icon']) ? trim((string) $item['icon']) : '';
                $text = isset($item['text']) ? trim((string) $item['text']) : '';
                if ($text === '') {
                    continue;
                }
                ?>
                <li class="usp-block__item">
                    <?php if ($icon !== '') : ?>
                        <span class="usp-block__icon">
                            <i class="<?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
                        </span>
                    <?php endif; ?>
                    <p class="usp-block__text"><?php echo esc_html($text); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
