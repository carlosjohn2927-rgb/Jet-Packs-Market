# Vortex Precision — Installation on cPanel / shared hosting

This guide assumes a cPanel account with:
- A MySQL 5.7+ / MariaDB 10.2+ database (cPanel → MySQL Databases)
- File Manager / FTP access, and SSH ("Terminal") access for the one-time install
- Apache with `mod_rewrite` (default on cPanel), PHP 8.1+ (PHP 8.2/8.3 recommended)

> **Security note:** there is **no default administrator password** anywhere in
> this project. The admin account is created during installation with a
> password you provide, or a random temporary password that the system forces
> you to change on first login.

## 1. Upload the application

The document root is the `app/` folder. Upload everything **inside** `app/` to
`public_html/` (or point an addon/subdomain at it):

```
public_html/
├── index.php
├── .htaccess
├── system/
├── application/
└── assets/
```

Do **not** upload the repository's top-level `README.md`, `docs/`, `scripts/`,
`install/` or `tests/` folders into the web root. The `install/` tooling is
**outside the document root by design** — it can never be reached through a
browser. (The root `.htaccess` blocks `/application`, `/system`, `/sql`,
`/tests`, dotfiles and `.sql/.md/.log/.json` files regardless, as defence in
depth.)

## 2. Create the database

In **cPanel → MySQL Databases**:
1. Create a database (e.g. `vortex_precision`).
2. Create a database user (e.g. `vortex_user`) with a strong password.
3. Add the user to the database with ALL PRIVILEGES.

## 3. Configure environment variables

Copy the template **`.env.example` → `app/.env`** on the server and fill in
real values:

```ini
# .env (never commit this file - it is gitignored)
CI_ENV=production
VP_DB_HOST=localhost
VP_DB_NAME=your_cpanel_db
VP_DB_USER=your_cpanel_user
VP_DB_PASS=your_strong_password

# Generate with: openssl rand -hex 32   (run twice)
VP_ENCRYPTION_KEY=64hexcharshere
VP_AUTH_SECRET=64hexcharshere

# Optional but recommended for reliable email
RESEND_API_KEY=re_xxxxxxxx

# First admin account (email defaults to admin@vortexprecision.com)
VP_ADMIN_EMAIL=admin@vortexprecision.com
# Leave VP_ADMIN_PASSWORD EMPTY to get a random temporary password
```

If you cannot place a `.env` file, the same variables can be set as real
environment variables (`SetEnv KEY value` in `.htaccess`, cPanel MultiPHP INI,
or shell profile). Real environment variables take precedence over `.env`.

If you set **no** secret env vars, the app generates random
`application/config/.secrets.php` on first boot (gitignored, chmod 0600) — but
explicit env vars are preferred in production because they survive redeploys.

## 4. Install (SSH / Terminal)

```bash
cd ~/repositories/frist-   # wherever you cloned the repo
php install/install.php
```

This:
1. Creates the schema (`install/install.sql` — 31 tables, FKs, indexes, JSON columns)
2. Creates the `SUPER_ADMIN` account (password from `VP_ADMIN_PASSWORD`, or a
   **random temporary password printed once**)
3. Loads demo content (`install/seed.sql`)
4. Generates stable secret keys if env vars are missing
5. Prints a completion summary

**Manual phpMyAdmin alternative:** import `install/install.sql` then
`install/seed.sql`, then run `php install/install.php --users-only` to create
the admin account. (If you skip `--users-only` there is simply no admin — the
seed contains no accounts by design.)

## 5. Test the install

1. Visit `https://your-domain.com` — the public home page should render.
2. Visit `https://your-domain.com/admin/login` and sign in with the email +
   password from the installer output.
3. If the password was auto-generated, the system forces a change on first
   login (Users → edit your account → new password).

## 6. HTTPS

The `.htaccess` already redirects `http://` → `https://` (301). It skips the
redirect when `X-Forwarded-Proto: https` or Cloudflare's `CF-Visitor` header is
present, so it will **not redirect-loop on cPanel + Cloudflare** setups. Make
sure the SSL certificate is installed (cPanel → SSL/TLS Status → AutoSSL).

## 7. File permissions (cPanel)

The application code is read-only; only uploads and logs are writable by the
PHP process:

```bash
find ~/public_html -type d -exec chmod 755 {} \;
find ~/public_html -type f -exec chmod 644 {} \;
chmod -R 775 ~/public_html/assets/uploads
chmod -R 775 ~/public_html/assets/logs
```

These directories are pre-created in the repo:
`assets/uploads/`, `assets/logs/`, `assets/logs/cache/`, `assets/logs/ratelimit/`
(each with its own `.htaccess` that denies direct web access to logs).

## 8. Cron jobs (recommended)

```bash
# Hourly: clean up old rate-limit buckets
0 * * * * find ~/public_html/assets/logs/ratelimit -mmin +120 -delete >/dev/null 2>&1

# Daily: rotate application log
15 0 * * * find ~/public_html/assets/logs -name "log-*.log" -mtime +30 -delete >/dev/null 2>&1
```

## 9. Verification

Run the no-PHP smoke check locally before uploading:

```bash
./scripts/smoke_check.sh
```

The repository's GitHub Actions workflow (`.github/workflows/acceptance.yml`)
runs the complete acceptance suite — clean install, every public route, admin
login/logout, RBAC, CSRF, password reset, remember-me, sessions, RFQ
end-to-end with attachments and email, upload security, and `.htaccess`
redirects/blocking — against **PHP 8.2/8.3 and MySQL 8.0/MariaDB 10.6** on
every push. See `app/tests/acceptance.php`.

## Troubleshooting

| Problem | Fix |
|---|---|
| `404 Not Found` on every page | Apache `mod_rewrite` not enabled. Ask your host. |
| `503 The application is not configured` | Production env vars (VP_DB_*) missing — see step 3. |
| `Class 'mysqli' not found` | Enable `mysqli` in cPanel → PHP Extensions. |
| Login form posts but nothing happens | `ci_sessions` table missing — run `php install/fix-admin.php`, which creates it. |
| Admin uploads fail | `assets/uploads/` needs 775 perms (step 7). |
| Can't sign in at `/admin/login` | Run `php install/fix-admin.php` — it repairs all six causes. See [TROUBLESHOOTING.md](TROUBLESHOOTING.md#1-admin-login). |
| Mail doesn't send | Usually a blank `VP_SMTP_PASS`, which silently demotes the Mailer to `mail()`. Run `php install/test-mail.php`. See [TROUBLESHOOTING.md](TROUBLESHOOTING.md#2-cpanel-email-smtp). |
| `500` after import | Check `assets/logs/log-*.log`. Common cause: missing extensions (`mysqli`, `mbstring`, `json`, `fileinfo`). |

For a step-by-step walkthrough of admin-login and cPanel-SMTP failures, see
**[docs/TROUBLESHOOTING.md](TROUBLESHOOTING.md)**.
