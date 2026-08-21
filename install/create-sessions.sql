-- Create the CodeIgniter 3 database session table (if missing)
CREATE TABLE IF NOT EXISTS `ci_sessions` (
  `id`            VARCHAR(128) NOT NULL,
  `ip_address`    VARCHAR(45)  NOT NULL,
  `timestamp`     INT UNSIGNED NOT NULL DEFAULT 0,
  `data`          BLOB         NOT NULL,
  `primary_key`   VARCHAR(64)  NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`ip_address`),
  KEY `ci_sessions_timestamp` (`timestamp`),
  KEY `ci_sessions_primary_key` (`primary_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
