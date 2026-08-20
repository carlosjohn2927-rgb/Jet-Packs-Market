# JetPacks Market — Installation

This guide covers two paths:

1. **Recommended — cPanel / shared hosting (no Terminal required):**
   upload a zip + import a single SQL file + edit `.env`. See
   [`DEPLOYMENT.md`](../DEPLOYMENT.md).
2. **Optional — CLI installer:** `php install/install.php` for operators who
   have SSH access and want a scripted, idempotent import.

> **Security note:** there is **no default administrator password** anywhere
> in this repo. The admin account is created during installation with the
> password you provide, or with a randomly-generated one that the system
> forces you to change on first login.

## A. Prerequisites

Assuming you have either:
- **cPanel / shared hosting** with a MySQL 5.7+ / MariaDB 10.2+ database,
  File Manager or FTP, and PHP 8.1+ (8.2/8.3 recommended).
- **or a Linux server** with `php-cli`, `php-mysqli`, `php-mbstring`, and the
  rest of `php8.x-standard` available.

## B. cPanel / no-CLI deployment (recommended)

1. **Upload the contents** of the unpacked application to `public_html/`
   (or to an addon/subdomain document root). The upload goes **inside**
   the document root:
   ```
   public_html/
   ├── index.php
   ├── .htaccess
   ├── system/
   ├── application/
   └── assets/
   ```
   Do not upload the repo's `docs/`, `install/`, `scripts/` or `tests/`
   folders into the web root. The `install/` tooling is intended to live
   *outside* the document root by design — it never gets reached from a
   browser. (Even if it did, the root `.htaccess` blocks `.sql`, `.md`,
   `.log`, `.json`, dotfiles, and the `/application`, `/system`, `/tests`
   paths as defence in depth.)

2. **Create the database:** cPanel → MySQL Databases → new database + user
   with ALL PRIVILEGES. Note the database name, username and password.

3. **Edit `.env`:** copy `.env.example` to `.env` in the document root and
   fill in:
   ```ini
   CI_ENV=production
   VP_BASE_URL=https://yourdomain.com
   VP_FORCE_HTTPS=1
   VP_DB_HOST=localhost
   VP_DB_NAME=your_cpanel_db
   VP_DB_USER=your_cpanel_user
   VP_DB_PASS=your_strong_password
   VP_ENCRYPTION_KEY=64hexchars     # generate with: openssl rand -hex 32
   VP_AUTH_SECRET=64hexchars        # generate with: openssl rand -hex 32
   ```
   Without `VP_ENCRYPTION_KEY` / `VP_AUTH_SECRET`, the app generates
   `application/config/.secrets.php` automatically (gitignored, chmod 0600)
   on first boot. Explicit env vars are preferred in production because
   they survive redeploys.

4. **Import the database:** cPanel → phpMyAdmin → select your database →
   Import → choose `database/production.sql` → Go. That single file contains
   the full schema + demo content for 34,000+ parts, 10 aircraft platforms,
   8 FAQs, 4 testimonials and 10 OEM partner logos.

5. **Verify** at `https://yourdomain.com/` and sign in at
   `https://yourdomain.com/admin/login` with the credentials below.

## C. CLI installer (SSH / Local)

```bash
cd /path/to/jetpacksmarket             # wherever you unpacked the app
php install/install.php                # imports production.sql + creates admin
```

The CLI installer:
1. Imports `database/production.sql` (or falls back to
   `install/install.sql` + `install/seed.sql` only when the combined
   file is absent, e.g. on legacy installations).
2. Re-applies every migration in `database/migrations/` idempotently
   so any future schema drift on an existing install is reconciled.
3. Creates or repaves the initial SUPER_ADMIN account using
   `VP_ADMIN_EMAIL` / `VP_ADMIN_PASSWORD` from the environment or
   `.env`. If no password is supplied, a strong 18-byte random
   temporary password is generated, printed once, and the account is
   flagged with `mustChangePassword=1` so the operator is forced to
   set a real one on first login.
4. Generates `application/config/.secrets.php` (md mode 0600) when
   env vars are absent.
5. Prints **row counts** for every important table so the operator
   can see the install actually loaded.

### CLI flags

