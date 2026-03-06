<?php
require '_auth.php';

$flash = popFlash();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $identifier = preg_replace('/[^a-zA-Z0-9._+\-]/', '', trim($_POST['identifier'] ?? ''));
    $email = $identifier ? $identifier . '@' . DOMAIN : '';
    $pw = $_POST['password'] ?? '';

    if ($action === 'create_smtp') {
        if (!$identifier) {
            setFlash('Username is required.', 'err');
        } else {
            if (!$pw) $pw = bin2hex(random_bytes(12));
            maddy('maddy creds create ' . escapeshellarg($email), $pw);
            setFlash('Created SMTP credential: ' . $email);
        }
        header('Location: /smtp.php'); exit;
    }

    if ($action === 'create_imap') {
        if ($email) {
            maddy('maddy imap-acct create ' . escapeshellarg($email));
            setFlash('Created IMAP account: ' . $email);
        }
        header('Location: /smtp.php'); exit;
    }

    if ($action === 'delete_smtp') {
        $email = $_POST['email'] ?? '';
        if ($email) {
            maddy('maddy creds remove ' . escapeshellarg($email));
            setFlash('Removed credential: ' . $email);
        }
        header('Location: /smtp.php'); exit;
    }

    if ($action === 'delete_imap') {
        $email = $_POST['email'] ?? '';
        if ($email) {
            // confirm removal non-interactively
            $out = shell_exec("printf 'y\\n' | docker exec " . escapeshellarg(CONTAINER) . " maddy imap-acct remove " . escapeshellarg($email) . " 2>&1");
            setFlash('Removed IMAP account: ' . $email);
        }
        header('Location: /smtp.php'); exit;
    }
}

// Lists
$creds_raw = maddy('maddy creds list');
$creds = array_filter(array_map('trim', explode("\n", $creds_raw)));

$imap_raw = maddy('maddy imap-acct list');
$imaps = array_filter(array_map('trim', explode("\n", $imap_raw)));

// connection info for display
$conf_file = __DIR__ . '/../../maddy_data/maddy.conf';
$conn = ['smtp' => [], 'submission' => [], 'imap' => [], 'hostname' => DOMAIN];
if (is_readable($conf_file)) {
  $c = file_get_contents($conf_file);
  if (preg_match('/^\$\(hostname\)\s*=\s*(\S+)/m', $c, $m)) {
    $conn['hostname'] = trim($m[1]);
  }
  if (preg_match_all('/^\s*(smtp|submission|imap)\s+([^\{\n]+)/m', $c, $mats, PREG_SET_ORDER)) {
    foreach ($mats as $m) {
      $key = $m[1];
      $parts = preg_split('/\s+/', trim($m[2]));
      foreach ($parts as $p) {
        if (preg_match('/:(\d+)/', $p, $pm)) {
          $port = $pm[1];
          $proto = strpos($p, 'tls://') === 0 || strpos($p, 'smtps://') === 0 ? 'tls' : (strpos($p, 'tcp://') === 0 ? 'tcp' : 'unknown');
          $conn[$key][] = ['port' => $port, 'proto' => $proto, 'raw' => $p];
        }
      }
    }
  }
}

$page = 'smtp';
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
<?php // copyText / toast handled globally in _foot.php ?>
