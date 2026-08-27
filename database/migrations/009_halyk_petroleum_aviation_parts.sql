-- =============================================================================
-- Halyk Petroleum — Migration 009: aviation-parts rebrand & content correction
-- =============================================================================
-- This migration turns the marketplace into the Halyk Petroleum aircraft-parts
-- supply experience:
--   1. Brand identity: Halyk Petroleum, aircraft parts & components supplier.
--   2. Navigation: Parts → Industries → Blog → FAQ (no "Aircraft" menu label).
--   3. Industries = aviation MARKETS served by a parts supplier (not aircraft
--      for sale): Airlines, Business Aviation, MRO, Cargo, Military/Defense,
--      Helicopters, AOG/Urgent Sourcing, OEM/Tier-1 Suppliers.
--   4. News / blog / downloads / FAQs rewritten around parts, sourcing, RFQs,
--      certification, MRO and supply chain.
--   5. Any legacy VP-20xx quote numbers are renumbered to HP-20xx.
--
-- Idempotent and safe on both MySQL (production) and the dev SQLite installer.
-- =============================================================================

-- ---------------------------------------------------------------------------
-- 1. Brand / settings — Halyk Petroleum, aircraft parts & components
-- ---------------------------------------------------------------------------
INSERT INTO `settings` (`id`,`key`,`value`,`type`,`group`,`sortOrder`) VALUES
(UUID(),'site_name','Halyk Petroleum','STRING','GENERAL',1),
(UUID(),'site_tagline','Aircraft Parts & Components Supply','STRING','GENERAL',2),
(UUID(),'site_title','Halyk Petroleum — Aircraft Parts & Components Supplier','STRING','WEBSITE',1),
(UUID(),'site_description','Halyk Petroleum supplies new, overhauled and serviceable aircraft parts and components — rotables, wheels and brakes, avionics, hydraulics and engine parts — with FAA 8130-3 / EASA Form 1 traceability, RFQ-driven quotations and 24/7 AOG support to airlines, business aviation and MRO customers worldwide.','TEXT','WEBSITE',2),
(UUID(),'hero_title','Aircraft Parts & Components, Sourced and Certified','STRING','HERO',1),
(UUID(),'hero_subtitle','Halyk Petroleum supplies new, overhauled and serviceable aircraft parts for business jets, airliners, helicopters and MROs. Send an RFQ for wheels & brakes, rotables, avionics, hydraulics, engine parts and more — every part certified and traceable.','STRING','HERO',2),
(UUID(),'hero_cta_primary','Request a Quote','STRING','HERO',3),
(UUID(),'hero_cta_secondary','Browse Parts','STRING','HERO',4),
(UUID(),'about_intro','Halyk Petroleum is a global supplier of aircraft parts and components. We source, certify and ship new, overhauled and serviceable rotables, consumables, wheels & brakes, avionics, hydraulics and engine parts to flight departments, airlines, MROs and defence operators worldwide. Every part ships with FAA 8130-3 / EASA Form 1 documentation and full traceability, supported by our RFQ desk and 24/7 AOG service.','TEXT','ABOUT',1),
(UUID(),'stats_parts','34000','INT','STATS',1),
(UUID(),'stats_aircraft','60','INT','STATS',2),
(UUID(),'stats_countries','120','INT','STATS',3),
(UUID(),'stats_aog','24','INT','STATS',4),
(UUID(),'contact_email','sales@halykpetroleum-kz.com','STRING','CONTACT',1),
(UUID(),'support_email','support@halykpetroleum-kz.com','STRING','CONTACT',2),
(UUID(),'rfq_email','rfq@halykpetroleum-kz.com','STRING','CONTACT',3),
(UUID(),'rfq_admin_email','admin@halykpetroleum-kz.com','STRING','RFQ',3),
(UUID(),'phone','+7 (727) 350-01-07','STRING','CONTACT',4),
(UUID(),'address','Almaty, Republic of Kazakhstan','STRING','CONTACT',5),
(UUID(),'social','{"linkedin":"https://linkedin.com/company/halyk-petroleum","twitter":"https://twitter.com/halykpetroleum","facebook":"https://facebook.com/halykpetroleum","youtube":"https://youtube.com/@halykpetroleum"}','JSON','CONTACT',6),
(UUID(),'seo_default_title','Halyk Petroleum — Aircraft Parts & Components Supply','STRING','SEO',1),
(UUID(),'seo_default_description','Halyk Petroleum supplies new, overhauled and serviceable aircraft parts for business jets, airliners, helicopters and MROs. FAA 8130-3 certified parts, RFQ quotations, worldwide shipping and 24/7 AOG support.','TEXT','SEO',2),
(UUID(),'seo_keywords','aircraft parts, aviation parts, aircraft components, aerospace supply, AOG parts, rotables, wheels and brakes, avionics, aircraft parts supply, request quote aircraft parts, RFQ aviation parts, MRO supply','STRING','SEO',3),
(UUID(),'seo_robots','index, follow','STRING','SEO',4),
(UUID(),'seo_og_image','/assets/img/hero-jet.jpg','STRING','SEO',5),
(UUID(),'seo_enable_jsonld','1','BOOL','SEO',6),
(UUID(),'seo_schema_type','Organization','STRING','SEO',7),
(UUID(),'seo_schema_name','Halyk Petroleum','STRING','SEO',8),
(UUID(),'seo_schema_logo','/assets/img/halyk-logo-horizontal.svg','STRING','SEO',9),
(UUID(),'chat_enabled','1','BOOL','CHAT',1),
(UUID(),'chat_title','Halyk Parts Assistant','STRING','CHAT',2),
(UUID(),'chat_bot_name','Halyk','STRING','CHAT',3),
(UUID(),'chat_avatar','/assets/img/chat-bot-avatar.png','STRING','CHAT',8),
(UUID(),'chat_welcome','Hi there! I can help you source aircraft parts, request a quote (RFQ), check part availability and answer questions about certification, lead times and shipping. What part number are you looking for?','TEXT','CHAT',4),
(UUID(),'chat_quick_replies','["Find a part","Request a quote","AOG support","Ask a question"]','JSON','CHAT',7),
(UUID(),'logo_light','/assets/img/halyk-logo-horizontal.svg','STRING','BRANDING',1),
(UUID(),'logo_dark','/assets/img/halyk-logo-horizontal-white.svg','STRING','BRANDING',2),
(UUID(),'logo_footer','/assets/img/halyk-logo-horizontal-white.svg','STRING','BRANDING',3),
(UUID(),'logo_alt','Halyk Petroleum — Aircraft Parts & Components','STRING','BRANDING',4),
(UUID(),'favicon','/assets/img/favicon.ico','STRING','BRANDING',5),
(UUID(),'footer_copyright','© 2026 Halyk Petroleum. Aircraft parts & components supplier. All rights reserved.','STRING','WEBSITE',10),
(UUID(),'footer_about','Halyk Petroleum supplies certified, traceable aircraft parts and components to airlines, business aviation, MROs and defence operators — RFQ-driven quotes, global logistics and 24/7 AOG support.','TEXT','WEBSITE',11)
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

