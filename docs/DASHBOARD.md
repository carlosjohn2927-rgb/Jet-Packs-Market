# Dashboard & CMS Guide

Halyk Petroleum ships with two dashboards backed by **one** set of data:

| Dashboard | Who | What it can do |
|---|---|---|
| **Super Admin Dashboard** | `SUPER_ADMIN` | Everything, always. Owns the administration system. |
| **Admin Dashboard** | `ADMIN`, `SALES`, `ENGINEER`, `EDITOR` | Exactly the sections the Super Admin granted. |

Both are the same application at `/admin`; the sidebar, the panels and every
server-side action are filtered by the signed-in account's permissions.

```
SUPER ADMIN
     ├── Controls admin accounts        (Dashboard → People → Administrators)
     ├── Controls admin permissions     (per account, per section)
     ├── Controls the website           (homepage, pages, navigation, branding)
     ├── Controls content & media
     ├── Controls settings & system
     └── ADMIN → only what was granted
```

---

## 1. Signing in

| URL | Purpose |
|---|---|
| `/admin/login` | Staff sign-in (rate-limited: 5 failures per 15 min per IP + email) |
| `/admin` | Dashboard home |
| `/admin/profile` | Own profile + password change |
| `/logout` | Secure sign-out (session destroyed, remember-me cookie cleared) |

The dashboard header always contains:

```
[LOGO → public homepage]   🏠 Homepage   🌐 View Website   🔔 Notifications   👤 Profile
```

The logo, the **Homepage** button and the **View Website** button all open the
public site **in a new tab**, so the dashboard session is never lost.

---

## 2. Roles and permissions

Roles live on `users.role`. Permissions are `resource.action` keys declared in
[`application/config/permissions.php`](../application/config/permissions.php)
and mirrored into the `permissions` table.

```
SUPER_ADMIN   every permission, always — cannot be reduced or removed
ADMIN         role defaults (role_permissions) ± per-account overrides
SALES / ENGINEER / EDITOR   narrower role defaults, same override mechanism
```

Effective permissions for an account:

```
role defaults (role_permissions)
  + explicit grants   (user_permissions.granted = 1)
  − explicit denials  (user_permissions.granted = 0)
  − super-only permissions (admins.manage, system.manage) — never grantable
```

### Enforcement

Enforcement is **server-side**, in `Admin_Controller`:

```php
class Pages extends Admin_Controller
{
    protected $required_permission = 'pages.manage';       // whole controller
    protected $method_permissions  = ['system' => 'system.manage'];  // per action
    protected $super_admin_only    = true;                 // Super Admin sections
}
```

Every request into `/admin/*` passes that gate before any action runs, so typing
a URL directly, replaying a form POST or calling an endpoint with curl all end
in a logged `403 ACCESS_DENIED`. Hiding a sidebar entry is cosmetic only.

### Permission catalogue

| Group | Permissions |
|---|---|
| Overview | `dashboard.view`, `reports.view` |
| Sales | `quotes.manage`, `contacts.manage` |
| Catalog | `products.manage`, `categories.manage`, `industries.manage`, `downloads.manage` |
| Content | `blog.manage`, `news.manage`, `faqs.manage`, `careers.manage`, `testimonials.manage`, `partners.manage` |
| Website | `homepage.manage`, `pages.manage`, `menus.manage`, `appearance.manage`, `media.manage`, `seo.manage` |
| People | `customers.manage`, `admins.manage` *(Super Admin only)* |
| System | `settings.manage`, `audit.view`, `system.manage` *(Super Admin only)* |

Personal notifications (`/admin/notifications`) and the own-profile page need no
permission — they are always scoped to the signed-in account.

---

## 3. Administrator management (Super Admin only)

`Dashboard → People → Administrators`

* **Create** an administrator (email, name, role, password, permissions)
* **Edit** any detail
* **Enable / disable** — a disabled account is signed out immediately and cannot log in
* **Delete** an account
* **Reset password** — type one or let the system generate a temporary one
  (optionally forcing a change at next sign-in)
* **Assign / remove permissions** — tick exactly what the account may open
* **View activity** — every recorded action of that administrator

### Super Admin protection (enforced in code)

A normal administrator can never:

* create a Super Admin (the role is not offered and is rejected server-side)
* promote themselves (`/admin/users` only ever writes `role = CUSTOMER`)
* edit, disable, delete or re-permission the Super Admin account
* open `admins.manage` or `system.manage` — those permissions are unassignable

The Super Admin row is also protected from other Super Admin accounts in
`Admins::_assert_manageable()` (only the account itself may change it).

---

## 4. Website content management

Everything the public site shows is stored in the database and edited here.

| Section | URL | Controls |
|---|---|---|
| Homepage builder | `/admin/homepage` | hero, stats, categories, featured products, industries, services, testimonials, partners, FAQ, rich text, banner, newsletter, CTA |
| Pages | `/admin/pages` | title, slug, content, featured image, SEO title/description, status, visibility, publish date |
| Navigation | `/admin/menus` | header menu, two footer columns, legal links: add / edit / reorder / enable / disable, internal path, CMS page or external URL |
| Logo & branding | `/admin/appearance` | website name/title/description, primary logo, dark logo, footer logo, favicon, alt text, logo height |
| Colours | `/admin/appearance/colors` | site-wide background and write-up colours, plus Admin / Super Admin sidebar background (black) and menu write-up (white) |
| Header & footer | `/admin/appearance/header` | announcement bar, CTA button, contact block, social links, footer about/copyright/note |
| Media library | `/admin/media` | upload, replace, rename, alt text, copy URL, delete (files used as logo/favicon are protected) |
| Settings | `/admin/settings` | identity, contact, social, system (maintenance, chat assistant, email identity), plus a raw key/value editor |
| Products | `/admin/products` | full product catalogue: create/edit/delete, images, specifications, category and industry assignment, filters by category **and** industry |
| SEO | `/admin/seo` | titles, descriptions, robots, Open Graph, JSON-LD |

