# Halyk Petroleum — cPanel Deployment Guide

## Overview

This guide covers **portable cPanel deployment** of the Halyk Petroleum website.

**No Terminal, SSH, Composer, Node.js, or any command-line tool is required.**

The complete deployment process is:

```
Upload Files → Create Database → Import SQL → Edit .env → Open Website
```

---

## Quick Start (New cPanel Deployment)

### Step 1 — Upload the Application Files

1. Download the deployment package: `application-deployment.zip`
2. Log into **cPanel**.
3. Open **File Manager**.
4. Navigate to the web root for your domain (typically `public_html` or `public_html/yourdomain.com`).
5. Click **Upload** and select `application-deployment.zip`.
6. After upload completes, right-click the ZIP file and choose **Extract**.
7. The files will be extracted into the current directory.

**No Terminal commands needed.**

---

### Step 2 — Create the Database

1. In cPanel, open **MySQL Databases**.
2. Under **Create New Database**:
   - Enter a database name (e.g., `halyk_production`).
   - Click **Create Database**.
3. Scroll to **Add New User**:
   - Enter a username (e.g., `halyk_user`).
   - Enter a strong password (use the **Password Generator**).
   - Click **Create User**.
4. Scroll to **Add User to Database**:
   - Select your new user and database.
   - Check **ALL PRIVILEGES**.
   - Click **Make Changes**.
5. Note down the **database name**, **username**, and **password**.

---

### Step 3 — Import the Database

1. In cPanel, open **phpMyAdmin**.
2. In the left sidebar, click on your new database (it will be empty).
3. Click the **Import** tab at the top.
4. Click **Choose File** and select:
   ```
   database/production.sql
   ```
   (found in the extracted files from Step 1)
5. Ensure the format is **SQL**.
6. Click **Go** at the bottom.

Wait for the import to complete. This creates all tables, seed data, and the initial admin account.

---

### Step 4 — Configure `.env`

1. In cPanel **File Manager**, locate the `.env` file in the web root (where `index.php` is).
2. Right-click `.env` and select **Edit**.
3. Update the following values with your new cPanel details:

```ini
# Your domain
VP_BASE_URL=https://yourdomain.com

# Database (from Step 2)
VP_DB_NAME=your_database_name
VP_DB_USER=your_database_user
VP_DB_PASS=your_database_password

# Keep these the same across deployments (don't change unless you know why)
VP_ENCRYPTION_KEY=403ed09b1cdeaf1f96a98276722e4e354c2531a31564ffad7e66a11e63195e65
VP_AUTH_SECRET=d8dba289fdb71eac60b1cf3a262be194e9bc17a539a7368ddb9724edc40fd44f
```

4. Save the file (`Ctrl+S` or **Save Changes**).

---

### Step 5 — Set Writable Directory Permissions

The following directories must be writable by the web server for file uploads, logs, and caching:

1. In **File Manager**, navigate to the web root.
2. For each directory below, right-click it and select **Change Permissions**:
   - `assets/uploads/` — **755** or **777**
   - `assets/logs/` — **755** or **777**
   - `assets/logs/cache/` — **755** or **777**
   - `assets/logs/ratelimit/` — **755** or **777**

Typical cPanel permissions: check **Write** for **User** and **Group** (0755).

---

### Step 6 — Open the Website

Visit:

```
https://yourdomain.com
```

The homepage should load. Log into the admin panel at:

```
https://yourdomain.com/admin/login
```

**Default admin credentials** (built into `database/production.sql`):

| Field    | Value                          |
|----------|--------------------------------|
| Email    | `admin@halykpetroleum-kz.com` |
| Password | `Nigeria1234@`                |

**Change this password immediately** after first login via
**Dashboard → My profile → Change password**.

That account is the **Super Admin**: it owns the administration system and can
create further administrators and decide, per account, which dashboard sections
they may use (**Dashboard → People → Administrators**). See
[`docs/DASHBOARD.md`](docs/DASHBOARD.md).

---

## Upgrading an Existing Installation (dashboard / CMS release)

If your database was imported **before** this release, add the new tables by
importing two more files in phpMyAdmin — no CLI, no data loss:

