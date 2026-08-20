-- Halyk Petroleum CMS upgrade: black write-up colour
-- Changes the site-wide "write-up" (body copy, headings and labels on light
-- surfaces) from the default dark navy (#0b1424) to pure black (#000000).
-- Safe to run repeatedly in phpMyAdmin on an existing installation.

INSERT INTO `settings` (`id`,`key`,`value`,`type`,`group`,`sortOrder`) VALUES
(UUID(),'theme_bg','#ffffff','STRING','THEME',1),
(UUID(),'theme_writeup','#000000','STRING','THEME',2)
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);
