<?php
require '_auth.php';
require_once __DIR__ . '/lib/AdminService.php';

AdminService::handlePost('smtp');
$flash = Flash::pop();
$data  = AdminService::getAccountsData();
$creds = $data['creds'];
$imaps = $data['imaps'];
$conn  = AdminService::getConnInfo();

$page  = 'smtp';
$title = 'SMTP Credentials';
require '_head.php';
?>

<div class="page-head">
  <h2 class="page-title">SMTP Credentials</h2>
  <span class="page-sub"><?= htmlspecialchars(DOMAIN) ?></span>
</div>

<?php if ($flash['msg']): ?>
<div class="flash flash-<?= $flash['type'] ?>">
  <?= $flash['type'] === 'ok' ? '✔' : '✖' ?> <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Connection Info -->
<div class="panel">
  <div class="panel-head"><h3>Connection Info</h3></div>
  <div class="panel-body">
    <p style="margin:.25rem 0 .6rem;color:#334155;font-size:.95rem">Server host: <strong><?= htmlspecialchars($conn['hostname']) ?></strong></p>
    <div style="display:flex;gap:1.5rem;flex-wrap:wrap">
      <div style="min-width:220px">
        <div style="font-size:.78rem;color:#64748b;margin-bottom:.4rem">SMTP (incoming / MX)</div>
        <?php if (empty($conn['smtp'])): ?>
          <div class="mono">Default port: 25 (tcp)</div>
        <?php else: ?>
          <?php foreach ($conn['smtp'] as $s): ?>
            <div style="display:flex;gap:.5rem;align-items:center">
              <div class="mono">Host: <?= htmlspecialchars($conn['hostname']) ?> &nbsp; Port: <?= htmlspecialchars($s['port']) ?> <?= $s['proto']==='tls'?'(TLS)':'' ?></div>
              <button type="button" class="ax-btn ax-btn--sm" onclick="copyText('<?= htmlspecialchars($conn['hostname']) ?>:<?= htmlspecialchars($s['port']) ?>')">Copy</button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div style="min-width:220px">
        <div style="font-size:.78rem;color:#64748b;margin-bottom:.4rem">Submission (client SMTP)</div>
        <?php if (empty($conn['submission'])): ?>
          <div class="mono">Ports: 587 (STARTTLS), 465 (SMTPS)</div>
        <?php else: ?>
          <?php foreach ($conn['submission'] as $s): ?>
            <div style="display:flex;gap:.5rem;align-items:center">
              <div class="mono">Host: <?= htmlspecialchars($conn['hostname']) ?> &nbsp; Port: <?= htmlspecialchars($s['port']) ?> <?= $s['proto']==='tls'?'(TLS)':'' ?></div>
              <button type="button" class="ax-btn ax-btn--sm" onclick="copyText('<?= htmlspecialchars($conn['hostname']) ?>:<?= htmlspecialchars($s['port']) ?>')">Copy</button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div style="min-width:220px">
        <div style="font-size:.78rem;color:#64748b;margin-bottom:.4rem">IMAP (clients)</div>
        <?php if (empty($conn['imap'])): ?>
          <div class="mono">Ports: 143 (STARTTLS), 993 (IMAPS)</div>
        <?php else: ?>
          <?php foreach ($conn['imap'] as $s): ?>
            <div style="display:flex;gap:.5rem;align-items:center">
              <div class="mono">Host: <?= htmlspecialchars($conn['hostname']) ?> &nbsp; Port: <?= htmlspecialchars($s['port']) ?> <?= $s['proto']==='tls'?'(TLS)':'' ?></div>
              <button type="button" class="ax-btn ax-btn--sm" onclick="copyText('<?= htmlspecialchars($conn['hostname']) ?>:<?= htmlspecialchars($s['port']) ?>')">Copy</button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div style="margin-top:.6rem;color:#64748b;font-size:.85rem">
      Use these settings for application SMTP/IMAP connections. POP3 is not provided by Maddy.
    </div>
  </div>
</div>

<!-- Examples -->
<div class="panel">
  <div class="panel-head"><h3>Client Examples</h3></div>
  <div class="panel-body">
    <div style="font-size:.85rem;color:#334155;margin-bottom:.5rem">Submission (client SMTP) example</div>
    <pre style="background:#0f172a;color:#fff;padding:.6rem;border-radius:6px;font-size:.85rem;overflow:auto">Host: <?= htmlspecialchars($conn['hostname']) ?>
Port: 587 (STARTTLS) or 465 (SMTPS)
Username: your-user@<?= htmlspecialchars(DOMAIN) ?>
Password: (the credential you created)

# Example (PHPMailer)
$mail->isSMTP();
$mail->Host = '<?= htmlspecialchars($conn['hostname']) ?>';
$mail->Port = 587;
$mail->SMTPSecure = 'tls';
$mail->SMTPAuth = true;
$mail->Username = 'your-user@<?= htmlspecialchars(DOMAIN) ?>';
$mail->Password = 'PASSWORD';
</pre>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3>Credentials Management</h3></div>
  <div class="panel-body">
    <p style="margin:0;color:#334155">Manage SMTP and IMAP credentials on the <a href="/accounts.php">Accounts</a> page.</p>
  </div>
</div>

<!-- Credentials are managed on the Accounts page -->

<?php require '_foot.php'; ?>