1. phpMyAdmin → your database → **Import**
2. `database/migrations/001_cms_and_permissions.sql` → **Go**
3. `database/migrations/002_cms_seed.sql` → **Go**
4. `database/migrations/003_admin_full_page_editing.sql` → **Go**
5. `database/migrations/004_black_writeup.sql` → **Go**

All files are safe to import more than once (`CREATE TABLE IF NOT EXISTS`,
`INSERT IGNORE`, `ON DUPLICATE KEY UPDATE`). The three `ALTER TABLE media …`
statements in the first file report *"Duplicate column name"* if they were
already applied — that message can be ignored.

After the import, sign in and open **Dashboard → Website → Homepage** to start
editing the public site.

---

## Migration (Moving from One Server to Another)

1. **Export files** — Download the entire site via cPanel File Manager → Compress as ZIP.
2. **Export database** — In phpMyAdmin, select the database → **Export** → **Quick** → **Go**.
3. **Upload files** to the new cPanel (Step 1 above).
4. **Create database** on the new cPanel (Step 2).
5. **Import** the exported SQL file (Step 3).
6. **Edit `.env`** with new database credentials and domain (Step 4).
7. Keep `VP_ENCRYPTION_KEY` and `VP_AUTH_SECRET` **the same** as before.
8. **Open the website** — everything should work, including existing sessions.

---

## What the Production SQL Includes

The file `database/production.sql` contains:

| Item | Details |
|------|---------|
| **All tables** | 25+ tables with columns, indexes, and foreign keys |
| **Admin account** | `admin@halykpetroleum-kz.com` / `Nigeria1234@` (bcrypt-hashed) |
| **Role permissions** | SUPER_ADMIN, ADMIN, SALES, ENGINEER, EDITOR roles |
| **Categories** | 6 product categories (Valves, Pumps, Heat Exchangers, etc.) |
| **Industries** | 6 industries (Oil & Gas, Chemical, Power, Water, Pharma, Food) |
| **Products** | 12 sample products with specifications |
| **FAQs** | 8 frequently asked questions |
| **Testimonials** | 4 customer testimonials |
| **Partners** | 6 partner logos |
| **Settings** | 60+ application settings (identity, branding, contact, social, header/footer, SEO, chat, system) |
| **Permissions** | Permission catalogue + role defaults for every staff role |
| **Homepage sections** | 8 editable homepage blocks (hero, stats, categories, products, industries, testimonials, partners, CTA) |
| **Navigation** | Header menu, two footer columns and legal links |
| **CMS pages** | Privacy Policy and Terms of Service starter pages |
| **Careers** | 4 job openings |
| **News** | 3 news articles |
| **Downloads** | 4 downloadable resources |
| **Blog posts** | 2 sample blog articles |
| **CI sessions** | Session table for database-backed sessions |

---

## Writable Directories

The following directories must be writable by the web server:

| Directory | Purpose |
|-----------|---------|
| `assets/uploads/` | User-uploaded images and files |
| `assets/logs/` | Application error logs |
| `assets/logs/cache/` | Data cache files |
| `assets/logs/ratelimit/` | Rate-limiter state files |

