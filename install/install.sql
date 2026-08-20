-- =====================================================================
-- Vortex Precision - MySQL/MariaDB schema (CodeIgniter 3 conversion)
-- Translation rules:
--   Prisma String @id @default(cuid())        -> CHAR(36) DEFAULT (UUID())
--   Prisma DateTime                           -> DATETIME
--   Prisma String[]                           -> JSON
--   Prisma enum Role/QuoteStatus/EmailStatus  -> ENUM(...)
--   Prisma Float                              -> DECIMAL(12,2) for money, DOUBLE for ratings
--   Prisma @@map("x")                         -> TABLE x
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Users + sessions + permissions
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`              CHAR(36)     NOT NULL,
  `email`           VARCHAR(190) NOT NULL,
  `password`        VARCHAR(255) NOT NULL,
  `firstName`       VARCHAR(100) NOT NULL DEFAULT '',
  `lastName`        VARCHAR(100) NOT NULL DEFAULT '',
  `role`            ENUM('SUPER_ADMIN','ADMIN','SALES','ENGINEER','EDITOR','CUSTOMER') NOT NULL DEFAULT 'CUSTOMER',
  `phone`           VARCHAR(50)  DEFAULT NULL,
  `company`         VARCHAR(190) DEFAULT NULL,
  `avatar`          VARCHAR(255) DEFAULT NULL,
  `isActive`        TINYINT(1)   NOT NULL DEFAULT 1,
  `mustChangePassword` TINYINT(1) NOT NULL DEFAULT 0,
  `emailVerified`   TINYINT(1)   NOT NULL DEFAULT 0,
  `twoFactorEnabled` TINYINT(1)  NOT NULL DEFAULT 0,
  `lastLoginAt`     DATETIME     DEFAULT NULL,
  `createdAt`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id`        CHAR(36)     NOT NULL,
  `role`      VARCHAR(40)  NOT NULL,
  `resource`  VARCHAR(100) NOT NULL,
  `actions`   JSON         NOT NULL,
  `createdAt` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_resource` (`role`,`resource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id`         VARCHAR(128) NOT NULL,
  `user_id`    CHAR(36)     DEFAULT NULL,
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `payload`    TEXT         NOT NULL,
  `last_activity` INT        NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sessions_user_id` (`user_id`),
  KEY `idx_sessions_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Catalog
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`              CHAR(36)     NOT NULL,
  `name`            VARCHAR(190) NOT NULL,
  `slug`            VARCHAR(190) NOT NULL,
  `description`     TEXT         DEFAULT NULL,
  `icon`            VARCHAR(190) DEFAULT NULL,
  `image`           VARCHAR(255) DEFAULT NULL,
  `parentId`        CHAR(36)     DEFAULT NULL,
  `sortOrder` INT          NOT NULL DEFAULT 0,
  `isActive`        TINYINT(1)   NOT NULL DEFAULT 1,
  `metaTitle`       VARCHAR(255) DEFAULT NULL,
  `metaDescription` VARCHAR(500) DEFAULT NULL,
  `createdAt`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parentId`),
  KEY `idx_categories_order` (`sortOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id`               CHAR(36)     NOT NULL,
  `name`             VARCHAR(255) NOT NULL,
  `slug`             VARCHAR(255) NOT NULL,
  `sku`              VARCHAR(100) NOT NULL,
  `description`      TEXT         NOT NULL,
  `shortDescription` VARCHAR(500) DEFAULT NULL,
  `price`            DECIMAL(12,2) DEFAULT NULL,
  `categoryId`       CHAR(36)     DEFAULT NULL,
  `industryIds`      JSON         DEFAULT NULL,
  `material`         VARCHAR(190) DEFAULT NULL,
  `pressure`         VARCHAR(100) DEFAULT NULL,
  `temperature`      VARCHAR(100) DEFAULT NULL,
  `voltage`          VARCHAR(100) DEFAULT NULL,
  `dimensions`       VARCHAR(190) DEFAULT NULL,
  `weight`           VARCHAR(100) DEFAULT NULL,
  `certifications`   JSON         DEFAULT NULL,
  `availability`     VARCHAR(40)  NOT NULL DEFAULT 'IN_STOCK',
  `featured`         TINYINT(1)   NOT NULL DEFAULT 0,
  `isActive`         TINYINT(1)   NOT NULL DEFAULT 1,
  `views`            INT          NOT NULL DEFAULT 0,
  `metaTitle`        VARCHAR(255) DEFAULT NULL,
  `metaDescription`  VARCHAR(500) DEFAULT NULL,
  `metaKeywords`     JSON         DEFAULT NULL,
  `createdAt`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_products_slug` (`slug`),
  UNIQUE KEY `uk_products_sku` (`sku`),
  KEY `idx_products_category` (`categoryId`),
  KEY `idx_products_featured` (`featured`),
  KEY `idx_products_isActive` (`isActive`),
  KEY `idx_products_created` (`createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_images` (
  `id`        CHAR(36)     NOT NULL,
  `productId` CHAR(36)     NOT NULL,
  `url`       VARCHAR(500) NOT NULL,
  `alt`       VARCHAR(255) DEFAULT NULL,
  `caption`   VARCHAR(500) DEFAULT NULL,
  `sortOrder` INT          NOT NULL DEFAULT 0,
  `isPrimary` TINYINT(1)   NOT NULL DEFAULT 0,
  `createdAt` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`productId`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`productId`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `specifications` (
  `id`        CHAR(36) NOT NULL,
  `productId` CHAR(36) NOT NULL,
  `key`       VARCHAR(190) NOT NULL,
  `value`     VARCHAR(500) NOT NULL,
  `unit`      VARCHAR(40)  DEFAULT NULL,
  `sortOrder` INT          NOT NULL DEFAULT 0,
  `createdAt` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_specifications_product` (`productId`),
  CONSTRAINT `fk_specifications_product` FOREIGN KEY (`productId`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_downloads` (
  `id`        CHAR(36)     NOT NULL,
  `productId` CHAR(36)     NOT NULL,
  `title`     VARCHAR(255) NOT NULL,
  `url`       VARCHAR(500) NOT NULL,
  `type`      VARCHAR(40)  NOT NULL DEFAULT 'PDF',
  `size`      VARCHAR(40)  DEFAULT NULL,
  `createdAt` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_downloads_product` (`productId`),
  CONSTRAINT `fk_product_downloads_product` FOREIGN KEY (`productId`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `related_products` (
  `id`        CHAR(36) NOT NULL,
  `productId` CHAR(36) NOT NULL,
  `relatedId` CHAR(36) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_related` (`productId`,`relatedId`),
  CONSTRAINT `fk_related_product`  FOREIGN KEY (`productId`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_related_related`  FOREIGN KEY (`relatedId`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Quotes / RFQ
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quotes` (
  `id`              CHAR(36)      NOT NULL,
  `quoteNumber`     VARCHAR(40)   NOT NULL,
  `userId`          CHAR(36)      DEFAULT NULL,
  `companyName`     VARCHAR(255)  NOT NULL,
  `contactPerson`   VARCHAR(255)  NOT NULL,
  `email`           VARCHAR(190)  NOT NULL,
  `phone`           VARCHAR(50)   DEFAULT NULL,
  `country`         VARCHAR(100)  NOT NULL,
  `address`         VARCHAR(500)  DEFAULT NULL,
  `industry`        VARCHAR(190)  DEFAULT NULL,
  `status`          ENUM('NEW','REVIEWING','QUOTED','APPROVED','REJECTED','COMPLETED') NOT NULL DEFAULT 'NEW',
  `deadline`        DATE          DEFAULT NULL,
  `totalAmount`     DECIMAL(14,2) DEFAULT NULL,
  `notes`           TEXT          DEFAULT NULL,
  `internalNotes`   TEXT          DEFAULT NULL,
  `pdfUrl`          VARCHAR(500)  DEFAULT NULL,
  `assignedTo`      CHAR(36)      DEFAULT NULL,
  `statusUpdatedAt` DATETIME      DEFAULT NULL,
  `lastNotifiedAt`  DATETIME      DEFAULT NULL,
  `version`         INT           NOT NULL DEFAULT 1,
  `createdAt`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_quotes_number` (`quoteNumber`),
  KEY `idx_quotes_status` (`status`),
  KEY `idx_quotes_user` (`userId`),
  KEY `idx_quotes_assigned` (`assignedTo`),
  KEY `idx_quotes_created` (`createdAt`),
  CONSTRAINT `fk_quotes_user`     FOREIGN KEY (`userId`)     REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_quotes_assigned` FOREIGN KEY (`assignedTo`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_items` (
  `id`             CHAR(36) NOT NULL,
  `quoteId`        CHAR(36) NOT NULL,
  `productId`      CHAR(36) DEFAULT NULL,
  `productName`    VARCHAR(255) NOT NULL,
  `quantity`       INT NOT NULL DEFAULT 1,
  `specifications` TEXT DEFAULT NULL,
  `unitPrice`      DECIMAL(12,2) DEFAULT NULL,
  `total`          DECIMAL(14,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_quote_items_quote` (`quoteId`),
  KEY `idx_quote_items_product` (`productId`),
  CONSTRAINT `fk_quote_items_quote`   FOREIGN KEY (`quoteId`)   REFERENCES `quotes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quote_items_product` FOREIGN KEY (`productId`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_attachments` (
  `id`        CHAR(36) NOT NULL,
  `quoteId`   CHAR(36) NOT NULL,
  `filename`  VARCHAR(255) NOT NULL,
  `url`       VARCHAR(500) NOT NULL,
  `size`      INT DEFAULT NULL,
  `mimeType`  VARCHAR(100) DEFAULT NULL,
  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quote_attachments_quote` (`quoteId`),
  CONSTRAINT `fk_quote_attachments_quote` FOREIGN KEY (`quoteId`) REFERENCES `quotes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_status_history` (
  `id`         CHAR(36) NOT NULL,
  `quoteId`    CHAR(36) NOT NULL,
  `fromStatus` ENUM('NEW','REVIEWING','QUOTED','APPROVED','REJECTED','COMPLETED') DEFAULT NULL,
  `toStatus`   ENUM('NEW','REVIEWING','QUOTED','APPROVED','REJECTED','COMPLETED') NOT NULL,
  `changedBy`  CHAR(36) DEFAULT NULL,
  `notes`      TEXT DEFAULT NULL,
  `createdAt`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_qsh_quote_created` (`quoteId`,`createdAt`),
  CONSTRAINT `fk_qsh_quote` FOREIGN KEY (`quoteId`) REFERENCES `quotes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_activities` (
  `id`          CHAR(36) NOT NULL,
  `quoteId`     CHAR(36) NOT NULL,
  `actorId`     CHAR(36) DEFAULT NULL,
  `action`      ENUM('QUOTE_CREATED','ASSIGNED','STATUS_CHANGED','INTERNAL_NOTE_ADDED','QUOTE_UPDATED','PDF_GENERATED','EMAIL_QUEUED','EMAIL_SENT') NOT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `metadata`    JSON DEFAULT NULL,
  `ipAddress`   VARCHAR(45) DEFAULT NULL,
  `userAgent`   VARCHAR(255) DEFAULT NULL,
  `createdAt`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_qa_quote_created` (`quoteId`,`createdAt`),
  CONSTRAINT `fk_qa_quote` FOREIGN KEY (`quoteId`) REFERENCES `quotes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qa_actor` FOREIGN KEY (`actorId`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_logs` (
  `id`             CHAR(36) NOT NULL,
  `to`             VARCHAR(190) NOT NULL,
  `subject`        VARCHAR(500) NOT NULL,
  `template`       VARCHAR(100) NOT NULL,
  `status`         ENUM('PENDING','SENT','FAILED','RETRYING') NOT NULL DEFAULT 'PENDING',
  `providerId`     VARCHAR(255) DEFAULT NULL,
  `dedupeKey`      VARCHAR(255) DEFAULT NULL,
  `errorMessage`   TEXT DEFAULT NULL,
  `sentAt`         DATETIME DEFAULT NULL,
  `retryCount`     INT NOT NULL DEFAULT 0,
  `relatedQuoteId` CHAR(36) DEFAULT NULL,
  `createdAt`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email_dedupe` (`dedupeKey`),
  KEY `idx_email_to_created` (`to`,`createdAt`),
  KEY `idx_email_quote` (`relatedQuoteId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Other content
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contacts` (
  `id`        CHAR(36) NOT NULL,
  `userId`    CHAR(36) DEFAULT NULL,
  `name`      VARCHAR(190) NOT NULL,
  `email`     VARCHAR(190) NOT NULL,
  `phone`     VARCHAR(50)  DEFAULT NULL,
  `company`   VARCHAR(190) DEFAULT NULL,
  `subject`   VARCHAR(255) NOT NULL,
  `message`   TEXT NOT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `status`    VARCHAR(40) NOT NULL DEFAULT 'NEW',
  `assignedTo` CHAR(36) DEFAULT NULL,
  `repliedAt` DATETIME DEFAULT NULL,
  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_status` (`status`),
  KEY `idx_contacts_user` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id`            CHAR(36) NOT NULL,
  `title`         VARCHAR(255) NOT NULL,
  `slug`          VARCHAR(255) NOT NULL,
  `excerpt`       TEXT DEFAULT NULL,
  `content`       LONGTEXT NOT NULL,
  `featuredImage` VARCHAR(500) DEFAULT NULL,
  `authorId`      CHAR(36) NOT NULL,
  `category`      VARCHAR(100) DEFAULT NULL,
  `tags`          JSON DEFAULT NULL,
  `status`        VARCHAR(40) NOT NULL DEFAULT 'DRAFT',
  `publishedAt`   DATETIME DEFAULT NULL,
  `views`         INT NOT NULL DEFAULT 0,
  `metaTitle`     VARCHAR(255) DEFAULT NULL,
  `metaDescription` VARCHAR(500) DEFAULT NULL,
  `createdAt`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_blog_slug` (`slug`),
  KEY `idx_blog_status_pub` (`status`,`publishedAt`),
  KEY `idx_blog_author` (`authorId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `careers` (
  `id`          CHAR(36) NOT NULL,
  `title`       VARCHAR(255) NOT NULL,
  `slug`        VARCHAR(255) NOT NULL,
  `department`  VARCHAR(100) NOT NULL,
  `location`    VARCHAR(190) NOT NULL,
  `type`        VARCHAR(40)  NOT NULL DEFAULT 'Full-time',
  `experience`  VARCHAR(100) DEFAULT NULL,
  `salary`      VARCHAR(100) DEFAULT NULL,
  `description` LONGTEXT NOT NULL,
  `requirements` LONGTEXT NOT NULL,
  `benefits`    TEXT DEFAULT NULL,
  `isActive`    TINYINT(1) NOT NULL DEFAULT 1,
  `postedAt`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closingAt`   DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_careers_slug` (`slug`),
  KEY `idx_careers_active` (`isActive`,`postedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `applications` (
  `id`          CHAR(36) NOT NULL,
  `careerId`    CHAR(36) NOT NULL,
  `userId`      CHAR(36) DEFAULT NULL,
  `name`        VARCHAR(190) NOT NULL,
  `email`       VARCHAR(190) NOT NULL,
  `phone`       VARCHAR(50)  DEFAULT NULL,
  `coverLetter` TEXT DEFAULT NULL,
  `resumeUrl`   VARCHAR(500) NOT NULL,
  `linkedin`    VARCHAR(255) DEFAULT NULL,
  `status`      VARCHAR(40) NOT NULL DEFAULT 'RECEIVED',
  `notes`       TEXT DEFAULT NULL,
  `createdAt`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_applications_career` (`careerId`),
  KEY `idx_applications_status` (`status`),
  CONSTRAINT `fk_applications_career` FOREIGN KEY (`careerId`) REFERENCES `careers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `testimonials` (
  `id`        CHAR(36) NOT NULL,
  `name`      VARCHAR(190) NOT NULL,
  `title`     VARCHAR(190) NOT NULL,
  `company`   VARCHAR(190) NOT NULL,
  `content`   TEXT NOT NULL,
  `rating`    INT NOT NULL DEFAULT 5,
  `avatar`    VARCHAR(500) DEFAULT NULL,
  `industry`  VARCHAR(100) DEFAULT NULL,
  `isActive`  TINYINT(1) NOT NULL DEFAULT 1,
  `featured`  TINYINT(1) NOT NULL DEFAULT 0,
  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_testimonials_active` (`isActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `partners` (
  `id`        CHAR(36) NOT NULL,
  `name`      VARCHAR(190) NOT NULL,
  `logo`      VARCHAR(500) NOT NULL,
  `website`   VARCHAR(500) DEFAULT NULL,
  `category`  VARCHAR(100) DEFAULT NULL,
  `sortOrder` INT NOT NULL DEFAULT 0,
  `isActive`  TINYINT(1) NOT NULL DEFAULT 1,
  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_partners_order` (`sortOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news` (
  `id`          CHAR(36) NOT NULL,
  `title`       VARCHAR(255) NOT NULL,
  `slug`        VARCHAR(255) NOT NULL,
  `summary`     TEXT DEFAULT NULL,
  `content`     LONGTEXT NOT NULL,
  `image`       VARCHAR(500) DEFAULT NULL,
  `category`    VARCHAR(100) DEFAULT NULL,
  `publishedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isActive`    TINYINT(1) NOT NULL DEFAULT 1,
  `createdAt`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_slug` (`slug`),
  KEY `idx_news_active_pub` (`isActive`,`publishedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `downloads` (
  `id`          CHAR(36) NOT NULL,
  `title`       VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `fileUrl`     VARCHAR(500) NOT NULL,
  `type`        VARCHAR(40) NOT NULL DEFAULT 'PDF',
  `category`    VARCHAR(100) DEFAULT NULL,
  `fileSize`    VARCHAR(40) DEFAULT NULL,
  `downloads`   INT NOT NULL DEFAULT 0,
  `isActive`    TINYINT(1) NOT NULL DEFAULT 1,
  `createdAt`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_downloads_active` (`isActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `faqs` (
  `id`        CHAR(36) NOT NULL,
  `question`  VARCHAR(500) NOT NULL,
  `answer`    TEXT NOT NULL,
  `category`  VARCHAR(100) NOT NULL,
  `sortOrder` INT NOT NULL DEFAULT 0,
  `isActive`  TINYINT(1) NOT NULL DEFAULT 1,
  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_faqs_active_order` (`isActive`,`sortOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `industries` (
  `id`            CHAR(36) NOT NULL,
  `name`          VARCHAR(190) NOT NULL,
  `slug`          VARCHAR(190) NOT NULL,
  `description`   TEXT NOT NULL,
  `image`         VARCHAR(500) DEFAULT NULL,
  `icon`          VARCHAR(190) DEFAULT NULL,
  `capabilities`  JSON DEFAULT NULL,
  `metaTitle`     VARCHAR(255) DEFAULT NULL,
  `metaDescription` VARCHAR(500) DEFAULT NULL,
  `sortOrder` INT NOT NULL DEFAULT 0,
  `isActive`      TINYINT(1) NOT NULL DEFAULT 1,
  `createdAt`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_industries_slug` (`slug`),
  KEY `idx_industries_order` (`sortOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `id`         CHAR(36) NOT NULL,
  `key`        VARCHAR(190) NOT NULL,
  `value`      LONGTEXT NOT NULL,
  `type`       VARCHAR(40) NOT NULL DEFAULT 'STRING',
  `group`      VARCHAR(40) NOT NULL DEFAULT 'GENERAL',
  `version`    INT NOT NULL DEFAULT 1,
  `enabled`    TINYINT(1) NOT NULL DEFAULT 1,
  `sortOrder` INT NOT NULL DEFAULT 0,
  `updatedBy`  CHAR(36) DEFAULT NULL,
  `createdAt`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_key` (`key`),
  KEY `idx_settings_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media` (
  `id`           CHAR(36) NOT NULL,
  `filename`     VARCHAR(255) NOT NULL,
  `originalName` VARCHAR(255) NOT NULL,
  `url`          VARCHAR(500) NOT NULL,
  `mimeType`     VARCHAR(100) NOT NULL,
  `size`         INT NOT NULL,
  `folder`       VARCHAR(100) DEFAULT 'general',
  `alt`          VARCHAR(255) DEFAULT NULL,
  `title`        VARCHAR(255) DEFAULT NULL,
  `uploadedBy`   CHAR(36) DEFAULT NULL,
  `isProtected`  TINYINT(1) NOT NULL DEFAULT 0,
  `createdAt`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_folder` (`folder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id`         CHAR(36) NOT NULL,
  `userId`     CHAR(36) DEFAULT NULL,
  `action`     VARCHAR(40) NOT NULL,
  `resource`   VARCHAR(100) NOT NULL,
  `resourceId` CHAR(36) DEFAULT NULL,
  `details`    JSON DEFAULT NULL,
  `ipAddress`  VARCHAR(45) DEFAULT NULL,
  `userAgent`  VARCHAR(255) DEFAULT NULL,
  `createdAt`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`userId`),
  KEY `idx_audit_resource` (`resource`,`resourceId`),
  KEY `idx_audit_created` (`createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`        CHAR(36) NOT NULL,
  `userId`    CHAR(36) NOT NULL,
  `type`      VARCHAR(40) NOT NULL,
  `title`     VARCHAR(255) NOT NULL,
  `message`   TEXT NOT NULL,
  `data`      JSON DEFAULT NULL,
  `read`      TINYINT(1) NOT NULL DEFAULT 0,
  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user_read` (`userId`,`read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CI3 native session table (for sess_driver=database)
CREATE TABLE IF NOT EXISTS `ci_sessions` (
  `id`            VARCHAR(128) NOT NULL,
  `ip_address`    VARCHAR(45)  NOT NULL,
  `timestamp`     INT UNSIGNED NOT NULL DEFAULT 0,
  `data`          BLOB        NOT NULL,
  `primary_key`   VARCHAR(64)  NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`ip_address`),
  KEY `ci_sessions_timestamp` (`timestamp`),
  KEY `ci_sessions_primary_key` (`primary_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens (single-use, time-limited)
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         CHAR(36)     NOT NULL,
  `userId`     CHAR(36)     NOT NULL,
  `email`      VARCHAR(190) NOT NULL,
  `token`      VARCHAR(128) NOT NULL,
  `expiresAt`  DATETIME     NOT NULL,
  `usedAt`     DATETIME     DEFAULT NULL,
  `ipAddress`  VARCHAR(45)  DEFAULT NULL,
  `createdAt`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_password_resets_token` (`token`),
  KEY `idx_password_resets_email` (`email`),
  KEY `idx_password_resets_expires` (`expiresAt`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- CMS + role/permission tables (dashboard control centre)
-- Mirrors database/migrations/001_cms_and_permissions.sql
-- =====================================================================
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

SET FOREIGN_KEY_CHECKS = 1;
