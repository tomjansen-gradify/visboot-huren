<?php
/**
 * Template name: Algemene voorwaarden
 *
 * @package visboothuren
 */
get_header();

while (have_posts()) :
    the_post();

    ob_start();
    the_content();
    $content_html = (string) ob_get_clean();

    $toc = [];
    $heading_counter = 0;

    $content_html = preg_replace_callback(
        '#<(h2|h3)([^>]*)>(.*?)</\1>#si',
        function (array $m) use (&$toc, &$heading_counter) {
            $tag   = strtolower($m[1]);
            $attrs = $m[2];
            $inner = $m[3];

            if (preg_match('/\bid\s*=\s*"[^"]+"/i', $attrs)) {
                $clean = trim(preg_replace('/\s+/', ' ', strip_tags($inner)));
                if (preg_match('/\bid\s*=\s*"([^"]+)"/i', $attrs, $idm)) {
                    $toc[] = [
                        'id'   => $idm[1],
                        'type' => $tag === 'h3' ? 'legal-part' : 'legal-section',
                        'text' => $clean,
                    ];
                }
                return $m[0];
            }

            $heading_counter++;
            $id   = 'sectie-' . $heading_counter;
            $clean = trim(preg_replace('/\s+/', ' ', strip_tags($inner)));
            $type  = $tag === 'h3' ? 'legal-part' : 'legal-section';
            $toc[] = ['id' => $id, 'type' => $type, 'text' => $clean];

            $display_inner = $inner;
            if ($tag === 'h2') {
                $display_inner = preg_replace(
                    '#^(\s*)(\d{1,3})\.(\s+)#',
                    '$1<span class="legal-num">$2.</span>$3',
                    $inner,
                    1
                );
            }

            return sprintf('<%s%s id="%s">%s</%s>', $tag, $attrs, $id, $display_inner, $tag);
        },
        $content_html
    );
    ?>

    <header class="legal-head">
        <div class="legal-head__container">
            <p class="legal-head__kicker">Visboot-huren.nl</p>
            <h1 class="legal-head__title"><?php the_title(); ?></h1>
            <p class="legal-head__updated">
                <?php
                $date = get_field('legal_updated');
                printf(
                    esc_html__('Laatst bijgewerkt: %s', 'visboothuren'),
                    esc_html($date ?: get_the_modified_date('j F Y'))
                );
                ?>
            </p>
        </div>
    </header>

    <div class="legal-layout">
        <div class="legal-layout__container">
            <?php if (!empty($toc)) : ?>
                <aside class="legal-toc" aria-label="<?php esc_attr_e('Inhoudsopgave', 'visboothuren'); ?>">
                    <p class="legal-toc__heading"><?php _e('Inhoud', 'visboothuren'); ?></p>
                    <nav class="legal-toc__nav">
                        <ol class="legal-toc__list">
                            <?php foreach ($toc as $item) : ?>
                                <li class="legal-toc__item legal-toc__item--<?php echo esc_attr($item['type']); ?>">
                                    <a href="#<?php echo esc_attr($item['id']); ?>" data-toc-link>
                                        <?php echo esc_html($item['text']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                </aside>
            <?php endif; ?>

            <article class="legal-body">
                <?php echo $content_html; ?>
            </article>
        </div>
    </div>

<?php endwhile;
get_footer();
