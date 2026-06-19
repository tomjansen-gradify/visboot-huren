<?php
/**
 * Contact info block — 3 cards met contact details uit Site Instellingen.
 */

$email        = (string) get_field('contact_email', 'option');
$phone        = (string) get_field('contact_phone', 'option');
$phone_link   = (string) get_field('contact_phone_link', 'option');
$address      = (string) get_field('contact_address', 'option');
$address_link = (string) get_field('contact_address_link', 'option');

$socials = [
    ['key' => 'facebook',  'url' => (string) get_field('social_facebook',  'option'), 'icon' => 'fa-brands fa-facebook-f',  'label' => 'Facebook'],
    ['key' => 'instagram', 'url' => (string) get_field('social_instagram', 'option'), 'icon' => 'fa-brands fa-instagram',   'label' => 'Instagram'],
    ['key' => 'linkedin',  'url' => (string) get_field('social_linkedin',  'option'), 'icon' => 'fa-brands fa-linkedin-in', 'label' => 'LinkedIn'],
];
$socials = array_values(array_filter($socials, fn($s) => $s['url'] !== ''));

$items = [];
if ($email) {
    $items[] = [
        'icon'  => 'fa-solid fa-envelope',
        'label' => 'E-mail',
        'value' => $email,
        'link'  => 'mailto:' . $email,
    ];
}
if ($address) {
    $items[] = [
        'icon'  => 'fa-solid fa-location-dot',
        'label' => 'Locatie',
        'value' => $address,
        'link'  => $address_link,
    ];
}
if ($phone) {
    $items[] = [
        'icon'  => 'fa-solid fa-phone',
        'label' => 'Telefoon',
        'value' => $phone,
        'link'  => $phone_link ? 'tel:' . $phone_link : '',
    ];
}
if (empty($items) && empty($socials)) {
    return;
}
?>
<section class="contact-info">
    <div class="contact-info__container">
        <?php if (!empty($socials)) : ?>
            <ul class="contact-info__socials">
                <?php foreach ($socials as $s) : ?>
                    <li class="contact-info__social contact-info__social--<?php echo esc_attr($s['key']); ?>">
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

        <?php if (!empty($items)) : ?>
            <ul class="contact-info__list">
                <?php foreach ($items as $item) : ?>
                    <li class="contact-info__item">
                        <span class="contact-info__icon"><i class="<?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></i></span>
                        <p class="contact-info__value">
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
    </div>
</section>
