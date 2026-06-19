<?php

declare(strict_types=1);

namespace Gradify\Services;

use Gradify\Traits\Register;
use WP_Error;
use WP_REST_Request;

class BookingService
{
    use Register;

    public function boot(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
        add_action('template_redirect', [$this, 'maybeShowConfirmation']);
    }

    public function registerRoutes(): void
    {
        register_rest_route('visboothuren/v1', '/booking', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handleBookingRequest'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('visboothuren/v1', '/contact', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handleContactRequest'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('visboothuren/v1', '/mollie/webhook', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handleMollieWebhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handleBookingRequest(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            $params = $request->get_params();
        }

        $boat_id        = isset($params['boat_id']) ? (int) $params['boat_id'] : 0;
        $dates          = isset($params['dates']) && is_array($params['dates']) ? array_values(array_filter(array_map('sanitize_text_field', $params['dates']))) : [];
        $slot_id        = isset($params['slot_id']) ? sanitize_text_field((string) $params['slot_id']) : '';
        $extras         = isset($params['extras']) && is_array($params['extras']) ? array_values(array_map('sanitize_text_field', $params['extras'])) : [];
        $name           = isset($params['name'])         ? sanitize_text_field((string) $params['name'])         : '';
        $first_name     = isset($params['first_name'])   ? sanitize_text_field((string) $params['first_name'])   : '';
        $last_name      = isset($params['last_name'])    ? sanitize_text_field((string) $params['last_name'])    : '';
        $email          = isset($params['email'])        ? sanitize_email((string) $params['email'])             : '';
        $phone          = isset($params['phone'])        ? sanitize_text_field((string) $params['phone'])        : '';
        $address        = isset($params['address'])      ? sanitize_text_field((string) $params['address'])      : '';
        $postal_code    = isset($params['postal_code'])  ? sanitize_text_field((string) $params['postal_code'])  : '';
        $city           = isset($params['city'])         ? sanitize_text_field((string) $params['city'])         : '';
        $deposit_method = isset($params['deposit_method']) ? sanitize_text_field((string) $params['deposit_method']) : 'cash';
        $accept_terms   = !empty($params['accept_terms']);
        $message        = isset($params['message'])      ? sanitize_textarea_field((string) $params['message']) : '';
        $total          = isset($params['total'])        ? (float) $params['total'] : 0.0;

        $errors = [];
        if ($boat_id <= 0 || get_post_type($boat_id) !== 'visboot') {
            $errors[] = 'Ongeldige boot.';
        }
        if (empty($dates))   { $errors[] = 'Kies minimaal één datum.'; }
        if ($name === '' && ($first_name === '' || $last_name === '')) { $errors[] = 'Vul je voor- en achternaam in.'; }
        if (!is_email($email)) { $errors[] = 'Vul een geldig e-mailadres in.'; }
        if ($phone === '')     { $errors[] = 'Vul je telefoonnummer in.'; }
        if ($address === '')   { $errors[] = 'Vul je adres in.'; }
        if ($postal_code === ''){ $errors[] = 'Vul je postcode in.'; }
        if ($city === '')      { $errors[] = 'Vul je plaats in.'; }
        if (!$accept_terms)    { $errors[] = 'Je moet akkoord gaan met de algemene voorwaarden.'; }
        if ($total <= 0)       { $errors[] = 'Het totaalbedrag is 0 — controleer je selectie.'; }
        if ($name === '') { $name = trim($first_name . ' ' . $last_name); }

        if (!empty($errors)) {
            return new WP_Error('booking_invalid', implode(' ', $errors), ['status' => 422, 'errors' => $errors]);
        }

        $boat_title = get_the_title($boat_id);
        $title = sprintf(
            '%s — %s — %s',
            $boat_title ?: 'Boot #' . $boat_id,
            !empty($dates) ? $dates[0] : 'geen datum',
            $name ?: 'onbekend'
        );

        $post_id = wp_insert_post([
            'post_type'    => 'booking',
            'post_status'  => 'pending',
            'post_title'   => $title,
            'post_content' => $message,
            'meta_input'   => [
                'boat_id'        => $boat_id,
                'dates'          => $dates,
                'slot_id'        => $slot_id,
                'extras'         => $extras,
                'name'           => $name,
                'first_name'     => $first_name,
                'last_name'      => $last_name,
                'email'          => $email,
                'phone'          => $phone,
                'address'        => $address,
                'postal_code'    => $postal_code,
                'city'           => $city,
                'deposit_method' => $deposit_method,
                'accept_terms'   => $accept_terms ? 1 : 0,
                'terms_accepted_at' => current_time('mysql'),
                'total'          => $total,
                'status'         => 'pending',
                'refund_due_date' => !empty($dates) ? date('Y-m-d', strtotime(max($dates) . ' +2 days')) : '',
            ],
        ], true);

        if (is_wp_error($post_id)) {
            return new WP_Error('booking_failed', 'Kon boeking niet opslaan.', ['status' => 500]);
        }

        $mollie_active = MollieService::isConfigured();
        if ($deposit_method === 'cash' || !$mollie_active) {
            $contact_email = function_exists('get_field') ? (string) get_field('contact_email', 'option') : '';
            $admin_email = $contact_email ?: get_option('admin_email');

            $extras_str = !empty($extras) ? implode(', ', $extras) : '-';
            if ($admin_email) {
                wp_mail(
                    $admin_email,
                    'Nieuwe reservering (cash op locatie) — ' . $boat_title,
                    "Reservering wordt cash afgerekend op locatie.\n\nNaam: $name\nEmail: $email\nTelefoon: $phone\nAdres: $address, $postal_code $city\nBoot: $boat_title\nDatums: " . implode(', ', $dates) . "\nExtras: $extras_str\nTotaal: € $total\n\nOpmerking:\n$message\n\nBekijk in admin: " . admin_url('post.php?action=edit&post=' . $post_id)
                );
            }
            if ($email) {
                wp_mail(
                    $email,
                    'Reservering ontvangen — ' . $boat_title,
                    "Hoi $name,\n\nBedankt voor je reservering!\n\nBoot: $boat_title\nDatums: " . implode(', ', $dates) . "\nTotaal: € $total\n\nWe nemen binnen 24u contact met je op om de reservering te bevestigen. Op locatie reken je het bedrag contant of per pin af.\n\nGroet,\nVisboot huren"
                );
            }

            return [
                'success' => true,
                'id'      => $post_id,
                'mode'    => 'message',
                'message' => 'Bedankt! Je reservering is ontvangen. We nemen binnen 24u contact met je op om te bevestigen. Op locatie reken je contant of per pin af.',
            ];
        }

        if (MollieService::isConfigured()) {
            $description = sprintf('Boeking #%d — %s', $post_id, $boat_title);
            $redirect_url = add_query_arg(
                ['vbh_booking' => $post_id],
                home_url('/boeking-bevestiging/')
            );
            $webhook_url = rest_url('visboothuren/v1/mollie/webhook');

            $payload = [
                'amount' => [
                    'currency' => 'EUR',
                    'value'    => number_format($total, 2, '.', ''),
                ],
                'description'  => $description,
                'redirectUrl'  => $redirect_url,
                'webhookUrl'   => $webhook_url,
                'metadata'     => [
                    'booking_id' => $post_id,
                    'boat_id'    => $boat_id,
                ],
            ];

            $methods = MollieService::getAllowedMethods($boat_id);
            if (!empty($methods)) {
                $payload['method'] = array_values($methods);
            }

            $payment = MollieService::createPayment($payload);

            if (is_wp_error($payment)) {
                update_post_meta($post_id, 'status', 'mollie_failed');
                update_post_meta($post_id, 'mollie_error', $payment->get_error_message());
                return new WP_Error(
                    'mollie_failed',
                    'Betaling kon niet worden aangemaakt: ' . $payment->get_error_message(),
                    ['status' => 502]
                );
            }

            update_post_meta($post_id, 'mollie_payment_id', $payment['id'] ?? '');
            update_post_meta($post_id, 'mollie_status', $payment['status'] ?? 'open');

            $checkout = $payment['_links']['checkout']['href'] ?? '';

            return [
                'success'      => true,
                'id'           => $post_id,
                'mode'         => 'redirect',
                'checkout_url' => $checkout,
                'message'      => 'Je wordt doorgestuurd naar de beveiligde betaalomgeving van Mollie.',
            ];
        }

        $admin_email = function_exists('get_field') ? get_field('contact_email', 'option') : '';
        $admin_email = $admin_email ?: get_option('admin_email');
        if ($admin_email) {
            wp_mail(
                $admin_email,
                'Nieuwe boekingsaanvraag — ' . $boat_title,
                "Naam: $name\nEmail: $email\nTelefoon: $phone\nBoot: $boat_title\nDatums: " . implode(', ', $dates) . "\nSlot: $slot_id\nExtras: " . implode(', ', $extras) . "\nTotaal: € $total\n\nOpmerking:\n$message"
            );
        }

        return [
            'success' => true,
            'id'      => $post_id,
            'mode'    => 'message',
            'message' => 'We hebben je aanvraag ontvangen. We nemen zo snel mogelijk contact met je op.',
        ];
    }

    public function handleContactRequest(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            $params = $request->get_params();
        }
        $name    = isset($params['name'])    ? sanitize_text_field((string) $params['name'])    : '';
        $email   = isset($params['email'])   ? sanitize_email((string) $params['email'])        : '';
        $phone   = isset($params['phone'])   ? sanitize_text_field((string) $params['phone'])   : '';
        $message = isset($params['message']) ? sanitize_textarea_field((string) $params['message']) : '';

        $errors = [];
        if ($name === '')      { $errors[] = 'Vul je naam in.'; }
        if (!is_email($email)) { $errors[] = 'Vul een geldig e-mailadres in.'; }
        if ($message === '')   { $errors[] = 'Vul een bericht in.'; }
        if (!empty($errors)) {
            return new WP_Error('contact_invalid', implode(' ', $errors), ['status' => 422]);
        }

        $admin_email = function_exists('get_field') ? get_field('contact_email', 'option') : '';
        $admin_email = $admin_email ?: get_option('admin_email');

        $pref_date = isset($params['preference_date']) ? sanitize_text_field((string) $params['preference_date']) : '';
        $pref_time = isset($params['preference_time']) ? sanitize_text_field((string) $params['preference_time']) : '';
        $extra_lines = [];
        if ($pref_date !== '') { $extra_lines[] = "Voorkeursdatum: {$pref_date}"; }
        if ($pref_time !== '') { $extra_lines[] = "Voorkeurstijd: {$pref_time}"; }
        $form_email_to = isset($params['email_to']) ? sanitize_email((string) $params['email_to']) : '';
        if (is_email($form_email_to)) {
            $admin_email = $form_email_to;
        }

        $body = "Naam: {$name}\nE-mail: {$email}\nTelefoon: {$phone}\n" . (!empty($extra_lines) ? implode("\n", $extra_lines) . "\n" : '') . "\nBericht:\n{$message}";
        $headers = ['Reply-To: ' . $name . ' <' . $email . '>'];
        wp_mail($admin_email, 'Nieuw contactbericht — Visboot huren', $body, $headers);

        return ['success' => true, 'message' => 'Bedankt voor je bericht! We nemen zo snel mogelijk contact met je op.'];
    }

    public function handleMollieWebhook(WP_REST_Request $request)
    {
        $payment_id = sanitize_text_field((string) ($request->get_param('id') ?: ''));
        if ($payment_id === '') {
            return new WP_Error('webhook_no_id', 'No payment id.', ['status' => 400]);
        }

        $payment = MollieService::fetchPayment($payment_id);
        if (is_wp_error($payment)) {
            return $payment;
        }

        $booking_id = (int) ($payment['metadata']['booking_id'] ?? 0);
        if ($booking_id <= 0) {
            return ['ok' => true];
        }

        $status = $payment['status'] ?? 'unknown';
        update_post_meta($booking_id, 'mollie_status', $status);

        if ($status === 'paid') {
            update_post_meta($booking_id, 'status', 'paid');
            wp_update_post([
                'ID'          => $booking_id,
                'post_status' => 'publish',
            ]);

            $boat_title = get_the_title((int) get_post_meta($booking_id, 'boat_id', true));
            $name       = get_post_meta($booking_id, 'name',  true);
            $email      = get_post_meta($booking_id, 'email', true);
            $finance_email = function_exists('get_field') ? (string) get_field('finance_email', 'option') : '';
            $contact_email = function_exists('get_field') ? (string) get_field('contact_email', 'option') : '';
            $notify_email  = $finance_email ?: ($contact_email ?: get_option('admin_email'));
            if ($notify_email) {
                wp_mail(
                    $notify_email,
                    '✅ Betaalde boeking — ' . $boat_title,
                    "De boeking is succesvol betaald.\n\nNaam: $name\nEmail: $email\nBoot: $boat_title\n\nBekijk in admin: " . admin_url('post.php?action=edit&post=' . $booking_id)
                );
            }
            if ($email) {
                wp_mail(
                    $email,
                    'Boeking bevestigd — ' . $boat_title,
                    "Hoi $name,\n\nBedankt voor je boeking! Je betaling is ontvangen.\nWe nemen binnenkort contact met je op met verdere details.\n\nGroet,\nVisboot huren"
                );
            }
        } elseif (in_array($status, ['canceled', 'expired', 'failed'], true)) {
            update_post_meta($booking_id, 'status', $status);
        }

        return ['ok' => true, 'status' => $status];
    }

    public function maybeShowConfirmation(): void
    {
        $booking_id = isset($_GET['vbh_booking']) ? (int) $_GET['vbh_booking'] : 0;
        if ($booking_id <= 0) {
            return;
        }
        if (get_post_type($booking_id) !== 'booking') {
            return;
        }

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/boeking-bevestiging') === false) {
            return;
        }

        $mollie_payment_id = (string) get_post_meta($booking_id, 'mollie_payment_id', true);
        $current_status    = (string) get_post_meta($booking_id, 'status', true);

        if ($mollie_payment_id !== '' && $current_status !== 'paid') {
            $payment = MollieService::fetchPayment($mollie_payment_id);
            if (!is_wp_error($payment)) {
                $status = $payment['status'] ?? '';
                update_post_meta($booking_id, 'mollie_status', $status);
                if ($status === 'paid') {
                    update_post_meta($booking_id, 'status', 'paid');
                    wp_update_post(['ID' => $booking_id, 'post_status' => 'publish']);
                    $current_status = 'paid';
                } elseif (in_array($status, ['canceled', 'expired', 'failed'], true)) {
                    update_post_meta($booking_id, 'status', $status);
                    $current_status = $status;
                }
            }
        }

        $boat_id    = (int) get_post_meta($booking_id, 'boat_id', true);
        $boat_title = $boat_id ? get_the_title($boat_id) : '';
        $name       = (string) get_post_meta($booking_id, 'name',  true);
        $dates      = (array) get_post_meta($booking_id, 'dates', true);
        $slot_id    = (string) get_post_meta($booking_id, 'slot_id', true);
        $total      = (float) get_post_meta($booking_id, 'total', true);

        get_header();
        ?>
        <section class="booking-confirm">
            <div class="booking-confirm__container">
                <?php if ($current_status === 'paid') : ?>
                    <div class="booking-confirm__icon booking-confirm__icon--success">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h1>Bedankt voor je boeking, <?php echo esc_html($name); ?>!</h1>
                    <p class="booking-confirm__lead">Je betaling is ontvangen. We sturen je zo een bevestigingsmail.</p>
                <?php elseif (in_array($current_status, ['canceled', 'expired', 'failed'], true)) : ?>
                    <div class="booking-confirm__icon booking-confirm__icon--error">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <h1>De betaling is niet voltooid</h1>
                    <p class="booking-confirm__lead">Je boeking is nog niet bevestigd — je kan het opnieuw proberen via de boot pagina.</p>
                <?php else : ?>
                    <div class="booking-confirm__icon booking-confirm__icon--pending">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <h1>Even geduld — we wachten op de bevestiging</h1>
                    <p class="booking-confirm__lead">De status van je betaling is nog niet bekend. Je krijgt automatisch een mail zodra alles rond is.</p>
                <?php endif; ?>

                <div class="booking-confirm__details">
                    <div><span>Boot</span><strong><?php echo esc_html($boat_title); ?></strong></div>
                    <div><span>Datums</span><strong><?php echo esc_html(implode(', ', (array) $dates)); ?></strong></div>
                    <div><span>Tijdsblok</span><strong><?php echo esc_html($slot_id); ?></strong></div>
                    <div><span>Totaal</span><strong>€ <?php echo number_format($total, 2, ',', '.'); ?></strong></div>
                </div>

                <p class="booking-confirm__actions">
                    <a class="booking-confirm__btn" href="<?php echo esc_url(home_url('/')); ?>">Terug naar home</a>
                    <?php if ($boat_id) : ?>
                        <a class="booking-confirm__btn booking-confirm__btn--ghost" href="<?php echo esc_url(get_permalink($boat_id)); ?>">Terug naar de boot</a>
                    <?php endif; ?>
                </p>
            </div>
        </section>
        <?php
        get_footer();
        exit;
    }
}
