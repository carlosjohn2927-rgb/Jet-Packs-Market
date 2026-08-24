<?php
/**
 * Pure unit checks for the card-payment helpers. No database, HTTP server,
 * Stripe account, Composer package, or cURL extension is required.
 */
require_once __DIR__ . '/_runner.php';

if (!defined('BASEPATH')) define('BASEPATH', __DIR__ . '/../../system/');
require_once __DIR__ . '/../../application/helpers/payment_helper.php';

section('Decimal amount → Stripe minor-unit conversion');
$valid = [
    '1' => 100,
    '1.2' => 120,
    '1.20' => 120,
    '0.01' => 1,
    '123456789.99' => 12345678999,
];
foreach ($valid as $input => $expected) {
    assert_same($expected, vp_payment_minor_units($input), "'$input' converts exactly");
}
foreach (['0', '0.00', '-1.00', '1.001', '01.00', '1,000.00', 'abc', '', '1000000000000.00'] as $input) {
    assert_same(null, vp_payment_minor_units($input), "'$input' is refused, never rounded");
}
assert_same('$123,456,789.99', vp_payment_format_minor(12345678999, 'USD'), 'large USD amount has stable grouping');
assert_same('CHF 5.05', vp_payment_format_minor(505, 'CHF'), 'CHF amount has expected symbol');
assert_same('EUR', vp_payment_currency('eur'), 'currency normalizes case');
assert_same('USD', vp_payment_currency('JPY'), 'unsupported currency falls back safely');

section('Opaque customer-link tokens');
$tokenA = vp_payment_access_token();
$tokenB = vp_payment_access_token();
assert_true((bool) preg_match('/^[a-f0-9]{64}$/', $tokenA), 'token is 256 bits of lowercase hex');
assert_true($tokenA !== $tokenB, 'tokens are freshly random');

section('Stripe webhook signatures');
$payload = '{"id":"evt_unit_1","type":"checkout.session.completed"}';
$secret = 'whsec_unit_test_secret';
$timestamp = 1700000000;
$validSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
$header = 't=' . $timestamp . ',v1=deadbeef,v1=' . $validSignature;
assert_true(vp_stripe_verify_signature($payload, $header, $secret, 300, $timestamp), 'valid v1 signature is accepted');
assert_false(vp_stripe_verify_signature($payload . 'x', $header, $secret, 300, $timestamp), 'body mutation is rejected');
assert_false(vp_stripe_verify_signature($payload, $header, 'wrong-secret', 300, $timestamp), 'wrong signing secret is rejected');
assert_false(vp_stripe_verify_signature($payload, $header, $secret, 300, $timestamp + 301), 'stale event is rejected');
assert_false(vp_stripe_verify_signature($payload, 'v1=' . $validSignature, $secret, 300, $timestamp), 'header without timestamp is rejected');
$parts = vp_stripe_signature_parts($header);
assert_same($timestamp, $parts['timestamp'], 'signature parser extracts timestamp');
assert_same(1, count($parts['signatures']), 'signature parser keeps valid 64-char v1 values only');

section('Hosted checkout redirect allowlist');
assert_true(vp_stripe_checkout_url_is_valid('https://checkout.stripe.com/c/pay/cs_test_abc'), 'official Stripe Checkout host is accepted');
assert_false(vp_stripe_checkout_url_is_valid('http://checkout.stripe.com/c/pay/cs_test_abc'), 'non-HTTPS checkout URL is rejected');
assert_false(vp_stripe_checkout_url_is_valid('https://checkout.stripe.com.attacker.invalid/c/pay/x'), 'lookalike host is rejected');
assert_false(vp_stripe_checkout_url_is_valid('https://example.com/checkout'), 'non-Stripe host is rejected');

summary();
exit($failures === 0 ? 0 : 1);
