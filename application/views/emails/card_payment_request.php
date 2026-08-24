<?php ob_start(); ?>
<h2 style="margin:0 0 12px;font-size:20px;">Your secure card payment link</h2>
<p>Hi <?= htmlspecialchars($firstName ?: 'there') ?>,</p>
<p>Your quote <strong><?= htmlspecialchars($quoteNumber) ?></strong> has been approved. You can pay the amount below by card through our secure Stripe checkout.</p>
<table cellpadding="6" style="font-size:14px;border-collapse:collapse;margin:12px 0;">
  <tr><td style="color:#5a6478;">Quote</td><td><strong><?= htmlspecialchars($quoteNumber) ?></strong></td></tr>
  <tr><td style="color:#5a6478;">Amount due</td><td><strong><?= htmlspecialchars($amount) ?> <?= htmlspecialchars($currency) ?></strong></td></tr>
  <?php if (!empty($expiresAt)): ?><tr><td style="color:#5a6478;">Link expires</td><td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($expiresAt))) ?></td></tr><?php endif; ?>
</table>
<p style="margin:24px 0;">
  <a href="<?= htmlspecialchars($paymentUrl) ?>" style="display:inline-block;background:#2f78ff;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:600;">Pay securely by card</a>
</p>
<p>Your card details are entered directly with Stripe. <?= htmlspecialchars($siteName) ?> does not see or store your card number or CVC.</p>
<p style="font-size:12px;color:#5a6478;margin-top:24px;">For your security, this link is personal to this quote. Or paste this URL into your browser:<br><span style="word-break:break-all;color:#0e3893;"><?= htmlspecialchars($paymentUrl) ?></span></p>
<?php $emailBody = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
