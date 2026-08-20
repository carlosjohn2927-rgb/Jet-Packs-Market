# Vortex Precision — RFQ (Request for Quote) System

The RFQ system is the highest-value piece of the application. It was ported carefully from the original NestJS implementation and preserves all production hardening.

## State machine

```
NEW
  ↓ (must assign admin)
REVIEWING
  ↓
QUOTED
  ↓
APPROVED  →  COMPLETED
  ↓
REJECTED
```

- **Forward-only.** No backward transitions.
- **No skipping stages.**
- **`assignedTo` is required** once status leaves `NEW`.
- **No transitions from terminal states** (`REJECTED`, `COMPLETED`).

The state machine is encoded in `app/application/config/constants.php` under `QUOTE_TRANSITIONS` and enforced by `Quote_model::transition_status()`.

## Optimistic locking

The `quotes` table has a `version` INT column. Every status / detail update runs:

```sql
UPDATE quotes SET status=?, version = version + 1, ...
WHERE id = ? AND version = ?
```

If the row was modified by another admin in the meantime (rowcount 0), the user sees an error and is asked to refresh.

This prevents lost updates when two sales engineers view the same quote and try to change its status simultaneously.

## Status history

Every transition writes an immutable row to `quote_status_history`:

```sql
INSERT INTO quote_status_history (quoteId, fromStatus, toStatus, changedBy, notes, createdAt)
```

These rows are never updated or deleted. They form the audit trail shown on the admin "view quote" page.

## Activity log

Every meaningful action (status change, assignment, internal note, PDF generation, email send) writes a row to `quote_activities` with a typed `action` enum, the actor's ID, IP, and user agent. The admin timeline view shows this.

## Idempotent email

When a notification email is sent, it's logged to `email_logs` with a `dedupe_key` of the form `template:recipient[:quoteId]`. The Mailer checks this key before sending and refuses to re-send an already-sent email. The `retryCount` tracks failed attempts.

Templates: `quote_submitted_admin`, `quote_confirmation_customer`, `quote_status_update`. Each is a PHP view under `app/application/views/emails/`.

## Rate limiting

Two layers:

| Scope | Limit | Window | Applies to |
|---|---|---|---|
| Global | 100 requests | 15 min | All `POST /rfq/submit` |
| RFQ submission | 5 quotes | 1 hour | `POST /rfq/submit` |

Implementation: file-based counter under `app/assets/logs/ratelimit/`, keyed by `rfq:ip:email` for RFQ and `global:ip` for the global limit.

## Public submission flow

1. User visits `/rfq` and fills the form (company, contact, line items, notes).
2. JS adds/removes line item rows client-side.
3. Form posts to `/rfq/submit`.
4. Controller validates (`form_validation`), checks rate limit, persists Quote + QuoteItems in a transaction, writes initial `quote_status_history` and `quote_activities` rows.
5. Mailer sends admin notification + customer confirmation (idempotent).
6. User redirected to `/rfq/thanks/{quoteNumber}`.

## Admin workflow

1. Sales engineer visits `/admin/quotes`.
2. Filters by status / assignee / free-text search.
3. Clicks a quote to see the detail page (items, history, activity, assignment, status update form).
4. Updates status (state machine validates; concurrent edit detected via version).
5. Optionally adds an internal note (separate from customer-visible status note).
6. Generates PDF (renders the printable HTML view, saves to `assets/uploads/quotes/`).
7. Exports all quotes to CSV via `/admin/quotes/export/csv`.

## Endpoints (route → action)

| URL | Action |
|---|---|
| `GET  /rfq` | Show RFQ form |
| `POST /rfq/submit` | Submit new RFQ |
| `GET  /rfq/thanks/{quoteNumber}` | Confirmation |
| `GET  /admin/quotes` | List + filter |
| `GET  /admin/quotes/{id}` | Single quote |
| `POST /admin/quotes/{id}/status` | Update status (state machine) |
| `POST /admin/quotes/{id}/assign` | Assign / reassign |
| `POST /admin/quotes/{id}/note` | Add internal note |
| `GET  /admin/quotes/{id}/pdf` | Render printable PDF |
| `GET  /admin/quotes/export/csv` | Export all as CSV |
| `POST /admin/quotes/{id}/delete` | Delete (Super Admin only) |

## Database tables

- `quotes` (with `version`, `assignedTo`, `statusUpdatedAt`)
- `quote_items`
- `quote_attachments` (not exposed in the public form yet)
- `quote_status_history` (immutable)
- `quote_activities` (audit trail with action enum)
- `email_logs` (with `dedupeKey` for idempotency)

See `install/install.sql` for full schema.