### Homepage sections

Each block on the homepage is a row in `page_sections`:

```
Super Admin → Edit homepage → Save → page_sections updated → public homepage changes
```

Sections can be **added**, **edited**, **reordered** (▲▼), **hidden/shown** and
**deleted**. The same builder also drives the About and Services pages
(`pageKey = about|services`): as soon as a section exists for those keys they
replace the built-in layout.

Section types understood by the public renderer:
`hero`, `stats`, `categories`, `products`, `industries`, `services`,
`testimonials`, `partners`, `faq`, `richtext`, `banner`, `newsletter`, `cta`.

### Pages

A published page is served at `/{slug}` (e.g. `/privacy-policy`) and at
`/page/{slug}`. Draft or private pages are visible to signed-in staff only, with
a preview banner. Slugs that belong to built-in sections (products, blog, admin…)
are rejected.

### Logo, favicon, branding

Nothing hard-codes a logo path any more:

```php
vp_logo_url('light')   // header
vp_logo_url('dark')    // dark backgrounds / admin sidebar
vp_logo_url('footer')  // footer
vp_favicon_url()       // browser tab
```

Each falls back to the bundled asset in `assets/img/` when the setting is empty,
so the site always renders. Uploads made in *Logo & branding* go into the media
library and update the setting in one step.

---

## 5. Activity / audit log

`Dashboard → System → Activity Log` (`audit.view`)

Recorded automatically: sign-in, sign-out, failed sign-in, create/update/delete
of products, pages, sections, menu items, media, settings and administrators,
permission changes, password resets, CSV exports and **denied access attempts**
(`ACCESS_DENIED`, including the permission that was missing).

Filter by administrator, action, resource or free text. Every administrator also
has a personal timeline at `/admin/admins/activity/{id}`.

---

## 6. Database objects added for the dashboard

| Table | Purpose |
|---|---|
| `permissions` | catalogue of grantable permissions (mirrors the config file) |
| `role_permissions` | default permissions per role (`resource` + JSON `actions`) |
| `user_permissions` | per-administrator grant (1) / denial (0), unique per (user, permission) |
| `pages` | CMS pages (+ FK to `users.authorId`) |
| `page_sections` | homepage / page content blocks, ordered, with JSON `settings` |
| `menu_items` | header, footer and legal navigation (+ FK to `pages.id`) |
| `media` | media library (extended with `title`, `uploadedBy`, `isProtected`) |
| `audit_logs` | administrator activity trail |
| `settings` | all site-wide values (identity, branding, contact, social, SEO, system) |

Schema: [`database/migrations/001_cms_and_permissions.sql`](../database/migrations/001_cms_and_permissions.sql)
Seed data: [`database/migrations/002_cms_seed.sql`](../database/migrations/002_cms_seed.sql)
Both are also included in `install/install.sql`, `install/seed.sql` and
`database/production.sql` for fresh installs.

**Upgrading an existing database:** import the two migration files in
phpMyAdmin (in order). They are idempotent — `CREATE TABLE IF NOT EXISTS` /
`INSERT IGNORE`; the three `ALTER TABLE media` statements report
"duplicate column" if they have already been applied, which is safe to ignore.

---

## 6b. Chat assistant

`Dashboard → Settings → System → Chat assistant` (Super Admin) controls the
floating helper on the public site: on/off, window title, assistant name,
welcome message, quick-reply buttons and the per-visitor hourly message limit.

The endpoint (`POST /chat/message`) is deliberately excluded from the global
CSRF filter and protected in the controller instead (same-origin check + rate
limit), because CodeIgniter rotates the CSRF cookie on every POST: when a proxy
or CDN dropped the rotated cookie, the **second** message of a conversation was
rejected with an HTML 403 that the widget could not parse ("Sorry, something
went wrong"). The endpoint now always answers JSON, exposes `GET /chat/token`
so the widget can re-synchronise, and the widget retries once with a fresh
token before showing any error.

## 7. Maintenance mode

`Dashboard → Settings → System` (Super Admin).

When enabled, visitors get a branded 503 maintenance page; signed-in staff keep
browsing the real site, and `/admin`, `/login`, `/logout` stay reachable. A
banner in the dashboard reminds you it is on.

---

## 8. Testing it

With the site running (see [`docs/INSTALLATION.md`](INSTALLATION.md) or
`scripts/start.sh`):

```bash
php tests/dashboard_acceptance.php http://127.0.0.1:8080
```

The suite signs in as Super Admin, creates a restricted Admin, and verifies
43 checks: permission enforcement (GET **and** POST, by direct URL), Super Admin
protection, self-promotion attempts, permission grant/removal taking effect
immediately, account enable/disable/delete, and that homepage, settings,
contact details, navigation, pages, logo, favicon, media and footer edits all
appear on the public website.
