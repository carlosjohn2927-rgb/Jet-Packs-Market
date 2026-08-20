<!doctype html>
<html><head><meta charset="utf-8"><title><?= htmlspecialchars($subject ?? $siteName) ?></title></head>
<body style="margin:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#101b2e;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 0;">
  <tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e6eaf0;">
      <tr><td style="background:linear-gradient(135deg,#2f78ff,#0e3893);padding:20px 24px;color:#fff;font-weight:700;font-size:18px;">
        <?= htmlspecialchars($siteName) ?>
      </td></tr>
      <tr><td style="padding:24px;font-size:15px;line-height:1.5;">
        <?= $emailBody ?? '' ?>
      </td></tr>
      <tr><td style="background:#f4f6f9;padding:16px 24px;font-size:12px;color:#5a6478;">
        © <?= date('Y') ?> <?= htmlspecialchars($siteName) ?> &middot; <a href="<?= htmlspecialchars($siteUrl) ?>" style="color:#2f78ff;text-decoration:none;"><?= htmlspecialchars($siteUrl) ?></a>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
