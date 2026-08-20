<?php /** @var array $row */ ?>
<div class="mb-4"><a class="text-sm text-brand-600 hover:underline" href="<?= base_url('admin/contacts') ?>">&larr; All contacts</a></div>
<div class="vp-card vp-card-pad">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h2 class="text-2xl font-extrabold"><?= vp_safe_html($row['name']) ?></h2>
            <p class="text-sm text-gray-500"><?= vp_safe_html($row['email']) ?> &middot; <?= vp_safe_html($row['phone'] ?? '') ?> &middot; <?= vp_safe_html($row['company'] ?? '') ?></p>
        </div>
        <span class="vp-pill <?= $row['status']==='NEW' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700' ?>"><?= vp_safe_html($row['status']) ?></span>
    </div>
    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
        <div class="text-xs uppercase tracking-widest text-gray-500">Subject</div>
        <div class="font-semibold"><?= vp_safe_html($row['subject']) ?></div>
        <div class="text-xs text-gray-500 mt-1"><?= vp_safe_html($row['department'] ?? '') ?> &middot; <?= vp_human_date($row['createdAt']) ?></div>
    </div>
    <div class="vp-prose mt-4">
        <?= nl2br(vp_safe_html($row['message'])) ?>
    </div>
    <div class="mt-6 flex gap-2">
        <a class="vp-btn vp-btn-primary" href="mailto:<?= vp_safe_html($row['email']) ?>?subject=Re: <?= urlencode($row['subject']) ?>">Reply via email</a>
    </div>
</div>
