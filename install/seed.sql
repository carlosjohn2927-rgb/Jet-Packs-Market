-- =====================================================================
-- Vortex Precision - seed data (content only - NO user accounts)
-- Run AFTER install.sql.
--
-- SECURITY: this file intentionally contains NO user accounts and NO
-- passwords. The initial administrator account is created by
--   php install/install.php
-- which either uses VP_ADMIN_PASSWORD from the environment or generates
-- a random temporary password that must be changed on first login.
--
-- Generates UUIDs inline using MySQL 8 / MariaDB 10.2+ UUID() function.
-- =====================================================================

-- ----- Default permissions (all resources for SUPER_ADMIN, scoped for others) -----
INSERT INTO `role_permissions` (`id`,`role`,`resource`,`actions`) VALUES
(UUID(),'SUPER_ADMIN','*',JSON_ARRAY('*')),
(UUID(),'ADMIN','products',JSON_ARRAY('read','create','update','delete')),
(UUID(),'ADMIN','categories',JSON_ARRAY('read','create','update','delete')),
(UUID(),'ADMIN','quotes',JSON_ARRAY('read','create','update','delete','export','status')),
(UUID(),'ADMIN','contacts',JSON_ARRAY('read','update','delete')),
(UUID(),'ADMIN','blog',JSON_ARRAY('read','create','update','delete')),
(UUID(),'ADMIN','careers',JSON_ARRAY('read','create','update','delete')),
(UUID(),'ADMIN','faqs',JSON_ARRAY('read','create','update','delete')),
(UUID(),'ADMIN','downloads',JSON_ARRAY('read','create','update','delete')),
(UUID(),'ADMIN','industries',JSON_ARRAY('read','create','update','delete')),
(UUID(),'ADMIN','news',JSON_ARRAY('read','create','update','delete')),
(UUID(),'ADMIN','users',JSON_ARRAY('read','update')),
(UUID(),'ADMIN','settings',JSON_ARRAY('read','update')),
(UUID(),'ADMIN','media',JSON_ARRAY('read','create','delete')),
(UUID(),'ADMIN','audit',JSON_ARRAY('read')),
(UUID(),'SALES','products',JSON_ARRAY('read')),
(UUID(),'SALES','categories',JSON_ARRAY('read')),
(UUID(),'SALES','quotes',JSON_ARRAY('read','create','update','status','export')),
(UUID(),'SALES','contacts',JSON_ARRAY('read','update')),
(UUID(),'ENGINEER','products',JSON_ARRAY('read','update')),
(UUID(),'ENGINEER','quotes',JSON_ARRAY('read','update')),
(UUID(),'EDITOR','blog',JSON_ARRAY('read','create','update','delete')),
(UUID(),'EDITOR','news',JSON_ARRAY('read','create','update','delete')),
(UUID(),'EDITOR','faqs',JSON_ARRAY('read','create','update','delete')),
(UUID(),'EDITOR','downloads',JSON_ARRAY('read','create','update','delete')),
(UUID(),'EDITOR','industries',JSON_ARRAY('read','create','update','delete'))
ON DUPLICATE KEY UPDATE `actions`=VALUES(`actions`);

-- ----- Categories -----
INSERT INTO `categories` (`id`,`name`,`slug`,`description`,`icon`,`sortOrder`,`isActive`,`metaTitle`) VALUES
(UUID(),'Wheels & Brakes','wheels-brakes','Wheels, tires, brake assemblies and anti-skid systems for business and commercial jets.','wheel',1,1,'Aircraft Wheels & Brakes - JetPacks Market'),
(UUID(),'Landing Gear','landing-gear','Complete landing gear assemblies, actuators, struts and steering components.','gear',2,1,'Aircraft Landing Gear - JetPacks Market'),
(UUID(),'Avionics','avionics','Radios, radars, displays, flight instruments, recorders and navigation systems.','radar',3,1,'Avionics & Instruments - JetPacks Market'),
(UUID(),'Engines & APUs','engines-apus','Turbofan engines, auxiliary power units and engine components.','engine',4,1,'Engines & APUs - JetPacks Market'),
(UUID(),'Flight Controls','flight-controls','Servos, actuators, power control units and trim systems.','servo',5,1,'Flight Controls - JetPacks Market'),
(UUID(),'Hydraulics','hydraulics','Hydraulic pumps, valves, reservoirs and accumulators.','hydraulic',6,1,'Hydraulic Systems - JetPacks Market'),
(UUID(),'Pneumatics & Bleed Air','pneumatics','Bleed air valves, pressure controllers and pneumatic components.','air',7,1,'Pneumatics & Bleed Air - JetPacks Market'),
(UUID(),'Electrical & Lighting','electrical-lighting','Generators, batteries, lights, relays and power distribution.','bolt',8,1,'Electrical & Lighting - JetPacks Market'),
(UUID(),'Interior & Cabin','interior-cabin','Escape slides, oxygen systems, galleys and cabin equipment.','seat',9,1,'Interior & Cabin - JetPacks Market'),
(UUID(),'Actuators & Valves','actuators-valves','Linear and rotary actuators, control valves and solenoids.','valve',10,1,'Actuators & Valves - JetPacks Market'),
(UUID(),'Fuel Systems','fuel-systems','Fuel pumps, indicators, valves and fuel system components.','fuel',11,1,'Fuel Systems - JetPacks Market'),
(UUID(),'Airframe & Structures','airframe','Structural components, cowlings, fairings and airframe parts.','plane',12,1,'Airframe & Structures - JetPacks Market');


INSERT INTO `industries` (`id`,`name`,`slug`,`description`,`icon`,`sortOrder`,`isActive`,`metaTitle`,`capabilities`) VALUES
(UUID(),'Gulfstream','gulfstream','New, overhauled and used parts for Gulfstream GII through G700 business jets.','plane',1,1,'Gulfstream Parts - JetPacks Market', JSON_ARRAY('GII','GIII','GIV','GV','G450','G550','G650','G700')),
(UUID(),'Dassault Falcon','dassault-falcon','Rotables, consumables and airframe parts for Falcon 10, 20, 50, 900 and 2000.','plane',2,1,'Dassault Falcon Parts - JetPacks Market', JSON_ARRAY('Falcon 10','Falcon 20','Falcon 50','Falcon 900','Falcon 2000','Falcon 7X')),
(UUID(),'Cessna Citation','cessna-citation','Parts for Citation I, II, III, V, X, Excel, Sovereign and Latitude.','plane',3,1,'Cessna Citation Parts - JetPacks Market', JSON_ARRAY('Citation I','Citation II','Citation III','Citation V','Citation X','Sovereign')),
(UUID(),'Bombardier Challenger','challenger','Support for Challenger 300, 600, 601, 604, 605 and 650 families.','plane',4,1,'Challenger Parts - JetPacks Market', JSON_ARRAY('Challenger 300','Challenger 600','Challenger 601','Challenger 604','Challenger 650')),
(UUID(),'Hawker','hawker','Hawker 700, 800, 800XP, 850XP and 900XP parts and components.','plane',5,1,'Hawker Parts - JetPacks Market', JSON_ARRAY('Hawker 700','Hawker 800','Hawker 800XP','Hawker 850XP','Hawker 900XP')),
(UUID(),'Learjet','learjet','Parts for Learjet 31, 35, 40, 45, 55, 60 and 75.','plane',6,1,'Learjet Parts - JetPacks Market', JSON_ARRAY('Learjet 35','Learjet 40','Learjet 45','Learjet 55','Learjet 60','Learjet 75')),
(UUID(),'Boeing','boeing','Commercial aircraft parts for the 737, 747, 757, 767, 777 and 787 fleets.','plane',7,1,'Boeing Parts - JetPacks Market', JSON_ARRAY('Boeing 737','Boeing 747','Boeing 757','Boeing 767','Boeing 777','Boeing 787')),
(UUID(),'Airbus','airbus','Commercial aircraft parts for the A318, A319, A320, A321, A330 and A350 families.','plane',8,1,'Airbus Parts - JetPacks Market', JSON_ARRAY('A318','A319','A320','A321','A330','A350')),
(UUID(),'Embraer','embraer','Parts for Embraer ERJ, E-Jet and Praetor business jet families.','plane',9,1,'Embraer Parts - JetPacks Market', JSON_ARRAY('ERJ 135','ERJ 145','E175','E190','Phenom 300','Praetor 600')),
(UUID(),'Pilatus','pilatus','Support for the Pilatus PC-12 turboprop and PC-24 jet.','plane',10,1,'Pilatus Parts - JetPacks Market', JSON_ARRAY('PC-12','PC-24'));


