# Multi-Warehouse Inventory & Lot Tracking

The inventory module replaces a product's single stock number with traceable,
warehouse-level lots. `products.quantity` remains a cached public total, while
`inventory_lots` and `inventory_movements` are the operational source of truth.

## Setup / upgrade

For an existing database, import:

```text
database/migrations/007_multi_warehouse_inventory.sql
```

The migration creates two starter locations:

- **DAL-AOG** — Dallas AOG Hub (24/7 dispatch)
- **AMS-EU** — Amsterdam EU Hub

It also turns every legacy `products.quantity` balance into one opening lot at
DAL-AOG and creates a matching receipt movement. Re-running the migration does
not duplicate lots or movements.

## Warehouse workflow

1. Go to **Dashboard → Catalog → Warehouses** and add/edit your locations.
2. Mark a location as an **AOG hub** when it can dispatch urgent aircraft-on-
   ground orders around the clock.
3. Open **Dashboard → Catalog → Inventory** to filter all lots by warehouse,
   status, product, SKU, batch, or serial number.

A warehouse that has lots cannot be deleted. Mark it inactive after all stock is
moved instead.

## Lot workflow

From **Products → Edit product → Multi-warehouse inventory & lots**:

- **Receive a lot** with warehouse, batch/lot number, serial number, bin,
  on-hand and reserved quantities, certification, traceability reference, and
  optional expiry date.
- **Adjust** physical or reserved quantities. The resulting on-hand amount can
  never fall below zero, and reservations can never exceed on-hand stock.
- **Quarantine** a lot to remove it from available stock without discarding its
  traceability history.
- Track expiration dates. Active expired lots automatically become `EXPIRED`
  and no longer contribute to sellable stock.
- Use the **Inventory board** to transfer only unreserved, active, unexpired
  stock between warehouses. The destination retains the same lot number and a
  `TRANSFER_OUT` / `TRANSFER_IN` pair is written to the movement ledger.

Lots are never silently deleted. Every receipt, adjustment, reservation change,
transfer, and detail update has an immutable row in `inventory_movements`.

## Public site behavior

Public product pages show only safe aggregate information:

- total available quantity;
- number of locations holding stock;
- whether an AOG hub has stock;
- the nearest expiry warning.

Warehouse addresses, bins, lot numbers, serial numbers, certification details,
and movement history remain staff-only.

## Stock status rules

| Lot status | Counts as available? | Meaning |
|---|---:|---|
| `ACTIVE` | Yes, minus reserved qty | Sellable stock with no elapsed expiry |
| `QUARANTINE` | No | Held for inspection / documentation / quality review |
| `EXPIRED` | No | Expiry date has elapsed |
| `DEPLETED` | No | On-hand quantity is zero |

A product's availability switches between `IN_STOCK` and `OUT_OF_STOCK` when
its derived available total changes. `MADE_TO_ORDER` and `DISCONTINUED` remain
under staff control.
