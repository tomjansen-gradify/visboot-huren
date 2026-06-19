<?php
/**
 * CTA block — ACF render template.
 * Contact info wordt uit de globale Site Instellingen gehaald
 * zodat e-mail/telefoon/adres op één plek beheerd worden.
 */

$title       = get_field('title');
if (!$title) {
    $title = function_exists('get_field') ? (string) get_field('cta_default_title', 'option') : '';
}
if (!$title) {
    $title = 'Ook genieten op het water?';
}

$subtitle    = get_field('subtitle');
$bg_color    = get_field('background_color') ?: '#1e2d44';
$bg_image    = get_field('background_image');

$bg_url = '';
if (is_array($bg_image) && !empty($bg_image['url'])) {
    $bg_url = $bg_image['url'];
} elseif (is_string($bg_image) && $bg_image !== '') {
    $bg_url = $bg_image;
}

$global_cta_bg = function_exists('get_field') ? get_field('cta_default_bg_image', 'option') : null;
if ($bg_url === '' && is_array($global_cta_bg) && !empty($global_cta_bg['url'])) {
    $bg_url = $global_cta_bg['url'];
}

$style_parts = ['background-color: ' . $bg_color];
if ($bg_url) {
    $style_parts[] = sprintf(
        'background-image: linear-gradient(180deg, rgba(30,45,68,0.55) 0%%, rgba(30,45,68,0.85) 100%%), url(%s)',
        esc_url($bg_url)
    );
}
$style = implode('; ', $style_parts);

$email        = (string) get_field('contact_email', 'option');
$phone        = (string) get_field('contact_phone', 'option');
$phone_link   = (string) get_field('contact_phone_link', 'option');
$address      = (string) get_field('contact_address', 'option');
$address_link = (string) get_field('contact_address_link', 'option');

$items = [];
if ($email) {
    $items[] = [
        'icon' => 'fa-solid fa-envelope',
        'text' => esc_html($email),
        'link' => 'mailto:' . $email,
    ];
}
if ($address) {
    $items[] = [
        'icon' => 'fa-solid fa-location-dot',
        'text' => nl2br(esc_html($address)),
        'link' => $address_link,
    ];
}
if ($phone) {
    $items[] = [
        'icon' => 'fa-solid fa-phone',
        'text' => esc_html($phone),
        'link' => $phone_link ? 'tel:' . $phone_link : '',
    ];
}
?>
<section class="cta-block" style="<?php echo esc_attr($style); ?>">
    <div class="cta-block__container">
        <?php if ($title) : ?>
            <h2 class="cta-block__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <?php if ($subtitle) : ?>
            <p class="cta-block__subtitle"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>

        <?php if (!empty($items)) : ?>
            <ul class="cta-block__items">
                <?php foreach ($items as $item) : ?>
                    <li class="cta-block__item">
                        <span class="cta-block__icon">
                            <i class="<?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></i>
                        </span>
                        <h4 class="cta-block__item-text">
                            <?php if (!empty($item['link'])) : ?>
                                <a href="<?php echo esc_url($item['link']); ?>">
                                    <?php echo $item['text']; ?>
                                </a>
                            <?php else : ?>
                                <?php echo $item['text']; ?>
                            <?php endif; ?>
                        </h4>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php
        $show_form = (bool) get_field('show_form');
        if ($show_form) :
            $form_title = (string) get_field('form_title') ?: 'Neem contact met ons op';
            $form_email_to = (string) get_field('form_email_to');
            $rest_url   = esc_url_raw(rest_url('visboothuren/v1/contact'));
            $nonce      = wp_create_nonce('wp_rest');
            ?>
            <div class="cta-block__form-wrap">
                <h3 class="cta-block__form-title"><?php echo esc_html($form_title); ?></h3>
                <form class="cta-form contact-form"
                      data-contact-form
                      data-endpoint="<?php echo esc_attr($rest_url); ?>"
                      data-nonce="<?php echo esc_attr($nonce); ?>"
                      data-success="Bedankt voor je bericht! We nemen zo snel mogelijk contact met je op."
                      novalidate>
                    <?php if ($form_email_to !== '') : ?>
                        <input type="hidden" name="email_to" value="<?php echo esc_attr($form_email_to); ?>">
                    <?php endif; ?>
                    <div class="cta-form__row">
                        <label class="cta-form__field">
                            <input type="text" name="name" placeholder="Naam *" required autocomplete="name">
                        </label>
                    </div>
                    <div class="cta-form__row cta-form__row--two">
                        <label class="cta-form__field">
                            <input type="email" name="email" placeholder="E-mailadres *" required autocomplete="email">
                        </label>
                        <label class="cta-form__field">
                            <input type="tel" name="phone" placeholder="Telefoonnummer" autocomplete="tel">
                        </label>
                    </div>
                    <div class="cta-form__row cta-form__row--two">
                        <label class="cta-form__field">
                            <input type="text" name="preference_date" placeholder="Voorkeursdatum" onfocus="this.type='date'" onblur="if(!this.value)this.type='text'">
                        </label>
                        <label class="cta-form__field">
                            <input type="text" name="preference_time" placeholder="Voorkeurstijd" onfocus="this.type='time'" onblur="if(!this.value)this.type='text'">
                        </label>
                    </div>
                    <div class="cta-form__row">
                        <label class="cta-form__field">
                            <textarea name="message" rows="4" placeholder="Opmerkingen *" required></textarea>
                        </label>
                    </div>
                    <button type="submit" class="cta-form__submit">Versturen</button>
                </form>
                <div class="cta-form__success" data-contact-success hidden>
                    <i class="fa-solid fa-circle-check"></i>
                    <p data-contact-success-msg>Bedankt voor je bericht! We nemen zo snel mogelijk contact met je op.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
