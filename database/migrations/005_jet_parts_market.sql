-- =============================================================================
-- Halyk Petroleum — Migration 005: marketplace part fields
-- =============================================================================
-- Adds the marketplace-specific columns to the `products` table and refreshes
-- brand settings for the Halyk Petroleum re-theme. Safe to re-run:
-- the ALTER statements fail harmlessly with "duplicate column" if already
-- applied, and the settings use ON DUPLICATE KEY UPDATE.
-- =============================================================================

-- ---------------------------------------------------------------------------
-- 1. New marketplace columns on products
-- ---------------------------------------------------------------------------
ALTER TABLE `products` ADD COLUMN `quantity`     INT          NOT NULL DEFAULT 1 AFTER `certifications`;
ALTER TABLE `products` ADD COLUMN `condition`    VARCHAR(40)  NOT NULL DEFAULT 'NEW' AFTER `quantity`;
ALTER TABLE `products` ADD COLUMN `manufacturer` VARCHAR(190) DEFAULT NULL AFTER `condition`;
ALTER TABLE `products` ADD COLUMN `aircraftType` VARCHAR(190) DEFAULT NULL AFTER `manufacturer`;

-- ---------------------------------------------------------------------------
-- 2. Brand / settings refresh (Halyk Petroleum)
-- ---------------------------------------------------------------------------
INSERT INTO `settings` (`id`,`key`,`value`,`type`,`group`,`sortOrder`) VALUES
(UUID(),'site_name','Halyk Petroleum','STRING','GENERAL',1),
(UUID(),'site_tagline','Aircraft Parts Marketplace','STRING','GENERAL',2),
(UUID(),'hero_title','Find the Right Jet Part. Fast.','STRING','HERO',1),
(UUID(),'hero_subtitle','Search thousands of new, overhauled and used aircraft parts for Gulfstream, Falcon, Citation, Challenger, Hawker, Learjet, Boeing and Airbus. Every part certified and traceable.','STRING','HERO',2),
(UUID(),'contact_email','sales@halykpetroleum-kz.com','STRING','CONTACT',1),
(UUID(),'support_email','support@halykpetroleum-kz.com','STRING','CONTACT',2),
(UUID(),'rfq_email','rfq@halykpetroleum-kz.com','STRING','CONTACT',3),
(UUID(),'phone','+1 (214) 350-0107','STRING','CONTACT',4),
(UUID(),'address','Hangar 4, Dallas Executive Airport, Dallas, TX 75209, USA','STRING','CONTACT',5),
(UUID(),'seo_default_title','Halyk Petroleum — Aircraft Parts Marketplace','STRING','SEO',1),
(UUID(),'seo_default_description','Halyk Petroleum sells new, overhauled and used aircraft parts for Gulfstream, Falcon, Citation, Challenger, Hawker, Learjet, Boeing and Airbus. FAA 8130-3 certified parts, 24/7 AOG support, worldwide shipping.','TEXT','SEO',2),
(UUID(),'seo_og_image','/assets/img/hero-jet.jpg','STRING','SEO',5),
(UUID(),'seo_schema_name','Halyk Petroleum','STRING','SEO',8),
(UUID(),'chat_title','Halyk Parts Assistant','STRING','CHAT',2),
(UUID(),'chat_bot_name','Halyk','STRING','CHAT',3)
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

-- Stats settings renamed for the marketplace (old keys deleted).
DELETE FROM `settings` WHERE `key` IN ('stats_years','stats_projects','stats_clients');
INSERT INTO `settings` (`id`,`key`,`value`,`type`,`group`,`sortOrder`) VALUES
(UUID(),'stats_parts','34000','INT','STATS',1),
(UUID(),'stats_aircraft','150','INT','STATS',2),
(UUID(),'stats_countries','120','INT','STATS',3),
(UUID(),'stats_aog','24','INT','STATS',4)
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

-- ---------------------------------------------------------------------------
-- 3. Header navigation labels (marketplace wording)
-- ---------------------------------------------------------------------------
UPDATE `menu_items` SET `label`='Parts'    WHERE `menu`='header' AND `label`='Products'  AND `url`='products';
UPDATE `menu_items` SET `label`='Aircraft' WHERE `menu`='header' AND `label`='Industries' AND `url`='industries';
UPDATE `menu_items` SET `label`='Parts'    WHERE `menu`='footer_solutions' AND `label`='Products'  AND `url`='products';
UPDATE `menu_items` SET `label`='Aircraft' WHERE `menu`='footer_solutions' AND `label`='Industries' AND `url`='industries';
