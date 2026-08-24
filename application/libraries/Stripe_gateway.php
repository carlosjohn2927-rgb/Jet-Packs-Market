<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * JetPacks Market — minimal Stripe Checkout gateway (no Composer required).
 *
 * Customers are redirected to Stripe-hosted Checkout; this application never
 * receives, stores, or logs card numbers, CVCs, or cardholder data. The class
 * intentionally uses only the small portion of Stripe's REST API required for
 * one-off quote payments: create/retrieve/expire a Checkout Session and verify
 * signed webhooks.
 */
class Stripe_gateway
{
    const API_BASE = 'https://api.stripe.com/v1';

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('payment_helper');
    }

    /**
     * Public-safe integration status for the admin UI. It deliberately never
     * returns a secret key or webhook secret.
     */
    public function status()
    {
        $enabled = $this->_setting_bool('stripe_payments_enabled', false);
        $secret = trim((string) $this->CI->config->item('stripe_secret_key'));
        $webhookSecret = trim((string) $this->CI->config->item('stripe_webhook_secret'));
        $currency = vp_payment_currency($this->_setting('stripe_currency', 'USD'));
        $ttl = (int) $this->_setting('stripe_checkout_ttl_hours', 24);
        $ttl = max(1, min(24, $ttl));

        $message = '';
        $configured = $enabled && $secret !== '' && $webhookSecret !== '' && function_exists('curl_init');
        if (!$enabled) {
            $message = 'Enable card payments in Settings → System after adding Stripe credentials to .env.';
        } elseif ($secret === '') {
            $message = 'VP_STRIPE_SECRET_KEY is missing from .env.';
        } elseif ($webhookSecret === '') {
            $message = 'VP_STRIPE_WEBHOOK_SECRET is missing from .env. A signed webhook is required before accepting payments.';
        } elseif (!function_exists('curl_init')) {
            $message = 'PHP cURL is required to communicate with Stripe.';
        }

        // Hosted Checkout needs HTTPS on a public deployment. Stripe allows
        // localhost HTTP in test development, so do not block local demos.
        $base = base_url();
        $isLocal = (bool) preg_match('~^https?://(?:localhost|127\.0\.0\.1|\[::1\])(?::\d+)?/~i', $base);
        if ($configured && defined('ENVIRONMENT') && ENVIRONMENT === 'production'
            && stripos($base, 'https://') !== 0 && !$isLocal) {
            $configured = false;
            $message = 'Card payments require an HTTPS VP_BASE_URL in production.';
        }

        $mode = 'unknown';
        if (strpos($secret, 'sk_test_') === 0) $mode = 'test';
        if (strpos($secret, 'sk_live_') === 0) $mode = 'live';

        return [
            'enabled'            => $enabled,
            'configured'         => $configured,
            'webhook_configured' => $webhookSecret !== '',
            'currency'           => $currency,
            'ttl_hours'          => $ttl,
            'mode'               => $mode,
            'message'            => $message,
            'webhook_url'        => base_url('payments/stripe/webhook'),
        ];
    }

    /**
     * Create one Stripe-hosted Checkout Session for an immutable payment row.
     * @return array ['ok' => bool, 'session' => array, 'error' => string]
     */
    public function create_checkout_session(array $payment, array $quote)
    {
        $status = $this->status();
        if (empty($status['configured'])) {
            return ['ok' => false, 'error' => $status['message'] ?: 'Card payments are not configured.'];
        }

        $paymentId = (string) ($payment['id'] ?? '');
        // The persisted accessToken field is an HMAC. The raw token exists
        // only for this public request and is used to build Stripe return URLs.
        $token = (string) ($payment['customerToken'] ?? '');
        $amount = (int) ($payment['amountMinor'] ?? 0);
        $currency = vp_payment_currency($payment['currency'] ?? $status['currency']);
        $expiresAt = strtotime((string) ($payment['expiresAt'] ?? ''));
        if ($paymentId === '' || !preg_match('/^[a-f0-9-]{36}$/i', $paymentId)
            || !preg_match('/^[a-f0-9]{64}$/i', $token) || $amount <= 0) {
            return ['ok' => false, 'error' => 'The payment request is invalid.'];
        }
        // Stripe requires expires_at to be at least 30 minutes in the future.
        if (!$expiresAt || $expiresAt < (time() + 30 * 60)) {
            return ['ok' => false, 'error' => 'This payment request has expired.'];
        }

        $quoteNumber = trim((string) ($quote['quoteNumber'] ?? ''));
        $productName = 'Quote ' . ($quoteNumber !== '' ? $quoteNumber : $paymentId);
        $description = 'Card payment for ' . ($quote['companyName'] ?? 'JetPacks Market customer');
        $paymentUrl = base_url('pay/' . rawurlencode($token));
        $successUrl = base_url('pay/' . rawurlencode($token) . '/complete')
            . '?session_id={CHECKOUT_SESSION_ID}';

        $params = [
            'mode'                                           => 'payment',
            'payment_method_types[0]'                        => 'card',
            'submit_type'                                    => 'pay',
            'success_url'                                    => $successUrl,
            'cancel_url'                                     => $paymentUrl . '?canceled=1',
            'client_reference_id'                            => $paymentId,
            'expires_at'                                     => (string) $expiresAt,
            'line_items[0][price_data][currency]'            => strtolower($currency),
            'line_items[0][price_data][product_data][name]'  => $productName,
            'line_items[0][price_data][product_data][description]' => substr($description, 0, 500),
            'line_items[0][price_data][unit_amount]'         => (string) $amount,
            'line_items[0][quantity]'                        => '1',
            'metadata[payment_id]'                           => $paymentId,
            'metadata[quote_id]'                             => (string) ($quote['id'] ?? ''),
            'metadata[quote_number]'                         => $quoteNumber,
            'payment_intent_data[metadata][payment_id]'      => $paymentId,
            'payment_intent_data[metadata][quote_id]'        => (string) ($quote['id'] ?? ''),
            'payment_intent_data[metadata][quote_number]'    => $quoteNumber,
        ];
        $email = trim((string) ($quote['email'] ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $params['customer_email'] = $email;
        }

        // A stable idempotency key prevents browser retries/double-clicks from
        // creating more than one Checkout Session for a payment request.
        $response = $this->_request('POST', '/checkout/sessions', $params, 'jpm_checkout_' . $paymentId);
        if (!$response['ok']) return $response;

        $session = (array) ($response['data'] ?? []);
        if (empty($session['id']) || empty($session['url']) || !vp_stripe_checkout_url_is_valid($session['url'])) {
            log_message('error', 'Stripe: create Checkout Session returned an invalid hosted URL for payment ' . $paymentId);
            return ['ok' => false, 'error' => 'Stripe did not return a valid checkout link.'];
        }
        return ['ok' => true, 'session' => $session];
    }

    /** Retrieve a Checkout Session from Stripe using the account secret key. */
    public function retrieve_checkout_session($sessionId)
    {
        $sessionId = trim((string) $sessionId);
        if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId)) {
            return ['ok' => false, 'error' => 'Invalid checkout session.'];
        }
        if (!$this->_api_ready()) {
            return ['ok' => false, 'error' => 'Card payments are not configured.'];
        }
        return $this->_request('GET', '/checkout/sessions/' . rawurlencode($sessionId));
    }

    /** Expire an open Checkout Session before locally cancelling its payment. */
    public function expire_checkout_session($sessionId)
    {
        $sessionId = trim((string) $sessionId);
        if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId)) {
            return ['ok' => false, 'error' => 'Invalid checkout session.'];
        }
        if (!$this->_api_ready()) {
            return ['ok' => false, 'error' => 'Stripe must be configured to cancel an open checkout.'];
        }
        return $this->_request('POST', '/checkout/sessions/' . rawurlencode($sessionId) . '/expire', []);
    }

    /** Verify the raw Stripe webhook body using its endpoint signing secret. */
    public function verify_webhook($payload, $signatureHeader)
    {
        $secret = trim((string) $this->CI->config->item('stripe_webhook_secret'));
        if ($secret === '') return ['ok' => false, 'error' => 'Stripe webhook secret is not configured.'];
        if (!vp_stripe_verify_signature($payload, $signatureHeader, $secret)) {
            return ['ok' => false, 'error' => 'Invalid Stripe webhook signature.'];
        }
        $event = json_decode((string) $payload, true);
        if (!is_array($event) || empty($event['id']) || empty($event['type'])) {
            return ['ok' => false, 'error' => 'Invalid Stripe webhook payload.'];
        }
        return ['ok' => true, 'event' => $event];
    }

    /**
     * Confirm that a Stripe session is for exactly this local payment. This is
     * called for both webhook events and the customer return URL; query-string
     * values are never trusted on their own.
     */
    public function session_matches_payment(array $session, array $payment)
    {
        $paymentId = (string) ($payment['id'] ?? '');
        $storedSessionId = trim((string) ($payment['stripeCheckoutSessionId'] ?? ''));
        $sessionId = trim((string) ($session['id'] ?? ''));
        $metadata = (array) ($session['metadata'] ?? []);
        $reference = (string) ($session['client_reference_id'] ?? '');
        $amount = isset($session['amount_total']) ? (int) $session['amount_total'] : -1;
        $currency = strtoupper((string) ($session['currency'] ?? ''));

        if ($paymentId === '' || $sessionId === '') return false;
        if ($storedSessionId !== '' && !hash_equals($storedSessionId, $sessionId)) return false;
        if (($reference !== $paymentId) && (($metadata['payment_id'] ?? '') !== $paymentId)) return false;
        if ($amount !== (int) ($payment['amountMinor'] ?? -2)) return false;
        if ($currency !== vp_payment_currency($payment['currency'] ?? 'USD')) return false;
        return true;
    }

    /**
     * Low-level Stripe REST request. Stripe uses form-encoded requests here;
     * no external SDK is needed for the small hosted Checkout surface.
     */
    private function _request($method, $path, array $params = [], $idempotencyKey = null)
    {
        $key = trim((string) $this->CI->config->item('stripe_secret_key'));
        if ($key === '' || !function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'Stripe is not configured on this server.'];
        }

        $method = strtoupper($method);
        $url = self::API_BASE . $path;
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = [
            'Authorization: Bearer ' . $key,
            'Accept: application/json',
            'User-Agent: JetPacksMarket-StripeCheckout/1.0',
        ];
        if ($idempotencyKey !== null) $headers[] = 'Idempotency-Key: ' . $idempotencyKey;

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if ($method !== 'GET') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $data = $body !== false ? json_decode((string) $body, true) : null;
        if ($body === false || $code < 200 || $code >= 300 || !is_array($data)) {
            $providerMessage = is_array($data) ? trim((string) ($data['error']['message'] ?? '')) : '';
            $error = $providerMessage !== '' ? $providerMessage : ($curlError ?: ('Stripe returned HTTP ' . $code . '.'));
            log_message('error', 'Stripe API request failed: HTTP ' . $code . ' ' . substr($error, 0, 500));
            return ['ok' => false, 'error' => 'Stripe checkout could not be created. ' . substr($error, 0, 180)];
        }

        return ['ok' => true, 'data' => $data];
    }

    /** API access remains available for settlement/cancellation even after an
     * admin disables new checkout links. This avoids abandoning a payment that
     * a customer already completed at Stripe. */
    private function _api_ready()
    {
        return trim((string) $this->CI->config->item('stripe_secret_key')) !== ''
            && function_exists('curl_init');
    }

    private function _setting($key, $default = '')
    {
        if (isset($this->CI->settings)) {
            $value = $this->CI->settings->get($key, $default);
            return $value === null || $value === '' ? $default : $value;
        }
        return $default;
    }

    private function _setting_bool($key, $default = false)
    {
        $value = $this->_setting($key, $default ? '1' : '0');
        return $value === true || $value === 1 || $value === '1' || $value === 'true' || $value === 'on';
    }
}
