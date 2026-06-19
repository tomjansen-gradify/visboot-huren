<?php
/**
 * @package visboothuren
 */
get_header();
?>
<div class="front-page">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <?php the_content(); ?>
        <?php endwhile; ?>
    <?php else : ?>
        <div class="container">
            <h1><?php bloginfo('name'); ?></h1>
            <p><?php _e('Welkom — bouw hier de homepage met ACF blocks.', 'visboothuren'); ?></p>
        </div>
    <?php endif; ?>
</div>
<?php
get_footer();
