-- =============================================================================
-- JetPacks Market — Migration 007: multi-warehouse inventory & lot tracking
-- =============================================================================
-- Creates warehouse, lot and immutable movement tables. Existing product
-- quantities are backfilled into an OPENING lot at the Dallas AOG hub, so the
-- public catalog quantity remains correct immediately after import.
-- =============================================================================

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

INSERT INTO `warehouses` (`id`,`name`,`code`,`address`,`city`,`region`,`country`,`timezone`,`phone`,`isAogHub`,`isActive`,`sortOrder`,`notes`) VALUES
(UUID(),'Dallas AOG Hub','DAL-AOG','Hangar 4, Dallas Executive Airport','Dallas','Texas','USA','America/Chicago','+1 (214) 350-0107',1,1,1,'24/7 AOG dispatch and primary receiving hub'),
(UUID(),'Amsterdam EU Hub','AMS-EU','Schiphol-Rijk logistics campus','Amsterdam','North Holland','Netherlands','Europe/Amsterdam','+31 20 000 0000',0,1,2,'European consolidation and export hub')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `isActive`=VALUES(`isActive`), `isAogHub`=VALUES(`isAogHub`), `sortOrder`=VALUES(`sortOrder`);

-- Backfill only products that do not already have any lot rows.
INSERT INTO `inventory_lots` (`id`,`productId`,`warehouseId`,`lotNumber`,`binLocation`,`condition`,`certification`,`traceabilityRef`,`quantityOnHand`,`quantityReserved`,`receivedAt`,`status`,`notes`)
SELECT UUID(), p.id, (SELECT id FROM warehouses WHERE code='DAL-AOG' LIMIT 1), p.sku, 'OPENING', p.condition,
       'Legacy catalog balance', p.sku, p.quantity, 0, CURRENT_DATE,
       CASE WHEN p.quantity > 0 THEN 'ACTIVE' ELSE 'DEPLETED' END,
       'Opening balance migrated from products.quantity by migration 007.'
FROM products p
WHERE NOT EXISTS (SELECT 1 FROM inventory_lots l WHERE l.productId = p.id);

INSERT INTO `inventory_movements` (`id`,`inventoryLotId`,`productId`,`warehouseId`,`movementType`,`quantityDelta`,`reservedDelta`,`notes`,`createdAt`)
SELECT UUID(), l.id, l.productId, l.warehouseId, 'RECEIPT', l.quantityOnHand, 0, 'Opening balance from migration 007.', NOW()
FROM inventory_lots l
WHERE l.notes LIKE 'Opening balance migrated from products.quantity%'
  AND NOT EXISTS (SELECT 1 FROM inventory_movements m WHERE m.inventoryLotId = l.id AND m.notes = 'Opening balance from migration 007.');

INSERT INTO `permissions` (`id`,`key`,`label`,`groupName`,`superOnly`,`sortOrder`) VALUES
(UUID(),'inventory.manage','Manage warehouse inventory and lots','Catalog',0,10)
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `groupName`=VALUES(`groupName`), `superOnly`=VALUES(`superOnly`);

INSERT INTO `role_permissions` (`id`,`role`,`resource`,`actions`) VALUES
(UUID(),'ADMIN','inventory',JSON_ARRAY('manage','read','create','update','delete')),
(UUID(),'ENGINEER','inventory',JSON_ARRAY('manage','read','create','update'))
ON DUPLICATE KEY UPDATE `actions`=VALUES(`actions`);