INSERT INTO `products`
  (`id`,`name`,`slug`,`sku`,`description`,`shortDescription`,`categoryId`,
   `industryIds`,`material`,`pressure`,`temperature`,`voltage`,`dimensions`,`weight`,
   `certifications`,`availability`,`quantity`,`condition`,`manufacturer`,`aircraftType`,
   `price`,`featured`,`isActive`,`views`,`metaTitle`)
SELECT
  UUID(),'Main Landing Gear Wheel Assembly','main-landing-gear-wheel-2612201-2','2612201-2',
  'Goodrich main landing gear wheel assembly for Gulfstream GIV/GV. Fully inspected, 4 wheels available. Includes bearings and lug nuts. Traceable to source, ships with export documentation.',
  'MLG wheel assembly, inspected and ready to ship.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','EASA Form 1'),
  'IN_STOCK',4,'USED','Goodrich','Gulfstream GIV / GV',
  14850.00,1,1,214,'Main Landing Gear Wheel Assembly - 2612201-2'
UNION ALL SELECT
  UUID(),'Main Wheel & Brake Assembly (Steel)','main-wheel-brake-2-1553-5','2-1553-5',
  'BFGoodrich main wheel and steel brake assembly for Cessna Citation II. New condition, zero time since overhaul. Includes wheel halves, brake discs and torque plate.',
  'Wheel and steel brake assembly, new, for Citation II.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','BFGoodrich','Cessna Citation II',
  8950.00,1,1,187,'Main Wheel & Brake Assembly - 2-1553-5'
UNION ALL SELECT
  UUID(),'Carbon Brake Assembly','carbon-brake-2612401-1','2612401-1',
  'Goodrich carbon brake assembly for Gulfstream G450. Low-time heat stack, serviceable condition, complete with torque plate and hardware kit. Carbon brakes save ~700 lb per aircraft.',
  'Carbon brake assembly, low-time heat stack.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','EASA Form 1'),
  'IN_STOCK',1,'NEW','Goodrich','Gulfstream G450',
  32400.00,1,1,156,'Carbon Brake Assembly - 2612401-1'
UNION ALL SELECT
  UUID(),'Nose Wheel Assembly','nose-wheel-208-150-0','208-150-0',
  'Goodyear nose wheel assembly with tire, for Dassault Falcon 50. New tire mounted on inspected wheel. Six units available, all zero-time.',
  'Nose wheel with new tire, six available.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',6,'NEW','Goodyear','Dassault Falcon 50',
  2150.00,0,1,98,'Nose Wheel Assembly - 208-150-0'
UNION ALL SELECT
  UUID(),'Main Gear Tire (Flight Leader)','main-gear-tire-132-101-0','132-101-0',
  'Goodyear Flight Leader main gear tire for Citation and Hawker aircraft. 18-ply rating, new, manufactured within the last 18 months. Eight tires in stock.',
  'Main gear tire, 18-ply, new, eight in stock.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation'),(SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',8,'NEW','Goodyear','Citation / Hawker',
  1890.00,0,1,143,'Main Gear Tire - 132-101-0'
UNION ALL SELECT
  UUID(),'Anti-Skid Control Unit','anti-skid-control-20-57-03','20-57-03',
  'Hydro-Aire Mark III anti-skid control unit for Bombardier Challenger 600/601. Overhauled with test certificate, 5,000-cycle warranty. Drop-in replacement, no wiring changes.',
  'Overhauled anti-skid control unit with warranty.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='challenger')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','OHC test cert'),
  'IN_STOCK',1,'OHC','Hydro-Aire','Challenger 600/601',
  6750.00,0,1,87,'Anti-Skid Control Unit - 20-57-03'
