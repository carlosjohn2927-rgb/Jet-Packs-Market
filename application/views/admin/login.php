<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin sign in | <?= vp_safe_html($site_name) ?></title>
    <link rel="icon" href="<?= vp_safe_html(vp_favicon_url()) ?>">
    <link rel="apple-touch-icon" href="<?= vp_safe_html(vp_logo_url('light')) ?>">
    <link rel="manifest" href="<?= base_url('site.webmanifest') ?>">
    <meta name="theme-color" content="#0b1424">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= CSS_URL ?>app.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="font-sans bg-ink-900 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="mb-7">
            <a href="<?= base_url() ?>" title="Go to the public website">
                <img src="<?= vp_safe_html(vp_logo_url('light')) ?>" alt="<?= vp_safe_html(vp_site('logo_alt', $site_name)) ?>" class="h-12 w-auto max-w-full object-contain">
            </a>
            <div class="text-xs uppercase tracking-widest text-gray-500 mt-3">Admin sign in</div>
        </div>
        <?php if ($flash): ?>
            <div class="rounded-lg px-4 py-3 mb-4 border <?= $flash['type']==='error'?'bg-red-50 border-red-200 text-red-800':'bg-blue-50 border-blue-200 text-blue-800' ?>">
                <?= vp_safe_html($flash['message']) ?>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= base_url('admin/login') ?>" class="space-y-4">
            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
            <input type="hidden" name="next" value="<?= vp_safe_html((string) $this->input->get('next')) ?>">
            <div>
                <label for="email">Email</label>
                <input class="vp-input" type="email" id="email" name="email" required autofocus>
            </div>
            <div>
                <label for="password">Password</label>
                <input class="vp-input" type="password" id="password" name="password" required>
            </div>
            <button class="vp-btn vp-btn-primary w-full justify-center" type="submit">Sign in to admin</button>
        </form>
        <p class="text-xs text-gray-500 mt-6 text-center">
            <a class="hover:underline" href="<?= base_url() ?>">Back to public site</a>
        </p>
    </div>
</body>
</html>
