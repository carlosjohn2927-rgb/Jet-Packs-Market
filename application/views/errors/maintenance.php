<?php
/**
 * Maintenance page (503) — shown to visitors while maintenance mode is on.
 * The message is editable in Dashboard → Settings → System.
 */
$site = $site ?? vp_site();
?><!doctype html>
<html lang="<?= vp_safe_html($site['language'] ?: 'en') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= vp_safe_html($site['name']) ?> — maintenance</title>
    <meta name="robots" content="noindex">
    <link rel="icon" href="<?= vp_safe_html(vp_favicon_url()) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-6 font-sans">
    <div class="bg-white rounded-2xl shadow-lg max-w-lg w-full p-10 text-center">
        <img src="<?= vp_safe_html(vp_logo_url('light')) ?>" alt="<?= vp_safe_html($site['logo_alt']) ?>" class="h-12 mx-auto object-contain">
        <div class="w-14 h-14 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mt-6">
            <i class="ri-tools-line text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mt-5">We&rsquo;ll be back shortly</h1>
        <p class="text-slate-600 mt-3"><?= vp_safe_html($message) ?></p>
        <?php if (!empty($site['email'])): ?>
            <p class="text-sm text-slate-500 mt-6">
                Need something urgently? <a class="text-blue-600 hover:underline" href="mailto:<?= vp_safe_html($site['email']) ?>"><?= vp_safe_html($site['email']) ?></a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
