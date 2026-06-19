<?php
/**
 * @package visboothuren
 */
get_header();
?>
<article <?php post_class('page-default'); ?>>
    <?php while (have_posts()) : the_post(); ?>
        <?php $has_blocks = has_blocks(get_the_content()); ?>
        <?php if (!$has_blocks) : ?>
            <div class="page-default__fallback">
                <div class="page-default__container">
                    <h1><?php the_title(); ?></h1>
                    <?php the_content(); ?>
                </div>
            </div>
        <?php else : ?>
            <?php the_content(); ?>
        <?php endif; ?>
    <?php endwhile; ?>
</article>
<?php
get_footer();