| Flag | Effect |
|---|---|
| `--source=auto` (default) | Use `database/production.sql` if available, otherwise the legacy split files. |
| `--source=production` | Force `database/production.sql` only (still runs migrations on top). |
| `--source=minimal` | Force the legacy split-file path (`install/install.sql` + `install/seed.sql` + every migration). |
| `--source=skip` | Skip schema import altogether; only create the admin + secrets. |
| `--users-only` | Skip schema and seed imports; create or repave the admin + secrets only. |
| `--status` | Read-only: print row counts for the key tables and exit. |

### Reset / recover an existing admin

```bash
VP_ADMIN_PASSWORD='a-strong-known-password' php install/install.php --users-only
```

This writes the new bcrypt hash, clears `mustChangePassword` and
reactivates the account.

### Read-only DB inspection

```bash
php install/install.php --status
```

Useful for debugging migrations or confirming `--source=production`
actually populated every table.

## D. Default administrator

- Email:    `admin@jetpacksmarket.com` (overridden with `VP_ADMIN_EMAIL`)
- Password: <printed once by the installer, or your `VP_ADMIN_PASSWORD`>
- Role:     `SUPER_ADMIN` — change it from
  **Dashboard → My profile → Change password** after first login.

## E. HTTPS

The `.htaccess` already redirects `http://` → `https://` (301). It skips the
redirect when `X-Forwarded-Proto: https` or a Cloudflare `CF-Visitor`
header says HTTPS, so it will **not redirect-loop on cPanel + Cloudflare**
setups. Make sure the SSL certificate is installed
(cPanel → SSL/TLS Status → AutoSSL).

## F. File permissions (cPanel)

The application code is read-only; only uploads and logs are writable by
the PHP process:

```bash
find ~/public_html -type d -exec chmod 755 {} \;
find ~/public_html -type f -exec chmod 644 {} \;
chmod -R 775 ~/public_html/assets/uploads
chmod -R 775 ~/public_html/assets/logs
```

These directories are pre-created: `assets/uploads/`, `assets/logs/`,
`assets/logs/cache/`, `assets/logs/ratelimit/` — each has its own
`.htaccess` denying direct web access.

## G. Cron jobs (recommended)

```bash
# Hourly: clean up old rate-limit buckets
0 * * * * find ~/public_html/assets/logs/ratelimit -mmin +120 -delete >/dev/null 2>&1

# Daily: rotate application log
15 0 * * * find ~/public_html/assets/logs -name "log-*.log" -mtime +30 -delete >/dev/null 2>&1
```

## H. Verification

The smoke test under `tests/roadmap_smoke.php` exercises the public
`/roadmap` route and the helpers that back it — runnable standalone:

```bash
php tests/roadmap_smoke.php                                  # unit-only
php tests/roadmap_smoke.php https://yourdomain.com           # also probes /roadmap live
```

## Troubleshooting

| Problem | Fix |
|---|---|
| `404 Not Found` everywhere | Apache `mod_rewrite` not enabled. Ask your host. |
| `503 The application is not configured` | Production env vars (`VP_DB_*`) missing — see step B3. |
| `Class 'mysqli' not found` | Enable `mysqli` in cPanel → PHP Extensions. |
| Login form posts but nothing happens | `ci_sessions` table missing — run `php install/fix-admin.php`, which creates it. |
| Admin uploads fail | `assets/uploads/` needs 775 perms (section F). |
| Can't sign in at `/admin/login` | Run `php install/fix-admin.php` — it repairs the six common causes. See [TROUBLESHOOTING.md](TROUBLESHOOTING.md#1-admin-login). |
| Mail doesn't send | Usually a blank `VP_SMTP_PASS`, which silently demotes the Mailer to `mail()`. Run `php install/test-mail.php`. See [TROUBLESHOOTING.md](TROUBLESHOOTING.md#2-cpanel-email-smtp). |
| `500` after import | Check `assets/logs/log-*.log`. Common cause: missing extensions (`mysqli`, `mbstring`, `json`, `fileinfo`). |

For the canonical step-by-step deployment, see **[`../DEPLOYMENT.md`](../DEPLOYMENT.md)**.
For admin-login and cPanel-SMTP failure walkthroughs, see
**[TROUBLESHOOTING.md](TROUBLESHOOTING.md)**.
