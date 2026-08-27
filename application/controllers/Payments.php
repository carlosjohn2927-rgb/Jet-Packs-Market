<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public, tokenized Stripe Checkout flow for approved quote payments.
 *
 * The customer receives an opaque link to this application, then a POST sends
 * them to Stripe-hosted Checkout. Payment success is authoritative only after
 * a Stripe API lookup or a signed Stripe webhook — never because a browser
 * visited a success URL.
 */
class Payments extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Payment_model', 'Quote_model']);
        $this->load->library(['stripe_gateway', 'mailer']);
        $this->load->helper(['form', 'url', 'security_helper', 'payment_helper']);

        // The opaque token in /pay/{token} is a bearer credential. Do not let
        // it leak to third-party font/CDN requests, browser history caches, or
        // search engines while rendering the customer payment page.
        $this->output
            ->set_header('Referrer-Policy: no-referrer')
            ->set_header('Cache-Control: no-store, private, max-age=0')
            ->set_header('Pragma: no-cache')
            ->set_header('X-Robots-Tag: noindex, nofollow');
    }

    /** GET /pay/{opaque-token} — a customer-friendly payment request page. */
    public function show($token = null)
    {
        $payment = $this->_payment_or_404($token);
        $quote = $this->Quote_model->find($payment['quoteId']);
        if (!$quote) show_404();

        $this->page_title = 'Secure card payment';
        $this->page_description = 'Secure card payment for your Halyk Petroleum quote.';
        $this->render('payments/show', [
            'payment' => $payment,
            'quote' => $quote,
            'stripe' => $this->stripe_gateway->status(),
            'checkout_canceled' => $this->input->get('canceled') === '1',
        ]);
    }

    /** POST /pay/{opaque-token}/checkout — creates/reuses hosted Stripe Checkout. */
    public function checkout($token = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $payment = $this->_payment_or_404($token);
        $quote = $this->Quote_model->find($payment['quoteId']);
        if (!$quote) show_404();

        if (!in_array($payment['status'], [PAYMENT_PENDING, PAYMENT_OPEN], true)) {
            $this->flash('error', 'This payment request is no longer available.');
            return redirect('pay/' . rawurlencode($payment['customerToken']));
        }
        if ($quote['status'] !== QUOTE_APPROVED) {
            // This should only occur if a staff member changed the quote after
            // sending the link. Do not collect money for a no-longer-approved quote.
            $this->flash('error', 'This quote is not currently available for card payment. Please contact our team.');
            return redirect('pay/' . rawurlencode($payment['customerToken']));
        }

        $stripe = $this->stripe_gateway->status();
        if (empty($stripe['configured'])) {
            $this->flash('error', 'Secure card checkout is temporarily unavailable. Please contact our team.');
            return redirect('pay/' . rawurlencode($payment['customerToken']));
        }

        // A customer who canceled Stripe Checkout can safely resume the exact
        // same open session. Never redirect an arbitrary URL from the database.
        if ($payment['status'] === PAYMENT_OPEN && !empty($payment['checkoutUrl'])
            && vp_stripe_checkout_url_is_valid($payment['checkoutUrl'])) {
            return redirect($payment['checkoutUrl']);
        }

        $created = $this->stripe_gateway->create_checkout_session($payment, $quote);
        if (empty($created['ok'])) {
            $this->Payment_model->record_gateway_error($payment['id'], $created['error'] ?? 'Unable to start checkout.');
            $this->flash('error', 'We could not start secure checkout. Please try again or contact our team.');
            return redirect('pay/' . rawurlencode($payment['customerToken']));
        }

        $stored = $this->Payment_model->set_checkout_session($payment['id'], $created['session']);
        if (empty($stored['ok']) || empty($stored['payment']['checkoutUrl'])
            || !vp_stripe_checkout_url_is_valid($stored['payment']['checkoutUrl'])) {
            $this->Payment_model->record_gateway_error($payment['id'], $stored['error'] ?? 'Could not store checkout session.');
            $this->flash('error', 'We could not start secure checkout. Please try again or contact our team.');
            return redirect('pay/' . rawurlencode($payment['customerToken']));
        }

        redirect($stored['payment']['checkoutUrl']);
    }

    /**
     * GET /pay/{token}/complete?session_id=cs_... — customer return page.
     * The query string merely identifies a session to retrieve; it cannot mark
     * a payment paid by itself.
     */
    public function complete($token = null)
    {
        $payment = $this->_payment_or_404($token);
        $quote = $this->Quote_model->find($payment['quoteId']);
        if (!$quote) show_404();

        $state = 'pending';
        $message = 'We are confirming your payment with our card processor.';
        $sessionId = trim((string) $this->input->get('session_id'));
        if ($payment['status'] === PAYMENT_PAID) {
            $state = 'paid';
            $message = 'Your card payment has been received. Thank you.';
        } elseif ($sessionId !== '') {
            $lookup = $this->stripe_gateway->retrieve_checkout_session($sessionId);
            if (!empty($lookup['ok']) && $this->stripe_gateway->session_matches_payment((array) $lookup['data'], $payment)) {
                $session = (array) $lookup['data'];
                if (($session['payment_status'] ?? '') === 'paid') {
                    $settled = $this->_settle_verified_session($payment, $session, 'return');
                    if (!empty($settled['ok'])) {
                        $payment = $settled['payment'] ?? $this->Payment_model->find($payment['id']);
                        $quote = $settled['quote'] ?? $this->Quote_model->find($payment['quoteId']);
                        $state = 'paid';
                        $message = 'Your card payment has been received. Thank you.';
                    } else {
                        log_message('error', 'Stripe return settlement failed for payment ' . $payment['id'] . ': ' . ($settled['error'] ?? 'unknown'));
                        $state = 'pending';
                    }
                } elseif (($session['status'] ?? '') === 'expired') {
                    $this->Payment_model->mark_expired($payment['id'], 'Stripe Checkout Session expired.');
                    $payment = $this->Payment_model->find($payment['id']);
                    $state = 'expired';
                    $message = 'This card-payment request has expired. Please contact our team for a new link.';
                }
            } else {
                // Do not tell an attacker whether an unrelated Stripe session
                // exists; keep the response indistinguishable from a delayed
                // payment confirmation.
                log_message('error', 'Stripe return session did not match payment ' . $payment['id']);
            }
        }

        if ($payment['status'] === PAYMENT_EXPIRED) {
            $state = 'expired';
            $message = 'This card-payment request has expired. Please contact our team for a new link.';
        } elseif ($payment['status'] === PAYMENT_CANCELED) {
            $state = 'canceled';
            $message = 'This card-payment request has been canceled. Please contact our team if you need assistance.';
        } elseif ($payment['status'] === PAYMENT_FAILED) {
            $state = 'failed';
            $message = 'Your payment was not completed. Please contact our team or request a new secure payment link.';
        }

        $this->page_title = $state === 'paid' ? 'Payment received' : 'Payment status';
        $this->page_description = 'Card payment status for your Halyk Petroleum quote.';
        $this->render('payments/complete', [
            'payment' => $payment,
            'quote' => $quote,
            'state' => $state,
            'message' => $message,
        ]);
    }

    /**
     * POST /payments/stripe/webhook
     *
     * This URI is the sole CSRF exclusion for payments. It is protected by the
     * Stripe-Signature HMAC over the unmodified raw request body instead.
     */
    public function stripe_webhook()
    {
        if ($this->input->method() !== 'post') {
            return $this->json(['ok' => false], 405);
        }
        if (!$this->db->table_exists('payments') || !$this->db->table_exists('payment_events')) {
            log_message('error', 'Stripe webhook received before payment migration was installed.');
            return $this->json(['ok' => false, 'error' => 'payment storage unavailable'], 503);
        }

        $payload = file_get_contents('php://input');
        $signature = $this->input->get_request_header('Stripe-Signature');
        $verified = $this->stripe_gateway->verify_webhook($payload, $signature);
        if (empty($verified['ok'])) {
            log_message('error', 'Stripe webhook rejected: ' . ($verified['error'] ?? 'signature verification failed'));
            return $this->json(['ok' => false], 400);
        }

        $event = (array) $verified['event'];
        $eventId = (string) $event['id'];
        $eventType = (string) $event['type'];
        if ($this->Payment_model->event_seen(PAYMENT_PROVIDER_STRIPE, $eventId)) {
            return $this->json(['ok' => true, 'duplicate' => true]);
        }

        $object = (array) (($event['data'] ?? [])['object'] ?? []);
        $payment = $this->_payment_from_stripe_object($object);
        $handled = true;
        $result = ['ok' => true];

        if ($payment && in_array($eventType, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            if (($object['payment_status'] ?? '') === 'paid'
                && $this->stripe_gateway->session_matches_payment($object, $payment)) {
                $result = $this->_settle_verified_session($payment, $object, 'webhook');
                $handled = !empty($result['ok']);
            } else {
                // A completed async session with no paid status is deliberately
                // recorded but not settled; a later success/failure event wins.
                $handled = true;
            }
        } elseif ($payment && $eventType === 'checkout.session.expired') {
            $result = $this->Payment_model->mark_expired($payment['id'], 'Stripe Checkout Session expired.');
            $handled = !empty($result['ok']);
        } elseif ($payment && $eventType === 'checkout.session.async_payment_failed') {
            $result = $this->Payment_model->mark_failed($payment['id'], 'Stripe reported that the asynchronous payment failed.');
            $handled = !empty($result['ok']);
        } elseif (!$payment && strpos($eventType, 'checkout.session.') === 0) {
            // A signed event that does not belong to this installation should
            // not be retried forever. It is still logged server-side for review.
            log_message('error', 'Stripe webhook could not resolve local payment for event ' . $eventId);
        }

        if (!$handled) {
            log_message('error', 'Stripe webhook processing failed for event ' . $eventId . ': ' . ($result['error'] ?? 'unknown'));
            // 5xx asks Stripe to retry instead of losing a potentially paid order.
            return $this->json(['ok' => false], 500);
        }

        $this->Payment_model->record_event(PAYMENT_PROVIDER_STRIPE, $eventId, $eventType, $payment['id'] ?? null);
        return $this->json(['ok' => true]);
    }

    /** Resolve a payment token without exposing whether a malformed one exists. */
    private function _payment_or_404($token)
    {
        $token = strtolower(trim((string) $token));
        $payment = $this->Payment_model->find_by_access_token($token);
        if (!$payment) show_404();
        // Keep the raw bearer token request-scoped only. The database field of
        // the same purpose contains its HMAC, not the plaintext token.
        $payment['customerToken'] = $token;
        return $payment;
    }

    /** Get a local payment from Stripe Checkout metadata or stored session ID. */
    private function _payment_from_stripe_object(array $object)
    {
        $metadata = (array) ($object['metadata'] ?? []);
        $paymentId = trim((string) ($metadata['payment_id'] ?? $object['client_reference_id'] ?? ''));
        if (preg_match('/^[a-f0-9-]{36}$/i', $paymentId)) {
            $payment = $this->Payment_model->find($paymentId);
            if ($payment) return $payment;
        }

        $sessionId = trim((string) ($object['id'] ?? ''));
        return $sessionId !== '' ? $this->Payment_model->find_by_stripe_session($sessionId) : null;
    }

    /** Settle once, then notify customer/staff only for the first successful transition. */
    private function _settle_verified_session(array $payment, array $session, $source)
    {
        $result = $this->Payment_model->settle_stripe_session($payment['id'], $session);
        if (empty($result['ok']) || empty($result['newly_paid'])) return $result;

        $settled = $result['payment'] ?? $this->Payment_model->find($payment['id']);
        $quote = $result['quote'] ?? $this->Quote_model->find($payment['quoteId']);
        if (!$settled || !$quote) return $result;

        $amount = vp_payment_format_minor($settled['amountMinor'], $settled['currency']);
        $this->audit->log(AUDIT_PAYMENT_PAID, 'payment', $settled['id'], [
            'quoteId' => $quote['id'],
            'quoteNumber' => $quote['quoteNumber'],
            'amountMinor' => (int) $settled['amountMinor'],
            'currency' => $settled['currency'],
            'source' => $source,
        ]);
        $this->notify(
            'payment_paid',
            'Card payment received: ' . $quote['quoteNumber'],
            $quote['companyName'] . ' paid ' . $amount . ' by card.',
            ['quoteId' => $quote['id'], 'paymentId' => $settled['id'], 'quoteNumber' => $quote['quoteNumber']],
            null
        );

        $tpl = $this->mailer->template_card_payment_receipt($quote, $settled);
        $this->mailer->send(
            $quote['email'],
            $tpl['subject'],
            $tpl['html'],
            'card_payment_receipt',
            'card_payment_receipt:' . $settled['id'],
            ['quoteId' => $quote['id']]
        );
        return $result;
    }
}
