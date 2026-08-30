-- =============================================================================
-- Halyk Petroleum — Migration 012: industry / aircraft-platform artwork
-- =============================================================================
-- Every industry row now carries its canonical artwork path in `industries.image`
-- instead of relying on the view layer to guess it.
--
-- Why: `vp_industry_image()` used to whitelist only the served-market slugs and
-- sent every aircraft platform (gulfstream, dassault-falcon, hawker, pilatus,
-- airbus, embraer, boeing, learjet, challenger, cessna-citation) to
-- /assets/img/industries/default.jpg — so all ten platform pages, and every card
-- on /industries, rendered the same generic photo. Each platform now ships its
-- own artwork (/assets/img/industries/<slug>.jpg) and the helper resolves it by
-- looking for that file, so adding a platform only needs its own picture.
--
-- Admin uploads are preserved: rows whose stored image is neither empty nor the
-- shared default placeholder are left untouched.
--
-- Safe to re-run: every statement is an idempotent UPDATE.
-- =============================================================================

-- Aircraft platforms (per-manufacturer support pages)
UPDATE `industries` SET `image` = '/assets/img/industries/gulfstream.jpg'
 WHERE `slug` = 'gulfstream'
   AND (`image` IS NULL OR `image` = '' OR `image` = '/assets/img/industries/default.jpg');

UPDATE `industries` SET `image` = '/assets/img/industries/dassault-falcon.jpg'
 WHERE `slug` = 'dassault-falcon'
   AND (`image` IS NULL OR `image` = '' OR `image` = '/assets/img/industries/default.jpg');

UPDATE `industries` SET `image` = '/assets/img/industries/cessna-citation.jpg'
 WHERE `slug` = 'cessna-citation'
   AND (`image` IS NULL OR `image` = '' OR `image` = '/assets/img/industries/default.jpg');

UPDATE `industries` SET `image` = '/assets/img/industries/challenger.jpg'
 WHERE `slug` = 'challenger'
   AND (`image` IS NULL OR `image` = '' OR `image` = '/assets/img/industries/default.jpg');

UPDATE `industries` SET `image` = '/assets/img/industries/hawker.jpg'
 WHERE `slug` = 'hawker'
   AND (`image` IS NULL OR `image` = '' OR `image` = '/assets/img/industries/default.jpg');

UPDATE `industries` SET `image` = '/assets/img/industries/learjet.jpg'
 WHERE `slug` = 'learjet'
   AND (`image` IS NULL OR `image` = '' OR `image` = '/assets/img/industries/default.jpg');

UPDATE `industries` SET `image` = '/assets/img/industries/boeing.jpg'
 WHERE `slug` = 'boeing'
   AND (`image` IS NULL OR `image` = '' OR `image` = '/assets/img/industries/default.jpg');

UPDATE `industries` SET `image` = '/assets/img/industries/airbus.jpg'
 WHERE `slug` = 'airbus'
   AND (`image` IS NULL OR `image` = '' OR `image` = '/assets/img/industries/default.jpg');

UPDATE `industries` SET `image` = '/assets/img/industries/embraer.jpg'
 WHERE `slug` = 'embraer'
   AND (`image` IS NULL OR `image` = '' OR `image` = '/assets/img/industries/default.jpg');

UPDATE `industries` SET `image` = '/assets/img/industries/pilatus.jpg'
 WHERE `slug` = 'pilatus'
   AND (`image` IS NULL OR `image` = '' OR `image` = '/assets/img/industries/default.jpg');

-- Markets served with parts (migration 009)
UPDATE `industries` SET `image` = '/assets/img/industries/airlines-commercial.jpg'
 WHERE `slug` = 'airlines-commercial' AND (`image` IS NULL OR `image` = '');

UPDATE `industries` SET `image` = '/assets/img/industries/business-aviation.jpg'
 WHERE `slug` = 'business-aviation' AND (`image` IS NULL OR `image` = '');

UPDATE `industries` SET `image` = '/assets/img/industries/mro-maintenance.jpg'
 WHERE `slug` = 'mro-maintenance' AND (`image` IS NULL OR `image` = '');

UPDATE `industries` SET `image` = '/assets/img/industries/cargo-logistics.jpg'
 WHERE `slug` = 'cargo-logistics' AND (`image` IS NULL OR `image` = '');

UPDATE `industries` SET `image` = '/assets/img/industries/military-government.jpg'
 WHERE `slug` = 'military-government' AND (`image` IS NULL OR `image` = '');

UPDATE `industries` SET `image` = '/assets/img/industries/helicopter-operators.jpg'
 WHERE `slug` = 'helicopter-operators' AND (`image` IS NULL OR `image` = '');

UPDATE `industries` SET `image` = '/assets/img/industries/aog-emergency.jpg'
 WHERE `slug` = 'aog-emergency' AND (`image` IS NULL OR `image` = '');

UPDATE `industries` SET `image` = '/assets/img/industries/oem-tier1.jpg'
 WHERE `slug` = 'oem-tier1' AND (`image` IS NULL OR `image` = '');
