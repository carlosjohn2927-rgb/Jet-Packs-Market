# Admin Guide — Halyk Petroleum

Sign in at `/admin/login` with your staff account. A fresh installation has no seeded passwords: `install/install.php` creates the first Super Admin with the password you provide via `VP_ADMIN_PASSWORD` — or a randomly generated temporary password printed by the installer. Temporary passwords are flagged in the database, and the account's first login is redirected to its edit screen so the password must be changed before using the rest of the admin area. The sidebar then gives you access to all admin areas.

> **Super Admin / Admin dashboards, roles and permissions, and the whole CMS
> (homepage, pages, navigation, logo, header/footer, settings) are documented in
> [`DASHBOARD.md`](DASHBOARD.md).** This guide covers the day-to-day screens.

## Dashboard

- Header: **Previous**, **Forward**, and **Back** (dashboard) navigation controls,
  clickable logo and **Homepage / View Website** buttons (open the public site
  in a new tab), notifications bell, profile menu
- Counts you are allowed to see (RFQs, products, messages, customers, pages, media)
- Quick links into the website-management screens you have permission for
- Latest quote requests, recent administrator activity
- Super Admin only: administrator overview, email transport health, failed
  sign-ins (7 days), maintenance-mode state

Every panel and sidebar entry is filtered by your permissions — and the same
permission is checked again on the server for each request. The `ADMIN` role
always retains all public website/content editors; operational areas can still
be granted or removed per account by the Super Admin.

## Quotes

