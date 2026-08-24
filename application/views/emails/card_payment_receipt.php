<?php ob_start(); ?>
<h2 style="margin:0 0 12px;font-size:20px;">Payment received</h2>
<p>Hi <?= htmlspecialchars($firstName ?: 'there') ?>,</p>
<p>Thank you. We have received your card payment for quote <strong><?= htmlspecialchars($quoteNumber) ?></strong>.</p>
<table cellpadding="6" style="font-size:14px;border-collapse:collapse;margin:12px 0;">
  <tr><td style="color:#5a6478;">Quote</td><td><strong><?= htmlspecialchars($quoteNumber) ?></strong></td></tr>
  <tr><td style="color:#5a6478;">Amount paid</td><td><strong><?= htmlspecialchars($amount) ?> <?= htmlspecialchars($currency) ?></strong></td></tr>
  <?php if (!empty($paidAt)): ?><tr><td style="color:#5a6478;">Received</td><td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($paidAt))) ?></td></tr><?php endif; ?>
</table>
<p>Our team has been notified and will follow up about fulfillment and shipping. Reply to this email if you have any questions.</p>
<?php $emailBody = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