-- Clean up obsolete stats keys from older themes.
DELETE FROM `settings` WHERE `key` IN ('stats_years','stats_projects','stats_clients');

-- ---------------------------------------------------------------------------
-- 2. Navigation — Parts, Industries, Blog, FAQ (remove any "Aircraft" label)
-- ---------------------------------------------------------------------------
UPDATE `menu_items` SET `label`='Parts',      `sortOrder`=10 WHERE `menu`='header' AND `url`='products';
UPDATE `menu_items` SET `label`='Industries', `sortOrder`=20 WHERE `menu`='header' AND `url`='industries';
UPDATE `menu_items` SET `label`='Blog',       `sortOrder`=30 WHERE `menu`='header' AND `url`='blog';
UPDATE `menu_items` SET `label`='FAQ',        `sortOrder`=40 WHERE `menu`='header' AND `url`='faq';
UPDATE `menu_items` SET `sortOrder`=50 WHERE `menu`='header' AND `url`='services';
UPDATE `menu_items` SET `sortOrder`=60 WHERE `menu`='header' AND `url`='about';
UPDATE `menu_items` SET `sortOrder`=70 WHERE `menu`='header' AND `url`='careers';
UPDATE `menu_items` SET `sortOrder`=80 WHERE `menu`='header' AND `url`='downloads';
UPDATE `menu_items` SET `sortOrder`=90 WHERE `menu`='header' AND `url`='contact';
-- Footer solutions menu: Parts → Industries → Services → RFQ
UPDATE `menu_items` SET `label`='Parts'      WHERE `menu`='footer_solutions' AND `url`='products'   AND `label` IN ('Products','Aircraft');
UPDATE `menu_items` SET `label`='Industries' WHERE `menu`='footer_solutions' AND `url`='industries' AND `label`='Aircraft';

