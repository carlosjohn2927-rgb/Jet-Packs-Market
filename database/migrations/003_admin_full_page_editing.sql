-- Halyk Petroleum CMS upgrade: ADMIN full website-editing access
-- Safe to run repeatedly in phpMyAdmin on an existing installation.

INSERT INTO `role_permissions` (`id`,`role`,`resource`,`actions`) VALUES
(UUID(),'ADMIN','products',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','categories',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','industries',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','downloads',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','blog',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','news',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','faqs',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','careers',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','testimonials',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','partners',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','homepage',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','pages',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','menus',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','appearance',JSON_ARRAY('manage','read','update')),
(UUID(),'ADMIN','media',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','seo',JSON_ARRAY('manage','read','update')),
(UUID(),'ADMIN','settings',JSON_ARRAY('manage','read','update'))
ON DUPLICATE KEY UPDATE `actions`=VALUES(`actions`);
