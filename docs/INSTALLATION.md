# Installation — JetPacks Market

## Production cPanel installation (the standard path)

Use [`DEPLOYMENT.md`](../DEPLOYMENT.md). The complete production workflow is:

```text
Upload application-deployment.zip in File Manager
→ extract into the document root
→ create MySQL database/user in cPanel
→ import database/production.sql in phpMyAdmin
→ edit .env in File Manager
→ open the domain
```

No terminal, SSH, CLI PHP, Composer, npm, Docker, migration command, seed
command, or CLI admin-creation command is required.

### Required cPanel capabilities

- PHP 8.1+ with `mysqli`, `json`, `mbstring`, `curl`, `fileinfo`, and GD where
  image resizing is desired.
- MySQL 5.7+ or MariaDB 10.2+.
- File Manager and phpMyAdmin.

### Required `.env` values

```ini
CI_ENV=production
VP_BASE_URL=https://yourdomain.com
VP_FORCE_HTTPS=1
VP_DB_HOST=localhost
VP_DB_PORT=3306
VP_DB_NAME=your_cpanel_database
VP_DB_USER=your_cpanel_database_user
VP_DB_PASS=your_database_password
VP_ENCRYPTION_KEY=stable_secret_from_this_or_old_site
VP_AUTH_SECRET=another_stable_secret_from_this_or_old_site
```

The two secrets are required in production and are read only from `.env` or the
host environment. The application does not write `application/config/.secrets.php`
or any machine-generated secret file. When moving an existing installation,
retain both values unchanged.

### Writable folders

Use **cPanel File Manager → Change Permissions** for:

```text
assets/uploads/
assets/logs/
assets/logs/cache/
assets/logs/ratelimit/
```

Try `0755` first; use `0775` only when the host's PHP user/group arrangement
requires it.

## Optional developer utilities

The repository retains `install/` and `scripts/` for local development,
troubleshooting, and CI. They are not shipped in `application-deployment.zip`
and are not part of production deployment. Their availability never changes the
fresh cPanel process above.

## Verification checklist

After deployment, verify in a browser:

- Homepage: `/`
- Admin login: `/admin/login`
- A normal sign-in/session refresh
- A small media upload from the dashboard
- A public route such as `/products`

See [`DEPLOYMENT.md`](../DEPLOYMENT.md) for the initial administrator credentials
and browser-only troubleshooting.