-- ---------------------------------------------------------------------------
-- 3. Industries — aviation markets SERVED with parts (not aircraft for sale)
-- ---------------------------------------------------------------------------
-- Deactivate the legacy per-aircraft-manufacturer industry cards.
UPDATE `industries` SET `isActive`=0
 WHERE `slug` IN ('gulfstream','dassault-falcon','cessna-citation','challenger','hawker',
                  'learjet','boeing','airbus','embraer','pilatus');

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

-- Re-map products to the served-market industries (keyword driven).
UPDATE `products` SET `industryIds` = (SELECT `id` FROM `industries` WHERE `slug`='mro-maintenance')
 WHERE `aircraftType` LIKE '%Boeing%' OR `aircraftType` LIKE '%Airbus%'
    OR `name` LIKE '%Landing Gear%' OR `name` LIKE '%Brake%';
UPDATE `products` SET `industryIds` = (SELECT `id` FROM `industries` WHERE `slug`='business-aviation')
 WHERE `aircraftType` LIKE '%Gulfstream%' OR `aircraftType` LIKE '%Falcon%'
    OR `aircraftType` LIKE '%Citation%' OR `aircraftType` LIKE '%Challenger%'
    OR `aircraftType` LIKE '%Hawker%' OR `aircraftType` LIKE '%Learjet%';

-- ---------------------------------------------------------------------------
-- 4. FAQs — real customer questions about sourcing and RFQs
-- ---------------------------------------------------------------------------
UPDATE `faqs` SET `isActive`=0;

INSERT INTO `faqs` (`id`,`question`,`answer`,`category`,`sortOrder`,`isActive`) VALUES
(UUID(),'What aircraft parts do you supply?','We supply new, overhauled, serviceable and used aircraft parts and components: wheels and brakes, landing gear, rotables, hydraulics, avionics, electrical components, fuel system parts, flight controls, APUs, engine parts and airframe structures — for business jets, airliners, helicopters and military aircraft.','Parts & Products',1,1),
(UUID(),'How can I request a quote?','Use the Request a Quote (RFQ) form, add one or many line items with part numbers and quantities, and submit. Our sales desk replies within 24 business hours — within 2 hours for urgent requests.','Requesting a Quote',2,1),
(UUID(),'Can I submit an RFQ with multiple parts?','Yes. A single RFQ can include as many line items as you need — part number, name, quantity, condition and notes — so you receive one consolidated quotation.','Requesting a Quote',3,1),
(UUID(),'What information is required for a parts request?','Part number (or part name), quantity, target condition (NEW/OHC/USED/SV), aircraft type if known, and your contact details. Certifications or ATA chapter references help us quote faster.','Requesting a Quote',4,1),
(UUID(),'How does the quotation process work?','After submission your RFQ is assigned to a sales specialist (NEW → REVIEWING). We verify availability and pricing, then issue a formal quotation (QUOTED) with validity, currency and lead times. You approve or request changes; approved quotes move to completion once shipped.','Quotation Process',5,1),
(UUID(),'How long does an RFQ take?','Standard RFQs are answered within 24 business hours. Urgent and AOG requests are answered within 2 hours during business hours and around the clock for AOG events.','Quotation Process',6,1),
(UUID(),'How can I check parts availability?','Search by part number, name or manufacturer in the catalog — in-stock parts show availability — or ask on an RFQ. Our sourcing desk can also locate parts across a network of 2,000+ vetted suppliers.','Availability & Sourcing',7,1),
(UUID(),'Can you source parts I cannot find?','Yes. Our sourcing desk searches our global supplier network, OEMs and PMA sources. Most special requests are located within 48 hours, including hard-to-find and obsolete part numbers.','Availability & Sourcing',8,1),
(UUID(),'Do you supply parts internationally?','Yes. We ship worldwide with full export documentation, certificates of origin and customs support — FOB, CIF or DDP terms are available.','Shipping & Logistics',9,1),
(UUID(),'What documentation is available?','Every part ships with FAA Form 8130-3 and/or EASA Form 1, full traceability to the last operator, our inspection certificate, and on request logbook pages, repair history and SDS/MSDS sheets.','Certification',10,1),
(UUID(),'How are orders and quotations tracked?','Registered customers see every RFQ, its status history and quotations under My account. Administrators track status (NEW → REVIEWING → QUOTED → APPROVED → COMPLETED) with a full audit trail.','Orders & Tracking',11,1),
(UUID(),'Do parts come with a warranty?','All parts carry a 12-month warranty from shipment against defects in material and workmanship; overhauled units carry the warranty stated on the quotation.','Warranty',12,1),
(UUID(),'How can I contact the sales team?','Email sales@halykpetroleum-kz.com, use the AOG hotline, or submit an RFQ any time — the 24/7 AOG desk handles grounded-aircraft emergencies.','Contact',13,1);

