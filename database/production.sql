-- =============================================================================
-- Halyk Petroleum — COMPLETE PRODUCTION DATABASE
-- =============================================================================
-- This single file contains everything needed for the application to run:
--   • All tables, columns, indexes, and foreign keys
--   • All seed/reference data (categories, parts, industries, FAQs, etc.)
--   • All application settings
--   • An initial SUPER_ADMIN administrator account
--   • Sample blog posts, news, careers, and downloads
--
-- HOW TO USE:
--   1. In cPanel, go to phpMyAdmin.
--   2. Select your newly-created (empty) database.
--   3. Click the "Import" tab.
--   4. Choose this file (database/production.sql) and click "Go".
--   5. Wait for the import to complete.
--   6. The database is now fully initialized — no further CLI steps needed.
--
-- The admin account credentials are:
--   Email:    admin@halykpetroleum-kz.com
--   Password: Nigeria1234@
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'TRADITIONAL';

-- #############################################################################
-- 1. SCHEMA — all tables
-- #############################################################################

-- ---------------------------------------------------------------------------
-- Users + sessions + permissions
-- ---------------------------------------------------------------------------
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

-- ---------------------------------------------------------------------------
-- Catalog
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`              CHAR(36)     NOT NULL,
  `name`            VARCHAR(190) NOT NULL,
  `nameNorm`        VARCHAR(190) DEFAULT NULL,
  `slug`            VARCHAR(190) NOT NULL,
  `description`     TEXT         DEFAULT NULL,
  `icon`            VARCHAR(190) DEFAULT NULL,
  `image`           VARCHAR(255) DEFAULT NULL,
  `parentId`        CHAR(36)     DEFAULT NULL,
  `sortOrder`       INT          NOT NULL DEFAULT 0,
  `isActive`        TINYINT(1)   NOT NULL DEFAULT 1,
  `metaTitle`       VARCHAR(255) DEFAULT NULL,
  `metaDescription` VARCHAR(500) DEFAULT NULL,
  `createdAt`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`),
  UNIQUE KEY `uk_categories_name_norm` (`nameNorm`),
  KEY `idx_categories_parent` (`parentId`),
  KEY `idx_categories_order` (`sortOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id`               CHAR(36)     NOT NULL,
  `name`             VARCHAR(255) NOT NULL,
  `nameNorm`         VARCHAR(255) DEFAULT NULL,
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
  `quantity`         INT          NOT NULL DEFAULT 1,
  `condition`        VARCHAR(40)  NOT NULL DEFAULT 'NEW',
  `manufacturer`     VARCHAR(190) DEFAULT NULL,
  `aircraftType`     VARCHAR(190) DEFAULT NULL,
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
  UNIQUE KEY `uk_products_name_norm` (`nameNorm`),
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

-- ---------------------------------------------------------------------------
-- Multi-warehouse inventory / lot traceability
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id`        CHAR(36)     NOT NULL,
  `name`      VARCHAR(190) NOT NULL,
  `code`      VARCHAR(30)  NOT NULL,
  `address`   VARCHAR(500) DEFAULT NULL,
  `city`      VARCHAR(100) DEFAULT NULL,
  `region`    VARCHAR(100) DEFAULT NULL,
  `country`   VARCHAR(100) DEFAULT NULL,
  `timezone`  VARCHAR(100) NOT NULL DEFAULT 'UTC',
  `phone`     VARCHAR(50)  DEFAULT NULL,
  `isAogHub`  TINYINT(1)   NOT NULL DEFAULT 0,
  `isActive`  TINYINT(1)   NOT NULL DEFAULT 1,
  `sortOrder` INT          NOT NULL DEFAULT 0,
  `notes`     TEXT         DEFAULT NULL,
  `createdAt` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_warehouses_code` (`code`),
  KEY `idx_warehouses_active_order` (`isActive`,`sortOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_lots` (
  `id`               CHAR(36)     NOT NULL,
  `productId`        CHAR(36)     NOT NULL,
  `warehouseId`      CHAR(36)     NOT NULL,
  `lotNumber`        VARCHAR(100) NOT NULL,
  `serialNumber`     VARCHAR(100) DEFAULT NULL,
  `binLocation`      VARCHAR(100) DEFAULT NULL,
  `condition`        VARCHAR(40)  DEFAULT NULL,
  `certification`    VARCHAR(255) DEFAULT NULL,
  `traceabilityRef`  VARCHAR(255) DEFAULT NULL,
  `quantityOnHand`   INT          NOT NULL DEFAULT 0,
  `quantityReserved` INT          NOT NULL DEFAULT 0,
  `receivedAt`       DATE         DEFAULT NULL,
  `expiresAt`        DATE         DEFAULT NULL,
  `status`           ENUM('ACTIVE','QUARANTINE','EXPIRED','DEPLETED') NOT NULL DEFAULT 'ACTIVE',
  `notes`            TEXT         DEFAULT NULL,
  `createdBy`        CHAR(36)     DEFAULT NULL,
  `createdAt`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inventory_lot` (`productId`,`warehouseId`,`lotNumber`),
  KEY `idx_inventory_lots_warehouse_status` (`warehouseId`,`status`),
  KEY `idx_inventory_lots_product_status` (`productId`,`status`),
  KEY `idx_inventory_lots_expiry` (`expiresAt`),
  CONSTRAINT `fk_inventory_lots_product` FOREIGN KEY (`productId`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventory_lots_warehouse` FOREIGN KEY (`warehouseId`) REFERENCES `warehouses`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_inventory_lots_creator` FOREIGN KEY (`createdBy`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_movements` (
  `id`               CHAR(36)     NOT NULL,
  `inventoryLotId`   CHAR(36)     NOT NULL,
  `productId`        CHAR(36)     NOT NULL,
  `warehouseId`      CHAR(36)     NOT NULL,
  `movementType`     ENUM('RECEIPT','ADJUST_IN','ADJUST_OUT','RESERVE','RELEASE','TRANSFER_IN','TRANSFER_OUT','DETAIL_UPDATE','WRITE_OFF') NOT NULL,
  `quantityDelta`    INT          NOT NULL DEFAULT 0,
  `reservedDelta`    INT          NOT NULL DEFAULT 0,
  `referenceType`    VARCHAR(60)  DEFAULT NULL,
  `referenceId`      VARCHAR(100) DEFAULT NULL,
  `notes`            VARCHAR(500) DEFAULT NULL,
  `actorId`          CHAR(36)     DEFAULT NULL,
  `createdAt`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_movements_lot_created` (`inventoryLotId`,`createdAt`),
  KEY `idx_inventory_movements_product_created` (`productId`,`createdAt`),
  KEY `idx_inventory_movements_warehouse_created` (`warehouseId`,`createdAt`),
  CONSTRAINT `fk_inventory_movements_lot` FOREIGN KEY (`inventoryLotId`) REFERENCES `inventory_lots`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_inventory_movements_product` FOREIGN KEY (`productId`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventory_movements_warehouse` FOREIGN KEY (`warehouseId`) REFERENCES `warehouses`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_inventory_movements_actor` FOREIGN KEY (`actorId`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Quotes / RFQ
-- ---------------------------------------------------------------------------
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
  `validUntil`      DATE          DEFAULT NULL,
  `totalAmount`     DECIMAL(14,2) DEFAULT NULL,
  `currency`        CHAR(3)       NOT NULL DEFAULT 'USD',
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
  `partNumber`     VARCHAR(120) DEFAULT NULL,
  `description`    TEXT DEFAULT NULL,
  `manufacturer`   VARCHAR(190) DEFAULT NULL,
  `condition`      VARCHAR(40)  DEFAULT NULL,
  `quantity`       INT NOT NULL DEFAULT 1,
  `specifications` TEXT DEFAULT NULL,
  `leadTime`       VARCHAR(120) DEFAULT NULL,
  `availability`   VARCHAR(120) DEFAULT NULL,
  `notes`          TEXT DEFAULT NULL,
  `unitPrice`      DECIMAL(12,2) DEFAULT NULL,
  `total`          DECIMAL(14,2) DEFAULT NULL,
  `currency`       CHAR(3) NOT NULL DEFAULT 'USD',
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
  `action`      ENUM('QUOTE_CREATED','ASSIGNED','STATUS_CHANGED','INTERNAL_NOTE_ADDED','QUOTE_UPDATED','PDF_GENERATED','EMAIL_QUEUED','EMAIL_SENT','PAYMENT_REQUESTED','PAYMENT_PAID','PAYMENT_CANCELED','PAYMENT_EXPIRED','PAYMENT_FAILED') NOT NULL,
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

-- ---------------------------------------------------------------------------
-- Stripe-hosted card payments
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id`                       CHAR(36)      NOT NULL,
  `quoteId`                  CHAR(36)      NOT NULL,
  `provider`                 VARCHAR(40)   NOT NULL DEFAULT 'stripe',
  `status`                   ENUM('PENDING','OPEN','PAID','EXPIRED','CANCELED','FAILED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `amount`                   DECIMAL(14,2) NOT NULL,
  `amountMinor`              BIGINT        NOT NULL,
  `currency`                 CHAR(3)       NOT NULL DEFAULT 'USD',
  `accessToken`              CHAR(64)      NOT NULL, -- HMAC-SHA256 of opaque customer link token
  `description`              VARCHAR(255)  DEFAULT NULL,
  `stripeCheckoutSessionId`  VARCHAR(255)  DEFAULT NULL,
  `stripePaymentIntentId`    VARCHAR(255)  DEFAULT NULL,
  `checkoutUrl`              VARCHAR(500)  DEFAULT NULL,
  `expiresAt`                DATETIME      DEFAULT NULL,
  `paidAt`                   DATETIME      DEFAULT NULL,
  `createdBy`                CHAR(36)      DEFAULT NULL,
  `lastError`                VARCHAR(1000) DEFAULT NULL,
  `createdAt`                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payments_access_token` (`accessToken`),
  UNIQUE KEY `uk_payments_stripe_session` (`stripeCheckoutSessionId`),
  KEY `idx_payments_quote_created` (`quoteId`,`createdAt`),
  KEY `idx_payments_status_expiry` (`status`,`expiresAt`),
  CONSTRAINT `fk_payments_quote` FOREIGN KEY (`quoteId`) REFERENCES `quotes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_created_by` FOREIGN KEY (`createdBy`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_events` (
  `id`              CHAR(36)     NOT NULL,
  `paymentId`       CHAR(36)     DEFAULT NULL,
  `provider`        VARCHAR(40)  NOT NULL,
  `providerEventId` VARCHAR(255) NOT NULL,
  `eventType`       VARCHAR(100) NOT NULL,
  `createdAt`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_event_provider_id` (`provider`,`providerEventId`),
  KEY `idx_payment_events_payment_created` (`paymentId`,`createdAt`),
  CONSTRAINT `fk_payment_events_payment` FOREIGN KEY (`paymentId`) REFERENCES `payments`(`id`) ON DELETE SET NULL
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

-- ---------------------------------------------------------------------------
-- Other content
-- ---------------------------------------------------------------------------
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
  `sortOrder`     INT NOT NULL DEFAULT 0,
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
  `sortOrder`  INT NOT NULL DEFAULT 0,
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

-- #############################################################################
-- 2. ADMINISTRATOR ACCOUNT (fixed UUID so blog posts can reference it)
-- #############################################################################
-- Email:    admin@halykpetroleum-kz.com
-- Password: Nigeria1234@
--
-- The password is bcrypt-hashed (cost 12). This seeded account has
-- mustChangePassword=1, so the first sign-in is forced to the password-change
-- screen. Do not leave the documented bootstrap password in use.
-- #############################################################################

/* ---------------------------------------------------------------------------
   Customer account tables (module 8: Customer accounts + parts-order history)
   --------------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `invoices` (
  `id`          CHAR(36)      NOT NULL,
  `paymentId`   CHAR(36)      NOT NULL,
  `quoteId`     CHAR(36)      DEFAULT NULL,
  `userId`      CHAR(36)      DEFAULT NULL,
  `invoiceNumber` VARCHAR(40) NOT NULL,
  `amount`      DECIMAL(14,2) NOT NULL,
  `currency`    CHAR(3)       NOT NULL DEFAULT 'USD',
  `status`      ENUM('ISSUED','PAID','REFUNDED','VOID') NOT NULL DEFAULT 'PAID',
  `pdfUrl`      VARCHAR(500)  DEFAULT NULL,
  `issuedAt`    DATETIME      DEFAULT NULL,
  `paidAt`      DATETIME      DEFAULT NULL,
  `createdAt`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invoices_number` (`invoiceNumber`),
  UNIQUE KEY `uk_invoices_payment` (`paymentId`),
  KEY `idx_invoices_quote` (`quoteId`),
  KEY `idx_invoices_user` (`userId`),
  CONSTRAINT `fk_invoices_payment` FOREIGN KEY (`paymentId`) REFERENCES `payments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoices_quote`   FOREIGN KEY (`quoteId`)   REFERENCES `quotes`(`id`)   ON DELETE SET NULL,
  CONSTRAINT `fk_invoices_user`    FOREIGN KEY (`userId`)    REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aog_dispatches` (
  `id`             CHAR(36)      NOT NULL,
  `userId`         CHAR(36)      DEFAULT NULL,
  `quoteId`        CHAR(36)      DEFAULT NULL,
  `reference`      VARCHAR(60)   NOT NULL,
  `aircraft`       VARCHAR(120)  DEFAULT NULL,
  `partDescription` TEXT         DEFAULT NULL,
  `quantity`       INT           NOT NULL DEFAULT 1,
  `priority`       ENUM('STANDARD','AOG') NOT NULL DEFAULT 'AOG',
  `status`         ENUM('REQUESTED','CONFIRMED','IN_TRANSIT','DELIVERED','CANCELLED') NOT NULL DEFAULT 'REQUESTED',
  `pickupLocation` VARCHAR(255)  DEFAULT NULL,
  `carrier`        VARCHAR(120)  DEFAULT NULL,
  `trackingNumber` VARCHAR(255)  DEFAULT NULL,
  `eta`            DATETIME      DEFAULT NULL,
  `deliveredAt`    DATETIME      DEFAULT NULL,
  `notes`          TEXT          DEFAULT NULL,
  `createdBy`      CHAR(36)      DEFAULT NULL,
  `createdAt`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_aog_reference` (`reference`),
  KEY `idx_aog_user` (`userId`),
  KEY `idx_aog_quote` (`quoteId`),
  KEY `idx_aog_status` (`status`),
  CONSTRAINT `fk_aog_user`  FOREIGN KEY (`userId`)  REFERENCES `users`(`id`)    ON DELETE SET NULL,
  CONSTRAINT `fk_aog_quote` FOREIGN KEY (`quoteId`) REFERENCES `quotes`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `email`, `password`, `firstName`, `lastName`, `role`, `company`, `isActive`, `mustChangePassword`, `emailVerified`, `createdAt`, `updatedAt`)
VALUES (
  'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
  'admin@halykpetroleum-kz.com',
  '$2b$12$XlT9QtIvi44/HE9Pf84ElOoDnG/GBvn5gxRM8fvOxU69j065wwwuS',
  'Admin',
  'User',
  'SUPER_ADMIN',
  'Halyk Petroleum',
  1,
  1,
  1,
  NOW(),
  NOW()
) ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`),
  `isActive` = VALUES(`isActive`),
  `mustChangePassword` = VALUES(`mustChangePassword`),
  `role` = VALUES(`role`),
  `updatedAt` = NOW();

-- #############################################################################
-- 3. DEFAULT ROLE PERMISSIONS
-- #############################################################################
INSERT INTO `role_permissions` (`id`,`role`,`resource`,`actions`) VALUES
(UUID(),'SUPER_ADMIN','*',JSON_ARRAY('*')),
(UUID(),'ADMIN','products',JSON_ARRAY('read','create','update','delete')),
(UUID(),'ADMIN','inventory',JSON_ARRAY('manage','read','create','update','delete')),
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
(UUID(),'ADMIN','homepage',JSON_ARRAY('read','create','update','delete','manage')),
(UUID(),'ADMIN','pages',JSON_ARRAY('read','create','update','delete','manage')),
(UUID(),'ADMIN','menus',JSON_ARRAY('read','create','update','delete','manage')),
(UUID(),'ADMIN','appearance',JSON_ARRAY('read','update','manage')),
(UUID(),'ADMIN','media',JSON_ARRAY('read','create','delete')),
(UUID(),'ADMIN','audit',JSON_ARRAY('read')),
(UUID(),'SALES','products',JSON_ARRAY('read')),
(UUID(),'SALES','categories',JSON_ARRAY('read')),
(UUID(),'SALES','quotes',JSON_ARRAY('read','create','update','status','export')),
(UUID(),'SALES','contacts',JSON_ARRAY('read','update')),
(UUID(),'ENGINEER','products',JSON_ARRAY('read','update')),
(UUID(),'ENGINEER','inventory',JSON_ARRAY('manage','read','create','update')),
(UUID(),'ENGINEER','quotes',JSON_ARRAY('read','update')),
(UUID(),'EDITOR','blog',JSON_ARRAY('read','create','update','delete')),
(UUID(),'EDITOR','news',JSON_ARRAY('read','create','update','delete')),
(UUID(),'EDITOR','faqs',JSON_ARRAY('read','create','update','delete')),
(UUID(),'EDITOR','downloads',JSON_ARRAY('read','create','update','delete')),
(UUID(),'EDITOR','industries',JSON_ARRAY('read','create','update','delete'))
ON DUPLICATE KEY UPDATE `actions`=VALUES(`actions`);

-- #############################################################################
-- 4. CATEGORIES
-- #############################################################################
INSERT INTO `categories` (`id`,`name`,`nameNorm`,`slug`,`description`,`icon`,`image`,`sortOrder`,`isActive`,`metaTitle`) VALUES
(UUID(),'Wheels & Brakes','wheels & brakes','wheels-brakes','Wheels, tires, brake assemblies and anti-skid systems for business and commercial jets.','wheel','/assets/img/products/wheels-brakes.jpg',1,1,'Aircraft Wheels & Brakes - Halyk Petroleum'),
(UUID(),'Landing Gear','landing gear','landing-gear','Complete landing gear assemblies, actuators, struts and steering components.','gear','/assets/img/products/landing-gear.jpg',2,1,'Aircraft Landing Gear - Halyk Petroleum'),
(UUID(),'Avionics','avionics','avionics','Radios, radars, displays, flight instruments, recorders and navigation systems.','radar','/assets/img/products/avionics.jpg',3,1,'Avionics & Instruments - Halyk Petroleum'),
(UUID(),'Engines & APUs','engines & apus','engines-apus','Turbofan engines, auxiliary power units and engine components.','engine','/assets/img/products/engines-apus.jpg',4,1,'Engines & APUs - Halyk Petroleum'),
(UUID(),'Flight Controls','flight controls','flight-controls','Servos, actuators, power control units and trim systems.','servo','/assets/img/products/flight-controls.jpg',5,1,'Flight Controls - Halyk Petroleum'),
(UUID(),'Hydraulics','hydraulics','hydraulics','Hydraulic pumps, valves, reservoirs and accumulators.','hydraulic','/assets/img/products/hydraulics.jpg',6,1,'Hydraulic Systems - Halyk Petroleum'),
(UUID(),'Pneumatics & Bleed Air','pneumatics & bleed air','pneumatics','Bleed air valves, pressure controllers and pneumatic components.','air','/assets/img/products/pneumatics.jpg',7,1,'Pneumatics & Bleed Air - Halyk Petroleum'),
(UUID(),'Electrical & Lighting','electrical & lighting','electrical-lighting','Generators, batteries, lights, relays and power distribution.','bolt','/assets/img/products/electrical-lighting.jpg',8,1,'Electrical & Lighting - Halyk Petroleum'),
(UUID(),'Interior & Cabin','interior & cabin','interior-cabin','Escape slides, oxygen systems, galleys and cabin equipment.','seat','/assets/img/products/interior-cabin.jpg',9,1,'Interior & Cabin - Halyk Petroleum'),
(UUID(),'Actuators & Valves','actuators & valves','actuators-valves','Linear and rotary actuators, control valves and solenoids.','valve','/assets/img/products/actuators-valves.jpg',10,1,'Actuators & Valves - Halyk Petroleum'),
(UUID(),'Fuel Systems','fuel systems','fuel-systems','Fuel pumps, indicators, valves and fuel system components.','fuel','/assets/img/products/fuel-systems.jpg',11,1,'Fuel Systems - Halyk Petroleum'),
(UUID(),'Airframe & Structures','airframe & structures','airframe','Structural components, cowlings, fairings and airframe parts.','plane','/assets/img/products/airframe.jpg',12,1,'Airframe & Structures - Halyk Petroleum');

-- #############################################################################
-- 5. INDUSTRIES (aircraft platforms supported)
-- #############################################################################
INSERT INTO `industries` (`id`,`name`,`slug`,`description`,`icon`,`sortOrder`,`isActive`,`metaTitle`,`capabilities`) VALUES
(UUID(),'Airlines & Commercial Operators','airlines-commercial','Rotables, wheels & brakes, avionics and consumables for A320, B737 and other airline fleets — exchange pools and power-by-the-hour support.','flight',1,1,'Airline Aircraft Parts Supply - Halyk Petroleum', JSON_ARRAY('Rotables','Consumables','Exchange pools','PBH support')),
(UUID(),'Business Aviation','business-aviation','Parts support for Gulfstream, Falcon, Citation, Challenger, Hawker and Learjet flight departments with rapid response and traceability.','plane',2,1,'Business Aviation Parts - Halyk Petroleum', JSON_ARRAY('Flight departments','Charter operators','Fractional fleets')),
(UUID(),'MRO & Maintenance Facilities','mro-maintenance','High-usage parts, tooling and bench stock for maintenance, repair and overhaul shops — wheels, brakes, hydraulics, avionics and airframe components.','tools',3,1,'MRO Parts Supply - Halyk Petroleum', JSON_ARRAY('Bench stock','Repair management','Tooling','Heavy checks')),
(UUID(),'Cargo & Logistics Operators','cargo-logistics','Parts programs for freighter fleets and cargo operators focused on dispatch reliability and fast AOG turnaround.','package',4,1,'Cargo Aircraft Parts - Halyk Petroleum', JSON_ARRAY('Freighters','Dispatch reliability','AOG turnaround')),
(UUID(),'Military & Government','military-government','Defence-grade parts procurement, export documentation and controlled-substance handling for government and military operators.','shield',5,1,'Defence Aviation Parts - Halyk Petroleum', JSON_ARRAY('Defence procurement','Export control','Controlled goods')),
(UUID(),'Helicopter Operators','helicopter-operators','Dynamic components, rotor parts and rotables for civil helicopter and EMS operators with full traceability paperwork.','helicopter',6,1,'Helicopter Parts - Halyk Petroleum', JSON_ARRAY('Dynamic components','Rotor parts','EMS operators')),
(UUID(),'AOG & Emergency Sourcing','aog-emergency','24/7 AOG desk, global supplier network of 2,000+ vetted sources, same-day quoting and hand-carry logistics for grounded aircraft.','alarm',7,1,'AOG Parts Support - Halyk Petroleum', JSON_ARRAY('24/7 desk','Hand-carry logistics','Emergency sourcing')),
(UUID(),'OEM & Tier-1 Suppliers','oem-tier1','PMA, OEM distribution and tier-1 component sourcing with manufacturer traceability and certificate management.','certificate',8,1,'OEM Aircraft Components - Halyk Petroleum', JSON_ARRAY('OEM distribution','PMA parts','Traceability')),
(UUID(),'Gulfstream','gulfstream','New, overhauled and used parts for Gulfstream GII through G700 business jets.','plane',11,1,'Gulfstream Parts - Halyk Petroleum', JSON_ARRAY('GII','GIII','GIV','GV','G450','G550','G650','G700')),
(UUID(),'Dassault Falcon','dassault-falcon','Rotables, consumables and airframe parts for Falcon 10, 20, 50, 900 and 2000.','plane',12,1,'Dassault Falcon Parts - Halyk Petroleum', JSON_ARRAY('Falcon 10','Falcon 20','Falcon 50','Falcon 900','Falcon 2000','Falcon 7X')),
(UUID(),'Cessna Citation','cessna-citation','Parts for Citation I, II, III, V, X, Excel, Sovereign and Latitude.','plane',13,1,'Cessna Citation Parts - Halyk Petroleum', JSON_ARRAY('Citation I','Citation II','Citation III','Citation V','Citation X','Sovereign')),
(UUID(),'Bombardier Challenger','challenger','Support for Challenger 300, 600, 601, 604, 605 and 650 families.','plane',14,1,'Challenger Parts - Halyk Petroleum', JSON_ARRAY('Challenger 300','Challenger 600','Challenger 601','Challenger 604','Challenger 650')),
(UUID(),'Hawker','hawker','Hawker 700, 800, 800XP, 850XP and 900XP parts and components.','plane',15,1,'Hawker Parts - Halyk Petroleum', JSON_ARRAY('Hawker 700','Hawker 800','Hawker 800XP','Hawker 850XP','Hawker 900XP')),
(UUID(),'Learjet','learjet','Parts for Learjet 31, 35, 40, 45, 55, 60 and 75.','plane',16,1,'Learjet Parts - Halyk Petroleum', JSON_ARRAY('Learjet 35','Learjet 40','Learjet 45','Learjet 55','Learjet 60','Learjet 75')),
(UUID(),'Boeing','boeing','Commercial aircraft parts for the 737, 747, 757, 767, 777 and 787 fleets.','plane',17,1,'Boeing Parts - Halyk Petroleum', JSON_ARRAY('Boeing 737','Boeing 747','Boeing 757','Boeing 767','Boeing 777','Boeing 787')),
(UUID(),'Airbus','airbus','Commercial aircraft parts for the A318, A319, A320, A321, A330 and A350 families.','plane',18,1,'Airbus Parts - Halyk Petroleum', JSON_ARRAY('A318','A319','A320','A321','A330','A350')),
(UUID(),'Embraer','embraer','Parts for Embraer ERJ, E-Jet and Praetor business jet families.','plane',19,1,'Embraer Parts - Halyk Petroleum', JSON_ARRAY('ERJ 135','ERJ 145','E175','E190','Phenom 300','Praetor 600')),
(UUID(),'Pilatus','pilatus','Support for the Pilatus PC-12 turboprop and PC-24 jet.','plane',20,1,'Pilatus Parts - Halyk Petroleum', JSON_ARRAY('PC-12','PC-24'));
-- Canonical artwork for every industry / aircraft-platform page: each platform
-- gets its own banner from /assets/img/industries/<slug>.jpg instead of sharing
-- one generic photo. (Migration 012 applies the same repair to live databases.)
UPDATE `industries`
   SET `image` = CONCAT('/assets/img/industries/', `slug`, '.jpg')
 WHERE `image` IS NULL
   AND `slug` IN ('gulfstream','dassault-falcon','cessna-citation','challenger',
                  'hawker','learjet','boeing','airbus','embraer','pilatus',
                  'airlines-commercial','business-aviation','mro-maintenance',
                  'cargo-logistics','military-government','helicopter-operators',
                  'aog-emergency','oem-tier1');


-- #############################################################################
-- 6. PRODUCTS
-- #############################################################################
INSERT INTO `products`
  (`id`,`name`,`nameNorm`,`slug`,`sku`,`description`,`shortDescription`,`categoryId`,
   `industryIds`,`material`,`pressure`,`temperature`,`voltage`,`dimensions`,`weight`,
   `certifications`,`availability`,`quantity`,`condition`,`manufacturer`,`aircraftType`,
   `price`,`featured`,`isActive`,`views`,`metaTitle`)
SELECT
  UUID(),'Main Landing Gear Wheel Assembly','main landing gear wheel assembly','main-landing-gear-wheel-2612201-2','2612201-2',
  'Goodrich main landing gear wheel assembly for Gulfstream GIV/GV. Fully inspected, 4 wheels available. Includes bearings and lug nuts. Traceable to source, ships with export documentation.',
  'MLG wheel assembly, inspected and ready to ship.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','EASA Form 1'),
  'IN_STOCK',4,'USED','Goodrich','Gulfstream GIV / GV',
  14850.00,1,1,214,'Main Landing Gear Wheel Assembly - 2612201-2'
UNION ALL SELECT
  UUID(),'Main Wheel & Brake Assembly (Steel)','main wheel & brake assembly (steel)','main-wheel-brake-2-1553-5','2-1553-5',
  'BFGoodrich main wheel and steel brake assembly for Cessna Citation II. New condition, zero time since overhaul. Includes wheel halves, brake discs and torque plate.',
  'Wheel and steel brake assembly, new, for Citation II.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','BFGoodrich','Cessna Citation II',
  8950.00,1,1,187,'Main Wheel & Brake Assembly - 2-1553-5'
UNION ALL SELECT
  UUID(),'Carbon Brake Assembly','carbon brake assembly','carbon-brake-2612401-1','2612401-1',
  'Goodrich carbon brake assembly for Gulfstream G450. Low-time heat stack, serviceable condition, complete with torque plate and hardware kit. Carbon brakes save ~700 lb per aircraft.',
  'Carbon brake assembly, low-time heat stack.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','EASA Form 1'),
  'IN_STOCK',1,'NEW','Goodrich','Gulfstream G450',
  32400.00,1,1,156,'Carbon Brake Assembly - 2612401-1'
UNION ALL SELECT
  UUID(),'Nose Wheel Assembly','nose wheel assembly','nose-wheel-208-150-0','208-150-0',
  'Goodyear nose wheel assembly with tire, for Dassault Falcon 50. New tire mounted on inspected wheel. Six units available, all zero-time.',
  'Nose wheel with new tire, six available.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',6,'NEW','Goodyear','Dassault Falcon 50',
  2150.00,0,1,98,'Nose Wheel Assembly - 208-150-0'
UNION ALL SELECT
  UUID(),'Main Gear Tire (Flight Leader)','main gear tire (flight leader)','main-gear-tire-132-101-0','132-101-0',
  'Goodyear Flight Leader main gear tire for Citation and Hawker aircraft. 18-ply rating, new, manufactured within the last 18 months. Eight tires in stock.',
  'Main gear tire, 18-ply, new, eight in stock.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation'),(SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',8,'NEW','Goodyear','Citation / Hawker',
  1890.00,0,1,143,'Main Gear Tire - 132-101-0'
UNION ALL SELECT
  UUID(),'Anti-Skid Control Unit','anti-skid control unit','anti-skid-control-20-57-03','20-57-03',
  'Hydro-Aire Mark III anti-skid control unit for Bombardier Challenger 600/601. Overhauled with test certificate, 5,000-cycle warranty. Drop-in replacement, no wiring changes.',
  'Overhauled anti-skid control unit with warranty.',
  (SELECT `id` FROM `categories` WHERE `slug`='wheels-brakes' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='challenger')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','OHC test cert'),
  'IN_STOCK',1,'OHC','Hydro-Aire','Challenger 600/601',
  6750.00,0,1,87,'Anti-Skid Control Unit - 20-57-03'
UNION ALL SELECT
  UUID(),'Nose Landing Gear Assembly','nose landing gear assembly','nose-landing-gear-9001252-3','9001252-3',
  'Messier-Dowty nose landing gear assembly for Dassault Falcon 2000. Serviceable, complete with steering collar and drag brace. Ultrasonic inspection current. Immediate AOG dispatch available.',
  'Complete nose landing gear, serviceable.',
  (SELECT `id` FROM `categories` WHERE `slug`='landing-gear' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','NDT current'),
  'IN_STOCK',1,'USED','Messier-Dowty','Dassault Falcon 2000',
  24500.00,1,1,176,'Nose Landing Gear Assembly - 9001252-3'
UNION ALL SELECT
  UUID(),'Main Landing Gear Actuator','main landing gear actuator','main-landing-gear-actuator-9001340-5','9001340-5',
  'Messier-Dowty main landing gear actuator for Falcon 900. Overhauled, bench-tested with report. Corrosion protection per latest SB. Two units available.',
  'MLG actuator, overhauled with bench test report.',
  (SELECT `id` FROM `categories` WHERE `slug`='landing-gear' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('EASA Form 1'),
  'IN_STOCK',2,'OHC','Messier-Dowty','Dassault Falcon 900',
  12400.00,0,1,121,'Main Landing Gear Actuator - 9001340-5'
UNION ALL SELECT
  UUID(),'Nose Wheel Steering Actuator','nose wheel steering actuator','nose-wheel-steering-46-162-01','46-162-01',
  'Parker Aerospace nose wheel steering actuator for Learjet 45. New manufacture, current revision, with placards and hardware. Two units in stock.',
  'New nose wheel steering actuator, current rev.',
  (SELECT `id` FROM `categories` WHERE `slug`='landing-gear' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='learjet')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','Parker Aerospace','Learjet 45',
  7850.00,0,1,92,'Nose Wheel Steering Actuator - 46-162-01'
UNION ALL SELECT
  UUID(),'Landing Gear Control Unit','landing gear control unit','landing-gear-control-82-345-2','82-345-2',
  'Collins landing gear control unit for Hawker 800 series. Overhauled with functional test. Includes gear-up warning inputs. Exchanged units accepted.',
  'Landing gear control unit, overhauled.',
  (SELECT `id` FROM `categories` WHERE `slug`='landing-gear' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Collins Aerospace','Hawker 800 / 800XP',
  9600.00,0,1,74,'Landing Gear Control Unit - 82-345-2'
UNION ALL SELECT
  UUID(),'VHF-4000 Comm Radio','vhf-4000 comm radio','vhf-4000-comm-radio','622-8920-005',
  'Collins Aerospace VHF-4000 communications transceiver for Hawker 800XP and Challenger. New, with rack and installation kit. 8.33 kHz spacing capable.',
  'New VHF-4000 comm radio with rack.',
  (SELECT `id` FROM `categories` WHERE `slug`='avionics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker'),(SELECT `id` FROM `industries` WHERE `slug`='challenger')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','TSO C37d'),
  'IN_STOCK',3,'NEW','Collins Aerospace','Hawker 800XP / Challenger',
  5400.00,1,1,233,'VHF-4000 Comm Radio - 622-8920-005'
UNION ALL SELECT
  UUID(),'Primus 660 Weather Radar','primus 660 weather radar','primus-660-weather-radar','830-0141-100',
  'Honeywell Primus 660 color weather radar with stabilized antenna for Citation X. New in box, latest software revision, includes radome adapter kit.',
  'New Primus 660 weather radar system.',
  (SELECT `id` FROM `categories` WHERE `slug`='avionics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'NEW','Honeywell','Cessna Citation X',
  28500.00,0,1,164,'Primus 660 Weather Radar - 830-0141-100'
UNION ALL SELECT
  UUID(),'LASEREF IV Inertial Reference','laseref iv inertial reference','laseref-iv-inertial-reference','46594-0304-0301',
  'Honeywell LASEREF IV inertial reference system for Gulfstream GIV/GV. Overhauled with 2,500-hour warranty, current IRU software. Includes mounting tray.',
  'Overhauled LASEREF IV IRS with warranty.',
  (SELECT `id` FROM `categories` WHERE `slug`='avionics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('EASA Form 1','FAA 8130-3'),
  'IN_STOCK',1,'OHC','Honeywell','Gulfstream GIV / GV',
  45000.00,0,1,118,'LASEREF IV Inertial Reference - 46594-0304-0301'
UNION ALL SELECT
  UUID(),'KMD-850 Multi-Function Display','kmd-850 multi-function display','kmd-850-multifunction-display','010-00866-02',
  'BendixKing KMD-850 multi-function display with GPS/WAAS and terrain. New, with data card and install kit. Two units available.',
  'New KMD-850 MFD with terrain and WAAS.',
  (SELECT `id` FROM `categories` WHERE `slug`='avionics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','BendixKing','Citation / Various',
  14900.00,0,1,139,'KMD-850 Multi-Function Display - 010-00866-02'
UNION ALL SELECT
  UUID(),'Flight Data Recorder','flight data recorder','flight-data-recorder-980-4700-043','980-4700-043',
  'Honeywell solid-state flight data recorder for Boeing 737. New, 25-hour recording, with mounting rack and underwater locator beacon. Export-ready.',
  'Solid-state FDR for 737, new with rack.',
  (SELECT `id` FROM `categories` WHERE `slug`='avionics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='boeing')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','TSO C124'),
  'MADE_TO_ORDER',1,'NEW','Honeywell','Boeing 737',
  12500.00,0,1,81,'Flight Data Recorder - 980-4700-043'
UNION ALL SELECT
  UUID(),'GTCP36-150 Auxiliary Power Unit','gtcp36-150 auxiliary power unit','gtcp36-150-apu','3606171-1',
  'Honeywell GTCP36-150 APU for Gulfstream GIV. Low-cycle used unit with complete logbooks, recently hot-section inspected. Includes ECU and harness.',
  'Low-cycle used APU with logbooks.',
  (SELECT `id` FROM `categories` WHERE `slug`='engines-apus' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','Logbook review'),
  'IN_STOCK',1,'USED','Honeywell','Gulfstream GIV',
  88000.00,1,1,205,'GTCP36-150 APU - 3606171-1'
UNION ALL SELECT
  UUID(),'CFE738-1-1B Turbofan Engine','cfe738-1-1b turbofan engine','cfe738-1-1b-turbofan','CFE738-1-1B',
  'GE/Honeywell CFE738-1-1B turbofan engine for Falcon 2000. Used, serviceable with current borescope and mid-life HSI. Complete with QEC, inlet and reverser kit.',
  'Serviceable CFE738 turbofan with QEC.',
  (SELECT `id` FROM `categories` WHERE `slug`='engines-apus' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','Engine logbooks'),
  'MADE_TO_ORDER',1,'USED','GE / Honeywell','Dassault Falcon 2000',
  450000.00,1,1,167,'CFE738-1-1B Turbofan Engine'
UNION ALL SELECT
  UUID(),'TFE731-5BR-1C Engine','tfe731-5br-1c engine','tfe731-5br-1c-engine','3131775-1',
  'Honeywell TFE731-5BR-1C turbofan for Falcon 900B. Overhauled with 1,000-hour warranty, includes ECU, sensors and installation kit. Ready to hang and fly.',
  'Overhauled TFE731-5BR with warranty.',
  (SELECT `id` FROM `categories` WHERE `slug`='engines-apus' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('EASA Form 1'),
  'MADE_TO_ORDER',1,'OHC','Honeywell','Dassault Falcon 900B',
  385000.00,0,1,94,'TFE731-5BR-1C Engine'
UNION ALL SELECT
  UUID(),'Engine Driven Hydraulic Pump','engine driven hydraulic pump','edp-hydraulic-pump','793-2583-001',
  'Eaton engine driven hydraulic pump for Gulfstream GIV. New, 3,000 PSI, SAE-A mount. Two units in stock with pressure test certificates.',
  'New EDP hydraulic pump, 3,000 PSI.',
  (SELECT `id` FROM `categories` WHERE `slug`='hydraulics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','Eaton Aerospace','Gulfstream GIV / GV',
  9750.00,1,1,188,'Engine Driven Hydraulic Pump - 793-2583-001'
UNION ALL SELECT
  UUID(),'Hydraulic System Valve','hydraulic system valve','hydraulic-system-valve-25d-660','25D-660',
  'Parker Hannifin hydraulic system valve for business jet utility systems. New, 4-way, 3,000 PSI, solenoid operated, with connector. Four in stock.',
  'New 4-way hydraulic valve, 3,000 PSI.',
  (SELECT `id` FROM `categories` WHERE `slug`='hydraulics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker'),(SELECT `id` FROM `industries` WHERE `slug`='learjet')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',4,'NEW','Parker Hannifin','Hawker / Learjet',
  4300.00,0,1,77,'Hydraulic System Valve - 25D-660'
UNION ALL SELECT
  UUID(),'Rudder Servo Actuator','rudder servo actuator','rudder-servo-523-0771-517','523-0771-517',
  'Collins rudder servo actuator for Challenger 604. Overhauled with bench test report, current SB compliance. Includes linkage hardware.',
  'Overhauled rudder servo, bench tested.',
  (SELECT `id` FROM `categories` WHERE `slug`='flight-controls' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='challenger')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Collins Aerospace','Challenger 604',
  18750.00,1,1,132,'Rudder Servo Actuator - 523-0771-517'
UNION ALL SELECT
  UUID(),'Elevator Trim Actuator','elevator trim actuator','elevator-trim-actuator','312-0025-010',
  'Parker elevator trim actuator for Citation III. New, current revision, with gearbox and position sensor. Two units in stock.',
  'New elevator trim actuator with sensor.',
  (SELECT `id` FROM `categories` WHERE `slug`='flight-controls' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','Parker Aerospace','Cessna Citation III',
  5900.00,0,1,85,'Elevator Trim Actuator - 312-0025-010'
UNION ALL SELECT
  UUID(),'Rudder Power Control Unit','rudder power control unit','rudder-pcu-692-0241-001','692-0241-001',
  'Parker rudder power control unit for Boeing 737. Overhauled, complete with test data and 5,000-flight-hour warranty. Exchange core accepted.',
  'Overhauled rudder PCU for 737.',
  (SELECT `id` FROM `categories` WHERE `slug`='flight-controls' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='boeing')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Parker Hannifin','Boeing 737',
  22000.00,0,1,66,'Rudder Power Control Unit - 692-0241-001'
UNION ALL SELECT
  UUID(),'Bleed Air Regulating Valve','bleed air regulating valve','bleed-air-regulating-valve','3070211-1',
  'Honeywell bleed air regulating valve for Challenger 601/604. New, with anti-ice bleed control, current SB. Two units in stock.',
  'New bleed air regulating valve.',
  (SELECT `id` FROM `categories` WHERE `slug`='pneumatics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='challenger')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','Honeywell','Challenger 601 / 604',
  8900.00,0,1,102,'Bleed Air Regulating Valve - 3070211-1'
UNION ALL SELECT
  UUID(),'Cabin Pressure Controller','cabin pressure controller','cabin-pressure-controller','8927-14',
  'Honeywell digital cabin pressure controller for Gulfstream GII/GIII. Overhauled, bench tested, includes outflow valve interface card.',
  'Overhauled digital cabin pressure controller.',
  (SELECT `id` FROM `categories` WHERE `slug`='pneumatics' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Honeywell','Gulfstream GII / GIII',
  6900.00,0,1,58,'Cabin Pressure Controller - 8927-14'
UNION ALL SELECT
  UUID(),'Fuel Boost Pump','fuel boost pump','fuel-boost-pump','501-072-020',
  'Eaton AC fuel boost pump for Hawker 800. New, 115 VAC, with check valve and mount gasket. Three units in stock.',
  'New AC fuel boost pump with check valve.',
  (SELECT `id` FROM `categories` WHERE `slug`='fuel-systems' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',3,'NEW','Eaton Aerospace','Hawker 800',
  6450.00,1,1,119,'Fuel Boost Pump - 501-072-020'
UNION ALL SELECT
  UUID(),'Fuel Quantity Indicator','fuel quantity indicator','fuel-quantity-indicator','900-1120-02',
  'Collins fuel quantity indicator for Boeing 737. Used, serviceable, bench checked. Two units available with test data.',
  'Serviceable fuel quantity indicator.',
  (SELECT `id` FROM `categories` WHERE `slug`='fuel-systems' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='boeing')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'USED','Collins Aerospace','Boeing 737',
  3750.00,0,1,49,'Fuel Quantity Indicator - 900-1120-02'
UNION ALL SELECT
  UUID(),'Starter / Generator','starter / generator','starter-generator','763-0411-1',
  'Hamilton Sundstrand starter/generator for Hawker 700. Overhauled with 800-hour warranty, includes regulator and cooling fan. Exchange unit available.',
  'Overhauled starter/generator with warranty.',
  (SELECT `id` FROM `categories` WHERE `slug`='electrical-lighting' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Hamilton Sundstrand','Hawker 700',
  28000.00,1,1,145,'Starter / Generator - 763-0411-1'
UNION ALL SELECT
  UUID(),'Ni-Cd Main Battery','ni-cd main battery','nicd-main-battery','4454-35',
  'Marathon Ni-Cd main battery for business and regional jets. New, 24 V, 35 Ah, with thermal fuse. Five batteries in stock, shipped charged.',
  'New 24 V Ni-Cd main battery, five in stock.',
  (SELECT `id` FROM `categories` WHERE `slug`='electrical-lighting' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker'),(SELECT `id` FROM `industries` WHERE `slug`='learjet')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',5,'NEW','Marathon Power','Hawker / Learjet',
  6300.00,0,1,133,'Ni-Cd Main Battery - 4454-35'
UNION ALL SELECT
  UUID(),'Landing Light Assembly','landing light assembly','landing-light-assembly','407-0120-04',
  'Grimes landing light assembly for Falcon 50. New, sealed beam, with mounting bracket and gasket. Four in stock.',
  'New landing light with bracket.',
  (SELECT `id` FROM `categories` WHERE `slug`='electrical-lighting' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='dassault-falcon')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',4,'NEW','Grimes / Collins','Dassault Falcon 50',
  1850.00,0,1,71,'Landing Light Assembly - 407-0120-04'
UNION ALL SELECT
  UUID(),'Emergency Oxygen System','emergency oxygen system','emergency-oxygen-system','850930-01',
  'Kidde crew emergency oxygen system for Learjet 60. New, complete with regulator, masks and cylinder. Two systems in stock, pressure tested.',
  'New crew emergency oxygen system.',
  (SELECT `id` FROM `categories` WHERE `slug`='interior-cabin' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='learjet')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3','TSO C64'),
  'IN_STOCK',2,'NEW','Kidde Aerospace','Learjet 60',
  4100.00,0,1,63,'Emergency Oxygen System - 850930-01'
UNION ALL SELECT
  UUID(),'Emergency Escape Slide','emergency escape slide','emergency-escape-slide','630-1580-01',
  'Air Cruisers emergency escape slide for Gulfstream GIV. Used, serviceable, current packing date, includes deployment bag and hardware.',
  'Serviceable escape slide, current pack.',
  (SELECT `id` FROM `categories` WHERE `slug`='interior-cabin' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'USED','Air Cruisers','Gulfstream GIV',
  9800.00,0,1,55,'Emergency Escape Slide - 630-1580-01'
UNION ALL SELECT
  UUID(),'Cabin Window Assembly','cabin window assembly','cabin-window-assembly','190-1260-11',
  'Cabin window assembly (inner pane) for Hawker 800XP. New, with gasket kit and anti-fog coating. Two units in stock.',
  'New cabin window inner pane.',
  (SELECT `id` FROM `categories` WHERE `slug`='interior-cabin' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'NEW','Hawker Beechcraft','Hawker 800XP',
  7250.00,0,1,44,'Cabin Window Assembly - 190-1260-11'
UNION ALL SELECT
  UUID(),'Flap Actuator','flap actuator','flap-actuator','12-425-01',
  'Parker flap actuator for Learjet 35/36. Overhauled with test report, current gearbox revision. Includes drive arm and mounting bolts.',
  'Overhauled flap actuator with test report.',
  (SELECT `id` FROM `categories` WHERE `slug`='actuators-valves' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='learjet')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'OHC','Parker Aerospace','Learjet 35 / 36',
  11500.00,1,1,109,'Flap Actuator - 12-425-01'
UNION ALL SELECT
  UUID(),'Solenoid Shutoff Valve','solenoid shutoff valve','solenoid-shutoff-valve','173-104-07',
  'Solenoid operated fuel shutoff valve for Gulfstream and Hawker fuel systems. New, 28 VDC, with connector and mounting plate. Six in stock.',
  'New solenoid fuel shutoff valve, six in stock.',
  (SELECT `id` FROM `categories` WHERE `slug`='actuators-valves' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='gulfstream'),(SELECT `id` FROM `industries` WHERE `slug`='hawker')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',6,'NEW','Eaton Aerospace','Gulfstream / Hawker',
  2950.00,0,1,83,'Solenoid Shutoff Valve - 173-104-07'
UNION ALL SELECT
  UUID(),'Engine Cowling (RH)','engine cowling (rh)','engine-cowling-rh','310-0452-05',
  'Right-hand engine cowling for Citation III. Serviceable composite, minor cosmetic damage only, includes cowl lip and hinges. AOG dispatch available.',
  'Serviceable RH engine cowling, AOG-ready.',
  (SELECT `id` FROM `categories` WHERE `slug`='airframe' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='cessna-citation')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',1,'USED','Cessna','Cessna Citation III',
  8900.00,0,1,52,'Engine Cowling (RH) - 310-0452-05'
UNION ALL SELECT
  UUID(),'APU Fire Extinguisher Bottle','apu fire extinguisher bottle','apu-fire-extinguisher','830121-01',
  'Kidde APU fire extinguisher bottle for Challenger 601/604. Overhauled with new discharge cartridge, hydro test current. Two units available.',
  'Overhauled APU fire bottle, hydro current.',
  (SELECT `id` FROM `categories` WHERE `slug`='airframe' LIMIT 1),
  JSON_ARRAY((SELECT `id` FROM `industries` WHERE `slug`='challenger')),
  NULL,NULL,NULL,NULL,NULL,NULL,
  JSON_ARRAY('FAA 8130-3'),
  'IN_STOCK',2,'OHC','Kidde Aerospace','Challenger 601 / 604',
  5600.00,0,1,38,'APU Fire Extinguisher Bottle - 830121-01';



-- #############################################################################
-- 6b. PRODUCT IMAGES — one unique primary illustration per catalog part
-- #############################################################################
-- Every product gets its own /assets/img/products/<slug>.jpg row in the data
-- layer so catalog cards never all share a single category image. Safe to
-- re-run: skips products that already have any image row.
INSERT INTO `product_images` (`id`,`productId`,`url`,`alt`,`sortOrder`,`isPrimary`,`createdAt`)
SELECT UUID(), p.`id`,
       CONCAT('/assets/img/products/', p.`slug`, '.jpg'),
       p.`name`,
       0, 1, NOW()
  FROM `products` p
 WHERE p.`slug` IS NOT NULL AND p.`slug` <> ''
   AND NOT EXISTS (SELECT 1 FROM `product_images` pi WHERE pi.`productId` = p.`id`);

-- #############################################################################
-- 7. WAREHOUSES + OPENING LOT BALANCES
-- #############################################################################
INSERT INTO `warehouses` (`id`,`name`,`code`,`address`,`city`,`region`,`country`,`timezone`,`phone`,`isAogHub`,`isActive`,`sortOrder`,`notes`) VALUES
(UUID(),'Dallas AOG Hub','DAL-AOG','Hangar 4, Dallas Executive Airport','Dallas','Texas','USA','America/Chicago','+1 (214) 350-0107',1,1,1,'24/7 AOG dispatch and primary receiving hub'),
(UUID(),'Amsterdam EU Hub','AMS-EU','Schiphol-Rijk logistics campus','Amsterdam','North Holland','Netherlands','Europe/Amsterdam','+31 20 000 0000',0,1,2,'European consolidation and export hub');

INSERT INTO `inventory_lots` (`id`,`productId`,`warehouseId`,`lotNumber`,`serialNumber`,`binLocation`,`condition`,`certification`,`traceabilityRef`,`quantityOnHand`,`quantityReserved`,`receivedAt`,`expiresAt`,`status`,`notes`)
SELECT UUID(), p.id,
       CASE WHEN p.slug IN ('vhf-4000-comm-radio','primus-660-weather-radar','laseref-iv-inertial-reference')
            THEN (SELECT id FROM warehouses WHERE code='AMS-EU' LIMIT 1)
            ELSE (SELECT id FROM warehouses WHERE code='DAL-AOG' LIMIT 1) END,
       p.sku, NULL, 'OPENING', p.condition, 'FAA 8130-3 / EASA Form 1', p.sku,
       p.quantity, 0, CURRENT_DATE,
       CASE WHEN p.slug='main-gear-tire-132-101-0' THEN '2027-06-30' ELSE NULL END,
       CASE WHEN p.quantity > 0 THEN 'ACTIVE' ELSE 'DEPLETED' END,
       'Opening balance migrated from catalog seed.'
FROM products p;

INSERT INTO `inventory_movements` (`id`,`inventoryLotId`,`productId`,`warehouseId`,`movementType`,`quantityDelta`,`reservedDelta`,`notes`,`createdAt`)
SELECT UUID(), l.id, l.productId, l.warehouseId, 'RECEIPT', l.quantityOnHand, 0, 'Opening balance from catalog seed.', NOW()
FROM inventory_lots l;

-- #############################################################################
-- 8. FAQs
-- #############################################################################
INSERT INTO `faqs` (`id`,`question`,`answer`,`category`,`sortOrder`,`isActive`) VALUES
(UUID(),'What is your typical lead time?','Stock parts ship the same or next business day. AOG (Aircraft on Ground) requests are prioritized and dispatched within hours, 24/7.','Lead Times',1,1),
(UUID(),'What do NEW, OHC, USED and SERVICEABLE mean?','NEW is unused manufacturer-new stock. OHC means Overhauled — disassembled, repaired to manufacturer limits with a bench test report. USED parts are removed serviceable with traceable history. SERVICEABLE parts are inspected and ready to install.','Part Conditions',2,1),
(UUID(),'Do parts come with certification?','Yes. Every part ships with FAA Form 8130-3 and/or EASA Form 1, full traceability to the last operator, and our own inspection certificate. Copies of logbook pages are provided on request.','Certification',3,1),
(UUID(),'Do you buy or trade surplus parts?','We buy outright and trade surplus rotables, engines, APUs and airframe parts. Email your inventory list to sales@halykpetroleum-kz.com — we typically respond within 24 hours with an offer.','Selling Parts',4,1),
(UUID(),'What is the warranty on parts?','All parts carry a 12-month warranty from shipment against defects in material and workmanship. Overhauled units carry an extended warranty as stated on the quotation.','Warranty',5,1),
(UUID(),'Do you ship internationally?','We ship worldwide with full export documentation, certificates of origin and ATA Carnet support. Choose FOB, CIF or DDP — we manage customs paperwork for you.','Logistics',6,1),
(UUID(),'How fast do I get a quote?','Standard RFQs are answered within 24 business hours. Urgent and AOG requests are answered within 2 hours during business hours, 24/7 for AOG.','Quoting',7,1),
(UUID(),'Can you source parts I cannot find?','Yes. If the part is not in our catalog, our sourcing desk searches our global supplier network of over 2,000 vetted aviation suppliers and OEMs. Most special requests are sourced within 48 hours.','Sourcing',8,1);

-- #############################################################################
-- 8. TESTIMONIALS
-- #############################################################################
INSERT INTO `testimonials` (`id`,`name`,`title`,`company`,`content`,`rating`,`avatar`,`industry`,`isActive`,`featured`) VALUES
(UUID(),'Mark Hendricks','Director of Maintenance','Aerovista Charter Group','Halyk Petroleum sourced a complete set of wheels and brakes for our Gulfstream fleet at 30% below OEM pricing — all with full 8130-3 paperwork. Our AOG team has their number on speed dial.',5,'/assets/img/reviews/mark-hendricks.jpg','Gulfstream',1,1),
(UUID(),'Sofia Marchetti','Procurement Manager','Meridian Air Lines','We have standardized our Falcon 2000 consumables on Halyk Petroleum. Consistent quality, predictable lead times, and every part arrives with traceable certification.',5,'/assets/img/reviews/sofia-marchetti.jpg','Dassault Falcon',1,1),
(UUID(),'David Okafor','Chief Pilot','TransContinental Air','Their APU desk found us a low-cycle GTCP36-150 in three days during an AOG. The unit was better than described and the logbook review was impeccable.',5,'/assets/img/reviews/david-okafor.jpg','Gulfstream',1,1),
(UUID(),'Elena Kovač','Operations Director','Skyline Business Jets','From RFQ to delivery in four days on a Challenger 604 rudder servo. The exchange program is excellent — they shipped first and took our core in return.',5,'/assets/img/reviews/elena-kovac.jpg','Challenger',1,0);

-- #############################################################################
-- 9. PARTNERS
-- #############################################################################
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

-- #############################################################################
-- 10. APPLICATION SETTINGS
-- #############################################################################
INSERT INTO `settings` (`id`,`key`,`value`,`type`,`group`,`sortOrder`) VALUES
(UUID(),'site_name','Halyk Petroleum','STRING','GENERAL',1),
(UUID(),'site_tagline','Aircraft Parts Marketplace','STRING','GENERAL',2),
(UUID(),'hero_title','Find the Right Jet Part. Fast.','STRING','HERO',1),
(UUID(),'hero_subtitle','Search thousands of new, overhauled and used aircraft parts for Gulfstream, Falcon, Citation, Challenger, Hawker, Learjet, Boeing and Airbus. Every part certified and traceable.','STRING','HERO',2),
(UUID(),'hero_cta_primary','Request a Quote','STRING','HERO',3),
(UUID(),'hero_cta_secondary','Browse Parts','STRING','HERO',4),
(UUID(),'about_intro','Halyk Petroleum is a global marketplace for new, overhauled and used aircraft parts. From our facility at Dallas Executive Airport, we supply rotables, consumables, engines, APUs and airframe parts to flight departments, airlines, MROs and brokers in over 120 countries — every part shipped with full FAA/EASA certification and traceability.','TEXT','ABOUT',1),
(UUID(),'stats_parts','34000','INT','STATS',1),
(UUID(),'stats_aircraft','150','INT','STATS',2),
(UUID(),'stats_countries','120','INT','STATS',3),
(UUID(),'stats_aog','24','INT','STATS',4),
(UUID(),'contact_email','sales@halykpetroleum-kz.com','STRING','CONTACT',1),
(UUID(),'support_email','support@halykpetroleum-kz.com','STRING','CONTACT',2),
(UUID(),'rfq_email','rfq@halykpetroleum-kz.com','STRING','CONTACT',3),
(UUID(),'phone','+1 (214) 350-0107','STRING','CONTACT',4),
(UUID(),'address','Hangar 4, Dallas Executive Airport, Dallas, TX 75209, USA','STRING','CONTACT',5),
(UUID(),'social','{\"linkedin\":\"https://linkedin.com/company/halykpetroleum\",\"twitter\":\"https://twitter.com/halykpetroleum\",\"facebook\":\"https://facebook.com/halykpetroleum\",\"youtube\":\"https://youtube.com/@halykpetroleum\"}','JSON','CONTACT',6),
(UUID(),'rfq_enabled','1','BOOL','RFQ',1),
(UUID(),'rfq_rate_limit_per_hour','5','INT','RFQ',2),
(UUID(),'rfq_admin_email','admin@halykpetroleum-kz.com','STRING','RFQ',3),
(UUID(),'stripe_payments_enabled','0','BOOL','PAYMENTS',1),
(UUID(),'stripe_currency','USD','STRING','PAYMENTS',2),
(UUID(),'stripe_checkout_ttl_hours','24','INT','PAYMENTS',3),
(UUID(),'seo_default_title','Halyk Petroleum — Aircraft Parts Marketplace','STRING','SEO',1),
(UUID(),'seo_default_description','Halyk Petroleum sells new, overhauled and used aircraft parts for Gulfstream, Falcon, Citation, Challenger, Hawker, Learjet, Boeing and Airbus. FAA 8130-3 certified parts, 24/7 AOG support, worldwide shipping.','TEXT','SEO',2),
(UUID(),'seo_keywords','aircraft parts, jet parts, aviation parts, airplane parts, aircraft marketplace, AOG parts, Gulfstream parts, Falcon parts, Citation parts, rotables, wheels and brakes, aircraft engines','STRING','SEO',3),
(UUID(),'seo_robots','index, follow','STRING','SEO',4),
(UUID(),'seo_og_image','/assets/img/hero-jet.jpg','STRING','SEO',5),
(UUID(),'seo_enable_jsonld','1','BOOL','SEO',6),
(UUID(),'seo_schema_type','Organization','STRING','SEO',7),
(UUID(),'seo_schema_name','Halyk Petroleum','STRING','SEO',8),
(UUID(),'seo_schema_logo','/assets/img/logo-header.png','STRING','SEO',9),
(UUID(),'seo_google_analytics','','STRING','SEO',10),
(UUID(),'chat_enabled','1','BOOL','CHAT',1),
(UUID(),'chat_title','Halyk Parts Assistant','STRING','CHAT',2),
(UUID(),'chat_bot_name','Halyk','STRING','CHAT',3),
(UUID(),'chat_avatar','/assets/img/chat-bot-avatar.png','STRING','CHAT',8),
(UUID(),'chat_welcome','Hi there! 👋 I can help you find parts, check prices, request a quote or answer questions about certification and shipping. What part number are you looking for?','TEXT','CHAT',4),
(UUID(),'chat_ai_provider','local','STRING','CHAT',5),
(UUID(),'chat_rate_limit_per_hour','60','INT','CHAT',6),
(UUID(),'chat_quick_replies','[\"Find a part\",\"Request a quote\",\"Ask a question\",\"AOG support\"]','JSON','CHAT',7),
(UUID(),'theme_bg','#ffffff','STRING','THEME',1),
(UUID(),'theme_writeup','#000000','STRING','THEME',2)
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

-- #############################################################################
-- 11. CAREERS
-- #############################################################################
INSERT INTO `careers` (`id`,`title`,`slug`,`department`,`location`,`type`,`experience`,`salary`,`description`,`requirements`,`benefits`,`isActive`) VALUES
(UUID(),'AOG Parts Coordinator','aog-parts-coordinator','Operations','Dallas, TX','Full-time','3+ years','Competitive','Lead our Aircraft-on-Ground desk: source, quote and dispatch urgent parts to customers worldwide within hours.','Experience in aviation parts sales or MRO purchasing, strong phone and email communication, familiarity with part number formats.', 'Health, dental, vision, 401(k) match, AOG shift bonus', 1),
(UUID(),'Aviation Parts Sourcing Specialist','aviation-parts-sourcing','Purchasing','Dallas, TX','Full-time','5+ years','Competitive','Grow our global supplier network and source hard-to-find rotables, engines, APUs and airframe parts.','5+ years in aviation parts procurement, established supplier relationships preferred, fluent in traceability requirements (FAA/EASA).', 'Health, dental, vision, 401(k) match', 1),
(UUID(),'Quality & Traceability Inspector','quality-traceability-inspector','Quality','Dallas, TX','Full-time','5+ years','Competitive','Verify incoming and outgoing parts against 8130-3 / Form 1 documentation, maintain traceability records and audit supplier paperwork.','Aviation quality experience, knowledge of FAA 8130-3 / EASA Form 1, meticulous documentation habits.', 'Health, dental, vision, 401(k) match', 1),
(UUID(),'Sales Representative - Business Aviation','sales-rep-business-aviation','Sales','Remote (US)','Full-time','5+ years','Base + Commission','Own key flight department and MRO accounts across the US, quoting and closing wheel, brake, hydraulic and avionics part sales.','5+ years aviation parts sales, existing customer relationships in business aviation, technical fluency.', 'Uncapped commission, health, 401(k) match, company vehicle', 1);

-- #############################################################################
-- 12. NEWS
-- #############################################################################
INSERT INTO `news` (`id`,`title`,`slug`,`summary`,`content`,`publishedAt`,`isActive`) VALUES
(UUID(),'Halyk Petroleum expands Gulfstream parts inventory to 4,000+ line items','gulfstream-inventory-expansion','New low-time wheels, brakes, APUs and avionics added for GIV, GV, G450, G550 and G650 fleets.','Halyk Petroleum has expanded its Gulfstream parts inventory to more than 4,000 line items, adding low-time wheels and brakes, GTCP36 APUs, LASEREF inertial reference systems and Collins avionics. The new stock is available for immediate dispatch with full 8130-3 certification.','2026-07-18 09:00:00',1),
(UUID(),'New 24/7 AOG hotline now live','aog-hotline-live','Aircraft on the ground? Our new round-the-clock hotline answers within three rings.','Halyk Petroleum has launched a 24/7 AOG hotline staffed by experienced parts coordinators. Flight departments and MROs can now reach a live coordinator at any hour for urgent sourcing, with most AOG parts dispatched within hours.','2026-06-22 09:00:00',1),
(UUID(),'Halyk Petroleum achieves AS9120B quality certification','as9120b-certification','Independent audit confirms our aerospace quality management system.','We are pleased to announce AS9120B certification for our quality management system, covering the distribution of aircraft parts with full traceability. The audit was completed with zero major non-conformances.','2026-05-02 09:00:00',1);

-- #############################################################################
-- 13. DOWNLOADS
-- #############################################################################
INSERT INTO `downloads` (`id`,`title`,`description`,`fileUrl`,`type`,`category`,`fileSize`,`downloads`,`isActive`) VALUES
(UUID(),'Marketplace Catalog 2026','Complete overview of Halyk Petroleum parts categories and supported aircraft.','/assets/files/marketplace-catalog-2026.pdf','PDF','General','4.1 MB',0,1),
(UUID(),'Wheels & Brakes Cross-Reference Guide','Part number cross-reference for Goodrich, BFGoodrich and Goodyear wheels and brakes.','/assets/files/wheels-brakes-cross-reference.pdf','PDF','Wheels & Brakes','1.6 MB',0,1),
(UUID(),'Avionics Exchange & Repair Guide','How our exchange and repair programs work for Collins, Honeywell and BendixKing units.','/assets/files/avionics-exchange-guide.pdf','PDF','Avionics','1.2 MB',0,1),
(UUID(),'AOG Service Level Agreement','SLA terms for our 24/7 Aircraft-on-Ground priority dispatch service.','/assets/files/aog-sla.pdf','PDF','AOG','310 KB',0,1);

-- #############################################################################
-- 14. BLOG POSTS (references the fixed admin account UUID)
-- #############################################################################
INSERT INTO `blog_posts` (`id`,`title`,`slug`,`excerpt`,`content`,`authorId`,`category`,`tags`,`status`,`publishedAt`,`views`,`metaTitle`) VALUES
(UUID(),
 'NEW vs OHC vs USED: choosing the right part condition',
 'new-vs-ohc-vs-used',
 'Understand what each condition means, when it makes sense to save money, and what certification you should demand.',
 '<p>Every aircraft part we sell is described by one of four conditions: NEW, OHC (Overhauled), USED or SERVICEABLE. Choosing the right one can save your flight department tens of thousands of dollars a year...</p><p><strong>NEW</strong> parts are unused manufacturer stock — ideal for flight-critical rotables. <strong>OHC</strong> parts have been disassembled, repaired to manufacturer limits and bench-tested, typically with an extended warranty. <strong>USED</strong> parts are removed serviceable with full traceability, and <strong>SERVICEABLE</strong> parts are inspected and ready to install...</p>',
 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
 'Parts Knowledge',
 JSON_ARRAY('conditions','parts','purchasing'),
 'PUBLISHED',
 '2026-06-15 09:00:00',
 412,
 'NEW vs OHC vs USED - Halyk Petroleum'),
(UUID(),
 'How to read a FAA Form 8130-3 airworthiness certificate',
 'reading-8130-3',
 'A plain-language walkthrough of the Authorized Release Certificate that ships with every certified part.',
 '<p>FAA Form 8130-3 is the Authorized Release Certificate that confirms a part is airworthy and traceable. Knowing how to read it — and what to do when it is missing — is essential for any buyer...</p><p>The form records the part number, serial number, quantity, the organization releasing the part, and the airworthiness statement. Always confirm the block 14 signature matches a valid FAA/EASA approval...</p>',
 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
 'Parts Knowledge',
 JSON_ARRAY('8130-3','certification','compliance'),
 'PUBLISHED',
 '2026-04-22 09:00:00',
 289,
 'Reading a FAA 8130-3 - Halyk Petroleum');


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

-- =============================================================================
-- END OF PRODUCTION DATABASE
-- After import, verify with:
--   SELECT COUNT(*) FROM users;
--   SELECT COUNT(*) FROM categories;
--   SELECT COUNT(*) FROM products;
--   SELECT COUNT(*) FROM settings;
-- =============================================================================

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
(UUID(),'quotes.view','View quote requests (RFQ)','Sales',0,3),
(UUID(),'quotes.manage','Manage quote requests (RFQ)','Sales',0,4),
(UUID(),'quotes.export','Export RFQs to CSV','Sales',0,4),
(UUID(),'quotes.assign','Assign RFQs to team members','Sales',0,4),
(UUID(),'quotes.update_status','Change RFQ status','Sales',0,4),
(UUID(),'quotes.generate_pdf','Generate / send PDF quotes','Sales',0,4),
(UUID(),'quotes.manage_attachments','Manage RFQ attachments','Sales',0,4),
(UUID(),'contacts.manage','Manage contact messages','Sales',0,5),
(UUID(),'products.manage','Manage products','Catalog',0,6),
(UUID(),'inventory.manage','Manage warehouse inventory and lots','Catalog',0,6),
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
(UUID(),'ADMIN','inventory',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','categories',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','homepage',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','pages',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','menus',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','appearance',JSON_ARRAY('manage','read','update')),
(UUID(),'ADMIN','media',JSON_ARRAY('manage','read','create','delete')),
(UUID(),'ADMIN','industries',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','downloads',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','blog',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','news',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','faqs',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','careers',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','testimonials',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','partners',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ADMIN','seo',JSON_ARRAY('manage','read','update')),
(UUID(),'ADMIN','settings',JSON_ARRAY('manage','read','update')),
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
(UUID(),'site_title','Halyk Petroleum — Aircraft Parts Marketplace','STRING','WEBSITE',1),
(UUID(),'site_description','Halyk Petroleum sells new, overhauled and used aircraft parts for Gulfstream, Falcon, Citation, Challenger, Hawker, Learjet, Boeing and Airbus. FAA 8130-3 certified parts, 24/7 AOG support, worldwide shipping.','TEXT','WEBSITE',2),
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
(UUID(),'header','Parts','INTERNAL','products','_self',10,1),
(UUID(),'header','Aircraft','INTERNAL','industries','_self',20,1),
(UUID(),'header','Services','INTERNAL','services','_self',30,1),
(UUID(),'header','About','INTERNAL','about','_self',40,1),
(UUID(),'header','Blog','INTERNAL','blog','_self',50,1),
(UUID(),'header','FAQ','INTERNAL','faq','_self',70,1),
(UUID(),'header','Downloads','INTERNAL','downloads','_self',80,1),
(UUID(),'header','Contact','INTERNAL','contact','_self',90,1),

(UUID(),'footer_solutions','Parts','INTERNAL','products','_self',10,1),
(UUID(),'footer_solutions','Aircraft','INTERNAL','industries','_self',20,1),
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
 'Find the Right Jet Part. Fast.',
 'Search thousands of new, overhauled and used aircraft parts for Gulfstream, Falcon, Citation, Challenger, Hawker, Learjet, Boeing and Airbus. Every part certified and traceable.',
 NULL,'/assets/img/hero-jet.jpg','Request a Quote','rfq','Browse Parts','products',
 '{"eyebrow":"Aircraft parts marketplace","badges":["FAA 8130-3 certified","24/7 AOG support","Worldwide shipping"],"showSearch":true}',10,1,1),

(UUID(),'home','stats','Key numbers',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,
 '{"items":[{"value":"34000+","label":"Parts in stock"},{"value":"150+","label":"Aircraft supported"},{"value":"120+","label":"Countries served"},{"value":"24/7","label":"AOG dispatch"}]}',20,1,0),

(UUID(),'home','categories','Part categories',
 'Shop by part category',
 'From wheels and brakes to engines and avionics — every part inspected, certified and ready to ship.',
 NULL,NULL,NULL,NULL,NULL,NULL,'{"limit":8}',30,1,0),

(UUID(),'home','products','Featured parts',
 'Featured parts','Ready-to-ship new, overhauled and used parts with verified traceability.',NULL,NULL,'View all parts','products',NULL,NULL,
 '{"limit":4}',40,1,0),

(UUID(),'home','industries','Aircraft types',
 'Aircraft we support','Rotables, consumables and airframe parts for the world''s most popular business and commercial jets.',
 NULL,NULL,NULL,NULL,NULL,NULL,'{"limit":6}',50,1,0),

(UUID(),'home','testimonials','Testimonials',
 'What our customers say','Flight departments, airlines and MROs in over 120 countries trust Halyk Petroleum for certified parts.',
 NULL,NULL,NULL,NULL,NULL,NULL,'{"limit":4}',60,1,0),

(UUID(),'home','partners','Partners',
 'Trusted OEM & supplier network',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{"limit":12}',70,1,0),

(UUID(),'home','cta','Closing call to action',
 'Need a part fast?','Send us the part number and your AOG status — we respond within 2 hours during business hours, 24/7 for aircraft on the ground.',
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
