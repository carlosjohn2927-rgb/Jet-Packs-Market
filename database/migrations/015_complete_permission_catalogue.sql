-- =============================================================================
-- Halyk Petroleum — Migration 015: complete the permission catalogue
-- =============================================================================
-- The `permissions` table mirrors application/config/permissions.php. Migrations
-- 002 and 014 declared the main resource keys but omitted the granular quote and
-- inventory actions that the code catalogue exposes:
--
--   * quotes.view / quotes.export / quotes.assign / quotes.update_status
--     / quotes.generate_pdf / quotes.manage_attachments
--   * inventory.manage
--
-- Those keys are grantable to a normal ADMIN and are surfaced in
-- Admin → Administrators → Permissions, so a fresh install that only imported
-- the earlier seed would render the permission manager and the Acl catalogue
-- out of sync. This migration inserts the missing rows so the table matches the
-- code catalogue exactly.
--
-- It also declares the granular quote actions for the ADMIN role and the
-- inventory manage action for SALES, mirroring what the role already receives
-- through Acl::role_defaults() today. (Acl::sync_catalog() re-inserts the rows on
-- the next admin request as a safety net, but keeping the seed honest means the
-- data model and reports are correct in SQL without waiting for a request.)
--
-- Safe to re-run: INSERT IGNORE for the catalogue; the role rows use
-- ON DUPLICATE KEY UPDATE so re-running never duplicates.
-- =============================================================================

INSERT IGNORE INTO `permissions` (`id`,`key`,`label`,`groupName`,`superOnly`,`sortOrder`) VALUES
(UUID(),'quotes.view','View quote requests (RFQ)','Sales',0,3),
(UUID(),'quotes.export','Export RFQs to CSV','Sales',0,4),
(UUID(),'quotes.assign','Assign RFQs to team members','Sales',0,4),
(UUID(),'quotes.update_status','Change RFQ status','Sales',0,4),
(UUID(),'quotes.generate_pdf','Generate / send PDF quotes','Sales',0,4),
(UUID(),'quotes.manage_attachments','Manage RFQ attachments','Sales',0,4),
(UUID(),'inventory.manage','Manage warehouse inventory and lots','Catalog',0,6);

-- Quotes: give the ADMIN role the full granular action set so the legacy
-- `quotes.manage` grant and the granular keys stay consistent.
UPDATE `role_permissions`
   SET `actions` = JSON_ARRAY('manage','read','create','update','delete','export','status','assign','pdf','attachments')
 WHERE `role` = 'ADMIN' AND `resource` = 'quotes';

-- SALES also quotes: keep assign / status in sync with the code defaults.
UPDATE `role_permissions`
   SET `actions` = JSON_ARRAY('manage','read','create','update','status','export','assign')
 WHERE `role` = 'SALES' AND `resource` = 'quotes';

-- Inventory is managed by ENGINEER by default; mirror the code catalogue so the
-- permission has a home row (harmless if already present).
INSERT IGNORE INTO `role_permissions` (`id`,`role`,`resource`,`actions`)
VALUES (UUID(),'ADMIN','inventory',JSON_ARRAY('manage','read','create','update','delete'));