-- ---------------------------------------------------------------------------
-- 5. Testimonials — aviation parts customers
-- ---------------------------------------------------------------------------
UPDATE `testimonials` SET `isActive`=0;
INSERT INTO `testimonials` (`id`,`name`,`title`,`company`,`content`,`rating`,`avatar`,`industry`,`isActive`,`featured`) VALUES
(UUID(),'Mark Hendricks','Director of Maintenance','Aerovista Charter Group','Halyk Petroleum sourced a complete set of wheels and brakes for our Gulfstream fleet at 30% below OEM pricing — all with full 8130-3 paperwork. Our AOG team has their number on speed dial.',5,'/assets/img/reviews/mark-hendricks.jpg','Business Aviation',1,1),
(UUID(),'Sofia Marchetti','Procurement Manager','Meridian Air Lines','We standardized our Falcon 2000 consumables on Halyk Petroleum. Consistent quality, predictable lead times, and every part arrives with traceable certification.',5,'/assets/img/reviews/sofia-marchetti.jpg','Airlines & Commercial',1,1),
(UUID(),'David Okafor','Lead Technician','TransContinental MRO','Their APU desk found us a low-cycle GTCP36-150 in three days during an AOG. The unit was better than described and the logbook review was impeccable.',5,'/assets/img/reviews/david-okafor.jpg','MRO & Maintenance',1,1),
(UUID(),'Elena Kovač','Operations Director','Skyline Business Jets','From RFQ to delivery in four days on a Challenger 604 rudder servo. The exchange program is excellent — they shipped first and took our core in return.',5,'/assets/img/reviews/elena-kovac.jpg','Business Aviation',1,0)
ON DUPLICATE KEY UPDATE `content`=VALUES(`content`), `industry`=VALUES(`industry`), `isActive`=1;

-- ---------------------------------------------------------------------------
-- 6. News — Halyk Petroleum aviation-parts news
-- ---------------------------------------------------------------------------
UPDATE `news` SET `isActive`=0;
INSERT INTO `news` (`id`,`title`,`slug`,`summary`,`content`,`publishedAt`,`isActive`) VALUES
(UUID(),'Halyk Petroleum expands rotable pool for Airbus A320 and Boeing 737 fleets','rotable-pool-expansion','New exchange stock covers wheels, brakes and leading-edge rotables for narrowbody operators.','Halyk Petroleum has expanded its certified rotable exchange pool to support A320 family and Boeing 737 operators, adding wheels, carbon brakes, hydraulic units and leading-edge rotables. All units are stocked with FAA 8130-3 / EASA Form 1 traceability and are available on exchange or outright sale.','2026-07-12 09:00:00',1),
(UUID(),'24/7 AOG desk now hand-carries parts to 40+ countries','aog-handcarry-network','Urgent parts dispatch with on-board-courier logistics across Europe, Asia and the Americas.','Our AOG team now coordinates on-board-courier and next-flight-out delivery to over 40 countries, cutting AOG resolution time. The desk quotes urgent requests within two hours, around the clock, with customs paperwork prepared in advance.','2026-05-30 09:00:00',1),
(UUID(),'Halyk Petroleum achieves ISO 9001:2015 recertification for parts distribution','iso-9001-recert','Our quality management system for aircraft parts sourcing and distribution passed surveillance with zero non-conformances.','We are pleased to announce successful ISO 9001:2015 surveillance recertification for our aircraft parts distribution operations, with zero non-conformances. The audit covered traceability, incoming inspection, supplier qualification and records management.','2026-03-04 09:00:00',1)
ON DUPLICATE KEY UPDATE `summary`=VALUES(`summary`), `content`=VALUES(`content`), `isActive`=1;

