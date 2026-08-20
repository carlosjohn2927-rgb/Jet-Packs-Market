# Vortex Precision — Production Deployment Guide

## 1. Prerequisites

- cPanel (or equivalent) with Apache + `mod_rewrite`, PHP 8.1+ (PHP 8.2/8.3 recommended)
- MySQL 5.7+ / MariaDB 10.2+ (verified on MySQL 8.0 and MariaDB 10.6/10.8)
- Outbound HTTPS (Resend API) and/or outbound SMTP (mail())
- SSL certificate (cPanel → SSL/TLS Status → AutoSSL)

## 2. First-time checklist

- [ ] Document root points at `app/`
- [ ] `app/.env` created from `.env.example` (DB, secrets, Resend key)
- [ ] Database installed via `php install/install.php` (or manual SQL + `--users-only`)
- [ ] Admin password set/changed (no default passwords exist in this project)
- [ ] HTTPS redirect verified (`.htaccess` handles it; Cloudflare-safe)
- [ ] `ENVIRONMENT=production` (`CI_ENV=production` in `.env`)
- [ ] `display_errors` off (automatic when `ENVIRONMENT=production`)
- [ ] Install tooling lives in `install/` — **outside** the web root
- [ ] `RESEND_API_KEY` set (or PHP `mail()` verified on the server)
- [ ] File permissions: only `assets/uploads/` + `assets/logs/` writable
- [ ] `./scripts/smoke_check.sh` passes locally

## 3. Environment configuration

All production configuration is environment-based (see `.env.example`):

| Variable | Purpose |
|---|---|
| `VP_DB_HOST` `VP_DB_PORT` `VP_DB_NAME` `VP_DB_USER` `VP_DB_PASS` | Database (required in production — app refuses to boot with dev defaults) |
| `VP_ENCRYPTION_KEY` / `VP_AUTH_SECRET` | HMAC/encryption secrets (`openssl rand -hex 32`) |
| `RESEND_API_KEY` / `VP_RESEND_API_URL` | Transactional email via Resend |
| `VP_FROM_EMAIL` / `VP_FROM_NAME` / `VP_REPLY_TO` | Email envelope |
| `VP_BASE_URL` | Explicit base URL (recommended behind Cloudflare) |
| `VP_SESSION_EXPIRATION` | Session lifetime (seconds, default 7200) |
| `VP_FORCE_HTTPS` | Force secure cookies/URLs behind proxies |

In production the app **fails fast with HTTP 503** if the database variables
are missing instead of booting with development defaults — no silent
misconfiguration.

## 4. Email

- `mail()` is the default transport (verified to log `FAILED` correctly when no
  MTA is configured).
- Set `RESEND_API_KEY` to use the Resend HTTP API. Emails carry From name,
  Reply-To and HTML body; every send is recorded in `email_logs` with
  `SENT`/`FAILED` status, provider id, error message and a dedupe key.
- Status-change emails are idempotent **per transition** (a retry of the same
  transition never double-sends; each new transition does send).

## 5. Security posture (already implemented)

- HTTPS 301 redirect (proxy-aware, no Cloudflare loop)
- Direct web access blocked: `/application`, `/system`, `/sql`, `/tests`,
  dotfiles, `.env`, `*.sql`, `*.md`, `*.log`, `*.json`
- Uploads: random disk filenames, extension whitelist, MIME sniffing against
  executable payloads, image/PDF content validation, no PHP execution in
  `assets/`, directory listings off (`Options -Indexes`)
- CSP (no `unsafe-eval`; `unsafe-inline` only for styles), `nosniff`,
  `X-Frame-Options: SAMEORIGIN`, Referrer-Policy, Permissions-Policy, HSTS
- CSRF on every form (rotating tokens), database sessions, session-ID
  rotation on login, remember-me cookies HMAC-signed, login rate limiting
  (file-based, 5 fails / 15 min per IP+account), password reset tokens
  hashed in DB, single-use, 1-hour TTL
- Audit trail for every admin mutation + login/logout

## 6. Backups

Daily database dumps (cPanel → Cron Jobs):

```bash
mysqldump -u USER -p'PASS' DB | gzip > ~/backups/db-$(date +\%Y\%m\%d).sql.gz
find ~/backups -name "db-*.sql.gz" -mtime +30 -delete
```

Weekly uploads backup:

```bash
tar -czf ~/backups/uploads-$(date +\%Y\%m\%d).tar.gz ~/public_html/assets/uploads
find ~/backups -name "uploads-*.tar.gz" -mtime +90 -delete
```

Also back up `app/.env` and `app/application/config/.secrets.php` (off-site,
encrypted) — without them remember-me tokens and sessions can't be verified
after a restore.

## 7. Monitoring

- Health check: `GET /` returns HTTP 200 with `<title>Vortex Precision</title>`.
- Admin login: `GET /admin/login` returns HTTP 200.
- Audit log: `/admin/audit` shows every admin mutation.
- Email log: `SELECT status, COUNT(*) FROM email_logs GROUP BY status;` — alert on `FAILED` count > 5/hour.
- RFQ backlog: `SELECT COUNT(*) FROM quotes WHERE status='NEW' AND createdAt < NOW() - INTERVAL 1 DAY;` — alert if > 0.

## 8. CI / verification

`.github/workflows/acceptance.yml` runs on every push:

- Clean install from an **empty** MySQL 8.0 and MariaDB 10.6 database
  (both the installer path and the manual `install.sql` + `seed.sql` path)
- `php -l` on every PHP file
- Full HTTP acceptance suite (public routes, auth matrix, RBAC, CSRF,
  password reset, remember-me, sessions/expiry, RFQ + attachments + email
  via mock Resend, upload attack matrix, rate limits, logs, DB integrity)
- Apache job verifying the `.htaccess` redirect, blocking and CSP behavior

## 9. Scaling

Single shared-hosting box by design. For higher traffic:

- Move MySQL to a managed instance (update `VP_DB_*`)
- Move `assets/uploads/` to S3 (thin `Upload` library patch)
- Redis for sessions and rate-limit buckets (thin `Rate_limiter` patch)

## 10. Disaster recovery

- Database restore: `gunzip < db-YYYYMMDD.sql.gz | mysql -u USER -p'PASS' DB`
- Uploads restore: `tar -xzf uploads-YYYYMMDD.tar.gz -C ~/public_html/assets/`

RPO target: 24h (daily backup). RTO target: 1h.
