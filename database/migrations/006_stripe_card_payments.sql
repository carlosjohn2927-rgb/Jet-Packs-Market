-- =============================================================================
-- Halyk Petroleum — Migration 006: Stripe-hosted card payments
-- =============================================================================
-- Import this file in phpMyAdmin for an existing installation. It creates the
-- payment ledger and webhook idempotency table, then extends the quote activity
-- enum so card-payment activity can be displayed on the quote timeline.
--
-- Stripe keys are NOT stored in SQL. Put VP_STRIPE_SECRET_KEY and
-- VP_STRIPE_WEBHOOK_SECRET in .env, then enable the feature in
-- Dashboard → Settings → System.
-- =============================================================================

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

-- Existing schemas have an ENUM, so expand it before the application writes a
-- payment activity. MODIFY is safe to execute repeatedly.
ALTER TABLE `quote_activities` MODIFY COLUMN `action`
  ENUM('QUOTE_CREATED','ASSIGNED','STATUS_CHANGED','INTERNAL_NOTE_ADDED','QUOTE_UPDATED','PDF_GENERATED','EMAIL_QUEUED','EMAIL_SENT','PAYMENT_REQUESTED','PAYMENT_PAID','PAYMENT_CANCELED','PAYMENT_EXPIRED','PAYMENT_FAILED') NOT NULL;

INSERT INTO `settings` (`id`,`key`,`value`,`type`,`group`,`sortOrder`) VALUES
(UUID(),'stripe_payments_enabled','0','BOOL','PAYMENTS',1),
(UUID(),'stripe_currency','USD','STRING','PAYMENTS',2),
(UUID(),'stripe_checkout_ttl_hours','24','INT','PAYMENTS',3)
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);
