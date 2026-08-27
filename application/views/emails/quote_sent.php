<?php ob_start(); ?>
<h2 style="margin:0 0 12px;font-size:20px;">Your Halyk Petroleum quotation</h2>
<p>Hi <?= htmlspecialchars($firstName ?: 'there') ?>,</p>
<p>Thank you for your request for quotation. Quotation <strong><?= htmlspecialchars($quoteNumber) ?></strong><?= $companyName ? ' for ' . htmlspecialchars($companyName) : '' ?> is ready.</p>
<?php if (!empty($items_html)): ?>
  <p style="margin-bottom:6px;"><strong>Quoted parts</strong></p>
  <div style="margin:0 0 12px;padding:12px 16px;border-left:3px solid #f5a623;background:#f7f9fc;border-radius:6px;font-size:14px;line-height:1.7;">
    <?= $items_html ?>
  </div>
<?php endif; ?>
<p><strong>Total:</strong> <?= htmlspecialchars(is_string($total) ? $total : '') ?> <?= htmlspecialchars($currency) ?></p>
<?php if (!empty($validUntil)): ?>
  <p><strong>Valid until:</strong> <?= htmlspecialchars($validUntil) ?></p>
<?php endif; ?>
<p style="margin:18px 0;">
  <?php if (!empty($pdf_url)): ?>
    <a href="<?= htmlspecialchars($pdf_url) ?>" style="display:inline-block;background:#135fd4;color:#fff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:bold;">Download quotation PDF</a>
  <?php endif; ?>
  <?php if (!empty($account_url)): ?>
    &nbsp;<a href="<?= htmlspecialchars($account_url) ?>" style="display:inline-block;border:1px solid #135fd4;color:#135fd4;text-decoration:none;padding:11px 20px;border-radius:8px;font-weight:bold;">View in your account</a>
  <?php endif; ?>
</p>
<p>Parts ship with FAA Form 8130-3 and/or EASA Form 1 release documentation and full traceability where applicable. Reply to this email to confirm the order, request changes, or ask about AOG / urgent delivery.</p>
<?php $emailBody = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
