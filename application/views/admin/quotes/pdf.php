<?php
/** @var array $quote */
/** @var array $items */
/** @var array $customer */
/** @var string $site */
/** @var array $contact */
$st = vp_quote_status_label($quote['status']);
?>
<!doctype html>
<html><head>
<meta charset="utf-8">
<title>Quote <?= vp_safe_html($quote['quoteNumber']) ?></title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#1b2740; margin: 40px; }
  h1 { color: #0e3893; margin: 0; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #2f78ff; padding-bottom: 16px; margin-bottom: 24px; }
  .meta { text-align: right; font-size: 12px; }
  table { width: 100%; border-collapse: collapse; margin-top: 16px; }
  th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; text-align: left; }
  th { background: #eef5ff; }
  .footer { margin-top: 40px; font-size: 11px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
  .pill { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
  .pill-<?= $quote['status'] ?> { background: #eef5ff; color: #0e3893; }
  @media print { body { margin: 16mm; } .noprint { display: none; } }
</style>
</head><body>
<div class="noprint" style="text-align:right;margin-bottom:8px;"><button onclick="window.print()" style="background:#2f78ff;color:#fff;border:0;padding:6px 14px;border-radius:6px;cursor:pointer;">Print / Save as PDF</button></div>
<div class="header">
  <div>
    <h1><?= vp_safe_html($site) ?></h1>
    <p style="margin:4px 0 0;color:#6b7280;font-size:12px;">Industrial manufacturing</p>
  </div>
  <div class="meta">
    <div style="font-size:24px;font-weight:800;color:#0e3893;">QUOTATION</div>
    <div><strong><?= vp_safe_html($quote['quoteNumber']) ?></strong></div>
    <div>Date: <?= date('Y-m-d', strtotime($quote['createdAt'])) ?></div>
    <div>Status: <span class="pill pill-<?= $quote['status'] ?>"><?= $st['label'] ?></span></div>
  </div>
</div>

<h3 style="margin-bottom:4px;">Bill to</h3>
<p style="margin-top:0;">
  <strong><?= vp_safe_html($quote['companyName']) ?></strong><br>
  <?= vp_safe_html($quote['contactPerson']) ?><br>
  <?= vp_safe_html($quote['email']) ?><?php if ($quote['phone']): ?> &middot; <?= vp_safe_html($quote['phone']) ?><?php endif; ?><br>
  <?= vp_safe_html($quote['country']) ?>
  <?php if ($quote['address']): ?><br><?= nl2br(vp_safe_html($quote['address'])) ?><?php endif; ?>
</p>

<table>
  <thead><tr><th>Item</th><th style="width:60px;">Qty</th><th>Specifications</th></tr></thead>
  <tbody>
  <?php foreach ($items as $it): ?>
    <tr>
      <td><?= vp_safe_html($it['productName']) ?></td>
      <td style="text-align:center;"><?= (int) $it['quantity'] ?></td>
      <td><?= nl2br(vp_safe_html($it['specifications'] ?? '')) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php if (!empty($quote['notes'])): ?>
  <h3>Customer notes</h3>
  <p><?= nl2br(vp_safe_html($quote['notes'])) ?></p>
<?php endif; ?>

<div class="footer">
  <?= vp_safe_html($site) ?> &middot; <?= vp_safe_html($contact['address']) ?> &middot; <?= vp_safe_html($contact['phone']) ?> &middot; <?= vp_safe_html($contact['email']) ?>
</div>
</body></html>