These are pre-created in the deployment package. Set permissions via cPanel **File Manager → Change Permissions** to **755** (or **777** if 755 doesn't work).

---

## Configuration via `.env` Only

All server-specific configuration comes from `.env`. No CLI-generated config files are required.

| Variable | Description |
|----------|-------------|
| `CI_ENV` | Environment mode (`production`, `development`, `testing`) |
| `VP_BASE_URL` | Full URL to your site (with trailing slash) |
| `VP_FORCE_HTTPS` | Set to `1` to force HTTPS cookies |
| `VP_COOKIE_DOMAIN` | Cookie domain (e.g., `.yourdomain.com`) |
| `VP_DB_HOST` | MySQL host |
| `VP_DB_PORT` | MySQL port |
| `VP_DB_NAME` | MySQL database name |
| `VP_DB_USER` | MySQL username |
| `VP_DB_PASS` | MySQL password |
| `VP_ENCRYPTION_KEY` | 64-character hex encryption key |
| `VP_AUTH_SECRET` | 64-character hex auth secret |
| `VP_SITE_NAME` | Your company name |
| `VP_SITE_TAGLINE` | Your company tagline |
| `VP_CONTACT_EMAIL` | Public contact email |
| `VP_SUPPORT_EMAIL` | Support email |
| `VP_RFQ_EMAIL` | Quote request email |
| `VP_PHONE` | Phone number |
| `VP_ADDRESS` | Physical address |
| `VP_FROM_EMAIL` | Outbound "From" email address |
| `VP_FROM_NAME` | Outbound "From" name |
| `VP_REPLY_TO` | Reply-to email |
| `VP_SMTP_HOST` | SMTP server hostname |
| `VP_SMTP_PORT` | SMTP port (465, 587, or 25) |
| `VP_SMTP_USER` | SMTP username |
| `VP_SMTP_PASS` | SMTP password |
| `VP_SMTP_CRYPTO` | SMTP encryption (`ssl`, `tls`, or blank) |
| `VP_SESSION_EXPIRATION` | Session lifetime in seconds |
| `VP_LOG_THRESHOLD` | Logging level (0=off, 1=error, 2=debug, 3=info, 4=all) |
| `VP_GLOBAL_RATE_LIMIT` | Requests per 15 minutes per IP |

---

## What is NOT Required

The following are **never required** for a normal deployment:

- ❌ `php install/install.php` — No CLI installer needed
- ❌ `php install/install.php --users-only` — No CLI admin creation
- ❌ `composer install` — No Composer
- ❌ `npm install` / `pnpm install` — No Node.js
- ❌ `chmod` / `chown` via SSH — Use File Manager instead
- ❌ Database migrations — All in `production.sql`
- ❌ Database seeding — All in `production.sql`
- ❌ Secret key generation — Already in `.env.example` or auto-generated

---

## Troubleshooting

### Homepage shows "The application is not configured"

Edit `.env` and verify all `VP_DB_*` variables are correct. Check that the database was imported successfully.

### "No input file specified" or 404 errors

Make sure the `.htaccess` file exists in the root directory (alongside `index.php`). In cPanel, ensure the document root is set to the folder containing these files.

### Admin login: "Invalid credentials"

1. Verify the admin credentials: `admin@halykpetroleum-kz.com` / `Nigeria1234@`
2. Check that `database/production.sql` was fully imported — run this in phpMyAdmin:
   ```sql
   SELECT COUNT(*) FROM users;
   ```
   If it returns 0, the admin account was not created. Re-import the SQL file.

### Login loops back to login page

The `ci_sessions` table may not exist or the session is being dropped. Run in phpMyAdmin:
```sql
SELECT COUNT(*) FROM ci_sessions;
```
If the table doesn't exist, re-import `database/production.sql`.

### Email not sending

Edit `.env` and verify:
- `VP_SMTP_HOST` and `VP_SMTP_PASS` are both set (both must be non-empty)
- `VP_SMTP_PORT` and `VP_SMTP_CRYPTO` match your cPanel email settings
- The email account exists in cPanel and the password is correct

### File uploads fail

Check permissions on `assets/uploads/` — must be writable by the web server (755 or 777).

---

## File Structure (Web Root)

```
/
├── .env                    # Environment config (gitignored)
├── .env.example            # Template for .env
├── .htaccess               # Apache rewrite rules
├── index.php               # Application front controller
├── site.webmanifest        # PWA manifest
├── application/            # CodeIgniter application code
│   ├── config/
│   ├── controllers/
│   ├── core/
│   ├── helpers/
│   ├── language/
│   ├── libraries/
│   ├── models/
│   └── views/
├── assets/                 # Public assets
│   ├── css/
│   ├── img/
│   ├── js/
│   ├── uploads/            # Writable: user uploads
│   └── logs/               # Writable: logs, cache, ratelimit
├── system/                 # CodeIgniter 3 framework
├── database/               # Database files
│   ├── production.sql      # Complete production database
│   └── migrations/         # Idempotent SQL upgrades for existing databases
├── install/                # Optional CLI tools (not needed for deployment)
├── docs/                   # Documentation
└── DEPLOYMENT.md           # This file
```