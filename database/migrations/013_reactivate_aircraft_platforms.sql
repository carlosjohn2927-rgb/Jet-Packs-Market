-- =============================================================================
-- Halyk Petroleum — Migration 013: bring the aircraft platform pages back
-- =============================================================================
-- Migration 009 repositioned /industries from "aircraft manufacturers" to
-- "markets we serve with parts". It inserted the eight market rows and
-- deactivated the ten per-platform rows, which left
-- /industries/gulfstream, /industries/cessna-citation, … returning 404 —
-- even though each platform ships its own banner artwork (migration 012).
--
-- Both sets belong on the site: the markets describe *who* we supply, the
-- platforms describe *which aircraft* we support. This migration puts the ten
-- platform pages back online alongside the markets:
--
--   - markets served keep sortOrder 1–8, so they lead the /industries grid and
--     the homepage block (which renders the first six rows) and match the page
--     copy "Industries & markets we supply";
--   - the ten aircraft platforms follow at sortOrder 11–20 in the order the
--     catalogue has always used;
--   - every platform row is active again, so its page renders, is linked from
--     the grid and is listed in the sitemap.
--
-- Safe to re-run: idempotent UPDATEs. Rows created later by an admin are not
-- touched.
-- =============================================================================

UPDATE `industries` SET `isActive` = 1, `sortOrder` = 11 WHERE `slug` = 'gulfstream';
UPDATE `industries` SET `isActive` = 1, `sortOrder` = 12 WHERE `slug` = 'dassault-falcon';
UPDATE `industries` SET `isActive` = 1, `sortOrder` = 13 WHERE `slug` = 'cessna-citation';
UPDATE `industries` SET `isActive` = 1, `sortOrder` = 14 WHERE `slug` = 'challenger';
UPDATE `industries` SET `isActive` = 1, `sortOrder` = 15 WHERE `slug` = 'hawker';
UPDATE `industries` SET `isActive` = 1, `sortOrder` = 16 WHERE `slug` = 'learjet';
UPDATE `industries` SET `isActive` = 1, `sortOrder` = 17 WHERE `slug` = 'boeing';
UPDATE `industries` SET `isActive` = 1, `sortOrder` = 18 WHERE `slug` = 'airbus';
UPDATE `industries` SET `isActive` = 1, `sortOrder` = 19 WHERE `slug` = 'embraer';
UPDATE `industries` SET `isActive` = 1, `sortOrder` = 20 WHERE `slug` = 'pilatus';
