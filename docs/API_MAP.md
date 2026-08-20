# API Map — NestJS endpoints → CodeIgniter 3 routes

This document maps every endpoint from the original NestJS backend to its CodeIgniter 3 equivalent. The new app is server-rendered, so most "API" calls become form posts that return HTML responses.

## Auth

| NestJS | CI3 | Notes |
|---|---|---|
| `POST /api/auth/login` | `POST /login` | Form post; sets session |
| `POST /api/auth/register` | `POST /register` | Form post; sets session |
| `POST /api/auth/logout` | `GET /logout` | Clears session |
| `GET /api/auth/me` | `$this->auth->user()` | Use the library, not a route |
| `POST /api/auth/refresh` | — | Re-login instead |

## Products

| NestJS | CI3 |
|---|---|
| `GET /api/products` | `GET /products` (with `?category=`, `?industry=`, `?q=`, `?page=`) |
| `GET /api/products/:slug` | `GET /products/:slug` |
| `POST /api/products` | `POST /admin/products/save` (admin) |
| `PATCH /api/products/:id` | `POST /admin/products/save` with `id` (admin) |
| `DELETE /api/products/:id` | `POST /admin/products/delete/:id` (admin) |

## Categories / Industries / Downloads

| NestJS | CI3 |
|---|---|
| `GET /api/categories` | public list at `/products`; admin at `/admin/categories` |
| `GET /api/industries` | `GET /industries`, `GET /industries/:slug`; admin at `/admin/industries` |
| `GET /api/downloads` | `GET /downloads`; admin at `/admin/downloads` |
| `POST /api/downloads/:id/download` | `GET /downloads/file/:id` (bumps counter) |

## Quotes / RFQ

| NestJS | CI3 |
|---|---|
| `POST /api/quotes` | `POST /rfq/submit` (public) |
| `GET  /api/quotes` | `GET  /admin/quotes` (admin) |
| `GET  /api/quotes/:id` | `GET  /admin/quotes/:id` |
| `PUT  /api/quotes/:id/status` | `POST /admin/quotes/:id/status` |
| `PUT  /api/quotes/:id` | `POST /admin/quotes/save` with `id` (admin) |
| `POST /api/quotes/:id/generate-pdf` | `GET  /admin/quotes/:id/pdf` |
| `GET  /api/quotes/export/csv` | `GET  /admin/quotes/export/csv` |
| `DELETE /api/quotes/:id` | `POST /admin/quotes/:id/delete` (Super Admin) |

## Blog / News / FAQs / Testimonials / Partners

All are now public list + detail pages, with admin CRUD at `/admin/blog`, `/admin/news`, `/admin/faqs`, `/admin/testimonials`, `/admin/partners`.

## Contacts

| NestJS | CI3 |
|---|---|
| `POST /api/contacts` | `POST /contact/submit` |
| `GET  /api/contacts` | `GET  /admin/contacts` |
| `GET  /api/contacts/:id` | `GET  /admin/contacts/:id` |

## Careers / Applications

| NestJS | CI3 |
|---|---|
| `GET  /api/careers` | `GET  /careers` |
| `GET  /api/careers/:slug` | `GET  /careers/:slug` |
| `POST /api/careers/:slug/apply` | `POST /careers/apply/:slug` |
| `GET  /api/careers/:id/applications` | `GET  /admin/careers/:id/applications` |

## CMS / Settings

| NestJS | CI3 |
|---|---|
| `GET /api/cms/hero` | `vp_setting('hero_title', ...)` |
| `GET /api/cms/about` | `vp_setting('about_intro', ...)` |
| `PATCH /api/cms/settings` | `POST /admin/settings/save` |

## Users / RBAC

| NestJS | CI3 |
|---|---|
| `GET  /api/users` | `GET  /admin/users` |
| `POST /api/users` | `POST /admin/users/save` |
| `PATCH /api/users/:id` | `POST /admin/users/save` with `id` |
| `DELETE /api/users/:id` | `POST /admin/users/delete/:id` (Super Admin) |

## Auth headers

The original NestJS API used `Authorization: Bearer <jwt>`. The new app uses PHP sessions + a remember-me cookie. There is no JWT. If you need to consume the app from a non-browser client (e.g. mobile), add a small token endpoint and reuse the `Auth` library.

## RBAC

The 6 roles are identical (SUPER_ADMIN, ADMIN, SALES, ENGINEER, EDITOR, CUSTOMER). Default permissions are seeded in `install/seed.sql` and editable via `role_permissions` rows (or via `/admin/users` for the UI).
