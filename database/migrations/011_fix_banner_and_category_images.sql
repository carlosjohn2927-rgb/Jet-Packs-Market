-- =============================================================================
-- Halyk Petroleum — Migration 011: Banner + category image repair
-- =============================================================================
-- 1. Homepage hero banner: remove the broken industrial-era image
--    (/assets/img/hero-industrial.jpg no longer ships with the theme) and
--    install the real aviation banner /assets/img/hero-jet.jpg. Empty/NULL
--    images are filled in the same way.
-- 2. Categories: give every known catalog category its canonical artwork path
--    /assets/img/products/<slug>.jpg when the stored image is empty. Admin
--    uploaded custom images are left untouched.
--
-- Deeper repair (stale custom paths, missing files) is applied once by
-- application/libraries/Catalog_integrity.php (artwork pass) on the next
-- request, identically on MySQL/MariaDB production and the SQLite dev
-- installer.
--
-- Safe to re-run: both statements are idempotent updates.
-- =============================================================================

UPDATE `page_sections`
   SET `image` = '/assets/img/hero-jet.jpg'
 WHERE `pageKey` = 'home'
   AND `type` = 'hero'
   AND (`image` IS NULL
        OR `image` = ''
        OR `image` = '/assets/img/hero-industrial.jpg'
        OR `image` = 'assets/img/hero-industrial.jpg');

UPDATE `categories`
   SET `image` = CONCAT('/assets/img/products/', `slug`, '.jpg')
 WHERE (`image` IS NULL OR `image` = '')
   AND `slug` IN (
        'wheels-brakes',
        'landing-gear',
        'avionics',
        'engines-apus',
        'flight-controls',
        'hydraulics',
        'pneumatics',
        'electrical-lighting',
        'interior-cabin',
        'actuators-valves',
        'fuel-systems',
        'airframe'
   );
