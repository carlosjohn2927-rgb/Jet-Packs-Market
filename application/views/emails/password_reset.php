<?php ob_start(); ?>
<h2 style="margin:0 0 12px;font-size:20px;">Reset your password</h2>
<p>Hi <?= htmlspecialchars($firstName ?: 'there') ?>,</p>
<p>We received a request to reset the password for your <?= htmlspecialchars($siteName) ?> account. Click the button below to choose a new password:</p>
<p style="margin:24px 0;">
  <a href="<?= htmlspecialchars($resetUrl) ?>" style="display:inline-block;background:#2f78ff;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:600;">Reset my password</a>
</p>
<p>This link is valid for <?= (int) $ttlMinutes ?> minutes and can be used once. If you didn't request a reset, you can safely ignore this email - your password will remain unchanged.</p>
<p style="font-size:12px;color:#5a6478;margin-top:24px;">Or paste this URL into your browser:<br><span style="word-break:break-all;color:#0e3893;"><?= htmlspecialchars($resetUrl) ?></span></p>
<?php $emailBody = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
