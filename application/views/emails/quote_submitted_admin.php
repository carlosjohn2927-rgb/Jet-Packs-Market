<?php ob_start(); ?>
<h2 style="margin:0 0 12px;font-size:20px;">New RFQ submitted</h2>
<p>A new Request for Quote has been submitted via the website.</p>
<table cellpadding="6" style="font-size:14px;border-collapse:collapse;">
  <tr><td style="color:#5a6478;">Quote #</td><td><strong><?= htmlspecialchars($quoteNumber) ?></strong></td></tr>
  <tr><td style="color:#5a6478;">Company</td><td><?= htmlspecialchars($companyName) ?></td></tr>
  <tr><td style="color:#5a6478;">Contact</td><td><?= htmlspecialchars($contactPerson) ?></td></tr>
  <tr><td style="color:#5a6478;">Email</td><td><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></td></tr>
</table>
<p style="margin-top:16px;">
  <a href="<?= htmlspecialchars($adminUrl) ?>" style="display:inline-block;background:#2f78ff;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-weight:600;">Review in Admin</a>
</p>
<?php $emailBody = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
