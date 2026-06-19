<?php
/**
 * Page header block — ACF render template.
 */

$kicker   = (string) get_field('kicker');
$title    = (string) get_field('title');
$subtitle = (string) get_field('subtitle');

if ($title === '' && function_exists('get_the_title')) {
    $title = (string) get_the_title();
}
?>
<header class="page-header-block">
    <div class="page-header-block__container">
        <?php if ($kicker !== '') : ?>
            <p class="page-header-block__kicker"><?php echo esc_html($kicker); ?></p>
        <?php endif; ?>
        <?php if ($title !== '') : ?>
            <h1 class="page-header-block__title"><?php echo esc_html($title); ?></h1>
        <?php endif; ?>
        <?php if ($subtitle !== '') : ?>
            <p class="page-header-block__subtitle"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>
    </div>
</header>
