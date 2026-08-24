<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * JetPacks Market — small, dependency-free payment helpers.
 *
 * Stripe accepts an integer number of minor currency units. Keeping the
 * conversion here (rather than using floating-point arithmetic at checkout)
 * prevents a displayed quote total and the amount sent to Stripe drifting by
 * one cent.
 */

if (!function_exists('vp_payment_supported_currencies')) {
    /**
     * Currencies exposed by the card-payment UI. All use two decimal minor
     * units, which keeps the amount conversion unambiguous.
     */
    function vp_payment_supported_currencies()
    {
        return [
            'USD' => 'USD — US Dollar',
            'EUR' => 'EUR — Euro',
            'GBP' => 'GBP — Pound Sterling',
            'CHF' => 'CHF — Swiss Franc',
            'CAD' => 'CAD — Canadian Dollar',
            'AUD' => 'AUD — Australian Dollar',
        ];
    }
}

if (!function_exists('vp_payment_currency')) {
    /** Return a supported ISO currency code, or $default when it is invalid. */
    function vp_payment_currency($currency, $default = 'USD')
    {
        $currency = strtoupper(trim((string) $currency));
        $currencies = vp_payment_supported_currencies();
        if (isset($currencies[$currency])) return $currency;

        $default = strtoupper(trim((string) $default));
        return isset($currencies[$default]) ? $default : 'USD';
    }
}

if (!function_exists('vp_payment_minor_units')) {
    /**
     * Convert a human-entered decimal total to an integer number of cents.
     *
     * Returns null for values that are ambiguous, negative, too large, or
     * have more than two decimal places. Card payments must never round a
     * staff-entered amount silently.
     */
    function vp_payment_minor_units($amount)
    {
        $amount = trim((string) $amount);
        if (!preg_match('/^(?:0|[1-9][0-9]{0,11})(?:\.([0-9]{1,2}))?$/', $amount, $m)) {
            return null;
        }

        $parts = explode('.', $amount, 2);
        $fraction = isset($parts[1]) ? str_pad($parts[1], 2, '0') : '00';

        // Validate against the running platform's integer range BEFORE casting
        // the whole part. That preserves exact cents even on a rare 32-bit PHP
        // shared-host build instead of overflowing through a float.
        $maxMinor = PHP_INT_MAX < 99999999999999 ? PHP_INT_MAX : 99999999999999;
        $maxWhole = (string) intdiv($maxMinor, 100);
        $wholeText = $parts[0];
        if (strlen($wholeText) > strlen($maxWhole)
            || (strlen($wholeText) === strlen($maxWhole) && strcmp($wholeText, $maxWhole) > 0)) {
            return null;
        }

        $whole = (int) $wholeText;
        $minor = ($whole * 100) + (int) $fraction;

        // Stripe rejects zero-value payment-mode Checkout Sessions.
        if ($minor <= 0 || $minor > $maxMinor) return null;
        return $minor;
    }
}

if (!function_exists('vp_payment_decimal_from_minor')) {
    /** Convert an integer number of cents to a database-safe decimal string. */
    function vp_payment_decimal_from_minor($minor)
    {
        if (!is_numeric($minor) || (int) $minor < 0) return null;
        $minor = (int) $minor;
        return intdiv($minor, 100) . '.' . str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('vp_payment_format_minor')) {
    /** Human-friendly card-payment amount without using a float. */
    function vp_payment_format_minor($minor, $currency = 'USD')
    {
        $decimal = vp_payment_decimal_from_minor($minor);
        if ($decimal === null) return '—';

        list($whole, $fraction) = explode('.', $decimal, 2);
        $whole = strrev(implode(',', str_split(strrev($whole), 3)));
        $currency = vp_payment_currency($currency);
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CHF' => 'CHF ',
            'CAD' => 'CA$',
            'AUD' => 'A$',
        ];
        return ($symbols[$currency] ?? ($currency . ' ')) . $whole . '.' . $fraction;
    }
}

if (!function_exists('vp_payment_access_token')) {
    /** Generate an opaque, high-entropy token for a customer payment link. */
    function vp_payment_access_token()
    {
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('vp_stripe_signature_parts')) {
    /** Parse a Stripe-Signature header into timestamp + v1 signature values. */
    function vp_stripe_signature_parts($header)
    {
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', (string) $header) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2) continue;
            $key = trim($pair[0]);
            $value = trim($pair[1]);
            if ($key === 't' && ctype_digit($value) && $timestamp === null) {
                $timestamp = (int) $value;
            } elseif ($key === 'v1' && preg_match('/^[a-f0-9]{64}$/i', $value)) {
                $signatures[] = strtolower($value);
            }
        }
        return ['timestamp' => $timestamp, 'signatures' => $signatures];
    }
}

if (!function_exists('vp_stripe_verify_signature')) {
    /**
     * Verify Stripe's signed raw webhook body without requiring stripe-php.
     *
     * Stripe signs "timestamp.raw-body" with HMAC-SHA256 and may include more
     * than one v1 signature during secret rotation. A five-minute tolerance
     * blocks replay of a previously valid webhook request.
     */
    function vp_stripe_verify_signature($payload, $header, $secret, $tolerance = 300, $now = null)
    {
        $secret = trim((string) $secret);
        if ($secret === '') return false;

        $parts = vp_stripe_signature_parts($header);
        $timestamp = $parts['timestamp'];
        if ($timestamp === null || empty($parts['signatures'])) return false;

        $now = $now === null ? time() : (int) $now;
        if (abs($now - $timestamp) > max(0, (int) $tolerance)) return false;

        $expected = hash_hmac('sha256', $timestamp . '.' . (string) $payload, $secret);
        foreach ($parts['signatures'] as $signature) {
            if (hash_equals($expected, $signature)) return true;
        }
        return false;
    }
}

if (!function_exists('vp_stripe_checkout_url_is_valid')) {
    /** Only ever redirect a customer to Stripe's hosted Checkout domain. */
    function vp_stripe_checkout_url_is_valid($url)
    {
        $parts = parse_url((string) $url);
        if (!is_array($parts)) return false;
        return strtolower($parts['scheme'] ?? '') === 'https'
            && strtolower($parts['host'] ?? '') === 'checkout.stripe.com';
    }
}

if (!function_exists('vp_payment_status_label')) {
    function vp_payment_status_label($status)
    {
        $map = [
            'PENDING'  => ['Pending',  'bg-amber-100 text-amber-800'],
            'OPEN'     => ['Open',     'bg-blue-100 text-blue-800'],
            'PAID'     => ['Paid',     'bg-emerald-100 text-emerald-800'],
            'EXPIRED'  => ['Expired',  'bg-gray-200 text-gray-700'],
            'CANCELED' => ['Canceled', 'bg-gray-200 text-gray-700'],
            'FAILED'   => ['Failed',   'bg-red-100 text-red-800'],
            'REFUNDED' => ['Refunded', 'bg-violet-100 text-violet-800'],
        ];
        return $map[strtoupper((string) $status)] ?? [strtoupper((string) $status), 'bg-gray-100 text-gray-700'];
    }
}
