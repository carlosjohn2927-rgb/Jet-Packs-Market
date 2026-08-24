-- ---------------------------------------------------------------------------
-- Migration 008 — Customer accounts + parts-order history
--
-- Adds the two tables the public "Customer account" area needs:
--   1. invoices       — one row per PAID card payment, generated on first
--                        download so the PDF is always reproducible.
--   2. aog_dispatches — AOG / emergency part dispatches a customer can track.
--
-- Re-runnable: every statement uses CREATE TABLE IF NOT EXISTS / idempotent
-- INSERT IGNORE-style guards where relevant. Safe to run more than once.
-- ---------------------------------------------------------------------------

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
