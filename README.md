# Halyk Petroleum — Industrial Manufacturing Website

A CodeIgniter 3 PHP application for an industrial manufacturing company, featuring:

- **Public site**: Home, Products, Services, Industries, Blog, News, Careers, FAQ, Contact, RFQ (quote requests) — every page, menu, logo and text is database-driven
- **Super Admin Dashboard**: full control of the application — administrators, permissions, homepage, pages, navigation, header/footer, branding, media, settings, system
- **Admin Dashboard**: full website/content editing for every Admin, with operational sections controlled by the Super Admin (enforced server-side)
- **CMS**: homepage section builder, CMS pages at `/{slug}`, navigation manager, logo/favicon manager, media library
- **AI Chat Assistant**: Floating widget with optional LLM integration (OpenAI-compatible)
- **Auth system**: Login/register, password reset, roles + per-account permissions (Super Admin, Admin, Sales, Engineer, Editor, Customer)
- **Email**: SMTP (cPanel) or Resend API with fallback to PHP `mail()`
- **Security**: CSRF, login rate limiting, session protection, bcrypt passwords, audit logging, maintenance mode

See [`docs/DASHBOARD.md`](docs/DASHBOARD.md) for the complete dashboard / CMS guide.

---

## Portable cPanel Deployment

**No Terminal, SSH, Composer, Node.js, or CLI commands required.**

```
Upload Files → Create Database → Import SQL → Edit .env → Open Website
```

See [`DEPLOYMENT.md`](DEPLOYMENT.md) for the complete step-by-step guide.

### Quick Steps

1. **Upload** `application-deployment.zip` via cPanel File Manager and extract
2. **Create** a MySQL database and user in cPanel
3. **Import** `database/production.sql` via phpMyAdmin
4. **Edit** `.env` with your database credentials and domain
5. **Open** `https://yourdomain.com` — the application works immediately

### Default administrator

| Field    | Value                          |
|----------|--------------------------------|
| Email    | `admin@halykpetroleum-kz.com` |
| Password | `Nigeria1234@`                |
| Role     | `SUPER_ADMIN`                  |

**Change this password immediately** after first login
(`Dashboard → My profile → Change password`).

The Super Admin then creates further administrators and decides, per account,
which dashboard sections they can use: `Dashboard → People → Administrators`.

### Upgrading an existing installation

Import these two files in phpMyAdmin (in order) to add the dashboard/CMS tables
to a database created before this release — both are safe to re-run:

```
database/migrations/001_cms_and_permissions.sql
database/migrations/002_cms_seed.sql
database/migrations/003_admin_full_page_editing.sql
database/migrations/004_black_writeup.sql
```

---

## File Structure

```
/
├── .env                    # Environment config (gitignored)
├── .env.example            # Template for .env
├── .htaccess               # Apache rewrite rules
├── index.php               # Application front controller
├── application/            # CodeIgniter application code
├── assets/                 # Public CSS, JS, images, uploads
├── system/                 # CodeIgniter 3 framework
├── database/               # Database files
│   ├── production.sql      # Complete production database (schema + seed)
│   └── migrations/         # Incremental SQL upgrades for existing databases
├── docs/                   # Documentation
├── install/                # Optional CLI tools (not needed for deployment)
├── scripts/                # Development helper scripts
├── tests/                  # Acceptance tests
├── DEPLOYMENT.md           # cPanel deployment guide
└── application-deployment.zip  # Deployment package
```

## Development

To run locally with PHP's built-in server:

```bash
cp .env.example .env
# Edit .env with your local database credentials
# Import database/production.sql via phpMyAdmin or MySQL CLI
bash scripts/start.sh
```

### Running without MySQL (demo / CI)

Production always uses MySQL/MariaDB, but the application can also run on a
single SQLite file when no database server is available — handy for demos and
for automated tests:

```bash
php install/dev-sqlite.php database/dev.sqlite     # translates the MySQL schema + seed
cat >> .env <<'EOF'
CI_ENV=development
VP_DB_DRIVER=sqlite3
VP_DB_NAME=/absolute/path/to/database/dev.sqlite
EOF
bash scripts/start.sh
```

Accounts created by the script: `superadmin@halykpetroleum-kz.com / SuperAdmin123!`
(SUPER_ADMIN) and `admin@halykpetroleum-kz.com / Admin123!` (ADMIN).

### Tests

```bash
php tests/dashboard_acceptance.php http://127.0.0.1:8080   # dashboard, permissions, CMS → public site
php tests/acceptance.php                                   # full application suite (needs MySQL)
```

## License

Proprietary — All rights reserved.