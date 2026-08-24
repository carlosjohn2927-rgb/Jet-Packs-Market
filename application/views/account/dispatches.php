<?php /** @var array $dispatches */ ?>

<?php
$status_badge = function ($status) {
    $map = [
        'REQUESTED'  => 'bg-amber-100 text-amber-800',
        'CONFIRMED'  => 'bg-sky-100 text-sky-800',
        'IN_TRANSIT' => 'bg-indigo-100 text-indigo-800',
        'DELIVERED'  => 'bg-emerald-100 text-emerald-800',
        'CANCELLED'  => 'bg-gray-200 text-gray-700',
    ];
    return '<span class="px-2 py-1 rounded-full text-xs font-semibold ' . ($map[$status] ?? 'bg-gray-100 text-gray-700') . '">' . $status . '</span>';
};
?>

<section class="container mx-auto px-4 py-10">
    <h1 class="text-3xl font-extrabold text-ink-900">AOG dispatches</h1>
    <p class="text-ink-800 mt-1">Track emergency and priority part dispatches in real time.</p>

    <div class="grid lg:grid-cols-4 gap-6 mt-8">
        <?= $this->load->view('account/_nav', get_defined_vars(), TRUE) ?>

        <div class="lg:col-span-3">
            <?php if (empty($dispatches)): ?>
                <div class="vp-card vp-card-pad text-center text-ink-800">
                    <p>No dispatches to track yet. AOG and priority shipments requested by our team will appear here.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($dispatches as $d): ?>
                        <a class="vp-card vp-card-pad flex flex-wrap items-center gap-4 hover:shadow-md transition" href="<?= base_url('account/dispatches/' . $d['id']) ?>">
                            <span class="text-2xl text-brand-600"><i class="ri-truck-line"></i></span>
                            <span class="min-w-[8rem]">
                                <span class="block font-semibold text-ink-900"><?= vp_safe_html($d['reference']) ?></span>
                                <span class="block text-sm text-ink-800"><?= vp_safe_html($d['aircraft'] ?: 'General dispatch') ?></span>
                            </span>
                            <span class="text-sm text-ink-800 flex-1"><?= vp_safe_html(mb_strlen($d['partDescription']) > 80 ? mb_substr($d['partDescription'], 0, 80) . '…' : $d['partDescription']) ?></span>
                            <span><?= $status_badge($d['status']) ?></span>
                            <?php if ($d['eta']): ?><span class="text-sm text-ink-800">ETA <?= vp_human_date($d['eta'], 'M j, H:i') ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
