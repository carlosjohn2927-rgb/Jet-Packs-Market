-- =============================================================================
-- Halyk Petroleum — Migration 014: ADMIN is a full administrator
-- =============================================================================
-- An Admin could not reach or edit several dashboard areas:
--
--   * People → Customers and AOG Dispatches (admin/users, admin/aog) — the
--     legacy role rows seed the resource as `users`, but the permission key is
--     `customers.manage`, so those rows granted nothing. (Acl maps the legacy
--     `users` resource onto `customers` from this release on; the rows below
--     keep the data itself consistent.)
--   * Quotes — assign, generate/send PDF and delete attachment actions need
--     quotes.assign / quotes.generate_pdf / quotes.manage_attachments.
--   * Activity Log (audit.view) and Reports/Quotes viewing (quotes.view) were
--     only inherited from legacy rows, never declared for the role.
--
-- The code now grants the ADMIN role every permission except the two
-- super-only keys (admins.manage, system.manage). That fixes fresh installs,
-- but an existing account can still carry explicit per-user denials written by
-- Admin → Administrators → Permissions, and those win over role defaults. This
-- migration lifts them so the change actually reaches live accounts.
--
-- Super Admin areas stay exclusive: creating/editing administrators and their
-- permissions, plus advanced system, mail and security settings.
--
-- Safe to re-run: idempotent UPDATEs.
-- =============================================================================

-- 1. Lift stale per-user denials for every ADMIN account.
UPDATE `user_permissions`
   SET `granted` = 1, `updatedAt` = NOW()
 WHERE `granted` = 0
   AND `userId` IN (SELECT `id` FROM `users` WHERE `role` = 'ADMIN')
   AND `permission` IN (
        'dashboard.view', 'reports.view',
        'quotes.view', 'quotes.manage', 'quotes.export', 'quotes.assign',
        'quotes.update_status', 'quotes.generate_pdf', 'quotes.manage_attachments',
        'contacts.manage',
        'products.manage', 'inventory.manage', 'categories.manage',
        'industries.manage', 'downloads.manage',
        'blog.manage', 'news.manage', 'faqs.manage', 'careers.manage',
        'testimonials.manage', 'partners.manage',
        'homepage.manage', 'pages.manage', 'menus.manage', 'appearance.manage',
        'media.manage', 'seo.manage', 'settings.manage',
        'customers.manage', 'audit.view'
   );

-- 2. Widen the legacy role rows so a fresh database mirrors the role contract.
--    (Customers seeded under the old `users` resource name.)
UPDATE `role_permissions` SET `actions` = JSON_ARRAY('manage','read','create','update','delete')
 WHERE `role` = 'ADMIN' AND `resource` = 'users';

UPDATE `role_permissions` SET `actions` = JSON_ARRAY('manage','read','create','update','delete','export','status','assign','pdf','attachments')
 WHERE `role` = 'ADMIN' AND `resource` = 'quotes';

UPDATE `role_permissions` SET `actions` = JSON_ARRAY('manage','read')
 WHERE `role` = 'ADMIN' AND `resource` = 'audit';

UPDATE `role_permissions` SET `actions` = JSON_ARRAY('manage','read','view')
 WHERE `role` = 'ADMIN' AND `resource` = 'dashboard';

UPDATE `role_permissions` SET `actions` = JSON_ARRAY('manage','read','view')
 WHERE `role` = 'ADMIN' AND `resource` = 'reports';
