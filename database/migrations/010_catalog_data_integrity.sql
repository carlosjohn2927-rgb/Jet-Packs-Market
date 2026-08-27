-- =============================================================================
-- Halyk Petroleum — Migration 010: Catalog data integrity (schema)
-- =============================================================================
-- Adds nameNorm columns used for case- and whitespace-insensitive uniqueness
-- of category and product names. Existing duplicate ROWS, unique indexes and
-- per-product image seeds are applied by application/libraries/Catalog_integrity.php
-- (runs once after this migration is present) so the cleanup works identically
-- on MySQL/MariaDB production and the SQLite dev installer.
--
-- Safe to re-run: duplicate-column failures are ignored by both installers.
-- =============================================================================

ALTER TABLE `categories` ADD COLUMN `nameNorm` VARCHAR(190) DEFAULT NULL AFTER `name`;
ALTER TABLE `products`   ADD COLUMN `nameNorm` VARCHAR(255) DEFAULT NULL AFTER `name`;

-- Backfill normalized names (trim + lower). Collapsing internal whitespace is
-- done in Catalog_integrity so multi-space names also match.
UPDATE `categories`
   SET `name` = TRIM(`name`),
       `nameNorm` = LOWER(TRIM(`name`))
 WHERE `name` IS NOT NULL;

UPDATE `products`
   SET `name` = TRIM(`name`),
       `nameNorm` = LOWER(TRIM(`name`))
 WHERE `name` IS NOT NULL;
