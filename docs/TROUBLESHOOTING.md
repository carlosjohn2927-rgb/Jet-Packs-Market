# Troubleshooting — cPanel Deployment, Login, and Email

This guide uses only **cPanel File Manager**, **MySQL Databases**,
**phpMyAdmin**, and the browser. Terminal/SSH tools in the repository are
optional developer utilities and are not required to deploy or recover a normal
cPanel installation.

## 1. “The application is not configured”

In **File Manager**, open the root `.env` beside `index.php` and confirm:

```ini
CI_ENV=production
VP_BASE_URL=https://yourdomain.com
VP_DB_HOST=localhost
VP_DB_PORT=3306
VP_DB_NAME=full_cpanel_database_name
VP_DB_USER=full_cpanel_database_user
VP_DB_PASS=database_password
VP_ENCRYPTION_KEY=stable_non_placeholder_secret
VP_AUTH_SECRET=another_stable_non_placeholder_secret
```

Common causes:

- cPanel prefixes database/user names; copy the complete names shown in
  **MySQL Databases**.
- `.env` was not extracted because File Manager hides dotfiles; enable
  **Settings → Show Hidden Files**.
- a `REPLACE_WITH_...` placeholder was left in `.env`.
- the domain's document root points to a parent or nested folder rather than
  the folder that contains `index.php`.

## 2. Admin login

A fresh import of `database/production.sql` includes:

| Field | Value |
|---|---|
| Email | `admin@jetpacksmarket.com` |
| Password | `Nigeria1234@` |

The first sign-in intentionally opens the password-change screen. Change the
bootstrap password immediately.

If the login account does not exist, open **phpMyAdmin**, select the database,
and verify:

```sql
SELECT email, role, isActive, mustChangePassword FROM users;
```

If no rows are returned, `database/production.sql` was not completely imported.
Re-import it into an empty database through phpMyAdmin; no CLI admin creator is
needed.

If login redirects back to the sign-in page, verify in phpMyAdmin that the
`ci_sessions` table exists. It is included in `production.sql`. Also confirm
`VP_BASE_URL` is HTTPS when `VP_FORCE_HTTPS=1` and leave `VP_COOKIE_DOMAIN`
blank unless you deliberately need a parent-domain cookie.

After five incorrect attempts, wait 15 minutes for the login rate limit to
clear. The rate-limit files are in `assets/logs/ratelimit/` and can be cleared
through File Manager only if necessary.

## 3. File uploads fail

Use **File Manager → Change Permissions** and ensure these folders are writable
by the PHP account:

```text
assets/uploads/
assets/logs/
assets/logs/cache/
assets/logs/ratelimit/
```

Try `0755` first, then `0775` if the host's ownership model requires it. Do not
use `0777` unless your host specifically directs you to.

## 4. Email does not send

In `.env`, configure either SMTP or Resend. Typical cPanel SMTP values are:

```ini
VP_SMTP_HOST=mail.yourdomain.com
VP_SMTP_PORT=465
VP_SMTP_USER=no-reply@yourdomain.com
VP_SMTP_PASS=your_mailbox_password
VP_SMTP_CRYPTO=ssl
```

Both `VP_SMTP_HOST` and `VP_SMTP_PASS` must be non-empty to activate SMTP. You
can send a test message from **Dashboard → Settings → System** after signing in.

For deliverability, use **cPanel → Email Deliverability** to add the suggested
SPF and DKIM records.

## 5. Routing / 404 errors

Confirm all of the following in File Manager:

- `.htaccess` exists beside `index.php` (enable hidden files to see it).
- the domain document root is the extracted application folder;
- Apache `mod_rewrite` or LiteSpeed rewriting is enabled by the host;
- `index.php`, `application/`, `system/`, and `assets/` are at that same level.

See [`DEPLOYMENT.md`](../DEPLOYMENT.md) for the complete browser-only deployment
workflow.
