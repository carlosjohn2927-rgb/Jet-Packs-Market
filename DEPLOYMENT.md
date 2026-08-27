# Halyk Petroleum — Portable cPanel Deployment

## The normal deployment process

A new cPanel deployment requires **only**:

```text
File Manager upload/extract → MySQL Databases → phpMyAdmin import → edit .env → open the site
```

It does **not** require SSH, cPanel Terminal, Composer, Node.js, npm, pnpm,
Docker, a migration command, a seed command, or `php install/install.php`.

The deployment ZIP is `application-deployment.zip`. It is a ready-to-extract
web-root package: production PHP/framework files, public assets, writable-folder
placeholders, a root `.env` template, and the single complete database file
`database/production.sql` are already included.

---

## 1. Upload and extract files

1. Open **cPanel → File Manager**.
2. Open the document root for the domain, normally `public_html/` or the
   domain's addon/subdomain document root.
3. Upload `application-deployment.zip`.
4. Right-click it and choose **Extract** in that same directory.
5. Confirm `index.php`, `.htaccess`, and `.env` now sit directly in that web
   directory — not inside an extra nested folder.

The ZIP has no Composer/vendor installation step and no Node build step.

---

## 2. Create the MySQL database

In **cPanel → MySQL Databases**:

1. Create a database.
2. Create a database user with a strong password.
3. Add that user to the database.
4. Select **ALL PRIVILEGES** and save.

cPanel often prefixes names. For example, a database entered as `jetparts` may
become `cpaneluser_jetparts`. Use the complete displayed names in `.env`.

---

## 3. Import the complete database

1. Open **cPanel → phpMyAdmin**.
2. Select the new, empty database from the left side.
3. Select **Import**.
4. Choose the extracted file:

   ```text
   database/production.sql
   ```

5. Leave format as **SQL** and click **Go**.

`production.sql` is the complete fresh-install database. It contains the schema,
indexes, foreign keys, roles, permissions, CMS/settings rows, page templates,
reference data, catalog data, payment/inventory tables, CI session storage, and
the initial Super Admin. Do **not** import `install/install.sql`, seed files, or
individual migrations for a fresh deployment.

---

## 4. Edit `.env` in File Manager

The extracted ZIP already contains a safe root `.env` template. In **File
Manager**, right-click `.env` → **Edit**, then update at least:

```ini
CI_ENV=production
VP_BASE_URL=https://yourdomain.com
VP_FORCE_HTTPS=1

VP_DB_HOST=localhost
VP_DB_PORT=3306
VP_DB_NAME=YOUR_FULL_CPANEL_DATABASE_NAME
VP_DB_USER=YOUR_FULL_CPANEL_DATABASE_USER
VP_DB_PASS=YOUR_DATABASE_PASSWORD

VP_ENCRYPTION_KEY=YOUR_STABLE_ENCRYPTION_KEY
VP_AUTH_SECRET=YOUR_STABLE_AUTH_SECRET
```

### Secrets when moving an existing site

Copy `VP_ENCRYPTION_KEY` and `VP_AUTH_SECRET` **unchanged** from the old site's
`.env`. They are deliberately environment-only and are never generated into a
hidden application config file. Keeping them stable preserves the expected
security behavior for encrypted values, password-reset tokens, and
remember-me signatures after a server move.

### Secrets for a brand-new site

Replace the two placeholder values with two different random 64-character hex
strings. This can be done with any browser-based random hexadecimal generator;
no server command is required. Keep a secure copy of both values. Do not use
`REPLACE_WITH_...` values in production.

Optional mail, Resend, Stripe, site identity, session, and rate-limit variables
are documented inline in `.env` and can be filled in through File Manager too.

---

## 5. Confirm writable folders

The package already contains these folders and privacy `.htaccess` files:

| Folder | Purpose | cPanel File Manager permission |
|---|---|---|
| `assets/uploads/` | media, RFQ attachments, generated quote files | `0755` first; `0775` only if needed |
| `assets/logs/` | application logs | `0755` first; `0775` only if needed |
| `assets/logs/cache/` | cache files | `0755` first; `0775` only if needed |
| `assets/logs/ratelimit/` | rate-limit state | `0755` first; `0775` only if needed |

Use **File Manager → Change Permissions**. Do not use `chmod` or `chown` over
SSH for the normal workflow. Do not use world-writable `0777` unless your host
specifically requires it and you understand its security implications.

---

## 6. Open the site and sign in

Open:

```text
https://yourdomain.com
```

Admin sign-in:

```text
https://yourdomain.com/admin/login
```

Fresh `production.sql` credentials:

| Field | Value |
|---|---|
| Email | `admin@halykpetroleum-kz.com` |
| Password | `Nigeria1234@` |
| Role | `SUPER_ADMIN` |

Change the password immediately after the first sign-in from **Dashboard → My
profile → Change password**. The database account is already present; no CLI
admin-creation command is involved.

---

## Existing-install upgrades

For an **existing** database, use phpMyAdmin to import the incremental files in
order as needed. This is still browser-only and is not part of a fresh install:

```text
database/migrations/001_cms_and_permissions.sql
database/migrations/002_cms_seed.sql
database/migrations/003_admin_full_page_editing.sql
database/migrations/004_black_writeup.sql
database/migrations/005_jet_parts_market.sql
database/migrations/006_stripe_card_payments.sql
database/migrations/007_multi_warehouse_inventory.sql
```

For a brand-new database, import **only** `database/production.sql`.

---

## Migration between cPanel accounts

1. Download the deployment ZIP (or compress the existing files in File Manager).
2. Export the existing database from phpMyAdmin, or use the supplied complete
   `database/production.sql` for a fresh/demo database.
3. Follow steps 1–3 above on the new cPanel account.
4. Copy the existing `.env` values, changing only the domain and database
   connection settings.
5. Keep `VP_ENCRYPTION_KEY` and `VP_AUTH_SECRET` unchanged.
6. Set writable-folder permissions through File Manager and open the site.

---

## Browser-only troubleshooting

- **“The application is not configured”** — check the four `VP_DB_*` values,
  including cPanel's username/database prefixes, and make sure placeholders
  were replaced.
- **Secret configuration error** — replace both `REPLACE_WITH_...` values in
  `.env` with stable values; never rely on an auto-generated `.secrets.php`.
- **404 on every route** — make sure `.htaccess` was extracted beside
  `index.php`, and that the domain document root points at this folder.
- **Login immediately returns to sign-in** — confirm `ci_sessions` exists in
  phpMyAdmin (it is part of `production.sql`) and `assets/logs/` is writable.
- **Uploads fail** — adjust `assets/uploads/` through File Manager as described
  above.
- **Email does not send** — configure SMTP or Resend values in `.env`; the site
  can otherwise use the host's PHP mail fallback.

## Normal deployment never requires

```text
php install/install.php
php install/install.php --users-only
composer install
npm install / pnpm install
Docker
SSH / Terminal
manual seed commands
manual migration commands for a fresh database
```
