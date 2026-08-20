<?php
/** @var array $quote */
/** @var array $items */
/** @var array $history */
/** @var array $activity */
/** @var array $assignee */
/** @var array $customer */
/** @var array $staff */
$allowed = QUOTE_TRANSITIONS[$quote['status']] ?? [];
$st = vp_quote_status_label($quote['status']);
?>
<div class="grid lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="vp-card vp-card-pad">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <div class="text-xs text-gray-500 font-mono"><?= vp_safe_html($quote['quoteNumber']) ?></div>
                    <h1 class="text-2xl font-extrabold"><?= vp_safe_html($quote['companyName']) ?></h1>
                    <p class="text-sm text-gray-500">Submitted <?= vp_time_ago($quote['createdAt']) ?> &middot; Version <?= (int) $quote['version'] ?></p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="vp-pill <?= $st['class'] ?>"><?= $st['label'] ?></span>
                    <a class="vp-btn vp-btn-secondary vp-btn-sm" href="<?= base_url('admin/quotes/' . $quote['id'] . '/pdf') ?>" target="_blank"><i class="ri-file-pdf-line"></i> PDF</a>
                    <?php if ($this->vp_auth->has_role(ROLE_SUPER_ADMIN)): ?>
                        <form action="<?= base_url('admin/quotes/' . $quote['id'] . '/delete') ?>" method="post" data-confirm="Delete this quote? This cannot be undone.">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <button class="vp-btn vp-btn-danger vp-btn-sm" type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="vp-card">
            <div class="vp-card-pad border-b">
                <h2 class="font-bold">Line items</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="vp-admin-table">
                    <thead><tr><th>Product</th><th>Qty</th><th>Specifications</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= vp_safe_html($it['productName']) ?></td>
                            <td class="text-center"><?= (int) $it['quantity'] ?></td>
                            <td class="text-sm text-gray-600"><?= nl2br(vp_safe_html($it['specifications'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($attachments)): ?>
        <div class="vp-card">
            <div class="vp-card-pad border-b"><h2 class="font-bold">Attachments</h2></div>
            <div class="vp-card-pad">
                <ul class="divide-y">
                    <?php foreach ($attachments as $a): ?>
                        <li class="py-2 flex items-center gap-3 text-sm">
                            <i class="ri-attachment-2 text-lg text-brand-600"></i>
                            <a class="text-brand-600 hover:underline font-semibold flex-1" href="<?= base_url($a['url']) ?>" target="_blank"><?= vp_safe_html($a['filename']) ?></a>
                            <span class="text-xs text-gray-500"><?= vp_format_bytes((int) ($a['size'] ?? 0)) ?> &middot; <?= vp_safe_html($a['mimeType'] ?? '') ?></span>
                            <form action="<?= base_url('admin/quotes/' . $quote['id'] . '/attachments/' . $a['id'] . '/delete') ?>" method="post" data-confirm="Remove this attachment?">
                                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                                <button class="text-red-600 hover:underline text-xs" type="submit">Delete</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <div class="vp-card">
            <div class="vp-card-pad border-b"><h2 class="font-bold">Activity timeline</h2></div>
            <div class="vp-card-pad">
                <div class="vp-timeline">
                <?php foreach ($activity as $a): ?>
                    <div class="vp-timeline-item">
                        <time><?= vp_time_ago($a['createdAt']) ?></time>
                        <div class="vp-action"><?= vp_safe_html($a['action']) ?></div>
                        <div class="vp-desc"><?= vp_safe_html($a['description'] ?? '') ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($activity)): ?>
                    <p class="text-sm text-gray-500">No activity yet.</p>
                <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="vp-card">
            <div class="vp-card-pad border-b"><h2 class="font-bold">Status history</h2></div>
            <div class="vp-card-pad">
                <ul class="text-sm divide-y">
                <?php foreach ($history as $h): ?>
                    <li class="py-2 flex items-center gap-2">
                        <span class="vp-pill bg-gray-100 text-gray-700"><?= vp_safe_html($h['fromStatus'] ?? '—') ?> &rarr; <?= vp_safe_html($h['toStatus']) ?></span>
                        <span class="text-gray-500 text-xs ml-2"><?= vp_time_ago($h['createdAt']) ?></span>
                        <span class="ml-auto text-xs text-gray-600"><?= vp_safe_html($h['notes'] ?? '') ?></span>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <aside class="space-y-4">
        <div class="vp-card vp-card-pad">
            <h3 class="font-bold mb-2">Contact</h3>
            <p class="text-sm"><?= vp_safe_html($quote['contactPerson']) ?><br>
            <a class="text-brand-600" href="mailto:<?= vp_safe_html($quote['email']) ?>"><?= vp_safe_html($quote['email']) ?></a></p>
            <?php if ($quote['phone']): ?><p class="text-sm mt-1"><?= vp_safe_html($quote['phone']) ?></p><?php endif; ?>
            <p class="text-sm mt-2 text-gray-600"><?= vp_safe_html($quote['country']) ?><?php if ($quote['industry']): ?> &middot; <?= vp_safe_html($quote['industry']) ?><?php endif; ?></p>
            <?php if ($quote['address']): ?><p class="text-xs text-gray-500 mt-1"><?= nl2br(vp_safe_html($quote['address'])) ?></p><?php endif; ?>
            <?php if ($quote['notes']): ?><p class="text-xs text-gray-700 mt-3 italic">"<?= nl2br(vp_safe_html($quote['notes'])) ?>"</p><?php endif; ?>
        </div>

        <div class="vp-card vp-card-pad">
            <h3 class="font-bold mb-2">Assignment</h3>
            <p class="text-sm text-gray-600">Current: <?= $assignee ? vp_safe_html(trim($assignee['firstName'] . ' ' . $assignee['lastName'])) : '<span class="text-gray-400">Unassigned</span>' ?></p>
            <form method="post" action="<?= base_url('admin/quotes/' . $quote['id'] . '/assign') ?>" class="mt-3 flex gap-2">
                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                <input type="hidden" name="version" value="<?= (int) $quote['version'] ?>">
                <select class="vp-select" name="assignedTo">
                    <?php foreach ($staff as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($assignee && $assignee['id'] === $s['id']) ? 'selected' : '' ?>><?= vp_safe_html(trim($s['firstName'] . ' ' . $s['lastName'])) ?> &middot; <?= vp_safe_html($s['role']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="vp-btn vp-btn-secondary" type="submit">Assign</button>
            </form>
        </div>

        <div class="vp-card vp-card-pad">
            <h3 class="font-bold mb-2">Update status</h3>
            <?php if (empty($allowed)): ?>
                <p class="text-sm text-gray-500">This is a terminal state.</p>
            <?php else: ?>
            <form method="post" action="<?= base_url('admin/quotes/' . $quote['id'] . '/status') ?>" class="space-y-3">
                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                <input type="hidden" name="version" value="<?= (int) $quote['version'] ?>">
                <select class="vp-select" name="status" required>
                    <option value="">Select new status…</option>
                    <?php foreach ($allowed as $a): ?>
                        <option value="<?= $a ?>"><?= vp_safe_html(ucfirst(strtolower(str_replace('_', ' ', $a)))) ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="vp-select" name="assignedTo">
                    <option value="">(keep current assignee)</option>
                    <?php foreach ($staff as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($assignee && $assignee['id'] === $s['id']) ? 'selected' : '' ?>><?= vp_safe_html(trim($s['firstName'] . ' ' . $s['lastName'])) ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea class="vp-textarea" name="notes" rows="3" placeholder="Note (sent to customer)…"></textarea>
                <button class="vp-btn vp-btn-primary w-full justify-center" type="submit">Update &amp; notify</button>
            </form>
            <?php endif; ?>
        </div>

        <div class="vp-card vp-card-pad">
            <h3 class="font-bold mb-2">Internal note</h3>
            <form method="post" action="<?= base_url('admin/quotes/' . $quote['id'] . '/note') ?>" class="space-y-3">
                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                <input type="hidden" name="version" value="<?= (int) $quote['version'] ?>">
                <textarea class="vp-textarea" name="note" rows="3" placeholder="Internal note (not sent to customer)…"></textarea>
                <button class="vp-btn vp-btn-secondary w-full justify-center" type="submit">Add note</button>
            </form>
            <?php if (!empty($quote['internalNotes'])): ?>
                <div class="mt-3 text-xs text-gray-600 whitespace-pre-line border-t pt-2"><?= vp_safe_html($quote['internalNotes']) ?></div>
            <?php endif; ?>
        </div>
    </aside>
</div>
