<?php
/**
 * Boat listing block — pulls visboot CPT.
 * Available: $block, $content, $is_preview, $post_id
 */

$title       = get_field('section_title') ?: 'Actuele aanbod visboten:';
$limit       = (int) (get_field('limit') ?: -1);
$cta_label   = get_field('cta_label') ?: 'Bekijk beschikbaarheid';

$query = new WP_Query([
    'post_type'      => 'visboot',
    'posts_per_page' => $limit > 0 ? $limit : -1,
    'orderby'        => 'menu_order date',
    'order'          => 'ASC',
    'post_status'    => 'publish',
]);
?>
<section class="boat-listing">
    <div class="boat-listing__container">
        <?php if ($title) : ?>
            <h2 class="boat-listing__title"><strong><?php echo esc_html($title); ?></strong></h2>
        <?php endif; ?>

        <?php if ($query->have_posts()) : ?>
            <div class="boat-listing__grid">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <article class="boat-card">
                        <a href="<?php the_permalink(); ?>" class="boat-card__image-link">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('large', ['class' => 'boat-card__image']); ?>
                            <?php else : ?>
                                <span class="boat-card__image boat-card__image--placeholder" aria-hidden="true"></span>
                            <?php endif; ?>
                        </a>
                        <div class="boat-card__body">
                            <h3 class="boat-card__title">
                                <a href="<?php the_permalink(); ?>">
                                    <em><strong><?php the_title(); ?></strong></em>
                                </a>
                            </h3>
                            <?php
                            $card_desc = trim((string) get_field('card_description'));
                            if ($card_desc !== '') {
                                $card_html = wpautop($card_desc);
                            } else {
                                $card_html = wpautop((string) get_the_excerpt());
                            }
                            ?>
                            <?php if (trim(strip_tags($card_html)) !== '') : ?>
                                <div class="boat-card__excerpt">
                                    <?php echo wp_kses_post($card_html); ?>
                                </div>
                            <?php endif; ?>
                            <a href="<?php the_permalink(); ?>" class="boat-card__cta">
                                <?php echo esc_html($cta_label); ?>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p class="boat-listing__empty">
                <?php
                if ($is_preview ?? false) {
                    esc_html_e('Voeg eerst een visboot toe via "Visboten" in het admin menu.', 'visboothuren');
                } else {
                    esc_html_e('Geen visboten beschikbaar.', 'visboothuren');
                }
                ?>
            </p>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</section>
