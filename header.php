<?php
/**
 * @package visboothuren
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$vbh_email      = function_exists('get_field') ? (string) get_field('contact_email', 'option') : '';
$vbh_phone      = function_exists('get_field') ? (string) get_field('contact_phone', 'option') : '';
$vbh_phone_link = function_exists('get_field') ? (string) get_field('contact_phone_link', 'option') : '';
if (!$vbh_email)      { $vbh_email      = 'info@visboot-huren.nl'; }
if (!$vbh_phone)      { $vbh_phone      = '+31 6 19709015'; }
if (!$vbh_phone_link) { $vbh_phone_link = '+31619709015'; }
?>
<div id="page">
    <header id="site-header" class="site-header">
        <div class="site-header__desktop">
            <div class="site-header__container">
                <div class="site-header__logo">
                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                        <?php
                        $logo = get_template_directory_uri() . '/resources/images/logo/logo-visboothuren.png';
                        $custom_logo_id = get_theme_mod('custom_logo');
                        if ($custom_logo_id) {
                            echo wp_get_attachment_image($custom_logo_id, 'full', false, [
                                'class' => 'site-header__logo-img',
                                'alt'   => get_bloginfo('name'),
                            ]);
                        } else {
                            printf(
                                '<img class="site-header__logo-img" src="%s" alt="%s">',
                                esc_url($logo),
                                esc_attr(get_bloginfo('name'))
                            );
                        }
                        ?>
                    </a>
                </div>
                <div class="site-header__right">
                    <div class="site-header__topbar">
                        <a href="mailto:<?php echo esc_attr($vbh_email); ?>" class="site-header__contact site-header__contact--mail">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            <span><?php echo esc_html($vbh_email); ?></span>
                        </a>
                        <span class="site-header__sep">|</span>
                        <a href="tel:<?php echo esc_attr($vbh_phone_link); ?>" class="site-header__contact site-header__contact--phone">
                            <i class="fas fa-phone-alt" aria-hidden="true"></i>
                            <span><?php echo esc_html($vbh_phone); ?></span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/visboten/')); ?>" class="site-header__cta">
                            <?php _e('Bekijk beschikbaarheid', 'visboothuren'); ?>
                        </a>
                    </div>
                    <nav class="site-header__nav" aria-label="<?php esc_attr_e('Hoofdmenu', 'visboothuren'); ?>">
                        <?php
                        wp_nav_menu([
                            'theme_location' => 'primary',
                            'container'      => false,
                            'menu_class'     => 'site-header__menu',
                            'fallback_cb'    => '__return_false',
                        ]);
                        ?>
                    </nav>
                </div>
            </div>
        </div>
        <div class="site-header__mobile">
            <div class="site-header__container">
                <div class="site-header__logo">
                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                        <?php
                        if ($custom_logo_id) {
                            echo wp_get_attachment_image($custom_logo_id, 'full', false, [
                                'class' => 'site-header__logo-img',
                                'alt'   => get_bloginfo('name'),
                            ]);
                        } else {
                            printf(
                                '<img class="site-header__logo-img" src="%s" alt="%s">',
                                esc_url($logo),
                                esc_attr(get_bloginfo('name'))
                            );
                        }
                        ?>
                    </a>
                </div>
                <div class="site-header__mobile-actions">
                    <a href="tel:<?php echo esc_attr($vbh_phone_link); ?>" class="site-header__mobile-phone" aria-label="<?php esc_attr_e('Bel ons', 'visboothuren'); ?>">
                        <i class="fas fa-phone-alt" aria-hidden="true"></i>
                    </a>
                    <button type="button" class="site-header__hamburger" aria-label="<?php esc_attr_e('Open menu', 'visboothuren'); ?>" aria-expanded="false" aria-controls="site-mobile-menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
            <div id="site-mobile-menu" class="site-header__mobile-menu" hidden>
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'site-header__menu site-header__menu--mobile',
                    'fallback_cb'    => '__return_false',
                ]);
                ?>
                <a href="<?php echo esc_url(home_url('/visboten/')); ?>" class="site-header__cta site-header__cta--mobile">
                    <?php _e('Bekijk beschikbaarheid', 'visboothuren'); ?>
                </a>
            </div>
        </div>
    </header>
    <main id="main">
