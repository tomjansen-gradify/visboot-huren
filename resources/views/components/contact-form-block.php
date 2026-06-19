<?php
/**
 * Contact form block — ACF render template.
 */

$title          = (string) get_field('title') ?: 'Plan jouw proefvaart';
$intro          = (string) get_field('intro');
$submit_label   = (string) get_field('submit_label') ?: 'Versturen';
$success_msg    = (string) get_field('success_message') ?: 'Bedankt voor je bericht! We nemen zo snel mogelijk contact met je op.';

$rest_url = esc_url_raw(rest_url('visboothuren/v1/contact'));
$nonce    = wp_create_nonce('wp_rest');
$email_to = (string) get_field('email_to');

// Contact-info uit Site Instellingen
$cf_email        = (string) get_field('contact_email', 'option');
$cf_phone        = (string) get_field('contact_phone', 'option');
$cf_phone_link   = (string) get_field('contact_phone_link', 'option');
$cf_address      = (string) get_field('contact_address', 'option');
$cf_address_link = (string) get_field('contact_address_link', 'option');

$cf_items = [];
if ($cf_email) {
    $cf_items[] = ['icon' => 'fa-solid fa-envelope', 'value' => $cf_email, 'link' => 'mailto:' . $cf_email];
}
if ($cf_address) {
    $cf_items[] = ['icon' => 'fa-solid fa-location-dot', 'value' => $cf_address, 'link' => $cf_address_link];
}
if ($cf_phone) {
    $cf_items[] = ['icon' => 'fa-solid fa-phone', 'value' => $cf_phone, 'link' => $cf_phone_link ? 'tel:' . $cf_phone_link : ''];
}

$cf_socials = [
    ['key' => 'facebook',  'url' => (string) get_field('social_facebook',  'option'), 'icon' => 'fa-brands fa-facebook-f',  'label' => 'Facebook'],
    ['key' => 'instagram', 'url' => (string) get_field('social_instagram', 'option'), 'icon' => 'fa-brands fa-instagram',   'label' => 'Instagram'],
    ['key' => 'linkedin',  'url' => (string) get_field('social_linkedin',  'option'), 'icon' => 'fa-brands fa-linkedin-in', 'label' => 'LinkedIn'],
];
$cf_socials = array_values(array_filter($cf_socials, fn($s) => $s['url'] !== ''));

$page_heading = trim((string) (function_exists('get_the_title') ? get_the_title() : ''));
if ($page_heading === '') { $page_heading = 'Contact'; }
?>
<section class="contact-form-block">
    <div class="contact-form-block__container">
        <header class="contact-form-block__page-heading">
            <h1 class="contact-form-block__page-title"><?php echo esc_html($page_heading); ?></h1>
            <span class="contact-form-block__divider" aria-hidden="true"></span>
        </header>

        <?php if (!empty($cf_socials)) : ?>
            <ul class="contact-form-block__socials">
                <?php foreach ($cf_socials as $s) : ?>
                    <li class="contact-form-block__social contact-form-block__social--<?php echo esc_attr($s['key']); ?>">
                        <a href="<?php echo esc_url($s['url']); ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="<?php echo esc_attr($s['label']); ?>">
                            <i class="<?php echo esc_attr($s['icon']); ?>" aria-hidden="true"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($cf_items)) : ?>
            <ul class="contact-form-block__info">
                <?php foreach ($cf_items as $item) : ?>
                    <li class="contact-form-block__info-card">
                        <span class="contact-form-block__info-icon"><i class="<?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></i></span>
                        <p class="contact-form-block__info-value">
                            <?php if (!empty($item['link'])) : ?>
                                <a href="<?php echo esc_url($item['link']); ?>"><?php echo nl2br(esc_html($item['value'])); ?></a>
                            <?php else : ?>
                                <?php echo nl2br(esc_html($item['value'])); ?>
                            <?php endif; ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="contact-form-block__header">
            <h2 class="contact-form-block__title"><?php echo esc_html($title); ?></h2>
            <?php if ($intro !== '') : ?>
                <p class="contact-form-block__intro"><?php echo esc_html($intro); ?></p>
            <?php endif; ?>
            <p class="contact-form-block__required-note"><span aria-hidden="true">*</span> geeft vereiste velden aan</p>
        </div>

        <form class="contact-form"
              data-contact-form
              data-endpoint="<?php echo esc_attr($rest_url); ?>"
              data-nonce="<?php echo esc_attr($nonce); ?>"
              data-success="<?php echo esc_attr($success_msg); ?>"
              novalidate>
            <?php if ($email_to !== '') : ?>
                <input type="hidden" name="email_to" value="<?php echo esc_attr($email_to); ?>">
            <?php endif; ?>
            <div class="contact-form__row">
                <label class="contact-form__field">
                    <span class="contact-form__label">Naam <em>*</em></span>
                    <input type="text" name="name" required autocomplete="name" placeholder="Naam *">
                </label>
            </div>
            <div class="contact-form__row contact-form__row--two">
                <label class="contact-form__field">
                    <span class="contact-form__label">E-mailadres <em>*</em></span>
                    <input type="email" name="email" required autocomplete="email" placeholder="E-mailadres *">
                </label>
                <label class="contact-form__field">
                    <span class="contact-form__label">Telefoonnummer</span>
                    <input type="tel" name="phone" autocomplete="tel" placeholder="Telefoonnummer">
                </label>
            </div>
            <div class="contact-form__row contact-form__row--two">
                <label class="contact-form__field">
                    <span class="contact-form__label">Voorkeursdatum</span>
                    <input type="date" name="preference_date" placeholder="Voorkeursdatum">
                </label>
                <label class="contact-form__field">
                    <span class="contact-form__label">Voorkeurstijd</span>
                    <input type="time" name="preference_time" placeholder="Voorkeurstijd">
                </label>
            </div>
            <div class="contact-form__row">
                <label class="contact-form__field">
                    <span class="contact-form__label">Opmerkingen <em>*</em></span>
                    <textarea name="message" rows="6" required placeholder="Opmerkingen *"></textarea>
                </label>
            </div>
            <button type="submit" class="contact-form__submit">
                <?php echo esc_html($submit_label); ?>
            </button>
        </form>

        <div class="contact-form-block__success" data-contact-success hidden>
            <div class="contact-form-block__success-icon"><i class="fa-solid fa-circle-check"></i></div>
            <h3 data-contact-success-msg><?php echo esc_html($success_msg); ?></h3>
        </div>
    </div>
</section>
