<?php
/**
 * 403 — permission denied inside the admin area.
 * Rendered standalone (no layout) because the request is terminated here.
 */
$name = trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''));
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Access denied</title>
    <link rel="icon" href="<?= vp_favicon_url() ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-6 font-sans">
    <div class="bg-white rounded-2xl shadow-lg max-w-lg w-full p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-red-50 text-red-600 flex items-center justify-center mx-auto">
            <i class="ri-lock-2-line text-3xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mt-5">Access denied</h1>
        <p class="text-slate-600 mt-3"><?= vp_safe_html($message ?? 'You do not have permission to view this page.') ?></p>

        <?php if (!empty($permission)): ?>
            <p class="mt-4 text-xs text-slate-500">
                Required permission:
                <code class="bg-slate-100 border rounded px-2 py-1 font-mono"><?= vp_safe_html($permission) ?></code>
            </p>
        <?php endif; ?>

        <p class="mt-4 text-sm text-slate-500">
            Signed in as <strong><?= vp_safe_html($name ?: ($user['email'] ?? 'unknown')) ?></strong>
            (<?= vp_safe_html(vp_role_label($user['role'] ?? '')) ?>).
            Ask the Super Admin to grant this permission.
        </p>

        <div class="mt-7 flex flex-wrap gap-3 justify-center">
            <a href="<?= base_url('admin') ?>" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold px-5 py-2.5 rounded-lg">
                <i class="ri-dashboard-line"></i> Back to dashboard
            </a>
            <a href="<?= base_url() ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-800 font-semibold px-5 py-2.5 rounded-lg">
                <i class="ri-external-link-line"></i> View website
            </a>
        </div>
    </div>
</body>
</html>
