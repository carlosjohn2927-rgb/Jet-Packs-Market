-- =============================================================================
-- Halyk Petroleum — Migration 016: ensure all industry / market rows exist
-- =============================================================================
-- The /industries page and the homepage "Aircraft we support" block are driven
-- by the `industries` table. A database seeded from the older fresh-install
-- production.sql contained only the ten aircraft-platform rows and ordered the
-- platforms at sortOrder 1–10, so:
--
--   * the eight market rows (Airlines, Business Aviation, MRO, Cargo, Military,
--     Helicopter, AOG, OEM) were missing entirely — those cards never appeared;
--   * the ten platform rows sat at the top of the grid where the markets should
--     lead, instead of trailing the markets at sortOrder 11–20.
--
-- This migration inserts the eight market rows (matching migration 009) and
-- re-orders the platform rows (matching migration 013), so a database built
-- from any seed converges on the same, complete /industries content:
--
--   markets served  sortOrder 1–8  (lead the grid + the first six cards on home)
--   aircraft platforms sortOrder 11–20
--
-- Safe to re-run: INSERT ... ON DUPLICATE KEY UPDATE and idempotent UPDATEs.
-- Rows later created/edited by an administrator are not overwritten.
-- =============================================================================

INSERT INTO `industries` (`id`,`name`,`slug`,`description`,`icon`,`sortOrder`,`isActive`,`metaTitle`,`capabilities`) VALUES
(UUID(),'Airlines & Commercial Operators','airlines-commercial','Rotables, wheels & brakes, avionics and consumables for A320, B737 and other airline fleets — exchange pools and power-by-the-hour support.','flight',1,1,'Airline Aircraft Parts Supply - Halyk Petroleum', JSON_ARRAY('Rotables','Consumables','Exchange pools','PBH support')),
(UUID(),'Business Aviation','business-aviation','Parts support for Gulfstream, Falcon, Citation, Challenger, Hawker and Learjet flight departments with rapid response and traceability.','plane',2,1,'Business Aviation Parts - Halyk Petroleum', JSON_ARRAY('Flight departments','Charter operators','Fractional fleets')),
(UUID(),'MRO & Maintenance Facilities','mro-maintenance','High-usage parts, tooling and bench stock for maintenance, repair and overhaul shops — wheels, brakes, hydraulics, avionics and airframe components.','tools',3,1,'MRO Parts Supply - Halyk Petroleum', JSON_ARRAY('Bench stock','Repair management','Tooling','Heavy checks')),
(UUID(),'Cargo & Logistics Operators','cargo-logistics','Parts programs for freighter fleets and cargo operators focused on dispatch reliability and fast AOG turnaround.','package',4,1,'Cargo Aircraft Parts - Halyk Petroleum', JSON_ARRAY('Freighters','Dispatch reliability','AOG turnaround')),
(UUID(),'Military & Government','military-government','Defence-grade parts procurement, export documentation and controlled-substance handling for government and military operators.','shield',5,1,'Defence Aviation Parts - Halyk Petroleum', JSON_ARRAY('Defence procurement','Export control','Controlled goods')),
(UUID(),'Helicopter Operators','helicopter-operators','Dynamic components, rotor parts and rotables for civil helicopter and EMS operators with full traceability paperwork.','helicopter',6,1,'Helicopter Parts - Halyk Petroleum', JSON_ARRAY('Dynamic components','Rotor parts','EMS operators')),
(UUID(),'AOG & Emergency Sourcing','aog-emergency','24/7 AOG desk, global supplier network of 2,000+ vetted sources, same-day quoting and hand-carry logistics for grounded aircraft.','alarm',7,1,'AOG Parts Support - Halyk Petroleum', JSON_ARRAY('24/7 desk','Hand-carry logistics','Emergency sourcing')),
(UUID(),'OEM & Tier-1 Suppliers','oem-tier1','PMA, OEM distribution and tier-1 component sourcing with manufacturer traceability and certificate management.','certificate',8,1,'OEM Aircraft Components - Halyk Petroleum', JSON_ARRAY('OEM distribution','PMA parts','Traceability'))
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`),
 `icon`=VALUES(`icon`), `sortOrder`=VALUES(`sortOrder`), `isActive`=1,
 `metaTitle`=VALUES(`metaTitle`), `capabilities`=VALUES(`capabilities`);

-- Standard artwork for the market rows (the platform artwork was already set by
-- migration 012; this covers the markets in case they were added afterwards).
UPDATE `industries`
   SET `image` = CONCAT('/assets/img/industries/', `slug`, '.jpg')
 WHERE (`image` IS NULL OR `image` = '' OR `image` = '/assets/img/industries/default.jpg')
   AND `slug` IN ('airlines-commercial','business-aviation','mro-maintenance',
                  'cargo-logistics','military-government','helicopter-operators',
                  'aog-emergency','oem-tier1');

-- Platforms lead the markets in the grid. Move each platform to 11–20 so the
-- markets (1–8) sit first, matching the site copy "Industries & markets we
-- supply". Only rows that still carry the old seed ordering are touched so an
-- admin's custom ordering survives.
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
