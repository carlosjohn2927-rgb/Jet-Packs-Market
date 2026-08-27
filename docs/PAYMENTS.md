# Stripe Card Payments

Halyk Petroleum can collect an **approved quote** by card using Stripe-hosted
Checkout. The application creates the amount and customer-specific payment link,
but Stripe collects the card data. No card number, CVC, or cardholder data is
sent to or stored by this application.

## Setup

1. Create or sign in to a Stripe account.
2. Add these values to the production `.env` file:

   ```dotenv
   # Start with Stripe test-mode values.
   VP_STRIPE_SECRET_KEY=sk_test_...
   VP_STRIPE_WEBHOOK_SECRET=whsec_...
   ```

   The keys are intentionally environment-only; do **not** place them in
   Dashboard settings or in source control.

3. In the Stripe Dashboard, create a webhook endpoint:

   ```text
   https://yourdomain.com/payments/stripe/webhook
   ```

   Subscribe to these events:

   - `checkout.session.completed`
   - `checkout.session.async_payment_succeeded`
   - `checkout.session.async_payment_failed`
   - `checkout.session.expired`

   Copy the endpoint signing secret (`whsec_...`) into
   `VP_STRIPE_WEBHOOK_SECRET`.

4. Confirm `VP_BASE_URL` is the public **HTTPS** URL of the site.
5. Go to **Dashboard → Settings → System → Stripe card payments**, select the
   currency/link lifetime, and enable card payments.
6. Test with Stripe test mode before replacing `sk_test_...` with a live key.

## Sales workflow

1. Assign and review an RFQ as usual.
2. Move it through **New → Reviewing → Quoted → Approved**.
3. Open the quote and use **Card payment** to enter the final amount and email a
   secure link. Only one active payment request can exist per quote.
4. The customer opens the opaque link and presses **Continue to secure card
   checkout**. The application redirects them to `checkout.stripe.com`.
5. Stripe sends a signed webhook after payment. The payment ledger is marked
   **Paid**, the quote moves from **Approved** to **Completed**, and the
   customer/staff receive confirmation messages.

A payment link can be canceled from the quote page. If the customer has already
opened Stripe Checkout, the application first asks Stripe to expire that open
session, preventing a later accidental charge.

## Security behavior

- Checkout links use random 256-bit bearer tokens; they are not guessable,
  are never included in quote list pages, and only an HMAC is retained in the
  database. The plaintext token is handled transiently to email/render the link
  and is never persisted.
- The Checkout return URL is not trusted. The application retrieves the Stripe
  session server-side and matches its payment ID, currency, and integer amount.
- `/payments/stripe/webhook` is excluded from browser CSRF only because it
  verifies Stripe's `Stripe-Signature` against the **raw** request body. Invalid
  signatures return HTTP 400.
- Webhook event IDs are stored in `payment_events`, so Stripe retries and a
  browser return cannot double-complete a quote or send duplicate receipts.
- The raw webhook payload is deliberately not retained; the ledger keeps only
  the provider event ID/type needed for idempotency.
- A payment is never treated as paid simply because the browser reaches the
  success page. A valid Stripe result is required.

## Database upgrade

For an existing database, import:

```text
database/migrations/006_stripe_card_payments.sql
```

The migration creates `payments` and `payment_events`, expands the quote
activity enum, and adds non-secret card-payment settings. It is safe to rerun.
