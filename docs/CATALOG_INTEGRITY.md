# Catalog Data Integrity

Halyk Petroleum treats **parts as the products being sold**. Category and product
records must be unique in the **database**, not merely de-duplicated in the UI.

## What was fixed (migration 010 + Catalog_integrity)

| Problem | Data-layer fix |
|---|---|
| Duplicate category names (`Wheels & Brakes` / ` wheels  & brakes `) | Names normalized (trim + collapse whitespace + casefold) into `categories.nameNorm`. Duplicates merged onto a canonical row; products reassigned first; then duplicate category rows deleted. |
| Future duplicate categories | `UNIQUE (nameNorm)` plus admin create/edit rejection via `nameNorm`. |
| Duplicate product names / SKUs / slugs | Same pattern on `products.nameNorm`, with existing `uk_products_sku` / `uk_products_slug`. Child rows (images, specs, downloads, related, inventory lots/movements, quote items) reassigned before the duplicate product is removed. |
| Duplicate / shared product images | Every catalog part gets its own primary `product_images` row pointing at `/assets/img/products/<slug>.jpg`. Listings read that row — they do not all fall back to one category image. |

## Upgrade path

For an **existing** MySQL database, import in order (phpMyAdmin is enough):

```text
database/migrations/010_catalog_data_integrity.sql
```

Then open any page of the site once. `Catalog_integrity` runs automatically on
first boot after the `nameNorm` columns exist, merges leftovers, creates the
unique indexes if missing, seeds missing product images, and writes the
`catalog_integrity_v1` setting so it does not re-run.

Fresh installs via `database/production.sql` already include `nameNorm`, the
unique indexes, and the per-product image seed.

## Admin behaviour

- **Categories** (`Admin → Catalog → Categories`): create/edit rejects a name or
  slug that collides case-insensitively (whitespace-collapsed for names).
- **Products** (`Admin → Catalog → Products`): create/edit rejects a colliding
  name, SKU or slug the same way. Saving always writes `nameNorm`.

## Safety guarantees

- Products are **never** deleted as a side-effect of merging categories — they
  are reassigned to the canonical category first.
- Inventory lots, quote line items and product images move with a merged product.
- Canonical rows keep the better of SEO metadata, images, featured flag and view
  counts from the duplicates they absorb.
