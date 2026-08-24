<?php
/** @var array|null $product */
/** @var array $industries */
/** @var array $categories */
/** @var array $all_products */
/** @var array $selected_industries */
/** @var array $selected_related */
/** @var string $certifications_csv */
/** @var array $specs_rows */
/** @var bool $inventory_available */
/** @var array|null $inventory_summary */
/** @var array $inventory_lots */
/** @var array $inventory_movements */
/** @var array $inventory_warehouses */
$is_create = empty($product);
$inventory_available = !empty($inventory_available);
$inventory_summary = $inventory_summary ?? null;
$inventory_lots = $inventory_lots ?? [];
$inventory_movements = $inventory_movements ?? [];
$inventory_warehouses = $inventory_warehouses ?? [];
$all_industries = $all_industries ?? ($industries ?? []);
$selected_aircraft_types = $selected_aircraft_types ?? (json_decode($product['aircraftType'] ?? '[]', true) ?: []);
$action = $is_create ? base_url('admin/products/save') : base_url('admin/products/save');
?>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="space-y-6">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?php if (!$is_create): ?><input type="hidden" name="id" value="<?= $product['id'] ?>"><?php endif; ?>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">Basics</h2>
        <div class="vp-grid-2">
            <div class="vp-form-row">
                <label>Name *</label>
                <input class="vp-input" name="name" required value="<?= vp_safe_html($product['name'] ?? '') ?>">
            </div>
            <div class="vp-form-row">
                <label>SKU *</label>
                <input class="vp-input" name="sku" required value="<?= vp_safe_html($product['sku'] ?? '') ?>">
            </div>
        </div>
        <div class="vp-grid-2">
            <div class="vp-form-row">
                <label>Slug</label>
                <input class="vp-input" name="slug" value="<?= vp_safe_html($product['slug'] ?? '') ?>" placeholder="auto from name">
                <p class="vp-help">URL: /products/<span id="vp-slug-preview"><?= vp_safe_html($product['slug'] ?? 'your-slug') ?></span></p>
            </div>
            <div class="vp-form-row">
                <label>Category</label>
                <select class="vp-select" name="categoryId">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($product['categoryId'] ?? '') === $c['id'] ? 'selected' : '' ?>><?= vp_safe_html($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="vp-form-row">
            <label>Short description</label>
            <textarea class="vp-textarea" name="shortDescription" rows="2"><?= vp_safe_html($product['shortDescription'] ?? '') ?></textarea>
        </div>
        <div class="vp-form-row">
            <label>Description *</label>
            <textarea class="vp-textarea" name="description" rows="8" required><?= vp_safe_html($product['description'] ?? '') ?></textarea>
            <p class="vp-help">HTML allowed.</p>
        </div>
    </div>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">Specs & ratings</h2>
        <div class="vp-grid-3">
            <div class="vp-form-row"><label>Material</label><input class="vp-input" name="material" value="<?= vp_safe_html($product['material'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Pressure</label><input class="vp-input" name="pressure" value="<?= vp_safe_html($product['pressure'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Temperature</label><input class="vp-input" name="temperature" value="<?= vp_safe_html($product['temperature'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Voltage</label><input class="vp-input" name="voltage" value="<?= vp_safe_html($product['voltage'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Dimensions</label><input class="vp-input" name="dimensions" value="<?= vp_safe_html($product['dimensions'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Weight</label><input class="vp-input" name="weight" value="<?= vp_safe_html($product['weight'] ?? '') ?>"></div>
        </div>
        <div class="vp-grid-2">
            <div class="vp-form-row"><label>Manufacturer</label><input class="vp-input" name="manufacturer" value="<?= vp_safe_html($product['manufacturer'] ?? '') ?>" placeholder="Honeywell, Collins, Goodrich…"></div>
            <div class="vp-form-row">
                <label>Aircraft compatibility</label>
                <select class="vp-select" name="aircraftType[]" multiple size="6">
                    <?php foreach ($all_industries as $i): ?>
                        <option value="<?= $i['id'] ?>" <?= in_array($i['id'], $selected_aircraft_types) ? 'checked' : '' ?>><?= vp_safe_html($i['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="vp-help">Select one or more aircraft platforms this part is compatible with.</p>
            </div>
            <div class="vp-form-row"><label>Price (USD)</label><input class="vp-input" type="number" step="0.01" name="price" value="<?= vp_safe_html($product['price'] ?? '') ?>"></div>
            <div class="vp-form-row">
                <?php if (!$is_create && $inventory_available && $inventory_summary !== null): ?>
                    <label>Available stock (derived from lots)</label>
                    <input class="vp-input bg-gray-50" type="text" readonly value="<?= (int) $inventory_summary['available'] ?> unit<?= (int) $inventory_summary['available'] === 1 ? '' : 's' ?> across <?= (int) $inventory_summary['warehouseCount'] ?> warehouse<?= (int) $inventory_summary['warehouseCount'] === 1 ? '' : 's' ?>">
                    <input type="hidden" name="quantity" value="<?= (int) $inventory_summary['available'] ?>">
                    <p class="vp-help">Use the Inventory lots panel below to receive, reserve or move stock.</p>
                <?php else: ?>
                    <label>Opening quantity</label><input class="vp-input" type="number" min="0" step="1" name="quantity" value="<?= vp_safe_html($product['quantity'] ?? '1') ?>">
                    <p class="vp-help">Saved as an opening lot at the default warehouse after the product is created.</p>
                <?php endif; ?>
            </div>
            <div class="vp-form-row">
                <label>Condition</label>
                <select class="vp-select" name="condition">
                    <?php foreach (['NEW','OHC','USED','SERVICEABLE'] as $c): ?>
                        <option value="<?= $c ?>" <?= ($product['condition'] ?? 'NEW') === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="vp-form-row">
                <label>Availability</label>
                <select class="vp-select" name="availability">
                    <?php foreach (['IN_STOCK','OUT_OF_STOCK','MADE_TO_ORDER','DISCONTINUED'] as $a): ?>
                        <option value="<?= $a ?>" <?= ($product['availability'] ?? 'IN_STOCK') === $a ? 'selected' : '' ?>><?= str_replace('_',' ',$a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="vp-form-row">
            <label>Certifications (comma-separated)</label>
            <input class="vp-input" name="certifications_csv" value="<?= vp_safe_html($certifications_csv) ?>" placeholder="ASME B31.3, API 610, ISO 9001">
        </div>
    </div>

    <?php if (!$is_create && $inventory_available && !empty($can['inventory.manage'])): ?>
    <div class="vp-card vp-card-pad">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <h2 class="font-bold text-lg">Multi-warehouse inventory &amp; lots</h2>
                <p class="vp-help">Every stock change writes a movement record. Available stock excludes reserved, expired and quarantined lots.</p>
            </div>
            <a class="vp-btn vp-btn-secondary vp-btn-sm" href="<?= base_url('admin/inventory') ?>"><i class="ri-stack-line"></i> Inventory board</a>
        </div>
        <?php if ($inventory_summary): ?>
            <div class="grid sm:grid-cols-4 gap-3 mb-5 text-sm">
                <div class="rounded-lg bg-emerald-50 border border-emerald-100 p-3"><span class="block text-xs text-emerald-800">Available</span><b class="text-lg"><?= (int) $inventory_summary['available'] ?></b></div>
                <div class="rounded-lg bg-blue-50 border border-blue-100 p-3"><span class="block text-xs text-blue-800">On hand</span><b class="text-lg"><?= (int) $inventory_summary['onHand'] ?></b></div>
                <div class="rounded-lg bg-amber-50 border border-amber-100 p-3"><span class="block text-xs text-amber-800">Reserved</span><b class="text-lg"><?= (int) $inventory_summary['reserved'] ?></b></div>
                <div class="rounded-lg bg-gray-50 border p-3"><span class="block text-xs text-gray-600">Locations</span><b class="text-lg"><?= (int) $inventory_summary['warehouseCount'] ?></b><?php if (!empty($inventory_summary['aogAvailable'])): ?><span class="block text-xs text-emerald-700 font-semibold">AOG-ready</span><?php endif; ?></div>
            </div>
        <?php endif; ?>

        <?php if (empty($inventory_lots)): ?>
            <p class="text-sm text-gray-500 mb-4">No lots have been received for this part yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto mb-5">
                <table class="vp-admin-table text-xs">
                    <thead><tr><th>Warehouse / bin</th><th>Lot / serial</th><th>On hand</th><th>Reserved</th><th>Available</th><th>Expiry</th><th>Status</th><th></th></tr></thead>
                    <tbody><?php foreach ($inventory_lots as $lot): [$lotLabel, $lotClass] = vp_inventory_lot_status_label($lot['status']); [$expLabel, $expClass] = vp_inventory_expiry_label($lot['expiresAt'] ?? null); ?>
                        <tr>
                            <td><strong><?= vp_safe_html($lot['warehouseCode'] ?? '') ?></strong><br><span class="text-gray-500"><?= vp_safe_html($lot['binLocation'] ?? '—') ?></span></td>
                            <td><strong><?= vp_safe_html($lot['lotNumber']) ?></strong><?php if (!empty($lot['serialNumber'])): ?><br><span class="text-gray-500">S/N <?= vp_safe_html($lot['serialNumber']) ?></span><?php endif; ?></td>
                            <td><?= (int) $lot['quantityOnHand'] ?></td><td><?= (int) $lot['quantityReserved'] ?></td><td><strong><?= (int) $lot['quantityAvailable'] ?></strong></td>
                            <td class="<?= $expClass ?>"><?= vp_safe_html($expLabel) ?></td><td><span class="vp-pill <?= $lotClass ?>"><?= vp_safe_html($lotLabel) ?></span></td>
                            <td class="text-right"><a href="#lot-<?= $lot['id'] ?>" class="text-brand-600 hover:underline">Manage</a></td>
                        </tr>
                    <?php endforeach; ?></tbody>
                </table>
            </div>
            <div class="space-y-3">
            <?php foreach ($inventory_lots as $lot): ?>
                <details id="lot-<?= $lot['id'] ?>" class="rounded-lg border bg-gray-50 p-3">
                    <summary class="cursor-pointer font-semibold text-sm">Manage lot <?= vp_safe_html($lot['lotNumber']) ?> · <?= vp_safe_html($lot['warehouseName'] ?? '') ?></summary>
                    <div class="grid lg:grid-cols-2 gap-4 mt-4">
                        <form method="post" action="<?= base_url('admin/products/' . $product['id'] . '/inventory/lots/' . $lot['id'] . '/adjust') ?>" class="space-y-2">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <h3 class="font-semibold text-sm">Adjust stock / reservation</h3>
                            <div class="grid grid-cols-2 gap-2"><input class="vp-input" name="quantityDelta" type="number" step="1" placeholder="On-hand delta, e.g. -1"><input class="vp-input" name="reservedDelta" type="number" step="1" placeholder="Reserved delta, e.g. +1"></div>
                            <input class="vp-input" name="note" maxlength="500" placeholder="Reason / reference (required for audit)">
                            <button class="vp-btn vp-btn-secondary vp-btn-sm" type="submit">Save adjustment</button>
                        </form>
                        <form method="post" action="<?= base_url('admin/products/' . $product['id'] . '/inventory/lots/' . $lot['id'] . '/update') ?>" class="space-y-2">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <h3 class="font-semibold text-sm">Traceability &amp; expiry</h3>
                            <div class="grid grid-cols-2 gap-2"><input class="vp-input" name="serialNumber" value="<?= vp_safe_html($lot['serialNumber'] ?? '') ?>" placeholder="Serial number"><input class="vp-input" name="binLocation" value="<?= vp_safe_html($lot['binLocation'] ?? '') ?>" placeholder="Bin / shelf"></div>
                            <div class="grid grid-cols-2 gap-2"><input class="vp-input" type="date" name="receivedAt" value="<?= vp_safe_html($lot['receivedAt'] ?? '') ?>"><input class="vp-input" type="date" name="expiresAt" value="<?= vp_safe_html($lot['expiresAt'] ?? '') ?>"></div>
                            <div class="grid grid-cols-2 gap-2"><input class="vp-input" name="certification" value="<?= vp_safe_html($lot['certification'] ?? '') ?>" placeholder="FAA 8130-3 / Form 1"><select class="vp-select" name="lotStatus"><?php foreach (['ACTIVE','QUARANTINE','EXPIRED','DEPLETED'] as $state): ?><option value="<?= $state ?>" <?= $state === $lot['status'] ? 'selected' : '' ?>><?= $state ?></option><?php endforeach; ?></select></div>
                            <input class="vp-input" name="traceabilityRef" value="<?= vp_safe_html($lot['traceabilityRef'] ?? '') ?>" placeholder="Traceability reference">
                            <textarea class="vp-textarea" name="lotNotes" rows="2" placeholder="Lot notes"><?= vp_safe_html($lot['notes'] ?? '') ?></textarea>
                            <button class="vp-btn vp-btn-secondary vp-btn-sm" type="submit">Save lot details</button>
                        </form>
                    </div>
                    <div class="mt-4 border-t pt-3">
                        <h4 class="text-xs font-bold uppercase tracking-wide text-gray-600 mb-2">Recent movement history</h4>
                        <?php $moves = $inventory_movements[$lot['id']] ?? []; ?>
                        <?php if (empty($moves)): ?><p class="text-xs text-gray-500">No movements recorded yet.</p><?php else: ?><ul class="text-xs divide-y"><?php foreach ($moves as $move): ?><li class="py-1 flex flex-wrap gap-x-3"><span class="font-semibold"><?= vp_safe_html($move['movementType']) ?></span><span>On hand <?= (int) $move['quantityDelta'] >= 0 ? '+' : '' ?><?= (int) $move['quantityDelta'] ?></span><span>Reserved <?= (int) $move['reservedDelta'] >= 0 ? '+' : '' ?><?= (int) $move['reservedDelta'] ?></span><span class="text-gray-500"><?= vp_time_ago($move['createdAt']) ?></span><?php if (!empty($move['notes'])): ?><span class="text-gray-600"><?= vp_safe_html($move['notes']) ?></span><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('admin/products/' . $product['id'] . '/inventory/lots/create') ?>" class="mt-6 border-t pt-5 space-y-3">
            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
            <h3 class="font-bold">Receive a new lot</h3>
            <?php if (empty($inventory_warehouses)): ?><p class="text-sm text-red-700">Create an active warehouse before receiving stock.</p><?php else: ?>
                <div class="grid md:grid-cols-3 gap-3"><select class="vp-select" name="warehouseId" required><option value="">Warehouse…</option><?php foreach ($inventory_warehouses as $warehouse): ?><option value="<?= $warehouse['id'] ?>"><?= vp_safe_html($warehouse['code'] . ' — ' . $warehouse['name']) ?></option><?php endforeach; ?></select><input class="vp-input" name="lotNumber" required maxlength="100" placeholder="Lot / batch number"><input class="vp-input" name="serialNumber" maxlength="100" placeholder="Serial number (optional)"></div>
                <div class="grid md:grid-cols-4 gap-3"><input class="vp-input" name="quantityOnHand" type="number" min="0" step="1" required placeholder="On-hand quantity"><input class="vp-input" name="quantityReserved" type="number" min="0" step="1" value="0" placeholder="Reserved"><input class="vp-input" name="binLocation" maxlength="100" placeholder="Bin / shelf"><input class="vp-input" type="date" name="expiresAt" title="Expiry date"></div>
                <div class="grid md:grid-cols-3 gap-3"><input class="vp-input" name="certification" maxlength="255" placeholder="Certification"><input class="vp-input" name="traceabilityRef" maxlength="255" placeholder="Traceability reference"><select class="vp-select" name="lotStatus"><option value="ACTIVE">ACTIVE</option><option value="QUARANTINE">QUARANTINE</option></select></div>
                <textarea class="vp-textarea" name="lotNotes" rows="2" placeholder="Receiving notes"></textarea>
                <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-add-circle-line"></i> Receive lot</button>
            <?php endif; ?>
        </form>
    </div>
    <?php elseif (!$is_create && !$inventory_available): ?>
    <div class="vp-card vp-card-pad"><h2 class="font-bold text-lg">Inventory</h2><p class="text-sm text-gray-600">Import <code>database/migrations/007_multi_warehouse_inventory.sql</code> to enable lot-level inventory.</p></div>
    <?php elseif (!$is_create): ?>
    <div class="vp-card vp-card-pad"><h2 class="font-bold text-lg">Inventory</h2><p class="text-sm text-gray-600">You do not have permission to manage inventory lots.</p></div>
    <?php endif; ?>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">Images</h2>
        <div class="vp-form-row">
            <label>Add new images</label>
            <input class="vp-input" type="file" name="images[]" accept="image/*" multiple>
            <p class="vp-help">You can select multiple images at once. All will be saved as additional product photos. Auto-resized to max 1600px wide.</p>
        </div>
        <?php if (!$is_create && $product): ?>
            <?php
            $existing_images = $this->db->get_where('product_images', ['productId' => $product['id']])->result_array();
            ?>
            <?php if (!empty($existing_images)): ?>
                <div class="mt-4">
                    <div class="text-sm font-semibold mb-2">Current images</div>
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                        <?php foreach ($existing_images as $img): ?>
                            <div class="relative group border rounded overflow-hidden aspect-square">
                                <img src="<?= base_url($img['url']) ?>" alt="" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1">
                                    <?php if (empty($img['isPrimary'])): ?>
                                        <form action="<?= base_url('admin/products/' . $product['id'] . '/images/' . $img['id'] . '/primary') ?>" method="post">
                                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                                            <button class="vp-btn vp-btn-secondary vp-btn-sm text-xs" type="submit" title="Make primary">★</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="vp-pill bg-yellow-400 text-yellow-900 text-xs">Primary</span>
                                    <?php endif; ?>
                                    <form action="<?= base_url('admin/products/' . $product['id'] . '/images/' . $img['id'] . '/delete') ?>" method="post" data-confirm="Delete this image?">
                                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                                        <button class="vp-btn vp-btn-danger vp-btn-sm text-xs" type="submit" title="Delete">×</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="vp-help mt-2">Hover an image to set as primary or delete. Uploading new images adds them without removing existing ones.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">Specifications (key/value pairs)</h2>
        <div id="vp-specs" class="space-y-2">
            <?php
            $rows = !empty($specs_rows) ? $specs_rows : [['key'=>'','value'=>'','unit'=>'']];
            foreach ($rows as $i => $s): ?>
            <div class="vp-spec-row grid grid-cols-12 gap-2">
                <input class="vp-input col-span-4" name="spec_key[]"   value="<?= vp_safe_html($s['key']) ?>"   placeholder="Key (e.g. Flow)">
                <input class="vp-input col-span-5" name="spec_value[]" value="<?= vp_safe_html($s['value']) ?>" placeholder="Value (e.g. 50)">
                <input class="vp-input col-span-2" name="spec_unit[]"  value="<?= vp_safe_html($s['unit'] ?? '') ?>" placeholder="Unit">
                <button type="button" class="vp-btn vp-btn-secondary col-span-1 vp-spec-del" title="Remove">×</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="vp-spec-add" class="vp-btn vp-btn-secondary mt-3 vp-btn-sm">+ Add specification</button>
    </div>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">Industries & related products</h2>
        <div class="vp-form-row">
            <label>Industries</label>
            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-2">
                <?php foreach ($industries as $i): ?>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="industries[]" value="<?= $i['id'] ?>" <?= in_array($i['id'], $selected_industries) ? 'checked' : '' ?>>
                        <?= vp_safe_html($i['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="vp-form-row">
            <label>Related products (max 12)</label>
            <select class="vp-select" name="related[]" multiple size="6">
                <?php foreach ($all_products as $ap): ?>
                    <option value="<?= $ap['id'] ?>" <?= in_array($ap['id'], $selected_related) ? 'selected' : '' ?>><?= vp_safe_html($ap['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">SEO & status</h2>
        <div class="vp-grid-2">
            <div class="vp-form-row"><label>Meta title</label><input class="vp-input" name="metaTitle" value="<?= vp_safe_html($product['metaTitle'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Meta keywords (comma)</label><input class="vp-input" name="metaKeywords" value="<?= vp_safe_html(implode(', ', json_decode($product['metaKeywords'] ?? '[]', true) ?: [])) ?>"></div>
        </div>
        <div class="vp-form-row"><label>Meta description</label><textarea class="vp-textarea" name="metaDescription" rows="2"><?= vp_safe_html($product['metaDescription'] ?? '') ?></textarea></div>
        <div class="vp-grid-2">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="featured" value="1" <?= !empty($product['featured']) ? 'checked' : '' ?>> Featured on home page
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="isActive" value="0">
                <input type="checkbox" name="isActive" value="1" <?= (!$is_create || !empty($product['isActive'])) ? 'checked' : '' ?>> Active
            </label>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <button class="vp-btn vp-btn-primary" type="submit"><?= $is_create ? 'Create product' : 'Save changes' ?></button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/products') ?>">Cancel</a>
    </div>
</form>

<?php /* Slug preview + spec rows are wired up by assets/js/admin.js
         (inline scripts are blocked by the production CSP). */ ?>