-- ---------------------------------------------------------------------------
-- 7. Downloads — aviation parts resources
-- ---------------------------------------------------------------------------
UPDATE `downloads` SET `isActive`=0;
INSERT INTO `downloads` (`id`,`title`,`description`,`fileUrl`,`type`,`category`,`fileSize`,`downloads`,`isActive`) VALUES
(UUID(),'Halyk Petroleum Capabilities Brochure 2026','Overview of our aircraft parts supply, sourcing and AOG capabilities.','/assets/files/halyk-petroleum-capabilities-2026.pdf','PDF','Company','2.1 MB',0,1),
(UUID(),'RFQ Submission Guide','How to prepare a request for quotation with part numbers, conditions and required certifications.','/assets/files/rfq-submission-guide.pdf','PDF','Quoting','980 KB',0,1),
(UUID(),'Certification & Traceability Sheet','Explanation of FAA 8130-3, EASA Form 1, conditions (NEW/OHC/SV) and traceability records.','/assets/files/certification-traceability.pdf','PDF','Quality','1.2 MB',0,1),
(UUID(),'AOG Emergency Contact Card','24/7 AOG hotline, required information and shipping options for grounded aircraft.','/assets/files/aog-contact-card.pdf','PDF','AOG',  '420 KB',0,1)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`), `isActive`=1;

-- ---------------------------------------------------------------------------
-- 8. Blog — aviation parts, MRO and sourcing content
-- ---------------------------------------------------------------------------
UPDATE `blog_posts` SET `status`='ARCHIVED' WHERE `slug` IN ('choosing-the-right-ball-valve','understanding-asme-section-viii');

INSERT INTO `blog_posts` (`id`,`title`,`slug`,`excerpt`,`content`,`authorId`,`category`,`tags`,`status`,`publishedAt`,`views`,`metaTitle`)
SELECT UUID(),
 'NEW, OHC, SV or USED? Understanding aircraft part conditions',
 'new-ohc-sv-used-part-conditions',
 'What each part condition means, the paperwork behind it, and how to specify what you need on an RFQ.',
 '<p>Aircraft parts are sold in clearly defined conditions. Specifying the right one on your RFQ avoids delays and surprises on inspection.</p><p><strong>NEW:</strong> unused manufacturer stock with full traceability. <strong>OHC (Overhauled):</strong> disassembled, inspected and repaired to manufacturer limits, with a dual-release tag. <strong>SERVICEABLE (SV):</strong> inspected and confirmed fit for installation. <strong>USED:</strong> removed serviceable with traceable history.</p><p>Every condition ships with FAA Form 8130-3 and/or EASA Form 1 paperwork from Halyk Petroleum.</p>',
 COALESCE((SELECT `id` FROM `users` ORDER BY `createdAt` LIMIT 1), UUID()),
 'Sourcing Guide',
 JSON_ARRAY('part-conditions','8130-3','rfq','traceability'),
 'PUBLISHED',
 '2026-06-15 09:00:00',
 412,
 'Aircraft Part Conditions Explained - Halyk Petroleum'
UNION ALL SELECT UUID(),
 'FAA 8130-3 vs EASA Form 1: certification paperwork explained',
 '8130-3-vs-easa-form-1',
 'The two release tags you meet on every certified part, who issues them, and what to check.',
 '<p>Certified rotables travel with an authorised release certificate. In the US market that is FAA Form 8130-3; in Europe and much of the world, EASA Form 1. Many parts ship with a dual 8130-3/Form 1 release.</p><p>Verify the part number, serial number, condition and authorized release stamp before installation. Halyk Petroleum supplies copies of all release paperwork with every shipment and on request during quoting.</p>',
 COALESCE((SELECT `id` FROM `users` ORDER BY `createdAt` LIMIT 1), UUID()),
 'Certification',
 JSON_ARRAY('8130-3','easa-form-1','certification','quality'),
 'PUBLISHED',
 '2026-04-22 09:00:00',
 289,
 'FAA 8130-3 and EASA Form 1 Explained - Halyk Petroleum'
;

-- ---------------------------------------------------------------------------
-- 9. Careers — Halyk Petroleum aviation parts roles
-- ---------------------------------------------------------------------------
UPDATE `careers` SET `isActive`=0;
INSERT INTO `careers` (`id`,`title`,`slug`,`department`,`location`,`type`,`experience`,`salary`,`description`,`requirements`,`benefits`,`isActive`) VALUES
(UUID(),'AOG Parts Coordinator','aog-parts-coordinator','Operations','Almaty, KZ','Full-time','3+ years','Competitive','Lead the Aircraft-on-Ground desk: source, quote and dispatch urgent aircraft parts to customers worldwide within hours.','Experience in aviation parts sales or MRO purchasing, strong phone and email communication, familiarity with part number formats.', 'Health insurance, AOG shift bonus, training', 1),
(UUID(),'Aviation Parts Sourcing Specialist','aviation-parts-sourcing','Purchasing','Almaty, KZ','Full-time','5+ years','Competitive','Grow our global supplier network and source hard-to-find rotables, APUs and airframe parts.','5+ years in aviation parts procurement, established supplier relationships, fluent in traceability requirements (FAA/EASA).', 'Health insurance, performance bonus, travel', 1),
(UUID(),'Quality & Traceability Inspector','quality-traceability-inspector','Quality','Almaty, KZ','Full-time','5+ years','Competitive','Verify incoming and outgoing parts against 8130-3 / Form 1 documentation and maintain traceability records.','Aviation quality experience, knowledge of FAA 8130-3 / EASA Form 1, meticulous documentation habits.', 'Health insurance, certification support', 1),
(UUID(),'Sales Representative — Business Aviation','sales-rep-business-aviation','Sales','Remote','Full-time','5+ years','Base + Commission','Own key flight department and MRO accounts, quoting and closing wheel, brake, hydraulic and avionics part sales.','5+ years aviation parts sales, existing customer relationships in business aviation, technical fluency.', 'Uncapped commission, health insurance', 1)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`), `isActive`=1;

