<?php

declare(strict_types=1);

namespace Gradify\Services;

use Gradify\Traits\Register;

class CronService
{
    use Register;

    private const HOOK              = 'visboothuren_daily_cron';
    private const RETENTION_DEFAULT = 90;

    public function boot(): void
    {
        add_action(self::HOOK, [$this, 'runDaily']);

        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time() + 60, 'daily', self::HOOK);
        }
    }

    public function runDaily(): void
    {
        $this->processDepositRefunds();
        $this->anonymizeOldBookings();
    }

    /**
     * Refund the deposit 48h after the last rental day for online-deposit bookings.
     */
    private function processDepositRefunds(): void
    {
        $today = strtotime(date('Y-m-d'));

        $query = new \WP_Query([
            'post_type'      => 'booking',
            'post_status'    => 'any',
            'posts_per_page' => 100,
            'meta_query'     => [
                'relation' => 'AND',
                ['key' => 'deposit_method',     'value' => 'online'],
                ['key' => 'status',             'value' => 'paid'],
                ['key' => 'deposit_refund_id',  'compare' => 'NOT EXISTS'],
            ],
        ]);

        foreach ($query->posts as $post) {
            $booking_id = (int) $post->ID;
            $dates      = (array) get_post_meta($booking_id, 'dates', true);
            if (empty($dates)) { continue; }
            sort($dates);
            $last_date = end($dates);
            $refund_due = strtotime($last_date . ' +2 days');
            if ($refund_due === false || $refund_due > $today) {
                continue;
            }

            $boat_id = (int) get_post_meta($booking_id, 'boat_id', true);
            $deposit = (float) (get_field('deposit_amount', $boat_id) ?: 150);
            $payment_id = (string) get_post_meta($booking_id, 'mollie_payment_id', true);
            if ($deposit <= 0 || $payment_id === '') { continue; }

            $refund = MollieService::createRefund(
                $payment_id,
                $deposit,
                'Borg retour — boeking #' . $booking_id,
                ['booking_id' => $booking_id, 'reason' => 'deposit_return']
            );

            if (is_wp_error($refund)) {
                update_post_meta($booking_id, 'deposit_refund_error', $refund->get_error_message());
                continue;
            }

            update_post_meta($booking_id, 'deposit_refund_id', $refund['id'] ?? '');
            update_post_meta($booking_id, 'deposit_refund_status', $refund['status'] ?? 'processed');
            update_post_meta($booking_id, 'deposit_refunded_at', current_time('mysql'));

            $finance = function_exists('get_field') ? (string) get_field('finance_email', 'option') : '';
            $contact = function_exists('get_field') ? (string) get_field('contact_email', 'option') : '';
            $notify  = $finance ?: ($contact ?: get_option('admin_email'));
            $client  = (string) get_post_meta($booking_id, 'email', true);
            $name    = (string) get_post_meta($booking_id, 'name', true);

            if ($notify) {
                wp_mail(
                    $notify,
                    '💸 Borg teruggestort — boeking #' . $booking_id,
                    "Borg van € " . number_format($deposit, 2, ',', '.') . " is teruggestort voor boeking #{$booking_id} ({$name})."
                );
            }
            if ($client) {
                wp_mail(
                    $client,
                    'Borg teruggestort — Visboot huren',
                    "Hoi {$name},\n\nJe borg van € " . number_format($deposit, 2, ',', '.') . " is teruggestort. Bedankt voor het huren!\n\nGroet,\nVisboot huren"
                );
            }
        }

        wp_reset_postdata();
    }

    /**
     * Anonymize bookings older than the retention period (default 90 days).
     * Keeps boat/dates/total for accounting (7 year legal requirement).
     */
    private function anonymizeOldBookings(): void
    {
        $retention_days = function_exists('get_field')
            ? (int) get_field('data_retention_days', 'option')
            : self::RETENTION_DEFAULT;
        if ($retention_days <= 0) { $retention_days = self::RETENTION_DEFAULT; }

        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $retention_days . ' days'));

        $query = new \WP_Query([
            'post_type'      => 'booking',
            'post_status'    => 'any',
            'posts_per_page' => 100,
            'date_query'     => [
                ['column' => 'post_date', 'before' => $cutoff],
            ],
            'meta_query'     => [
                ['key' => 'data_anonymized', 'compare' => 'NOT EXISTS'],
            ],
        ]);

        foreach ($query->posts as $post) {
            $booking_id = (int) $post->ID;
            $fields_to_clear = ['name', 'first_name', 'last_name', 'email', 'phone', 'address', 'postal_code', 'city'];
            foreach ($fields_to_clear as $field) {
                update_post_meta($booking_id, $field, '[verwijderd]');
            }

            wp_update_post([
                'ID'          => $booking_id,
                'post_title'  => 'Boeking #' . $booking_id . ' — [geanonimiseerd]',
                'post_content'=> '',
            ]);

            update_post_meta($booking_id, 'data_anonymized', 1);
            update_post_meta($booking_id, 'anonymized_at',  current_time('mysql'));
        }

        wp_reset_postdata();
    }
}
