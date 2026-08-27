<?php
/** @var array $quote */
/** @var array $items */
/** @var array $customer */
/** @var string $site */
/** @var array $contact */
$st = vp_quote_status_label($quote['status']);
$currency = $quote['currency'] ?? 'USD';
$total = 0.0;
foreach ($items as $it) {
    if (!empty($it['total'])) {
        $total += (float) $it['total'];
    } elseif (!empty($it['unitPrice'])) {
        $total += (float) $it['unitPrice'] * (int) $it['quantity'];
    }
}
if (!empty($quote['totalAmount'])) $total = (float) $quote['totalAmount'];
?>
<!doctype html>
<html><head>
<meta charset="utf-8">
<title>Quote <?= vp_safe_html($quote['quoteNumber']) ?> — Halyk Petroleum</title>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; color:#101f38; margin: 32px; }
  .brandbar { background:#0b1f3f; color:#fff; padding:18px 24px; border-bottom:4px solid #f5a623; display:flex; justify-content:space-between; align-items:center; }
  .brandbar h1 { margin:0; font-size:22px; letter-spacing:1px; }
  .brandbar .tag { font-size:11px; color:#c9d6f0; margin-top:4px; letter-spacing:1px; }
  .brandbar .co { font-size:11px; text-align:right; color:#e7edf7; line-height:1.6; }
  .meta { display:flex; justify-content:space-between; margin:18px 0 6px; font-size:12px; }
  table { width:100%; border-collapse: collapse; margin-top:12px; font-size:12px; }
  th, td { border-bottom: 1px solid #e5e7eb; padding:8px; text-align:left; vertical-align:top; }
  th { background:#eef5ff; color:#0b2f68; }
  td.num, th.num { text-align:right; }
  .pn { font-family: ui-monospace, Menlo, Consolas, monospace; font-size:11px; color:#334; }
  .footer { margin-top: 40px; font-size: 10px; color:#6b7280; border-top:2px solid #f5a623; padding-top:8px; }
  .pill { display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#eef5ff; color:#0e3893; }
  @media print { body { margin: 12mm; } .noprint { display:none; } }
</style>
</head><body>
<div class="brandbar">
  <div>
    <h1>HALYK PETROLEUM</h1>
    <div class="tag">AIRCRAFT PARTS &amp; COMPONENTS</div>
  </div>
  <div class="co">
    <?= vp_safe_html($contact['address'] ?? '') ?><br>
    <?= vp_safe_html($contact['phone'] ?? '') ?> &middot; <?= vp_safe_html($contact['email'] ?? '') ?>
  </div>
</div>

<div class="noprint" style="text-align:right;margin:8px 0;">
  <button onclick="window.print()" style="background:#135fd4;color:#fff;border:0;padding:8px 16px;border-radius:6px;cursor:pointer;font-weight:700;">Print / Save as PDF</button>
</div>

<div class="meta">
  <div>
    <div style="font-size:22px;font-weight:800;color:#0d4f9e;">QUOTATION</div>
    <div><strong><?= vp_safe_html($quote['quoteNumber']) ?></strong></div>
    <div>Date: <?= date('Y-m-d', strtotime($quote['createdAt'])) ?></div>
    <?php if (!empty($quote['validUntil'])): ?><div>Valid until: <?= date('Y-m-d', strtotime($quote['validUntil'])) ?></div><?php endif; ?>
    <div>Status: <span class="pill"><?= $st['label'] ?></span></div>
  </div>
  <div style="text-align:right;">
    <strong>Bill to</strong><br>
    <?= vp_safe_html($quote['companyName']) ?><br>
    <?= vp_safe_html($quote['contactPerson']) ?><br>
    <?= vp_safe_html($quote['email']) ?><?php if ($quote['phone']): ?> &middot; <?= vp_safe_html($quote['phone']) ?><?php endif; ?><br>
    <?= vp_safe_html($quote['country']) ?>
    <?php if ($quote['address']): ?><br><?= nl2br(vp_safe_html($quote['address'])) ?><?php endif; ?>
  </div>
</div>

<table>
  <thead><tr>
    <th>Part number</th><th>Part / description</th><th>Cond.</th><th class="num">Qty</th>
    <th class="num">Unit price (<?= vp_safe_html($currency) ?>)</th><th class="num">Amount (<?= vp_safe_html($currency) ?>)</th>
  </tr></thead>
  <tbody>
  <?php foreach ($items as $it): ?>
    <tr>
      <td class="pn"><?= vp_safe_html($it['partNumber'] ?? '') ?></td>
      <td>
        <strong><?= vp_safe_html($it['productName']) ?></strong>
        <?php if (!empty($it['manufacturer'])): ?><br><span style="color:#6b7280;"><?= vp_safe_html($it['manufacturer']) ?></span><?php endif; ?>
        <?php if (!empty($it['specifications'])): ?><br><span style="color:#6b7280;"><?= nl2br(vp_safe_html($it['specifications'])) ?></span><?php endif; ?>
      </td>
      <td><?= vp_safe_html($it['condition'] ?? '') ?></td>
      <td class="num"><?= (int) $it['quantity'] ?></td>
      <td class="num"><?= ($it['unitPrice'] !== null && $it['unitPrice'] !== '') ? vp_safe_html(vp_money($it['unitPrice'], $it['currency'] ?? $currency)) : 'On quote' ?></td>
      <td class="num"><?= ($it['total'] !== null && $it['total'] !== '') ? vp_safe_html(vp_money($it['total'], $it['currency'] ?? $currency)) : '—' ?></td>
    </tr>
  <?php endforeach; ?>
  <tr>
    <td colspan="5" class="num" style="font-weight:800;">Total (<?= vp_safe_html($currency) ?>)</td>
    <td class="num" style="font-weight:800;"><?= vp_safe_html(vp_money($total, $currency)) ?></td>
  </tr>
  </tbody>
</table>

<?php if (!empty($quote['notes'])): ?>
  <h3 style="margin-top:20px;">Customer notes</h3>
  <p style="font-size:12px;"><?= nl2br(vp_safe_html($quote['notes'])) ?></p>
<?php endif; ?>

<p style="font-size:11px;color:#4b5563;margin-top:24px;">
  Quotation for aircraft parts and components in <?= vp_safe_html($currency) ?>, EXW Halyk Petroleum unless otherwise stated.
  Parts ship with FAA Form 8130-3 and/or EASA Form 1 release documentation and full traceability where applicable.
  Halyk Petroleum supplies aircraft parts and components — not complete aircraft.
</p>

<div class="footer">
  <?= vp_safe_html($site) ?> &middot; Aircraft Parts &amp; Components &middot;
  <?= vp_safe_html($contact['address'] ?? '') ?> &middot;
  <?= vp_safe_html($contact['phone'] ?? '') ?> &middot;
  <?= vp_safe_html($contact['email'] ?? '') ?>
</div>
</body></html>
