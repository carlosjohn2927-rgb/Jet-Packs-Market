# Vortex Precision — test suite

Run from the repository root. Requires: PHP 8.1+ (CLI with `mysqli`, `mbstring`,
`curl`, `fileinfo`, `openssl`), an empty MySQL 5.7+ / MariaDB 10.2+ database,
and a shell with `curl`.

```bash
# 1. Set up an empty database and point the installer at it
VP_DB_HOST=127.0.0.1 VP_DB_PORT=3306 \
VP_DB_NAME=vortex_ci VP_DB_USER=root VP_DB_PASS=secret \
VP_ADMIN_PASSWORD='Test-Admin-Pass-123!' \
php install/install.php

# 2. Run the full acceptance suite
VP_DB_HOST=127.0.0.1 VP_DB_PORT=3306 \
VP_DB_NAME=vortex_ci VP_DB_USER=root VP_DB_PASS=secret \
VP_ADMIN_PASSWORD='Test-Admin-Pass-123!' \
php app/tests/acceptance.php
```

`acceptance.php --install` runs step 1 for you.

## Standalone unit tests (no DB, no HTTP server, < 1s total)

For fast feedback on a single laptop or in CI before the full acceptance
suite — and for shape-checking the helpers + permission catalogue without
a database — there is also a standalone suite under `tests/units/`:

```bash
# Every standalone suite, all in well under a second.
bash tests/units/run_all.sh
# (or)
php tests/units/run_all.php

# A single suite
php tests/units/helpers_basic.php
php tests/units/quote_state_machine.php
php tests/units/rbac_catalog.php
php tests/units/product_image_resolution.php
```

Coverage:

| Suite | Checks |
|---|---|
| `helpers_basic.php` | `vp_money`, `vp_condition_badge`, `vp_part_price`, `vp_slugify`, `vp_truncate`, `vp_format_bytes` |
| `quote_state_machine.php` | `QUOTE_*` constants, `QUOTE_TRANSITIONS` forward-only + terminal contract, `vp_quote_status_label` |
| `stripe_payments.php` | exact decimal-to-minor-unit conversion, opaque link tokens, Stripe webhook HMAC verification and hosted-Checkout URL allowlist |
| `inventory_helpers.php` | lot-number normalization, expiry warnings and lot-status UI contract |
| `rbac_catalog.php` | every `permissions.php` row well-formed, groups registered, `super_only` set is exactly `{admins.manage, system.manage}`, role defaults reference only existing keys and never a super-only one, every critical key reachable by at least one non-super role |
| `product_image_resolution.php` | 6-step image URL resolution chain: uploaded > curated > category > keyword > default; null/empty inputs never throw |
| `pdf_render.php` | pure-PHP PDF generator: `%PDF-1.4` magic + `%%EOF` trailer, required object types (Catalog / Pages / Page / Font), A4 MediaBox, content stream + /Length, full quote document round-trip, parentheses + backslash escaping, empty doc + 60-row bulk render |


## What it verifies (140+ checks)

| Area | Checks |
|---|---|
| Syntax | `php -l` on every file under `app/` |
| Secrets | no change-me placeholders, no bcrypt hashes in `install/`, stable production keys come from `.env`, no generated `.secrets.php` dependency |
| Public site | `/`, about, services, contact, rfq, products (+detail), industries (+detail), blog (+post), careers (+detail), faq, downloads, news (+item), login, register, forgot, search |
| Auth | admin login/logout, invalid password, lockout after 5 failures (file-based), session persistence, session-id rotation (fixation), deactivated users signed out mid-session, forced password change for temp passwords |
| CSRF | POST without token rejected (403), token rotation |
| Password reset | full flow (forgot → email → reset → login), single-use tokens, old password invalidated, no user enumeration |
| Remember-me | HMAC cookie restores session; tampered signature rejected |
| RBAC | SALES can open dashboard + quotes, blocked from users/products |
| Sessions | DB rows written, expiry honored (short-lifetime server), destroyed session signs out |
| Uploads | valid PNG/PDF accepted; `.php`/`.phtml`/`.sh`/`.cgi`/`.phar` rejected; PHP-in-`.jpg` rejected (content sniffing); empty, oversized, `../` traversal, duplicate filenames, career resumes |
| Upload structure | valid `.docx`/`.xlsx`/`.zip`/`.doc`/`.dwg`/`.dxf`/`.step`/`.iges` accepted; mismatched content (plain text, PHP payloads, zip-without-`[Content_Types].xml`) wearing those extensions rejected |
| RFQ | full end-to-end: form → validation → DB (quote + items) → attachment on disk → 2 emails via mock Resend (From/Reply-To/subject/body) → email_logs SENT → admin list → assign → status transitions (optimistic lock + per-transition email dedupe) → rate limit |
| Contact | 5 submissions stored + 1 email each (per-message dedupe key), 6th rate-limited |
| Email | transport failure logged as FAILED with error message (dead endpoint server) |
| Downloads | redirect + counter |
| Admin CRUD | category create/edit/delete + audit trail |
| Production | homepage 200; missing DB/secrets env → fail-fast 503 with no secret leakage and no generated secret file |
| Database | 31 tables, 16+ FKs, 100+ indexes, UUID CHAR(36), JSON round-trip, role permissions, CI3 sessions |
| Runtime health | zero PHP warnings/deprecations/fatals across every server log |

## Servers booted by the suite

- **app** (default config, Resend → mock)
- **expiry** (`VP_SESSION_EXPIRATION=3`)
- **prod** (`CI_ENV=production`)
- **mailfail** (Resend pointed at a dead port)
- **noenv** (production with DB/secret env stripped → 503)
- **mock** (mock Resend API at `tests/mock_resend.php`)

The suite drives everything over real HTTP with curl cookie jars — no mocks
of the application itself. The only mock is the Resend HTTP endpoint.

The same suite runs in CI (`.github/workflows/acceptance.yml`) on a matrix of
PHP 8.2/8.3 × MySQL 8.0/MariaDB 10.6, plus an Apache job that verifies the
`.htaccess` redirects, blocking and CSP.
