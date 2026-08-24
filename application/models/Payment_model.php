<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stripe-backed quote payment ledger.
 *
 * A payment row is created before a customer sees a checkout page. Amount and
 * currency are immutable thereafter; both are checked again against Stripe's
 * signed Checkout Session before anything is marked paid. The model also owns
 * the atomic paid -> quote-completed hand-off, making webhook retries safe.
 */
class Payment_model extends MY_Model
{
    protected $table = 'payments';
    protected $fillable = [
        'quoteId','provider','status','amount','amountMinor','currency','accessToken',
        'description','stripeCheckoutSessionId','stripePaymentIntentId','checkoutUrl',
        'expiresAt','paidAt','createdBy','lastError',
    ];
    protected $order_by = ['createdAt' => 'DESC'];

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['payment_helper', 'security_helper']);
    }

    /** Return newest-first payment history for one quote. */
    public function for_quote($quoteId)
    {
        $this->expire_due($quoteId);
        return $this->db->order_by('createdAt', 'DESC')
            ->get_where($this->table, ['quoteId' => $quoteId])->result_array();
    }

    /** Find an opaque customer payment-link token and expire it if necessary. */
    public function find_by_access_token($token)
    {
        $token = strtolower(trim((string) $token));
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
        // The database stores only an HMAC of the bearer token, matching the
        // password-reset-token pattern. A database export cannot be replayed
        // to open a customer's payment link.
        $payment = $this->find_one(['accessToken' => vp_hmac_sign($token)]);
        if (!$payment) return null;
        $this->expire_due(null, $payment['id']);
        return $this->find($payment['id']);
    }

    public function find_by_stripe_session($sessionId)
    {
        return $this->find_one(['stripeCheckoutSessionId' => trim((string) $sessionId)]);
    }

    /** One active card-payment request at a time prevents accidental double charging. */
    public function active_for_quote($quoteId)
    {
        $this->expire_due($quoteId);
        return $this->db->where('quoteId', $quoteId)
            ->where_in('status', [PAYMENT_PENDING, PAYMENT_OPEN])
            ->order_by('createdAt', 'DESC')->get($this->table)->row_array();
    }

    /**
     * Create an immutable payment request and update the quote's displayed
     * total in one transaction. Card collection is allowed only after a staff
     * member has marked the quote APPROVED.
     */
    public function create_request($quoteId, $amountMinor, $currency, $actorId, $expectedQuoteVersion, $expiresAt)
    {
        $amountMinor = (int) $amountMinor;
        $currency = vp_payment_currency($currency);
        $amount = vp_payment_decimal_from_minor($amountMinor);
        if ($amount === null || $amountMinor <= 0) {
            return ['ok' => false, 'error' => 'Enter a valid positive payment amount.'];
        }
        if (!$expiresAt || strtotime($expiresAt) < (time() + 30 * 60)) {
            return ['ok' => false, 'error' => 'Payment links must remain valid for at least 30 minutes.'];
        }

        $this->db->trans_begin();
        $quote = $this->db->get_where('quotes', ['id' => $quoteId])->row_array();
        if (!$quote) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Quote not found.'];
        }
        if ((int) $quote['version'] !== (int) $expectedQuoteVersion) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'This quote changed while you were editing it. Refresh and try again.', 'conflict' => true];
        }
        if ($quote['status'] !== QUOTE_APPROVED) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Approve the quote before requesting a card payment.'];
        }

        $active = $this->db->where('quoteId', $quoteId)
            ->where_in('status', [PAYMENT_PENDING, PAYMENT_OPEN])
            ->get($this->table)->row_array();
        if ($active) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'An active card payment request already exists for this quote.', 'payment' => $active];
        }

        $now = date('Y-m-d H:i:s');
        $customerToken = vp_payment_access_token();
        $payment = [
            'id'                       => MY_Model::uuid(),
            'quoteId'                  => $quoteId,
            'provider'                 => PAYMENT_PROVIDER_STRIPE,
            'status'                   => PAYMENT_PENDING,
            'amount'                   => $amount,
            'amountMinor'              => $amountMinor,
            'currency'                 => $currency,
            // Store only the HMAC, never the bearer token itself.
            'accessToken'              => vp_hmac_sign($customerToken),
            'description'              => 'Card payment for quote ' . $quote['quoteNumber'],
            'expiresAt'                => $expiresAt,
            'createdBy'                => $actorId ?: null,
            'createdAt'                => $now,
            'updatedAt'                => $now,
        ];
        $this->db->insert($this->table, $payment);

        // Bump the quote version to make any other open admin form stale.
        $this->db->where('id', $quoteId)->where('version', $quote['version'])
            ->update('quotes', [
                'totalAmount' => $amount,
                'version'     => (int) $quote['version'] + 1,
            ]);
        if ($this->db->affected_rows() === 0) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Concurrent update detected. Refresh and try again.', 'conflict' => true];
        }

        $this->_quote_activity(
            $quoteId,
            $actorId,
            QA_PAYMENT_REQUESTED,
            'Card payment requested: ' . vp_payment_format_minor($amountMinor, $currency),
            ['paymentId' => $payment['id'], 'amountMinor' => $amountMinor, 'currency' => $currency, 'expiresAt' => $expiresAt]
        );

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Could not create the payment request.'];
        }
        $this->db->trans_commit();
        return [
            'ok' => true,
            'payment' => $this->find($payment['id']),
            // Returned only to the immediately creating request so it can be
            // emailed. It is never written to the payments table.
            'customerToken' => $customerToken,
            'quote' => $this->db->get_where('quotes', ['id' => $quoteId])->row_array(),
        ];
    }

    /** Store the one Stripe Checkout Session created for a payment request. */
    public function set_checkout_session($paymentId, array $session)
    {
        $payment = $this->find($paymentId);
        if (!$payment) return ['ok' => false, 'error' => 'Payment request not found.'];
        if ($payment['status'] === PAYMENT_PAID) return ['ok' => false, 'error' => 'This payment has already been received.'];

        $sessionId = trim((string) ($session['id'] ?? ''));
        $url = trim((string) ($session['url'] ?? ''));
        if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId) || !vp_stripe_checkout_url_is_valid($url)) {
            return ['ok' => false, 'error' => 'Stripe returned an invalid checkout session.'];
        }
        if (!empty($payment['stripeCheckoutSessionId']) && $payment['stripeCheckoutSessionId'] !== $sessionId) {
            return ['ok' => false, 'error' => 'A different checkout session already exists for this payment.'];
        }

        $stripeExpiry = !empty($session['expires_at']) ? date('Y-m-d H:i:s', (int) $session['expires_at']) : $payment['expiresAt'];
        $this->db->where('id', $paymentId)
            ->where_in('status', [PAYMENT_PENDING, PAYMENT_OPEN])
            ->update($this->table, [
                'status'                  => PAYMENT_OPEN,
                'stripeCheckoutSessionId' => $sessionId,
                'checkoutUrl'             => $url,
                'expiresAt'               => $stripeExpiry,
                'lastError'               => null,
            ]);
        if ($this->db->affected_rows() === 0) {
            $latest = $this->find($paymentId);
            if ($latest && $latest['status'] === PAYMENT_OPEN && $latest['stripeCheckoutSessionId'] === $sessionId) {
                return ['ok' => true, 'payment' => $latest, 'already_open' => true];
            }
            return ['ok' => false, 'error' => 'This payment is no longer available for checkout.'];
        }
        return ['ok' => true, 'payment' => $this->find($paymentId)];
    }

    /** Keep transient gateway errors available to staff without exposing them to customers. */
    public function record_gateway_error($paymentId, $error)
    {
        $this->db->where('id', $paymentId)->where_in('status', [PAYMENT_PENDING, PAYMENT_OPEN])
            ->update($this->table, ['lastError' => substr(trim((string) $error), 0, 1000)]);
    }

    /** Cancel an unused payment request. For OPEN sessions, Stripe is expired first by the controller. */
    public function cancel($paymentId, $actorId = null)
    {
        $payment = $this->find($paymentId);
        if (!$payment) return ['ok' => false, 'error' => 'Payment request not found.'];
        if ($payment['status'] === PAYMENT_PAID) return ['ok' => false, 'error' => 'A paid request cannot be canceled.'];
        if (in_array($payment['status'], [PAYMENT_CANCELED, PAYMENT_EXPIRED], true)) {
            return ['ok' => true, 'payment' => $payment, 'already_closed' => true];
        }

        $this->db->where('id', $paymentId)->where_in('status', [PAYMENT_PENDING, PAYMENT_OPEN, PAYMENT_FAILED])
            ->update($this->table, ['status' => PAYMENT_CANCELED, 'lastError' => null]);
        if ($this->db->affected_rows() === 0) return ['ok' => false, 'error' => 'This payment could not be canceled.'];

        $latest = $this->find($paymentId);
        $this->_quote_activity($latest['quoteId'], $actorId, QA_PAYMENT_CANCELED, 'Card payment request canceled.', ['paymentId' => $paymentId]);
        return ['ok' => true, 'payment' => $latest];
    }

    /** Mark an unpaid payment expired (from time checks or checkout.session.expired). */
    public function mark_expired($paymentId, $detail = null)
    {
        $payment = $this->find($paymentId);
        if (!$payment || $payment['status'] === PAYMENT_PAID) return ['ok' => false, 'error' => 'Payment request not available.'];
        if (in_array($payment['status'], [PAYMENT_EXPIRED, PAYMENT_CANCELED], true)) return ['ok' => true, 'payment' => $payment];

        $this->db->where('id', $paymentId)->where_in('status', [PAYMENT_PENDING, PAYMENT_OPEN])
            ->update($this->table, ['status' => PAYMENT_EXPIRED, 'lastError' => $detail ? substr($detail, 0, 1000) : null]);
        $latest = $this->find($paymentId);
        if ($latest && $latest['status'] === PAYMENT_EXPIRED) {
            $this->_quote_activity($latest['quoteId'], null, QA_PAYMENT_EXPIRED, 'Card payment request expired.', ['paymentId' => $paymentId]);
        }
        return ['ok' => true, 'payment' => $latest];
    }

    public function mark_failed($paymentId, $detail = null)
    {
        $payment = $this->find($paymentId);
        if (!$payment || $payment['status'] === PAYMENT_PAID) return ['ok' => false, 'error' => 'Payment request not available.'];
        $this->db->where('id', $paymentId)->where_in('status', [PAYMENT_PENDING, PAYMENT_OPEN])
            ->update($this->table, ['status' => PAYMENT_FAILED, 'lastError' => $detail ? substr($detail, 0, 1000) : null]);
        $latest = $this->find($paymentId);
        if ($latest && $latest['status'] === PAYMENT_FAILED) {
            $this->_quote_activity($latest['quoteId'], null, QA_PAYMENT_FAILED, 'Stripe reported that a card payment failed.', ['paymentId' => $paymentId]);
        }
        return ['ok' => true, 'payment' => $latest];
    }

    /**
     * Atomically settle a verified Stripe Checkout Session. The caller has
     * already verified session metadata, amount and currency with
     * Stripe_gateway::session_matches_payment(). If a customer was charged
     * after a race with cancellation, PAID still wins — the financial record is
     * never hidden merely because a local status changed first.
     */
    public function settle_stripe_session($paymentId, array $session)
    {
        $this->db->trans_begin();
        $payment = $this->db->get_where($this->table, ['id' => $paymentId])->row_array();
        if (!$payment) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Payment request not found.'];
        }
        if ($payment['status'] === PAYMENT_PAID) {
            $this->db->trans_rollback();
            return ['ok' => true, 'payment' => $payment, 'already_paid' => true, 'newly_paid' => false];
        }

        $sessionId = trim((string) ($session['id'] ?? ''));
        $intentId = trim((string) ($session['payment_intent'] ?? ''));
        $amount = isset($session['amount_total']) ? (int) $session['amount_total'] : -1;
        $currency = strtoupper((string) ($session['currency'] ?? ''));
        if ($sessionId === '' || $amount !== (int) $payment['amountMinor']
            || $currency !== vp_payment_currency($payment['currency'])
            || (!empty($payment['stripeCheckoutSessionId']) && $payment['stripeCheckoutSessionId'] !== $sessionId)) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Stripe checkout details did not match the payment request.'];
        }

        $this->db->where('id', $paymentId)->where('status !=', PAYMENT_PAID)
            ->update($this->table, [
                'status'                  => PAYMENT_PAID,
                'stripeCheckoutSessionId' => $sessionId,
                'stripePaymentIntentId'   => $intentId ?: null,
                'paidAt'                  => date('Y-m-d H:i:s'),
                'lastError'               => null,
            ]);
        if ($this->db->affected_rows() === 0) {
            $latest = $this->find($paymentId);
            $this->db->trans_rollback();
            if ($latest && $latest['status'] === PAYMENT_PAID) {
                return ['ok' => true, 'payment' => $latest, 'already_paid' => true, 'newly_paid' => false];
            }
            return ['ok' => false, 'error' => 'Could not record this payment.'];
        }

        $quote = $this->db->get_where('quotes', ['id' => $payment['quoteId']])->row_array();
        $quoteCompleted = false;
        if ($quote && $quote['status'] === QUOTE_APPROVED) {
            // An internal note/assignment may have bumped the quote version
            // while Checkout was open. Completion must still win when the
            // quote is *still* APPROVED; only a status change may stop it.
            $this->db->set('status', QUOTE_COMPLETED)
                ->set('version', 'version + 1', false)
                ->set('statusUpdatedAt', date('Y-m-d H:i:s'))
                ->where('id', $quote['id'])->where('status', QUOTE_APPROVED)
                ->update('quotes');
            if ($this->db->affected_rows() > 0) {
                $quoteCompleted = true;
                $this->db->insert('quote_status_history', [
                    'id'         => MY_Model::uuid(),
                    'quoteId'    => $quote['id'],
                    'fromStatus' => QUOTE_APPROVED,
                    'toStatus'   => QUOTE_COMPLETED,
                    'changedBy'  => null,
                    'notes'      => 'Card payment confirmed by Stripe.',
                    'createdAt'  => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->_quote_activity(
            $payment['quoteId'],
            null,
            QA_PAYMENT_PAID,
            'Card payment confirmed by Stripe: ' . vp_payment_format_minor($payment['amountMinor'], $payment['currency']),
            ['paymentId' => $paymentId, 'stripeSessionId' => $sessionId, 'paymentIntentId' => $intentId ?: null]
        );

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Could not finalize this payment.'];
        }
        $this->db->trans_commit();
        return [
            'ok' => true,
            'payment' => $this->find($paymentId),
            'quote' => $quote ? $this->db->get_where('quotes', ['id' => $quote['id']])->row_array() : null,
            'quote_completed' => $quoteCompleted,
            'newly_paid' => true,
            'already_paid' => false,
        ];
    }

    /** Stripe event receipts are kept without raw payloads to avoid retaining unnecessary PII. */
    public function event_seen($provider, $eventId)
    {
        return (bool) $this->db->get_where('payment_events', [
            'provider' => $provider,
            'providerEventId' => $eventId,
        ])->row_array();
    }

    public function record_event($provider, $eventId, $eventType, $paymentId = null)
    {
        if ($eventId === '') return false;
        if ($this->event_seen($provider, $eventId)) return true;
        $this->db->insert('payment_events', [
            'id'              => MY_Model::uuid(),
            'paymentId'       => $paymentId ?: null,
            'provider'        => $provider,
            'providerEventId' => $eventId,
            'eventType'       => substr((string) $eventType, 0, 100),
            'createdAt'       => date('Y-m-d H:i:s'),
        ]);
        return $this->db->affected_rows() > 0 || $this->event_seen($provider, $eventId);
    }

    /** Opportunistically close links that have passed their server-side expiry. */
    public function expire_due($quoteId = null, $paymentId = null)
    {
        $now = date('Y-m-d H:i:s');
        if ($paymentId !== null) $this->db->where('id', $paymentId);
        if ($quoteId !== null) $this->db->where('quoteId', $quoteId);
        $this->db->where_in('status', [PAYMENT_PENDING, PAYMENT_OPEN])
            ->where('expiresAt IS NOT NULL', null, false)
            ->where('expiresAt <=', $now)
            ->update($this->table, ['status' => PAYMENT_EXPIRED]);
    }

    private function _quote_activity($quoteId, $actorId, $action, $description, array $metadata = [])
    {
        $this->db->insert('quote_activities', [
            'id'          => MY_Model::uuid(),
            'quoteId'     => $quoteId,
            'actorId'     => $actorId,
            'action'      => $action,
            'description' => substr((string) $description, 0, 500),
            'metadata'    => !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'ipAddress'   => function_exists('vp_get_client_ip') ? vp_get_client_ip() : null,
            'userAgent'   => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'createdAt'   => date('Y-m-d H:i:s'),
        ]);
    }
}