This is the most-used screen. List view supports:
- Free-text search (quote #, company, contact, email)
- Filter by status / assignee
- CSV export (top-right)

Click any quote to see:
- All line items
- Full status history (immutable)
- Activity timeline (every action with actor, IP, timestamp)
- Assignment controls
- Status update form (only valid forward transitions are shown)
- Internal note form (separate from customer-facing notes)
- PDF generation (rendered as printable HTML, downloadable via the browser's print dialog)
- Delete (Super Admin only)

## Products

Create products with **Products → New product**: name, SKU, slug, descriptions,
price, availability, category, industries, certifications, specifications and
one or many photos (drag several files in at once — the first becomes the
primary image). The list can be filtered by category *and* by industry, and the
**New product** button keeps the current filter so "add another product to this
industry" is one click. Duplicate SKUs/slugs are refused with a clear message.


- Search by name, SKU or description
- Filter by category and by industry
- Create / edit / delete (needs the `products.manage` permission)
- Form supports: multi-image upload (auto-resized to 1600px), dynamic specifications, related products, industries, SEO meta, featured flag, active/draft

## Categories

Flat list ordered by `order`. Used for product groupings on the public site.

## Industries

List + detail on public site. Capabilities (e.g. "ASME B31.3, API 610") are a comma-separated field stored as a JSON array.

## Blog / News

Standard CMS. Drafts vs published. Each post has excerpt, content (HTML allowed), category, tags, author, status, publish date, SEO meta.

## Careers

- List of open positions
- Form to create a new role (title, slug auto-generated, department, location, type, experience, salary, description, requirements, benefits, active flag, posted + closing dates)
- "Applications" link next to each role shows submitted applications with resume download links

## Contacts

Submitted via the public contact form. List view shows status (NEW = unread), subject, department, received time. Click to view, then "Reply via email" opens your mail client.

## FAQs

Question + answer + category. Order within category controls display order.

## Downloads

Title, description, file URL (or relative path under `/assets/files/`), type, category, file size. Public download counter is shown.

## Testimonials / Partners

Editable lists used on the home page.

## Media

Upload images and files to a chosen folder. Images are auto-resized to 1600px max width. The media list is paginated; click "View" to open.

## Customers (`/admin/users`)

Customer accounts only — staff accounts are managed by the Super Admin under
**People → Administrators**, which is why no role selector exists here.

- List, search, create, edit, deactivate, delete
- Requires the `customers.manage` permission

## Administrators (`/admin/admins`, Super Admin only)

- Create / edit / enable / disable / delete administrators
- Reset passwords (typed or auto-generated, optionally forcing a change at next login)
- Assign or remove permissions per account, section by section
- Review each administrator's activity trail

The Super Admin account itself cannot be edited, disabled, deleted or
re-permissioned by anybody else, and no administrator can ever be promoted to
Super Admin from the dashboard.

## Website content

| Screen | What it controls |
|---|---|
| **Homepage** (`/admin/homepage`) | every block of the homepage (and the About / Services pages): add, edit, reorder, hide, delete |
| **Pages** (`/admin/pages`) | CMS pages served at `/{slug}` with SEO fields, status, visibility, publish date |
| **Navigation** (`/admin/menus`) | header menu, footer columns and legal links |
| **Header & Footer** (`/admin/appearance/header`) | announcement bar, CTA button, contact block, social links, footer text |
| **Logo & Branding** (`/admin/appearance`) | website name/title/description, logo (light/dark/footer), favicon, alt text, logo size |
| **Colours** (`/admin/appearance/colors`) | background and write-up colours for the whole site; Admin / Super Admin sidebar (black background, white menu text by default) |

Changes are live on the public website as soon as you save.

## Settings

Tabbed screen at `/admin/settings`:

- **General** — website name, title, tagline, description, URL, default language
- **Contact** — email addresses, phone, address, opening hours (used in the footer, contact page and emails)
- **Social** — social profile URLs (empty ones are hidden on the site)
- **System** *(Super Admin)* — maintenance mode + message, chat/RFQ switches, email transport health
- **All values** — the raw key/value editor (every setting row, add or delete keys)

Settings are cached per request; there is no cache to clear.

## SEO

Dedicated screen under **System → SEO** for search-engine settings:

- Default title, title suffix, meta description, keywords, robots directive
- Canonical domain (used to generate canonical URLs, `robots.txt`, `sitemap.xml`)
- Social sharing image (`og:image`), Twitter handle, Facebook App ID
- Google / Bing site-verification codes
- JSON-LD structured data (Organization schema by default, or a custom document)

A `robots.txt` and `sitemap.xml` are generated automatically at those URLs and include all public pages, products, industries, blog posts, news and careers.

## AI chat

The floating chat widget is configured under **Settings → System → Chat
assistant** (on/off, title, assistant name, welcome message, quick replies,
hourly message limit per visitor). Advanced keys stay under **Settings → All values → CHAT group** (see `docs/AI_CHAT.md`). By default it answers locally from FAQs, products, industries and contact info with no external service. To use a real LLM, set `chat_ai_provider` to `openai`/`custom` and provide `chat_ai_api_key` (or set `VP_AI_API_KEY` in the environment).

## Audit log

Append-only log of every admin mutation. Filter by user, action, resource. Useful for compliance and post-incident review.

## Notifications

In-app notifications. Mark individual items as read. Currently populated by the system; can be extended to push notifications (WebSockets out of scope).

## Media

Upload images and files to a chosen folder (images are auto-resized to 1600px
max width), edit names and alt text, replace a file everywhere it is used, copy
its URL, or delete it. Files currently used as the logo, favicon or social
share image are protected from deletion until the setting points elsewhere.
The same library opens as a picker inside every content editor.

## Common admin actions

- **Log out**: profile menu, top-right of any admin page, or `GET /admin/logout`.
- **Open the public website**: click the logo, the **Homepage** button or
  **View Website** in the header — all open a new tab and keep your session.
- **Change your password**: `/admin/profile` → *Change password*.

## Keyboard tips

- `Ctrl+S` / `Cmd+S` does NOT save the form (browsers handle it as "save page"). Click the "Save" button.
- Browser autocomplete works for login email.

## Limits and quotas

- Per-IP: 100 requests / 15 minutes on public pages
- Per-IP+email: 5 RFQ submissions / hour
- Login: 5 failed attempts / 15 minutes
- File uploads: 8 MB default (configurable per controller)
