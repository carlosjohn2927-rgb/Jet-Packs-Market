-- =====================================================================
-- Halyk Petroleum — CMS + Role/Permission upgrade (migration 001)
-- =====================================================================
-- Adds the tables required by the Super Admin / Admin dashboards:
--   permissions        catalogue of every grantable permission
--   user_permissions   per-administrator grant / deny overrides
--   pages              CMS pages published on the public website
--   page_sections      homepage (and other page) content sections
--   menu_items         header / footer / legal navigation
--   media (extended)   alt text, ownership, protection flag
--
-- Safe to run more than once (CREATE TABLE IF NOT EXISTS). The ALTER
-- statements at the bottom fail harmlessly with "duplicate column" if the
-- migration has already been applied.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Permission catalogue
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
  `id`          CHAR(36)     NOT NULL,
  `key`         VARCHAR(100) NOT NULL,
  `label`       VARCHAR(190) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `groupName`   VARCHAR(80)  NOT NULL DEFAULT 'General',
  `superOnly`   TINYINT(1)   NOT NULL DEFAULT 0,
  `sortOrder`   INT          NOT NULL DEFAULT 0,
  `createdAt`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissions_key` (`key`),
  KEY `idx_permissions_group` (`groupName`,`sortOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Per-user permission overrides (grant = 1, explicit deny = 0)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id`          CHAR(36)     NOT NULL,
  `userId`      CHAR(36)     NOT NULL,
  `permission`  VARCHAR(100) NOT NULL,
  `granted`     TINYINT(1)   NOT NULL DEFAULT 1,
  `grantedBy`   CHAR(36)     DEFAULT NULL,
  `createdAt`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_permission` (`userId`,`permission`),
  KEY `idx_user_permissions_user` (`userId`),
  CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- CMS pages
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pages` (
  `id`              CHAR(36)     NOT NULL,
  `title`           VARCHAR(190) NOT NULL,
  `slug`            VARCHAR(190) NOT NULL,
  `excerpt`         TEXT         DEFAULT NULL,
  `content`         LONGTEXT     DEFAULT NULL,
  `featuredImage`   VARCHAR(500) DEFAULT NULL,
  `template`        VARCHAR(40)  NOT NULL DEFAULT 'default',
  `metaTitle`       VARCHAR(255) DEFAULT NULL,
  `metaDescription` VARCHAR(500) DEFAULT NULL,
  `status`          ENUM('DRAFT','PUBLISHED') NOT NULL DEFAULT 'DRAFT',
  `visibility`      ENUM('PUBLIC','PRIVATE')  NOT NULL DEFAULT 'PUBLIC',
  `publishedAt`     DATETIME     DEFAULT NULL,
  `showInMenu`      TINYINT(1)   NOT NULL DEFAULT 0,
  `sortOrder`       INT          NOT NULL DEFAULT 0,
  `isSystem`        TINYINT(1)   NOT NULL DEFAULT 0,
  `authorId`        CHAR(36)     DEFAULT NULL,
  `createdAt`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pages_slug` (`slug`),
  KEY `idx_pages_status` (`status`,`publishedAt`),
  KEY `idx_pages_menu` (`showInMenu`,`sortOrder`),
  CONSTRAINT `fk_pages_author` FOREIGN KEY (`authorId`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Homepage / page content sections
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `page_sections` (
  `id`          CHAR(36)     NOT NULL,
  `pageKey`     VARCHAR(60)  NOT NULL DEFAULT 'home',
  `type`        VARCHAR(40)  NOT NULL DEFAULT 'richtext',
  `name`        VARCHAR(190) NOT NULL DEFAULT '',
  `title`       VARCHAR(255) DEFAULT NULL,
  `subtitle`    VARCHAR(500) DEFAULT NULL,
  `body`        LONGTEXT     DEFAULT NULL,
  `image`       VARCHAR(500) DEFAULT NULL,
  `buttonText`  VARCHAR(120) DEFAULT NULL,
  `buttonUrl`   VARCHAR(500) DEFAULT NULL,
  `buttonText2` VARCHAR(120) DEFAULT NULL,
  `buttonUrl2`  VARCHAR(500) DEFAULT NULL,
  `settings`    JSON         DEFAULT NULL,
  `sortOrder`   INT          NOT NULL DEFAULT 0,
  `isActive`    TINYINT(1)   NOT NULL DEFAULT 1,
  `isSystem`    TINYINT(1)   NOT NULL DEFAULT 0,
  `createdAt`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sections_page_order` (`pageKey`,`sortOrder`),
  KEY `idx_sections_active` (`isActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Navigation / menu items
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id`        CHAR(36)     NOT NULL,
  `menu`      VARCHAR(40)  NOT NULL DEFAULT 'header',
  `label`     VARCHAR(120) NOT NULL,
  `type`      ENUM('INTERNAL','PAGE','EXTERNAL') NOT NULL DEFAULT 'INTERNAL',
  `url`       VARCHAR(500) DEFAULT NULL,
  `pageId`    CHAR(36)     DEFAULT NULL,
  `target`    VARCHAR(10)  NOT NULL DEFAULT '_self',
  `icon`      VARCHAR(60)  DEFAULT NULL,
  `parentId`  CHAR(36)     DEFAULT NULL,
  `sortOrder` INT          NOT NULL DEFAULT 0,
  `isActive`  TINYINT(1)   NOT NULL DEFAULT 1,
  `createdAt` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menu_items_menu` (`menu`,`sortOrder`),
  KEY `idx_menu_items_active` (`isActive`),
  CONSTRAINT `fk_menu_items_page` FOREIGN KEY (`pageId`) REFERENCES `pages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Media library extensions (ignore "duplicate column" errors on re-run)
-- ---------------------------------------------------------------------
ALTER TABLE `media` ADD COLUMN `title`       VARCHAR(255) DEFAULT NULL;
ALTER TABLE `media` ADD COLUMN `uploadedBy`  CHAR(36)     DEFAULT NULL;
ALTER TABLE `media` ADD COLUMN `isProtected` TINYINT(1)   NOT NULL DEFAULT 0;
