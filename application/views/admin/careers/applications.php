<?php /** @var array $job */ /** @var array $rows */ ?>
<div class="mb-4">
    <a class="text-sm text-brand-600 hover:underline" href="<?= base_url('admin/careers') ?>">&larr; All jobs</a>
</div>
<div class="vp-card vp-card-pad mb-4">
    <h2 class="font-bold"><?= vp_safe_html($job['title']) ?></h2>
    <p class="text-sm text-gray-500"><?= vp_safe_html($job['department']) ?> &middot; <?= vp_safe_html($job['location']) ?></p>
</div>
<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead><tr><th>Applicant</th><th>Email</th><th>Phone</th><th>Status</th><th>Received</th><th>Resume</th></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center text-gray-500">No applications yet.</td></tr>
        <?php else: foreach ($rows as $a): ?>
            <tr>
                <td><?= vp_safe_html($a['name']) ?></td>
                <td class="text-xs"><a class="text-brand-600" href="mailto:<?= vp_safe_html($a['email']) ?>"><?= vp_safe_html($a['email']) ?></a></td>
                <td class="text-xs"><?= vp_safe_html($a['phone'] ?? '') ?></td>
                <td><span class="vp-pill bg-blue-100 text-blue-800"><?= vp_safe_html($a['status']) ?></span></td>
                <td class="text-xs text-gray-500"><?= vp_time_ago($a['createdAt']) ?></td>
                <td class="text-xs"><a class="text-brand-600 hover:underline" href="<?= base_url($a['resumeUrl']) ?>" target="_blank">Download</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
