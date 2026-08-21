<?php /** @var array $rows */ ?>
<div class="flex flex-wrap gap-2 mb-4">
    <form method="post" action="<?= base_url('admin/notifications/read_all') ?>">
        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
        <button class="vp-btn vp-btn-secondary vp-btn-sm" type="submit"><i class="ri-check-double-line"></i> Mark all read</button>
    </form>
    <form method="post" action="<?= base_url('admin/notifications/clear') ?>" data-confirm="Delete all notifications you have already read?">
        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
        <button class="vp-btn vp-btn-secondary vp-btn-sm" type="submit"><i class="ri-delete-bin-line"></i> Clear read</button>
    </form>
</div>
<?php if (empty($rows)): ?>
    <p class="text-gray-500 text-center py-12">No notifications.</p>
<?php else: ?>
    <div class="space-y-2">
    <?php foreach ($rows as $n): ?>
        <div class="vp-card vp-card-pad flex items-center gap-3 <?= empty($n['read']) ? 'border-l-4 border-brand-500' : '' ?>">
            <i class="ri-notification-3-line text-xl text-brand-600"></i>
            <div class="flex-1">
                <div class="font-semibold"><?= vp_safe_html($n['title']) ?></div>
                <p class="text-sm text-gray-600"><?= vp_safe_html($n['message']) ?></p>
                <div class="text-xs text-gray-500 mt-1"><?= vp_time_ago($n['createdAt']) ?> &middot; <?= vp_safe_html($n['type']) ?></div>
            </div>
            <?php if (empty($n['read'])): ?>
                <form action="<?= base_url('admin/notifications/read/' . $n['id']) ?>" method="post">
                    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                    <button class="vp-btn vp-btn-secondary vp-btn-sm" type="submit">Mark read</button>
                </form>
            <?php endif; ?>
            <form action="<?= base_url('admin/notifications/delete/' . $n['id']) ?>" method="post">
                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                <button class="vp-btn vp-btn-secondary vp-btn-sm text-red-600" type="submit"><i class="ri-delete-bin-line"></i></button>
            </form>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
