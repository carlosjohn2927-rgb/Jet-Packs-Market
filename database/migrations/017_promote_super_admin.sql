-- =============================================================================
-- Halyk Petroleum — Migration 017: guarantee a working Super Admin account
-- =============================================================================
-- The admin sidebar is permission-filtered server-side (Admin_Controller::_nav()
-- + Acl). Only a SUPER_ADMIN sees every section, including the two super-only
-- areas that a normal ADMIN can never open:
--
--     * People → Administrators        (admins.manage)
--     * System → advanced/security     (system.manage)
--
-- Symptom: the sidebar shows "Administration" instead of "Super Admin" under
-- the logo, and "People → Administrators" is missing. That means the account
-- you signed in with is a normal ADMIN, not the Super Admin.
--
-- Fix: promote your account to SUPER_ADMIN. Replace the email below with YOUR
-- login email if it differs from the seeded default.
--
-- Safe to re-run: idempotent UPDATE (only sets the column when different).
-- =============================================================================

UPDATE `users`
   SET `role`      = 'SUPER_ADMIN',
       `isActive`  = 1,
       `updatedAt` = NOW()
 WHERE `email`     = 'admin@halykpetroleum-kz.com'
   AND (`role` <> 'SUPER_ADMIN' OR `isActive` <> 1);

-- -----------------------------------------------------------------------------
-- If you signed up with a different email, use this line instead (edit the
-- email), and remove/ignore the block above:
--
--   UPDATE `users` SET `role` = 'SUPER_ADMIN', `isActive` = 1, `updatedAt` = NOW()
--    WHERE `email` = 'YOUR_LOGIN_EMAIL';
-- -----------------------------------------------------------------------------

-- Verify the result (the row should show role = SUPER_ADMIN, isActive = 1):
--
--   SELECT id, email, role, isActive, mustChangePassword
--     FROM users
--    WHERE email = 'admin@halykpetroleum-kz.com';
--
-- After running this, SIGN OUT and sign back in. The label under the logo
-- becomes "Super Admin" (amber shield) and every sidebar section — including
-- People → Administrators — appears.
