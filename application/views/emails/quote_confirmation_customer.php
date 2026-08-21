<?php ob_start(); ?>
<h2 style="margin:0 0 12px;font-size:20px;">We received your RFQ</h2>
<p>Hi <?= htmlspecialchars($firstName ?: 'there') ?>,</p>
<p>Thanks for submitting a Request for Quote to <?= htmlspecialchars($siteName) ?>. Your reference number is:</p>
<p style="font-size:22px;font-weight:700;color:#0e3893;letter-spacing:0.5px;"><?= htmlspecialchars($quoteNumber) ?></p>
<p>Our engineering and sales team will review your requirements and respond with a formal quote within <strong>2 business days</strong>.</p>
<p>If you have any updates or additional information, simply reply to this email.</p>
<?php $emailBody = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
