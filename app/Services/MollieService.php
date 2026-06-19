<?php

declare(strict_types=1);

namespace Gradify\Services;

class MollieService
{
    private const API_BASE = 'https://api.mollie.com/v2';

    public static function getApiKey(): string
    {
        $mode = (string) (function_exists('get_field') ? get_field('mollie_mode', 'option') : 'test');
        if ($mode === 'live') {
            return (string) (function_exists('get_field') ? get_field('mollie_live_key', 'option') : '');
        }
        return (string) (function_exists('get_field') ? get_field('mollie_test_key', 'option') : '');
    }

    public static function isConfigured(): bool
    {
        return self::getApiKey() !== '' && !self::isDisabled();
    }

    public static function isDisabled(): bool
    {
        return function_exists('get_field') && (bool) get_field('mollie_disabled', 'option');
    }

    public static function getAllowedMethods(int $boat_id = 0): array
    {
        $boat_methods = $boat_id ? (function_exists('get_field') ? get_field('mollie_methods', $boat_id) : []) : [];
        if (!empty($boat_methods)) {
            return is_array($boat_methods) ? $boat_methods : [];
        }
        $global = function_exists('get_field') ? get_field('mollie_methods', 'option') : [];
        return is_array($global) ? $global : [];
    }

    public static function createPayment(array $data)
    {
        $key = self::getApiKey();
        if ($key === '') {
            return new \WP_Error('mollie_no_key', 'Mollie API key niet geconfigureerd in Site Instellingen.');
        }

        $response = wp_remote_post(self::API_BASE . '/payments', [
            'method'  => 'POST',
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $key,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($data),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 200 && $code < 300 && is_array($body)) {
            return $body;
        }

        return new \WP_Error(
            'mollie_create_failed',
            'Mollie gaf een fout terug: ' . ($body['detail'] ?? 'onbekende fout'),
            ['status' => $code, 'body' => $body]
        );
    }

    public static function createRefund(string $payment_id, float $amount, string $description = '', array $metadata = [])
    {
        $key = self::getApiKey();
        if ($key === '' || $payment_id === '' || $amount <= 0) {
            return new \WP_Error('mollie_invalid_refund', 'Ongeldige refund aanvraag.');
        }

        $body = [
            'amount' => [
                'currency' => 'EUR',
                'value'    => number_format($amount, 2, '.', ''),
            ],
        ];
        if ($description !== '') { $body['description'] = $description; }
        if (!empty($metadata))   { $body['metadata']    = $metadata; }

        $response = wp_remote_post(self::API_BASE . '/payments/' . rawurlencode($payment_id) . '/refunds', [
            'method'  => 'POST',
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $key,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 200 && $code < 300 && is_array($data)) {
            return $data;
        }

        return new \WP_Error(
            'mollie_refund_failed',
            'Refund mislukt: ' . ($data['detail'] ?? 'onbekende fout'),
            ['status' => $code, 'body' => $data]
        );
    }

    public static function fetchPayment(string $payment_id)
    {
        $key = self::getApiKey();
        if ($key === '' || $payment_id === '') {
            return new \WP_Error('mollie_invalid', 'Ongeldige aanvraag.');
        }

        $response = wp_remote_get(self::API_BASE . '/payments/' . rawurlencode($payment_id), [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $key,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 200 && $code < 300 && is_array($body)) {
            return $body;
        }

        return new \WP_Error('mollie_fetch_failed', 'Kon payment niet ophalen.', ['status' => $code]);
    }
}
