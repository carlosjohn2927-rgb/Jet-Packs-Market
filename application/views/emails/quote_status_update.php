<?php ob_start(); ?>
<h2 style="margin:0 0 12px;font-size:20px;">Your RFQ has been updated</h2>
<p>Hi <?= htmlspecialchars($firstName ?: 'there') ?>,</p>
<p>The status of your Request for Quote <strong><?= htmlspecialchars($quoteNumber) ?></strong> has been updated:</p>
<p>From <strong><?= htmlspecialchars($oldStatus) ?></strong> &rarr; <strong><?= htmlspecialchars($newStatus) ?></strong></p>
<?php if (!empty($note)): ?>
  <blockquote style="margin:0;padding:12px 16px;border-left:3px solid #2f78ff;background:#eef5ff;border-radius:6px;">
    <?= nl2br(htmlspecialchars($note)) ?>
  </blockquote>
<?php endif; ?>
<p>We'll follow up with the next step shortly. Reply to this email if you have any questions.</p>
<?php $emailBody = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
