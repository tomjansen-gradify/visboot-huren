<?php
/**
 * Single visboot template.
 *
 * @package visboothuren
 */
get_header();

while (have_posts()) : the_post();

    $header_image = get_field('header_image');
    $header_url   = '';
    if (is_array($header_image) && !empty($header_image['url'])) {
        $header_url = $header_image['url'];
    } elseif (has_post_thumbnail()) {
        $header_url = (string) get_the_post_thumbnail_url(null, 'full');
    } else {
        $header_url = get_template_directory_uri() . '/resources/images/home/hero-fishing.jpg';
    }

    $subtitle  = (string) get_field('header_subtitle');
    $cta_label = (string) get_field('header_cta_label');
    if (trim($cta_label) === '') {
        $cta_label = 'Bekijk beschikbaarheid';
    }
    $cta_link = (string) get_field('header_cta_link');
    if (trim($cta_link) === '') {
        $cta_link = '#boekingsformulier';
    }

    $hero_style = sprintf(
        'background-image: linear-gradient(0deg, rgba(0,0,0,0.55), rgba(62,62,63,0.25)), url(%s);',
        esc_url($header_url)
    );

    $specs           = get_field('specs');
    $gallery         = get_field('gallery');
    $specifications  = get_field('specifications');
    $features        = get_field('features');
    $faqs            = get_field('faqs');
    ?>

    <section class="hero-block" style="<?php echo esc_attr($hero_style); ?>">
        <div class="hero-block__container">
            <div class="hero-block__content">
                <h1 class="hero-block__title">
                    <em><strong><?php the_title(); ?></strong></em>
                </h1>
                <?php if ($subtitle !== '') : ?>
                    <p class="hero-block__subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>
                <a class="hero-block__cta" href="<?php echo esc_url($cta_link); ?>">
                    <?php echo esc_html($cta_label); ?>
                </a>
            </div>
        </div>
    </section>

    <?php if (is_array($specs) && !empty($specs)) : ?>
        <section class="usp-block">
            <div class="usp-block__container">
                <ul class="usp-block__list">
                    <?php foreach ($specs as $spec) :
                        $icon      = isset($spec['icon'])      ? trim((string) $spec['icon'])      : '';
                        $primary   = isset($spec['primary'])   ? trim((string) $spec['primary'])   : '';
                        $secondary = isset($spec['secondary']) ? trim((string) $spec['secondary']) : '';
                        if ($primary === '' && $secondary === '') {
                            continue;
                        }
                        ?>
                        <li class="usp-block__item">
                            <?php if ($icon !== '') : ?>
                                <span class="usp-block__icon">
                                    <i class="<?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
                                </span>
                            <?php endif; ?>
                            <?php if ($primary !== '') : ?>
                                <p class="usp-block__text"><?php echo esc_html($primary); ?></p>
                            <?php endif; ?>
                            <?php if ($secondary !== '') : ?>
                                <p class="usp-block__text usp-block__text--small"><?php echo esc_html($secondary); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    <?php endif; ?>

    <article <?php post_class('single-visboot'); ?>>
        <div class="single-visboot__content">
            <div class="single-visboot__container">
                <?php the_content(); ?>
            </div>
        </div>

        <?php
        $bookable_field = get_field('bookable');
        $is_bookable = ($bookable_field === null) ? true : (bool) $bookable_field;
        if ($is_bookable) :
            $tiered_prices       = get_field('tiered_prices') ?: [];
            $deposit_amount      = (float) (get_field('deposit_amount') ?: 0);
            $global_slots        = get_field('time_slots', 'option') ?: [];
            $boat_slots          = get_field('time_slots') ?: [];
            $time_slots          = !empty($boat_slots) ? $boat_slots : $global_slots;

            $global_extras       = get_field('default_extras', 'option') ?: [];
            $boat_extras         = get_field('extras') ?: [];

            $license_required    = (bool) get_field('license_required');
            $builtin_extras      = [];
            if (!$license_required) {
                $builtin_extras[] = ['label' => 'Livescope plus systeem', 'price' => 50.0, 'type' => 'check', 'description' => ''];
            }
            $builtin_extras[] = ['label' => 'Top kwaliteit koelbox met ijs',        'price' => 25.0, 'type' => 'check',    'description' => ''];
            $builtin_extras[] = ['label' => 'Hengelset snoek incl. net',            'price' => 30.0, 'type' => 'quantity', 'description' => ''];
            $builtin_extras[] = ['label' => 'Hengelset baars/snoekbaars incl. net', 'price' => 30.0, 'type' => 'quantity', 'description' => ''];

            if (!empty($boat_extras)) {
                $extras = $boat_extras;
            } elseif (!empty($global_extras)) {
                $extras = $global_extras;
            } else {
                $extras = $builtin_extras;
            }

            $boat_season_start   = (string) get_field('rental_season_start');
            $rental_start        = $boat_season_start !== '' ? $boat_season_start : (string) get_field('rental_season_start', 'option');
            $boat_season_end     = (string) get_field('rental_season_end');
            $rental_end          = $boat_season_end !== '' ? $boat_season_end : (string) get_field('rental_season_end', 'option');

            // Hardcoded vloer: verhuur kan niet vóór 1 juli 2026 starten (eigenaren tot dan in buitenland).
            $hard_floor = '2026-07-01';
            if ($rental_start === '' || $rental_start < $hard_floor) {
                $rental_start = $hard_floor;
            }

            $boat_weekdays       = get_field('rental_weekdays');
            $rental_weekdays     = !empty($boat_weekdays) ? $boat_weekdays : (get_field('rental_weekdays', 'option') ?: ['0','1','2','3','4','5','6']);

            $global_closed       = get_field('global_closed_dates', 'option') ?: [];
            $boat_closed         = get_field('closed_dates') ?: [];
            $boat_prices         = get_field('prices') ?: [];

            $price_map = [];
            foreach ($time_slots as $slot) {
                $sid = isset($slot['id']) ? (string) $slot['id'] : '';
                if ($sid === '') continue;
                $price_map[$sid] = isset($slot['default_price']) ? (float) $slot['default_price'] : 0.0;
            }
            foreach ($boat_prices as $bp) {
                $sid = isset($bp['slot_id']) ? (string) $bp['slot_id'] : '';
                if ($sid === '') continue;
                $price_map[$sid] = isset($bp['amount']) ? (float) $bp['amount'] : 0.0;
            }

            $slot_payload = [];
            foreach ($time_slots as $slot) {
                $sid = isset($slot['id']) ? (string) $slot['id'] : '';
                if ($sid === '') continue;
                $slot_payload[] = [
                    'id'    => $sid,
                    'label' => isset($slot['label']) ? (string) $slot['label'] : $sid,
                    'start' => isset($slot['start']) ? (string) $slot['start'] : '',
                    'end'   => isset($slot['end'])   ? (string) $slot['end']   : '',
                    'price' => $price_map[$sid] ?? 0.0,
                ];
            }

            $extras_payload = [];
            foreach ($extras as $ex) {
                $label = isset($ex['label']) ? (string) $ex['label'] : '';
                $type  = isset($ex['type']) ? (string) $ex['type'] : '';
                // Auto-detect: "Hengelset …" wordt standaard een aantal-veld, ook als type leeg is.
                if ($type === '' && stripos($label, 'hengelset') !== false) {
                    $type = 'quantity';
                }
                if ($type !== 'quantity') $type = 'check';
                $extras_payload[] = [
                    'label'       => $label,
                    'price'       => isset($ex['price']) ? (float) $ex['price'] : 0.0,
                    'description' => isset($ex['description']) ? (string) $ex['description'] : '',
                    'type'        => $type,
                ];
            }

            $closed_dates = [];
            foreach ($global_closed as $c) {
                if (!empty($c['date'])) $closed_dates[] = (string) $c['date'];
            }
            foreach ($boat_closed as $c) {
                if (!empty($c['date'])) $closed_dates[] = (string) $c['date'];
            }
            $closed_dates = array_values(array_unique($closed_dates));

            $tiers_payload = [];
            foreach ($tiered_prices as $tier) {
                $min  = isset($tier['min_days']) ? max(1, (int) $tier['min_days']) : 1;
                $price = isset($tier['price']) ? (float) $tier['price'] : 0.0;
                $label = isset($tier['label']) ? (string) $tier['label'] : '';
                $tiers_payload[] = ['min_days' => $min, 'price' => $price, 'label' => $label];
            }
            usort($tiers_payload, fn($a, $b) => $a['min_days'] <=> $b['min_days']);

            $mollie_active = class_exists('\Gradify\Services\MollieService')
                ? \Gradify\Services\MollieService::isConfigured()
                : false;

            $config = [
                'mollie_enabled'  => $mollie_active,
                'boat_id'         => get_the_ID(),
                'boat_title'      => get_the_title(),
                'season_start'    => $rental_start ?: date('Y-m-d'),
                'season_end'      => $rental_end ?: date('Y-m-d', strtotime('+12 months')),
                'weekdays'        => array_map('intval', (array) $rental_weekdays),
                'closed_dates'    => $closed_dates,
                'slots'           => $slot_payload,
                'tiers'           => $tiers_payload,
                'deposit_amount'  => $deposit_amount,
                'extras'          => $extras_payload,
                'rest_url'        => esc_url_raw(rest_url('visboothuren/v1/booking')),
                'rest_nonce'      => wp_create_nonce('wp_rest'),
                'today'           => date('Y-m-d'),
                'terms_url'       => esc_url_raw(home_url('/algemene-voorwaarden/')),
            ];
            ?>
            <section class="booking-wizard" id="boekingsformulier" data-booking-wizard data-config="<?php echo esc_attr(wp_json_encode($config)); ?>">
                <div class="booking-wizard__container">
                    <header class="booking-wizard__header">
                        <h2 class="booking-wizard__title">Reserveer <?php echo esc_html(get_the_title()); ?></h2>
                        <p class="booking-wizard__intro">In 3 stappen check je de beschikbaarheid en reserveer je je boot.</p>
                    </header>

                    <ol class="booking-wizard__steps" data-stepper>
                        <li class="booking-wizard__step is-active" data-step="1">
                            <span class="booking-wizard__step-num">1</span>
                            <span class="booking-wizard__step-label">Datum &amp; tijd</span>
                        </li>
                        <li class="booking-wizard__step" data-step="2">
                            <span class="booking-wizard__step-num">2</span>
                            <span class="booking-wizard__step-label">Extra opties</span>
                        </li>
                        <li class="booking-wizard__step" data-step="3">
                            <span class="booking-wizard__step-num">3</span>
                            <span class="booking-wizard__step-label">Gegevens</span>
                        </li>
                    </ol>

                    <div class="booking-wizard__layout">
                        <div class="booking-wizard__main">
                            <!-- Step 1: dates + slot -->
                            <section class="booking-wizard__panel is-active" data-panel="1">
                                <div class="booking-wizard__panel-head">
                                    <h3>Kies je datum</h3>
                                    <p>Selecteer één of meerdere dagen in de kalender hieronder.</p>
                                </div>
                                <div class="booking-calendar" data-calendar></div>
                                <div class="booking-legend">
                                    <span class="booking-legend__item"><span class="booking-legend__dot booking-legend__dot--available"></span>Beschikbaar</span>
                                    <span class="booking-legend__item"><span class="booking-legend__dot booking-legend__dot--selected"></span>Geselecteerd</span>
                                    <span class="booking-legend__item"><span class="booking-legend__dot booking-legend__dot--closed"></span>Niet beschikbaar</span>
                                </div>

                                <div class="booking-wizard__panel-head booking-wizard__panel-head--secondary" data-slots-section hidden>
                                    <h3>Kies een tijdsblok</h3>
                                </div>
                                <div class="booking-slots" data-slots hidden></div>
                            </section>

                            <!-- Step 2: extras -->
                            <section class="booking-wizard__panel" data-panel="2" hidden>
                                <div class="booking-wizard__panel-head">
                                    <h3>Extra opties</h3>
                                    <p>Voeg toe wat je verder nodig hebt — kan ook later nog gewijzigd worden.</p>
                                </div>
                                <div class="booking-extras" data-extras></div>
                            </section>

                            <!-- Step 3: gegevens -->
                            <section class="booking-wizard__panel" data-panel="3" hidden>
                                <div class="booking-wizard__panel-head">
                                    <h3>Jouw gegevens</h3>
                                    <p>Laat je gegevens achter zodat we contact met je kunnen opnemen om de reservering te bevestigen.</p>
                                </div>
                                <form class="booking-form" data-booking-form novalidate>
                                    <div class="booking-form__row booking-form__row--two">
                                        <label class="booking-form__field">
                                            <span>Voornaam *</span>
                                            <input type="text" name="first_name" required autocomplete="given-name">
                                        </label>
                                        <label class="booking-form__field">
                                            <span>Achternaam *</span>
                                            <input type="text" name="last_name" required autocomplete="family-name">
                                        </label>
                                    </div>
                                    <div class="booking-form__row booking-form__row--two">
                                        <label class="booking-form__field">
                                            <span>E-mailadres *</span>
                                            <input type="email" name="email" required autocomplete="email">
                                        </label>
                                        <label class="booking-form__field">
                                            <span>Telefoonnummer *</span>
                                            <input type="tel" name="phone" required autocomplete="tel">
                                        </label>
                                    </div>
                                    <div class="booking-form__row">
                                        <label class="booking-form__field">
                                            <span>Adres *</span>
                                            <input type="text" name="address" required autocomplete="street-address" placeholder="Straat + huisnummer">
                                        </label>
                                    </div>
                                    <div class="booking-form__row booking-form__row--two">
                                        <label class="booking-form__field">
                                            <span>Postcode *</span>
                                            <input type="text" name="postal_code" required autocomplete="postal-code">
                                        </label>
                                        <label class="booking-form__field">
                                            <span>Plaats *</span>
                                            <input type="text" name="city" required autocomplete="address-level2">
                                        </label>
                                    </div>
                                    <div class="booking-form__row">
                                        <label class="booking-form__field">
                                            <span>Eventuele opmerkingen</span>
                                            <textarea name="message" rows="3"></textarea>
                                        </label>
                                    </div>

                                    <?php if ($deposit_amount > 0) : ?>
                                    <div class="booking-form__row">
                                        <fieldset class="booking-form__choice">
                                            <legend>Borg (€ <?php echo number_format($deposit_amount, 2, ',', '.'); ?>) *</legend>
                                            <?php if ($mollie_active) : ?>
                                                <label class="booking-form__choice-option">
                                                    <input type="radio" name="deposit_method" value="online" required checked>
                                                    <span>
                                                        <strong>Online betalen</strong>
                                                        <small>Wordt automatisch teruggestort binnen 48u na de laatste huurdag</small>
                                                    </span>
                                                </label>
                                                <label class="booking-form__choice-option">
                                                    <input type="radio" name="deposit_method" value="cash">
                                                    <span>
                                                        <strong>Cash op locatie</strong>
                                                        <small>Bij ophalen contant of pin afrekenen</small>
                                                    </span>
                                                </label>
                                            <?php else : ?>
                                                <input type="hidden" name="deposit_method" value="cash">
                                                <div class="booking-form__notice">
                                                    <i class="fa-solid fa-circle-info"></i>
                                                    <span>De borg van € <?php echo number_format($deposit_amount, 2, ',', '.'); ?> reken je <strong>cash of per pin op locatie</strong> af bij het ophalen.</span>
                                                </div>
                                            <?php endif; ?>
                                        </fieldset>
                                    </div>
                                    <?php endif; ?>

                                    <div class="booking-form__row">
                                        <label class="booking-form__check">
                                            <input type="checkbox" name="accept_terms" required>
                                            <span>Ik ga akkoord met de <a href="<?php echo esc_url(home_url('/algemene-voorwaarden/')); ?>" target="_blank">algemene voorwaarden</a> *</span>
                                        </label>
                                    </div>
                                </form>
                                <div class="booking-success" data-booking-success hidden>
                                    <div class="booking-success__icon"><i class="fa-solid fa-circle-check"></i></div>
                                    <h3>Aanvraag verstuurd!</h3>
                                    <p data-success-message>We hebben je aanvraag ontvangen. We nemen zo snel mogelijk contact met je op.</p>
                                </div>
                            </section>

                            <div class="booking-wizard__nav" data-nav>
                                <button type="button" class="booking-wizard__btn booking-wizard__btn--ghost" data-prev hidden>
                                    <i class="fa-solid fa-arrow-left"></i> Vorige
                                </button>
                                <button type="button" class="booking-wizard__btn booking-wizard__btn--primary" data-next disabled>
                                    Volgende <i class="fa-solid fa-arrow-right"></i>
                                </button>
                                <button type="button" class="booking-wizard__btn booking-wizard__btn--primary" data-submit hidden>
                                    Naar betaling <i class="fa-solid fa-credit-card"></i>
                                </button>
                            </div>
                        </div>

                        <aside class="booking-summary" data-summary>
                            <h3 class="booking-summary__title">Jouw reservering</h3>

                            <div class="booking-summary__section">
                                <span class="booking-summary__label">Boot</span>
                                <span class="booking-summary__value"><?php echo esc_html(get_the_title()); ?></span>
                            </div>

                            <div class="booking-summary__section">
                                <span class="booking-summary__label">Datum(s)</span>
                                <span class="booking-summary__value" data-summary-dates>—</span>
                            </div>

                            <div class="booking-summary__section">
                                <span class="booking-summary__label">Tijdsblok</span>
                                <span class="booking-summary__value" data-summary-slot>—</span>
                            </div>

                            <div class="booking-summary__section" data-summary-extras-wrap hidden>
                                <span class="booking-summary__label">Extra opties</span>
                                <ul class="booking-summary__extras" data-summary-extras></ul>
                            </div>

                            <div class="booking-summary__total">
                                <span class="booking-summary__label">Totaal</span>
                                <span class="booking-summary__value booking-summary__value--total" data-summary-total>€ 0,00</span>
                            </div>
                            <p class="booking-summary__small">Excl. brandstof — bevestiging via mail.</p>
                        </aside>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (is_array($gallery) && !empty($gallery)) : ?>
            <section class="visboot-gallery">
                <div class="visboot-gallery__container">
                    <h2 class="visboot-gallery__title">Foto's</h2>
                    <ul class="visboot-gallery__grid">
                        <?php foreach ($gallery as $img) :
                            $url     = $img['url']               ?? '';
                            $thumb   = $img['sizes']['large']    ?? $url;
                            $alt     = $img['alt']               ?? '';
                            if (!$url) { continue; }
                            ?>
                            <li class="visboot-gallery__item">
                                <a href="<?php echo esc_url($url); ?>" class="visboot-gallery__link" target="_blank" rel="noopener">
                                    <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy">
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>

        <?php if ((is_array($specifications) && !empty($specifications)) || (is_array($features) && !empty($features))) : ?>
            <section class="visboot-details">
                <div class="visboot-details__container">
                    <div class="visboot-details__grid">
                        <?php if (is_array($specifications) && !empty($specifications)) : ?>
                            <div class="visboot-details__panel visboot-details__panel--specs">
                                <h2 class="visboot-details__title">Specificaties</h2>
                                <dl class="visboot-specs">
                                    <?php foreach ($specifications as $spec) :
                                        $icon  = isset($spec['icon'])  ? trim((string) $spec['icon'])  : '';
                                        $label = isset($spec['label']) ? trim((string) $spec['label']) : '';
                                        $value = isset($spec['value']) ? trim((string) $spec['value']) : '';
                                        if ($label === '' && $value === '') { continue; }
                                        ?>
                                        <div class="visboot-specs__row">
                                            <?php if ($icon !== '') : ?>
                                                <span class="visboot-specs__icon">
                                                    <i class="<?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
                                                </span>
                                            <?php endif; ?>
                                            <dt class="visboot-specs__label"><?php echo esc_html($label); ?></dt>
                                            <dd class="visboot-specs__value"><?php echo esc_html($value); ?></dd>
                                        </div>
                                    <?php endforeach; ?>
                                </dl>
                            </div>
                        <?php endif; ?>

                        <?php if (is_array($features) && !empty($features)) : ?>
                            <div class="visboot-details__panel visboot-details__panel--features">
                                <h2 class="visboot-details__title">Uitrusting</h2>
                                <ul class="visboot-features">
                                    <?php foreach ($features as $feature) :
                                        $icon = isset($feature['icon']) ? trim((string) $feature['icon']) : '';
                                        $text = isset($feature['text']) ? trim((string) $feature['text']) : '';
                                        if ($text === '') { continue; }
                                        ?>
                                        <li class="visboot-features__item">
                                            <span class="visboot-features__icon" aria-hidden="true">
                                                <i class="<?php echo esc_attr($icon !== '' ? $icon : 'fa-solid fa-check'); ?>"></i>
                                            </span>
                                            <span class="visboot-features__text"><?php echo esc_html($text); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (is_array($faqs) && !empty($faqs)) : ?>
            <section class="visboot-faq">
                <div class="visboot-faq__container">
                    <h2 class="visboot-faq__title">Veelgestelde vragen</h2>
                    <ul class="visboot-faq__list">
                        <?php foreach ($faqs as $idx => $faq) :
                            $q = isset($faq['question']) ? trim((string) $faq['question']) : '';
                            $a = isset($faq['answer'])   ? (string) $faq['answer']         : '';
                            if ($q === '') { continue; }
                            $panel_id = 'visboot-faq-' . get_the_ID() . '-' . $idx;
                            ?>
                            <li class="visboot-faq__item">
                                <button type="button"
                                        class="visboot-faq__question"
                                        aria-expanded="false"
                                        aria-controls="<?php echo esc_attr($panel_id); ?>">
                                    <span class="visboot-faq__question-text"><?php echo esc_html($q); ?></span>
                                    <span class="visboot-faq__chevron" aria-hidden="true">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </span>
                                </button>
                                <div id="<?php echo esc_attr($panel_id); ?>" class="visboot-faq__answer" hidden>
                                    <div class="visboot-faq__answer-inner">
                                        <?php echo wp_kses_post($a); ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>
    </article>

<?php endwhile;
get_footer();