UNION ALL SELECT
  UUID(),'Nose Landing Gear Assembly','nose-landing-gear-9001252-3','9001252-3',
  'Messier-Dowty nose landing gear assembly for Dassault Falcon 2000. Serviceable, complete with steering collar and drag brace. Ultrasonic inspection current. Immediate AOG dispatch available.',
  'Complete nose landing gear, serviceable.',
  (SELECT `id` FROM `categories` WHERE `slug`='landing-gear' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','NDT current'),
  'IN_STOCK',1,'USED','Messier-Dowty','Dassault Falcon 2000',
  24500.00,1,1,176,'Nose Landing Gear Assembly - 9001252-3'
UNION ALL SELECT
  UUID(),'Main Landing Gear Actuator','main-landing-gear-actuator-9001340-5','9001340-5',
  'Messier-Dowty main landing gear actuator for Falcon 900. Overhauled, bench-tested with report. Corrosion protection per latest SB. Two units available.',
  'MLG actuator, overhauled with bench test report.',
  (SELECT `id` FROM `categories` WHERE `slug`='landing-gear' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('EASA Form 1'),
  'IN_STOCK',2,'OHC','Messier-Dowty','Dassault Falcon 900',
  12400.00,0,1,121,'Main Landing Gear Actuator - 9001340-5'
UNION ALL SELECT
  UUID(),'Nose Wheel Steering Actuator','nose-wheel-steering-46-162-01','46-162-01',
  'Parker Aerospace nose wheel steering actuator for Learjet 45. New manufacture, current revision, with placards and hardware. Two units in stock.',
  'New nose wheel steering actuator, current rev.',
  (SELECT `id` FROM `categories` WHERE `slug`='landing-gear' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='learjet')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','Parker Aerospace','Learjet 45',
  7850.00,0,1,92,'Nose Wheel Steering Actuator - 46-162-01'
UNION ALL SELECT
  UUID(),'Landing Gear Control Unit','landing-gear-control-82-345-2','82-345-2',
  'Collins landing gear control unit for Hawker 800 series. Overhauled with functional test. Includes gear-up warning inputs. Exchanged units accepted.',
  'Landing gear control unit, overhauled.',
  (SELECT `id` FROM `categories` WHERE `slug`='landing-gear' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Collins Aerospace','Hawker 800 / 800XP',
  9600.00,0,1,74,'Landing Gear Control Unit - 82-345-2'
UNION ALL SELECT
  UUID(),'VHF-4000 Comm Radio','vhf-4000-comm-radio','622-8920-005',
  'Collins Aerospace VHF-4000 communications transceiver for Hawker 800XP and Challenger. New, with rack and installation kit. 8.33 kHz spacing capable.',
  'New VHF-4000 comm radio with rack.',
  (SELECT `id` FROM `categories` WHERE `slug`='avionics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker'),(SELECT `id` FROM `industries` WHERE `slug`='challenger')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','TSO C37d'),
  'IN_STOCK',3,'NEW','Collins Aerospace','Hawker 800XP / Challenger',
  5400.00,1,1,233,'VHF-4000 Comm Radio - 622-8920-005'
UNION ALL SELECT
  UUID(),'Primus 660 Weather Radar','primus-660-weather-radar','830-0141-100',
  'Honeywell Primus 660 color weather radar with stabilized antenna for Citation X. New in box, latest software revision, includes radome adapter kit.',
  'New Primus 660 weather radar system.',
  (SELECT `id` FROM `categories` WHERE `slug`='avionics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'NEW','Honeywell','Cessna Citation X',
  28500.00,0,1,164,'Primus 660 Weather Radar - 830-0141-100'
UNION ALL SELECT
  UUID(),'LASEREF IV Inertial Reference','laseref-iv-inertial-reference','46594-0304-0301',
  'Honeywell LASEREF IV inertial reference system for Gulfstream GIV/GV. Overhauled with 2,500-hour warranty, current IRU software. Includes mounting tray.',
  'Overhauled LASEREF IV IRS with warranty.',
  (SELECT `id` FROM `categories` WHERE `slug`='avionics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('EASA Form 1','FAA 8130-3'),
  'IN_STOCK',1,'OHC','Honeywell','Gulfstream GIV / GV',
  45000.00,0,1,118,'LASEREF IV Inertial Reference - 46594-0304-0301'
UNION ALL SELECT
  UUID(),'KMD-850 Multi-Function Display','kmd-850-multifunction-display','010-00866-02',
  'BendixKing KMD-850 multi-function display with GPS/WAAS and terrain. New, with data card and install kit. Two units available.',
  'New KMD-850 MFD with terrain and WAAS.',
  (SELECT `id` FROM `categories` WHERE `slug`='avionics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','BendixKing','Citation / Various',
  14900.00,0,1,139,'KMD-850 Multi-Function Display - 010-00866-02'
UNION ALL SELECT
  UUID(),'Flight Data Recorder','flight-data-recorder-980-4700-043','980-4700-043',
  'Honeywell solid-state flight data recorder for Boeing 737. New, 25-hour recording, with mounting rack and underwater locator beacon. Export-ready.',
  'Solid-state FDR for 737, new with rack.',
  (SELECT `id` FROM `categories` WHERE `slug`='avionics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='boeing')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','TSO C124'),
  'MADE_TO_ORDER',1,'NEW','Honeywell','Boeing 737',
  12500.00,0,1,81,'Flight Data Recorder - 980-4700-043'
UNION ALL SELECT
  UUID(),'GTCP36-150 Auxiliary Power Unit','gtcp36-150-apu','3606171-1',
  'Honeywell GTCP36-150 APU for Gulfstream GIV. Low-cycle used unit with complete logbooks, recently hot-section inspected. Includes ECU and harness.',
  'Low-cycle used APU with logbooks.',
  (SELECT `id` FROM `categories` WHERE `slug`='engines-apus' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','Logbook review'),
  'IN_STOCK',1,'USED','Honeywell','Gulfstream GIV',
  88000.00,1,1,205,'GTCP36-150 APU - 3606171-1'
UNION ALL SELECT
  UUID(),'CFE738-1-1B Turbofan Engine','cfe738-1-1b-turbofan','CFE738-1-1B',
  'GE/Honeywell CFE738-1-1B turbofan engine for Falcon 2000. Used, serviceable with current borescope and mid-life HSI. Complete with QEC, inlet and reverser kit.',
  'Serviceable CFE738 turbofan with QEC.',
  (SELECT `id` FROM `categories` WHERE `slug`='engines-apus' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','Engine logbooks'),
  'MADE_TO_ORDER',1,'USED','GE / Honeywell','Dassault Falcon 2000',
  450000.00,1,1,167,'CFE738-1-1B Turbofan Engine'
UNION ALL SELECT
  UUID(),'TFE731-5BR-1C Engine','tfe731-5br-1c-engine','3131775-1',
  'Honeywell TFE731-5BR-1C turbofan for Falcon 900B. Overhauled with 1,000-hour warranty, includes ECU, sensors and installation kit. Ready to hang and fly.',
  'Overhauled TFE731-5BR with warranty.',
  (SELECT `id` FROM `categories` WHERE `slug`='engines-apus' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('EASA Form 1'),
  'MADE_TO_ORDER',1,'OHC','Honeywell','Dassault Falcon 900B',
  385000.00,0,1,94,'TFE731-5BR-1C Engine'
UNION ALL SELECT
  UUID(),'Engine Driven Hydraulic Pump','edp-hydraulic-pump','793-2583-001',
  'Eaton engine driven hydraulic pump for Gulfstream GIV. New, 3,000 PSI, SAE-A mount. Two units in stock with pressure test certificates.',
  'New EDP hydraulic pump, 3,000 PSI.',
  (SELECT `id` FROM `categories` WHERE `slug`='hydraulics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','Eaton Aerospace','Gulfstream GIV / GV',
  9750.00,1,1,188,'Engine Driven Hydraulic Pump - 793-2583-001'
UNION ALL SELECT
  UUID(),'Hydraulic System Valve','hydraulic-system-valve-25d-660','25D-660',
  'Parker Hannifin hydraulic system valve for business jet utility systems. New, 4-way, 3,000 PSI, solenoid operated, with connector. Four in stock.',
  'New 4-way hydraulic valve, 3,000 PSI.',
  (SELECT `id` FROM `categories` WHERE `slug`='hydraulics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker'),(SELECT `id` FROM `industries` WHERE `slug`='learjet')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',4,'NEW','Parker Hannifin','Hawker / Learjet',
  4300.00,0,1,77,'Hydraulic System Valve - 25D-660'
UNION ALL SELECT
  UUID(),'Rudder Servo Actuator','rudder-servo-523-0771-517','523-0771-517',
  'Collins rudder servo actuator for Challenger 604. Overhauled with bench test report, current SB compliance. Includes linkage hardware.',
  'Overhauled rudder servo, bench tested.',
  (SELECT `id` FROM `categories` WHERE `slug`='flight-controls' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='challenger')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Collins Aerospace','Challenger 604',
  18750.00,1,1,132,'Rudder Servo Actuator - 523-0771-517'
UNION ALL SELECT
  UUID(),'Elevator Trim Actuator','elevator-trim-actuator','312-0025-010',
  'Parker elevator trim actuator for Citation III. New, current revision, with gearbox and position sensor. Two units in stock.',
  'New elevator trim actuator with sensor.',
  (SELECT `id` FROM `categories` WHERE `slug`='flight-controls' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','Parker Aerospace','Cessna Citation III',
  5900.00,0,1,85,'Elevator Trim Actuator - 312-0025-010'
UNION ALL SELECT
  UUID(),'Rudder Power Control Unit','rudder-pcu-692-0241-001','692-0241-001',
  'Parker rudder power control unit for Boeing 737. Overhauled, complete with test data and 5,000-flight-hour warranty. Exchange core accepted.',
  'Overhauled rudder PCU for 737.',
  (SELECT `id` FROM `categories` WHERE `slug`='flight-controls' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='boeing')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Parker Hannifin','Boeing 737',
  22000.00,0,1,66,'Rudder Power Control Unit - 692-0241-001'
UNION ALL SELECT
  UUID(),'Bleed Air Regulating Valve','bleed-air-regulating-valve','3070211-1',
  'Honeywell bleed air regulating valve for Challenger 601/604. New, with anti-ice bleed control, current SB. Two units in stock.',
  'New bleed air regulating valve.',
  (SELECT `id` FROM `categories` WHERE `slug`='pneumatics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='challenger')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','Honeywell','Challenger 601 / 604',
  8900.00,0,1,102,'Bleed Air Regulating Valve - 3070211-1'
UNION ALL SELECT
  UUID(),'Cabin Pressure Controller','cabin-pressure-controller','8927-14',
  'Honeywell digital cabin pressure controller for Gulfstream GII/GIII. Overhauled, bench tested, includes outflow valve interface card.',
  'Overhauled digital cabin pressure controller.',
  (SELECT `id` FROM `categories` WHERE `slug`='pneumatics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Honeywell','Gulfstream GII / GIII',
  6900.00,0,1,58,'Cabin Pressure Controller - 8927-14'
UNION ALL SELECT
  UUID(),'Fuel Boost Pump','fuel-boost-pump','501-072-020',
  'Eaton AC fuel boost pump for Hawker 800. New, 115 VAC, with check valve and mount gasket. Three units in stock.',
  'New AC fuel boost pump with check valve.',
  (SELECT `id` FROM `categories` WHERE `slug`='fuel-systems' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',3,'NEW','Eaton Aerospace','Hawker 800',
  6450.00,1,1,119,'Fuel Boost Pump - 501-072-020'
UNION ALL SELECT
  UUID(),'Fuel Quantity Indicator','fuel-quantity-indicator','900-1120-02',
  'Collins fuel quantity indicator for Boeing 737. Used, serviceable, bench checked. Two units available with test data.',
  'Serviceable fuel quantity indicator.',
  (SELECT `id` FROM `categories` WHERE `slug`='fuel-systems' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='boeing')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'USED','Collins Aerospace','Boeing 737',
  3750.00,0,1,49,'Fuel Quantity Indicator - 900-1120-02'
UNION ALL SELECT
  UUID(),'Starter / Generator','starter-generator','763-0411-1',
  'Hamilton Sundstrand starter/generator for Hawker 700. Overhauled with 800-hour warranty, includes regulator and cooling fan. Exchange unit available.',
  'Overhauled starter/generator with warranty.',
  (SELECT `id` FROM `categories` WHERE `slug`='electrical-lighting' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Hamilton Sundstrand','Hawker 700',
  28000.00,1,1,145,'Starter / Generator - 763-0411-1'
UNION ALL SELECT
  UUID(),'Ni-Cd Main Battery','nicd-main-battery','4454-35',
  'Marathon Ni-Cd main battery for business and regional jets. New, 24 V, 35 Ah, with thermal fuse. Five batteries in stock, shipped charged.',
  'New 24 V Ni-Cd main battery, five in stock.',
  (SELECT `id` FROM `categories` WHERE `slug`='electrical-lighting' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker'),(SELECT `id` FROM `industries` WHERE `slug`='learjet')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',5,'NEW','Marathon Power','Hawker / Learjet',
  6300.00,0,1,133,'Ni-Cd Main Battery - 4454-35'
UNION ALL SELECT
  UUID(),'Landing Light Assembly','landing-light-assembly','407-0120-04',
  'Grimes landing light assembly for Falcon 50. New, sealed beam, with mounting bracket and gasket. Four in stock.',
  'New landing light with bracket.',
  (SELECT `id` FROM `categories` WHERE `slug`='electrical-lighting' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',4,'NEW','Grimes / Collins','Dassault Falcon 50',
  1850.00,0,1,71,'Landing Light Assembly - 407-0120-04'
UNION ALL SELECT
  UUID(),'Emergency Oxygen System','emergency-oxygen-system','850930-01',
  'Kidde crew emergency oxygen system for Learjet 60. New, complete with regulator, masks and cylinder. Two systems in stock, pressure tested.',
  'New crew emergency oxygen system.',
  (SELECT `id` FROM `categories` WHERE `slug`='interior-cabin' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='learjet')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','TSO C64'),
  'IN_STOCK',2,'NEW','Kidde Aerospace','Learjet 60',
  4100.00,0,1,63,'Emergency Oxygen System - 850930-01'
UNION ALL SELECT
  UUID(),'Emergency Escape Slide','emergency-escape-slide','630-1580-01',
  'Air Cruisers emergency escape slide for Gulfstream GIV. Used, serviceable, current packing date, includes deployment bag and hardware.',
  'Serviceable escape slide, current pack.',
  (SELECT `id` FROM `categories` WHERE `slug`='interior-cabin' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'USED','Air Cruisers','Gulfstream GIV',
  9800.00,0,1,55,'Emergency Escape Slide - 630-1580-01'
UNION ALL SELECT
  UUID(),'Cabin Window Assembly','cabin-window-assembly','190-1260-11',
  'Cabin window assembly (inner pane) for Hawker 800XP. New, with gasket kit and anti-fog coating. Two units in stock.',
  'New cabin window inner pane.',
  (SELECT `id` FROM `categories` WHERE `slug`='interior-cabin' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','Hawker Beechcraft','Hawker 800XP',
  7250.00,0,1,44,'Cabin Window Assembly - 190-1260-11'
UNION ALL SELECT
  UUID(),'Flap Actuator','flap-actuator','12-425-01',
  'Parker flap actuator for Learjet 35/36. Overhauled with test report, current gearbox revision. Includes drive arm and mounting bolts.',
  'Overhauled flap actuator with test report.',
  (SELECT `id` FROM `categories` WHERE `slug`='actuators-valves' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='learjet')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Parker Aerospace','Learjet 35 / 36',
  11500.00,1,1,109,'Flap Actuator - 12-425-01'
UNION ALL SELECT
  UUID(),'Solenoid Shutoff Valve','solenoid-shutoff-valve','173-104-07',
  'Solenoid operated fuel shutoff valve for Gulfstream and Hawker fuel systems. New, 28 VDC, with connector and mounting plate. Six in stock.',
  'New solenoid fuel shutoff valve, six in stock.',
  (SELECT `id` FROM `categories` WHERE `slug`='actuators-valves' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream'),(SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',6,'NEW','Eaton Aerospace','Gulfstream / Hawker',
  2950.00,0,1,83,'Solenoid Shutoff Valve - 173-104-07'
UNION ALL SELECT
  UUID(),'Engine Cowling (RH)','engine-cowling-rh','310-0452-05',
  'Right-hand engine cowling for Citation III. Serviceable composite, minor cosmetic damage only, includes cowl lip and hinges. AOG dispatch available.',
  'Serviceable RH engine cowling, AOG-ready.',
  (SELECT `id` FROM `categories` WHERE `slug`='airframe' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'USED','Cessna','Cessna Citation III',
  8900.00,0,1,52,'Engine Cowling (RH) - 310-0452-05'
UNION ALL SELECT
  UUID(),'APU Fire Extinguisher Bottle','apu-fire-extinguisher','830121-01',
  'Kidde APU fire extinguisher bottle for Challenger 601/604. Overhauled with new discharge cartridge, hydro test current. Two units available.',
  'Overhauled APU fire bottle, hydro current.',
  (SELECT `id` FROM `categories` WHERE `slug`='airframe' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='challenger')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'OHC','Kidde Aerospace','Challenger 601 / 604',
  5600.00,0,1,38,'APU Fire Extinguisher Bottle - 830121-01';


INSERT INTO `faqs` (`id`,`question`,`answer`,`category`,`sortOrder`,`isActive`) VALUES
(UUID(),'What is your typical lead time?','Stock parts ship the same or next business day. AOG (Aircraft on Ground) requests are prioritized and dispatched within hours, 24/7.','Lead Times',1,1),
(UUID(),'What do NEW, OHC, USED and SERVICEABLE mean?','NEW is unused manufacturer-new stock. OHC means Overhauled — disassembled, repaired to manufacturer limits with a bench test report. USED parts are removed serviceable with traceable history. SERVICEABLE parts are inspected and ready to install.','Part Conditions',2,1),
(UUID(),'Do parts come with certification?','Yes. Every part ships with FAA Form 8130-3 and/or EASA Form 1, full traceability to the last operator, and our own inspection certificate. Copies of logbook pages are provided on request.','Certification',3,1),
(UUID(),'Do you buy or trade surplus parts?','We buy outright and trade surplus rotables, engines, APUs and airframe parts. Email your inventory list to sales@jetpacksmarket.com — we typically respond within 24 hours with an offer.','Selling Parts',4,1),
(UUID(),'What is the warranty on parts?','All parts carry a 12-month warranty from shipment against defects in material and workmanship. Overhauled units carry an extended warranty as stated on the quotation.','Warranty',5,1),
(UUID(),'Do you ship internationally?','We ship worldwide with full export documentation, certificates of origin and ATA Carnet support. Choose FOB, CIF or DDP — we manage customs paperwork for you.','Logistics',6,1),
(UUID(),'How fast do I get a quote?','Standard RFQs are answered within 24 business hours. Urgent and AOG requests are answered within 2 hours during business hours, 24/7 for AOG.','Quoting',7,1),
(UUID(),'Can you source parts I cannot find?','Yes. If the part is not in our catalog, our sourcing desk searches our global supplier network of over 2,000 vetted aviation suppliers and OEMs. Most special requests are sourced within 48 hours.','Sourcing',8,1);


INSERT INTO `testimonials` (`id`,`name`,`title`,`company`,`content`,`rating`,`avatar`,`industry`,`isActive`,`featured`) VALUES
(UUID(),'Mark Hendricks','Director of Maintenance','Aerovista Charter Group','JetPacks Market sourced a complete set of wheels and brakes for our Gulfstream fleet at 30% below OEM pricing — all with full 8130-3 paperwork. Our AOG team has their number on speed dial.',5,'/assets/img/reviews/mark-hendricks.jpg','Gulfstream',1,1),
(UUID(),'Sofia Marchetti','Procurement Manager','Meridian Air Lines','We have standardized our Falcon 2000 consumables on JetPacks Market. Consistent quality, predictable lead times, and every part arrives with traceable certification.',5,'/assets/img/reviews/sofia-marchetti.jpg','Dassault Falcon',1,1),
(UUID(),'David Okafor','Chief Pilot','TransContinental Air','Their APU desk found us a low-cycle GTCP36-150 in three days during an AOG. The unit was better than described and the logbook review was impeccable.',5,'/assets/img/reviews/david-okafor.jpg','Gulfstream',1,1),
(UUID(),'Elena Kovač','Operations Director','Skyline Business Jets','From RFQ to delivery in four days on a Challenger 604 rudder servo. The exchange program is excellent — they shipped first and took our core in return.',5,'/assets/img/reviews/elena-kovac.jpg','Challenger',1,0);


INSERT INTO `partners` (`id`,`name`,`logo`,`website`,`category`,`sortOrder`,`isActive`) VALUES
(UUID(),'Honeywell Aerospace','/assets/img/partners/honeywell.svg','https://aerospace.honeywell.com','OEM',1,1),
(UUID(),'Collins Aerospace','/assets/img/partners/collins.svg','https://www.collinsaerospace.com','OEM',2,1),
(UUID(),'Parker Aerospace','/assets/img/partners/parker.svg','https://www.parker.com','OEM',3,1),
(UUID(),'Safran Landing Systems','/assets/img/partners/safran.svg','https://www.safran-group.com','OEM',4,1),
(UUID(),'Eaton Aerospace','/assets/img/partners/eaton.svg','https://www.eaton.com','OEM',5,1),
(UUID(),'Kidde Aerospace','/assets/img/partners/kidde.svg','https://www.collinsaerospace.com','OEM',6,1),
(UUID(),'GE Aviation','/assets/img/partners/ge.svg','https://www.geaerospace.com','OEM',7,1),
(UUID(),'Thales','/assets/img/partners/thales.svg','https://www.thalesgroup.com','OEM',8,1),
(UUID(),'BFGoodrich','/assets/img/partners/bfgoodrich.svg','https://www.collinsaerospace.com','OEM',9,1),
(UUID(),'Meggitt','/assets/img/partners/meggitt.svg','https://www.meggitt.com','OEM',10,1);


INSERT INTO `settings` (`id`,`key`,`value`,`type`,`group`,`sortOrder`) VALUES
(UUID(),'site_name','JetPacks Market','STRING','GENERAL',1),
(UUID(),'site_tagline','Aircraft Parts Marketplace','STRING','GENERAL',2),
(UUID(),'hero_title','Find the Right Jet Part. Fast.','STRING','HERO',1),
(UUID(),'hero_subtitle','Search thousands of new, overhauled and used aircraft parts for Gulfstream, Falcon, Citation, Challenger, Hawker, Learjet, Boeing and Airbus. Every part certified and traceable.','STRING','HERO',2),
(UUID(),'hero_cta_primary','Request a Quote','STRING','HERO',3),
(UUID(),'hero_cta_secondary','Browse Parts','STRING','HERO',4),
(UUID(),'about_intro','JetPacks Market is a global marketplace for new, overhauled and used aircraft parts. From our facility at Dallas Executive Airport, we supply rotables, consumables, engines, APUs and airframe parts to flight departments, airlines, MROs and brokers in over 120 countries — every part shipped with full FAA/EASA certification and traceability.','TEXT','ABOUT',1),
(UUID(),'stats_parts','34000','INT','STATS',1),
(UUID(),'stats_aircraft','150','INT','STATS',2),
(UUID(),'stats_countries','120','INT','STATS',3),
(UUID(),'stats_aog','24','INT','STATS',4),
(UUID(),'contact_email','sales@jetpacksmarket.com','STRING','CONTACT',1),
(UUID(),'support_email','support@jetpacksmarket.com','STRING','CONTACT',2),
(UUID(),'rfq_email','rfq@jetpacksmarket.com','STRING','CONTACT',3),
(UUID(),'phone','+1 (214) 350-0107','STRING','CONTACT',4),
(UUID(),'address','Hangar 4, Dallas Executive Airport, Dallas, TX 75209, USA','STRING','CONTACT',5),
(UUID(),'social','{"linkedin":"https://linkedin.com/company/jetpacksmarket","twitter":"https://twitter.com/jetpacksmarket","facebook":"https://facebook.com/jetpacksmarket","youtube":"https://youtube.com/@jetpacksmarket"}','JSON','CONTACT',6),
(UUID(),'rfq_enabled','1','BOOL','RFQ',1),
(UUID(),'rfq_rate_limit_per_hour','5','INT','RFQ',2),
(UUID(),'rfq_admin_email','admin@jetpacksmarket.com','STRING','RFQ',3),

-- ----- SEO -----
(UUID(),'seo_default_title','JetPacks Market — Aircraft Parts Marketplace','STRING','SEO',1),
(UUID(),'seo_default_description','JetPacks Market sells new, overhauled and used aircraft parts for Gulfstream, Falcon, Citation, Challenger, Hawker, Learjet, Boeing and Airbus. FAA 8130-3 certified parts, 24/7 AOG support, worldwide shipping.','TEXT','SEO',2),
(UUID(),'seo_keywords','aircraft parts, jet parts, aviation parts, airplane parts, aircraft marketplace, AOG parts, Gulfstream parts, Falcon parts, Citation parts, rotables, wheels and brakes, aircraft engines','STRING','SEO',3),
(UUID(),'seo_robots','index, follow','STRING','SEO',4),
(UUID(),'seo_og_image','/assets/img/hero-jet.jpg','STRING','SEO',5),
(UUID(),'seo_enable_jsonld','1','BOOL','SEO',6),
(UUID(),'seo_schema_type','Organization','STRING','SEO',7),
(UUID(),'seo_schema_name','JetPacks Market','STRING','SEO',8),
(UUID(),'seo_schema_logo','/assets/img/logo-header.png','STRING','SEO',9),

-- ----- AI Chat -----
(UUID(),'chat_enabled','1','BOOL','CHAT',1),
(UUID(),'chat_title','JetPacks Assistant','STRING','CHAT',2),
(UUID(),'chat_bot_name','JetPacks','STRING','CHAT',3),
(UUID(),'chat_avatar','/assets/img/chat-bot-avatar.png','STRING','CHAT',8),
(UUID(),'chat_welcome','Hi there! 👋 I can help you find parts, check prices, request a quote or answer questions about certification and shipping. What part number are you looking for?','TEXT','CHAT',4),
(UUID(),'chat_ai_provider','local','STRING','CHAT',5),
(UUID(),'chat_rate_limit_per_hour','60','INT','CHAT',6),
(UUID(),'chat_quick_replies','["Find a part","Request a quote","Ask a question","AOG support"]','JSON','CHAT',7)
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

-- ----- Careers -----
INSERT INTO `careers` (`id`,`title`,`slug`,`department`,`location`,`type`,`experience`,`salary`,`description`,`requirements`,`benefits`,`isActive`) VALUES
(UUID(),'AOG Parts Coordinator','aog-parts-coordinator','Operations','Dallas, TX','Full-time','3+ years','Competitive','Lead our Aircraft-on-Ground desk: source, quote and dispatch urgent parts to customers worldwide within hours.','Experience in aviation parts sales or MRO purchasing, strong phone and email communication, familiarity with part number formats.', 'Health, dental, vision, 401(k) match, AOG shift bonus', 1),
(UUID(),'Aviation Parts Sourcing Specialist','aviation-parts-sourcing','Purchasing','Dallas, TX','Full-time','5+ years','Competitive','Grow our global supplier network and source hard-to-find rotables, engines, APUs and airframe parts.','5+ years in aviation parts procurement, established supplier relationships preferred, fluent in traceability requirements (FAA/EASA).', 'Health, dental, vision, 401(k) match', 1),
(UUID(),'Quality & Traceability Inspector','quality-traceability-inspector','Quality','Dallas, TX','Full-time','5+ years','Competitive','Verify incoming and outgoing parts against 8130-3 / Form 1 documentation, maintain traceability records and audit supplier paperwork.','Aviation quality experience, knowledge of FAA 8130-3 / EASA Form 1, meticulous documentation habits.', 'Health, dental, vision, 401(k) match', 1),
(UUID(),'Sales Representative - Business Aviation','sales-rep-business-aviation','Sales','Remote (US)','Full-time','5+ years','Base + Commission','Own key flight department and MRO accounts across the US, quoting and closing wheel, brake, hydraulic and avionics part sales.','5+ years aviation parts sales, existing customer relationships in business aviation, technical fluency.', 'Uncapped commission, health, 401(k) match, company vehicle', 1);


-- ----- News (3 sample) -----
INSERT INTO `news` (`id`,`title`,`slug`,`summary`,`content`,`publishedAt`,`isActive`) VALUES
(UUID(),'Vortex Precision completes delivery of 18 skidded heat exchanger packages to Gulf Coast chemical plant','skid-delivery-gulf-coast','A milestone order demonstrating our ability to deliver turnkey process skids to tight schedules.','Vortex Precision has successfully delivered 18 custom-engineered heat exchanger skids to a major Gulf Coast chemical complex. The packages, which include plate heat exchangers, instrumentation and structural steel, were delivered on a 14-week accelerated schedule and are now in commissioning.','2026-07-12 09:00:00',1),
(UUID(),'New EHEDG-certified sanitary pump line launched','sanitary-pump-launch','Our new PD pump line brings hygienic fluid handling to dairy, brewing and pharmaceutical customers.','Vortex Precision has launched a new line of rotary lobe positive-displacement pumps for sanitary service. The line is EHEDG-certified, available in 316L stainless with EHEDG-compliant elastomers.','2026-05-30 09:00:00',1),
(UUID(),'Vortex achieves ISO 9001:2015 recertification','iso-9001-recert','Quality system recertification reflects our continued commitment to customer satisfaction.','We are pleased to announce the successful completion of our ISO 9001:2015 surveillance audit, with zero non-conformances raised by the lead auditor.','2026-03-04 09:00:00',1);

-- ----- Downloads -----
INSERT INTO `downloads` (`id`,`title`,`description`,`fileUrl`,`type`,`category`,`fileSize`,`downloads`,`isActive`) VALUES
(UUID(),'Company Brochure 2026','Full-line overview of Vortex Precision capabilities and reference projects.','/assets/files/vortex-brochure-2026.pdf','PDF','General','3.2 MB',0,1),
(UUID(),'Valve Selection Guide','Engineering guide for selecting the right Vortex valve for your service.','/assets/files/valve-selection-guide.pdf','PDF','Valves','1.4 MB',0,1),
(UUID(),'Pump Selection Guide','Engineering guide for Vortex centrifugal and positive-displacement pumps.','/assets/files/pump-selection-guide.pdf','PDF','Pumps','1.8 MB',0,1),
(UUID(),'Heat Exchanger Sizing Worksheet','Excel worksheet to size plate or shell-and-tube exchangers.','/assets/files/hx-sizing.xlsx','XLSX','Heat Exchangers','85 KB',0,1);

-- ----- Blog posts (2 sample) -----
-- Only inserted when at least one author account exists. install/install.php
-- creates the admin BEFORE running this file; in the manual phpMyAdmin flow
-- (install.sql + seed.sql with no users yet) these are simply skipped.
INSERT INTO `blog_posts` (`id`,`title`,`slug`,`excerpt`,`content`,`authorId`,`category`,`tags`,`status`,`publishedAt`,`views`,`metaTitle`)
SELECT UUID(),
 'Choosing the right ball valve for your process',
 'choosing-the-right-ball-valve',
 'A practical guide to selecting full-port vs reduced-port, fire-safe vs standard, and floating vs trunnion.',
 '<p>Ball valves are the workhorse of industrial fluid handling. Choosing the right one is about understanding your service, not just line size. In this guide we walk through the three most important decisions...</p><p><strong>Full-port vs reduced-port:</strong> Full-port valves have an unobstructed bore equal to the pipe ID. They minimise pressure drop and are required for pigging...</p>',
 (SELECT `id` FROM `users` ORDER BY `createdAt` LIMIT 1),
 'Engineering',
 JSON_ARRAY('valves','selection','engineering'),
 'PUBLISHED',
 '2026-06-15 09:00:00',
 412,
 'Choosing the right ball valve - Vortex Precision'
WHERE EXISTS (SELECT 1 FROM `users`)
UNION ALL SELECT UUID(),
 'Understanding ASME Section VIII pressure vessel design',
 'understanding-asme-section-viii',
 'A non-lawyer introduction to the U-stamp code, mandatory appendices, and how to read a Manufacturer''s Data Report.',
 '<p>ASME Section VIII governs the design and manufacture of unfired pressure vessels in the United States and much of the world. Whether you are specifying a storage tank or a custom reactor, the code is large but approachable...</p>',
 (SELECT `id` FROM `users` ORDER BY `createdAt` LIMIT 1),
 'Engineering',
 JSON_ARRAY('pressure-vessels','asme','engineering'),
 'PUBLISHED',
 '2026-04-22 09:00:00',
 289,
 'Understanding ASME Section VIII - Vortex Precision'
WHERE EXISTS (SELECT 1 FROM `users`);

-- =====================================================================
-- CMS + permission seed data
-- Mirrors database/migrations/002_cms_seed.sql
-- =====================================================================
-- =====================================================================
-- Halyk Petroleum — CMS + permissions seed data (migration 002)
-- =====================================================================
-- Idempotent: uses INSERT IGNORE so re-running never overwrites content
-- that an administrator has since edited in the dashboard.
-- =====================================================================


-- ---------------------------------------------------------------------
-- Permission catalogue (mirrors application/config/permissions.php)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`id`,`key`,`label`,`groupName`,`superOnly`,`sortOrder`) VALUES
(UUID(),'dashboard.view','View dashboard','Overview',0,1),
(UUID(),'reports.view','View reports and analytics','Overview',0,2),
(UUID(),'quotes.manage','Manage quote requests (RFQ)','Sales',0,4),
(UUID(),'contacts.manage','Manage contact messages','Sales',0,5),
(UUID(),'products.manage','Manage products','Catalog',0,6),
(UUID(),'categories.manage','Manage categories','Catalog',0,7),
(UUID(),'industries.manage','Manage industries','Catalog',0,8),
(UUID(),'downloads.manage','Manage downloads','Catalog',0,9),
(UUID(),'blog.manage','Manage blog posts','Content',0,10),
(UUID(),'news.manage','Manage news','Content',0,11),
(UUID(),'faqs.manage','Manage FAQs','Content',0,12),
(UUID(),'careers.manage','Manage careers and applications','Content',0,13),
(UUID(),'testimonials.manage','Manage testimonials','Content',0,14),
(UUID(),'partners.manage','Manage partners','Content',0,15),
(UUID(),'homepage.manage','Manage homepage sections','Website',0,16),
(UUID(),'pages.manage','Manage website pages','Website',0,17),
(UUID(),'menus.manage','Manage navigation menus','Website',0,18),
(UUID(),'appearance.manage','Manage logo, favicon, header and footer','Website',0,19),
(UUID(),'media.manage','Manage the media library','Website',0,20),
(UUID(),'seo.manage','Manage SEO settings','Website',0,21),
(UUID(),'customers.manage','Manage customer accounts','People',0,22),
(UUID(),'admins.manage','Manage administrators and permissions','People',1,23),
(UUID(),'settings.manage','Manage website settings','System',0,24),
(UUID(),'audit.view','View the activity / audit log','System',0,25),
(UUID(),'system.manage','Manage system, email and security settings','System',1,26);

-- ---------------------------------------------------------------------
-- Role defaults. SUPER_ADMIN keeps the wildcard row; ADMIN gets a sane
-- starting set that the Super Admin can widen or narrow per account.
-- ---------------------------------------------------------------------
INSERT INTO `role_permissions` (`id`,`role`,`resource`,`actions`) VALUES
(UUID(),'SUPER_ADMIN','*',JSON_ARRAY('*')),
(UUID(),'ADMIN','dashboard',JSON_ARRAY('view','read')),
(UUID(),'ADMIN','reports',JSON_ARRAY('view','read')),
(UUID(),'ADMIN','quotes',JSON_ARRAY('manage','read','create','update','delete','export','status')),
(UUID(),'ADMIN','contacts',JSON_ARRAY('manage','read','update','delete')),
(UUID(),'ADMIN','products',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','categories',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','media',JSON_ARRAY('manage','read','create','delete')),
(UUID(),'SALES','dashboard',JSON_ARRAY('view','read')),
(UUID(),'SALES','quotes',JSON_ARRAY('manage','read','create','update','status','export')),
(UUID(),'SALES','contacts',JSON_ARRAY('manage','read','update')),
(UUID(),'ENGINEER','dashboard',JSON_ARRAY('view','read')),
(UUID(),'ENGINEER','products',JSON_ARRAY('manage','read','update')),
(UUID(),'ENGINEER','downloads',JSON_ARRAY('manage','read','update','create')),
(UUID(),'EDITOR','dashboard',JSON_ARRAY('view','read')),
(UUID(),'EDITOR','blog',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'EDITOR','news',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'EDITOR','faqs',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'EDITOR','pages',JSON_ARRAY('manage','read','create','update')),
(UUID(),'EDITOR','media',JSON_ARRAY('manage','read','create'))
ON DUPLICATE KEY UPDATE `actions`=VALUES(`actions`);

-- ---------------------------------------------------------------------
-- Website settings managed from Dashboard → Settings / Appearance
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `settings` (`id`,`key`,`value`,`type`,`group`,`sortOrder`) VALUES
(UUID(),'site_title','Halyk Petroleum — Industrial Manufacturing','STRING','WEBSITE',1),
(UUID(),'site_description','Halyk Petroleum designs and manufactures industrial valves, pumps, heat exchangers, pressure vessels and filtration systems for demanding operators worldwide.','TEXT','WEBSITE',2),
(UUID(),'site_url','','STRING','WEBSITE',3),
(UUID(),'site_language','en','STRING','WEBSITE',4),

(UUID(),'logo_light','/assets/img/logo-header.png','STRING','BRANDING',1),
(UUID(),'logo_dark','/assets/img/logo-footer.png','STRING','BRANDING',2),
(UUID(),'logo_footer','/assets/img/logo-footer.png','STRING','BRANDING',3),
(UUID(),'logo_alt','Halyk Petroleum','STRING','BRANDING',4),
(UUID(),'logo_height','44','INT','BRANDING',5),
(UUID(),'favicon','/assets/img/favicon.ico','STRING','BRANDING',6),

(UUID(),'contact_hours','Mon–Fri, 08:00–18:00','STRING','CONTACT',7),

(UUID(),'social_linkedin','','STRING','SOCIAL',1),
(UUID(),'social_twitter','','STRING','SOCIAL',2),
(UUID(),'social_facebook','','STRING','SOCIAL',3),
(UUID(),'social_youtube','','STRING','SOCIAL',4),
(UUID(),'social_instagram','','STRING','SOCIAL',5),
(UUID(),'social_telegram','','STRING','SOCIAL',6),
(UUID(),'social_whatsapp','','STRING','SOCIAL',7),

(UUID(),'header_cta_enabled','1','BOOL','HEADER',1),
(UUID(),'header_cta_label','Request a Quote','STRING','HEADER',2),
(UUID(),'header_cta_url','rfq','STRING','HEADER',3),
(UUID(),'header_topbar_enabled','0','BOOL','HEADER',4),
(UUID(),'header_topbar_text','','STRING','HEADER',5),

(UUID(),'footer_about','Industrial manufacturing excellence — engineered, tested and delivered worldwide.','TEXT','FOOTER',1),
(UUID(),'footer_copyright','','STRING','FOOTER',2),
(UUID(),'footer_note','','STRING','FOOTER',3),
(UUID(),'footer_newsletter_enabled','0','BOOL','FOOTER',4),

(UUID(),'mail_from_email','','STRING','EMAIL',1),
(UUID(),'mail_from_name','','STRING','EMAIL',2),
(UUID(),'mail_reply_to','','STRING','EMAIL',3),
(UUID(),'smtp_host','','STRING','EMAIL',4),
(UUID(),'smtp_port','465','INT','EMAIL',5),
(UUID(),'smtp_user','','STRING','EMAIL',6),
(UUID(),'smtp_pass','','STRING','EMAIL',7),
(UUID(),'smtp_crypto','ssl','STRING','EMAIL',8),

(UUID(),'maintenance_mode','0','BOOL','SYSTEM',1),
(UUID(),'maintenance_message','We are performing scheduled maintenance. Please check back shortly.','TEXT','SYSTEM',2);

-- ---------------------------------------------------------------------
-- Navigation (header, footer columns, legal)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `menu_items` (`id`,`menu`,`label`,`type`,`url`,`target`,`sortOrder`,`isActive`) VALUES
(UUID(),'header','Products','INTERNAL','products','_self',10,1),
(UUID(),'header','Industries','INTERNAL','industries','_self',20,1),
(UUID(),'header','Services','INTERNAL','services','_self',30,1),
(UUID(),'header','About','INTERNAL','about','_self',40,1),
(UUID(),'header','Blog','INTERNAL','blog','_self',50,1),
(UUID(),'header','Careers','INTERNAL','careers','_self',60,1),
(UUID(),'header','FAQ','INTERNAL','faq','_self',70,1),
(UUID(),'header','Downloads','INTERNAL','downloads','_self',80,1),
(UUID(),'header','Contact','INTERNAL','contact','_self',90,1),

(UUID(),'footer_solutions','Products','INTERNAL','products','_self',10,1),
(UUID(),'footer_solutions','Industries','INTERNAL','industries','_self',20,1),
(UUID(),'footer_solutions','Services','INTERNAL','services','_self',30,1),
(UUID(),'footer_solutions','Request a Quote','INTERNAL','rfq','_self',40,1),

(UUID(),'footer_company','About','INTERNAL','about','_self',10,1),
(UUID(),'footer_company','Blog','INTERNAL','blog','_self',20,1),
(UUID(),'footer_company','News','INTERNAL','news','_self',30,1),
(UUID(),'footer_company','Careers','INTERNAL','careers','_self',40,1),
(UUID(),'footer_company','Contact','INTERNAL','contact','_self',50,1),

(UUID(),'footer_legal','Privacy Policy','INTERNAL','privacy-policy','_self',10,1),
(UUID(),'footer_legal','Terms of Service','INTERNAL','terms-of-service','_self',20,1);

-- ---------------------------------------------------------------------
-- Homepage sections (the public homepage renders exactly these rows)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `page_sections`
(`id`,`pageKey`,`type`,`name`,`title`,`subtitle`,`body`,`image`,`buttonText`,`buttonUrl`,`buttonText2`,`buttonUrl2`,`settings`,`sortOrder`,`isActive`,`isSystem`) VALUES
(UUID(),'home','hero','Hero banner',
 'Precision-engineered for the most demanding industries',
 'Halyk Petroleum designs and manufactures industrial valves, pumps, heat exchangers, pressure vessels and filtration systems trusted by operators worldwide.',
 NULL,'/assets/img/hero-industrial.jpg','Request a Quote','rfq','Explore Products','products',
 '{"eyebrow":"Industrial manufacturing","badges":["ASME certified","ISO 9001:2015","Global support"]}',10,1,1),

(UUID(),'home','stats','Key numbers',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,
 '{"items":[{"value":"35+","label":"Years of experience"},{"value":"60+","label":"Countries served"},{"value":"4200+","label":"Projects delivered"},{"value":"850+","label":"Satisfied clients"}]}',20,1,0),

(UUID(),'home','categories','Product categories',
 'Our product categories',
 'From precision-machined valves to ASME-coded pressure vessels, every category is engineered to the same standard.',
 NULL,NULL,NULL,NULL,NULL,NULL,'{"limit":6}',30,1,0),

(UUID(),'home','products','Featured products',
 'Featured products','Our most-requested, in-stock equipment.',NULL,NULL,'View all','products',NULL,NULL,
 '{"limit":4}',40,1,0),

(UUID(),'home','industries','Industries',
 'Industries we serve','Engineered for the requirements of the world''s most demanding sectors.',
 NULL,NULL,NULL,NULL,NULL,NULL,'{"limit":6}',50,1,0),

(UUID(),'home','testimonials','Testimonials',
 'What our customers say','Operators across oil and gas, chemicals, water and food processing trust our equipment and field teams.',
 NULL,NULL,NULL,NULL,NULL,NULL,'{"limit":4}',60,1,0),

(UUID(),'home','partners','Partners',
 'Trusted by world-class operators',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{"limit":12}',70,1,0),

(UUID(),'home','cta','Closing call to action',
 'Have a project in mind?','Submit your specifications and our engineering team will respond with a formal quote within 2 business days.',
 NULL,NULL,'Request a Quote','rfq',NULL,NULL,NULL,80,1,0);

-- ---------------------------------------------------------------------
-- Starter CMS pages
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `pages`
(`id`,`title`,`slug`,`excerpt`,`content`,`template`,`metaTitle`,`metaDescription`,`status`,`visibility`,`publishedAt`,`showInMenu`,`sortOrder`,`isSystem`) VALUES
(UUID(),'Privacy Policy','privacy-policy','How we collect, use and protect your personal information.',
'<h2>Privacy Policy</h2><p>This policy explains what information we collect when you use our website, how it is used and the choices you have. Edit this page from <strong>Dashboard → Website → Pages</strong>.</p><h3>Information we collect</h3><p>We collect the details you submit through our contact and quote request forms: your name, company, email address, phone number and the content of your enquiry.</p><h3>How we use it</h3><p>Your information is used solely to respond to your enquiry, prepare quotations and provide after-sales support.</p><h3>Contact</h3><p>Questions about this policy can be sent to our contact address listed in the website footer.</p>',
'default','Privacy Policy','How we collect, use and protect your personal information.','PUBLISHED','PUBLIC',NOW(),0,10,0),

(UUID(),'Terms of Service','terms-of-service','The terms that apply to the use of this website.',
'<h2>Terms of Service</h2><p>By using this website you agree to the terms below. Edit this page from <strong>Dashboard → Website → Pages</strong>.</p><h3>Use of the website</h3><p>Content published here is provided for information purposes. Specifications may change without notice; a written quotation is the only binding offer.</p><h3>Intellectual property</h3><p>All trademarks, drawings and documentation remain the property of their respective owners.</p>',
'default','Terms of Service','The terms that apply to the use of this website.','PUBLISHED','PUBLIC',NOW(),0,20,0);