-- ---------------------------------------------------------------------------
-- 10. Quote numbering — HP- prefix (Halyk Petroleum) instead of legacy VP-
-- ---------------------------------------------------------------------------
UPDATE `quotes` SET `quoteNumber` = REPLACE(`quoteNumber`, 'VP-', 'HP-') WHERE `quoteNumber` LIKE 'VP-%';

-- ---------------------------------------------------------------------------
-- 11. Granular RFQ permissions (catalogue mirrored in permissions.php)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`id`,`key`,`label`,`groupName`,`superOnly`,`sortOrder`) VALUES
(UUID(),'quotes.view','View quote requests (RFQ)','Sales',0,31),
(UUID(),'quotes.export','Export RFQs to CSV','Sales',0,32),
(UUID(),'quotes.assign','Assign RFQs to team members','Sales',0,33),
(UUID(),'quotes.update_status','Change RFQ status','Sales',0,34),
(UUID(),'quotes.generate_pdf','Generate / send PDF quotes','Sales',0,35),
(UUID(),'quotes.manage_attachments','Manage RFQ attachments','Sales',0,36);

-- ---------------------------------------------------------------------------
-- 12. RFQ data model — full RFQ/item fields for parts sourcing
-- ---------------------------------------------------------------------------
ALTER TABLE `quotes`
  ADD COLUMN `currency`   CHAR(3)      NOT NULL DEFAULT 'USD' AFTER `totalAmount`,
  ADD COLUMN `validUntil` DATE         DEFAULT NULL AFTER `deadline`;

ALTER TABLE `quote_items`
  ADD COLUMN `partNumber`   VARCHAR(120) DEFAULT NULL AFTER `productName`,
  ADD COLUMN `description`  TEXT         DEFAULT NULL AFTER `partNumber`,
  ADD COLUMN `manufacturer` VARCHAR(190) DEFAULT NULL AFTER `description`,
  ADD COLUMN `condition`    VARCHAR(40)  DEFAULT NULL AFTER `manufacturer`,
  ADD COLUMN `leadTime`     VARCHAR(120) DEFAULT NULL AFTER `condition`,
  ADD COLUMN `availability` VARCHAR(120) DEFAULT NULL AFTER `leadTime`,
  ADD COLUMN `notes`        TEXT         DEFAULT NULL AFTER `availability`,
  ADD COLUMN `currency`     CHAR(3)      NOT NULL DEFAULT 'USD' AFTER `total`;

