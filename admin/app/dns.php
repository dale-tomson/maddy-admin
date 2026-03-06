<?php
require '_auth.php';

$flash        = popFlash();
$flash = popFlash();
require_once dirname(__DIR__) . '/lib/AdminService.php';

// Delegate POST (DKIM generation) and fetch DNS data
AdminService::handlePost('dns');
$dns = AdminService::getDnsData();
$dkimSelector = $dns['dkimSelector'];
$dkimKeyPath = '/data/dkim_keys/' . DOMAIN . '/' . $dkimSelector . '.key';
$hostname = 'mx.' . DOMAIN;
$dkimValue = $dns['dkimValue'];
$dkimError = $dns['dkimError'];
$dkimExists = $dns['dkimExists'];
$serverIp = $dns['serverIp'];
$records = $dns['records'];

$page  = 'dns';
$title = 'DNS Records';
require '_head.php';
?>

<style>
  .rec-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:1.25rem; }
  @media(max-width:640px) { .rec-grid { grid-template-columns:1fr; } }

  .rec-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
  .rec-head { display:flex; align-items:center; justify-content:space-between;
              padding:.6rem 1rem; background:#fafafa; border-bottom:1px solid #f1f5f9; }
  .rec-type { font-size:.7rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
              padding:.15rem .5rem; border-radius:4px; }
  .rec-type-MX  { background:#dbeafe; color:#1d4ed8; }
  .rec-type-A   { background:#fef9c3; color:#854d0e; }
  .rec-type-TXT { background:#dcfce7; color:#166534; }
  .rec-body { padding:.9rem 1rem; }
  .rec-label { font-size:.72rem; font-weight:600; color:#94a3b8; text-transform:uppercase;
               letter-spacing:.05em; margin-bottom:.2rem; }
  .rec-name  { font-family:ui-monospace,"SF Mono",monospace; font-size:.82rem; color:#0f172a;
               background:#f1f5f9; border-radius:4px; padding:.1rem .35rem; display:inline-block; }
  .rec-value-wrap { position:relative; margin-top:.5rem; }
  .rec-value { display:block; width:100%; font-family:ui-monospace,"SF Mono",monospace;
               font-size:.78rem; color:#1e293b; background:#f8fafc; border:1px solid #e2e8f0;
               border-radius:6px; padding:.55rem .9rem; padding-right:2.8rem;
               word-break:break-all; white-space:pre-wrap; resize:none; line-height:1.5; }
  .copy-btn { position:absolute; top:.4rem; right:.4rem; background:#e2e8f0; border:none;
              border-radius:4px; padding:.2rem .45rem; font-size:.7rem; color:#475569;
              cursor:pointer; transition:background .15s; }
  .copy-btn:hover { background:#cbd5e1; }
  .copy-btn.copied { background:#bbf7d0; color:#166534; }
  .rec-note   { font-size:.75rem; color:#94a3b8; margin-top:.45rem; line-height:1.4; }
  .warn-tag   { font-size:.7rem; color:#b45309; background:#fef3c7; border:1px solid #fde68a;
                border-radius:4px; padding:.1rem .4rem; margin-left:.4rem; }

  /* DKIM section */
  .dkim-panel { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; margin-bottom:1.25rem; }
  .dkim-head  { display:flex; align-items:center; justify-content:space-between;
                padding:.75rem 1.25rem; background:#fafafa; border-bottom:1px solid #f1f5f9; }
  .dkim-head h3 { font-size:.875rem; font-weight:600; color:#334155; margin:0; }
  .dkim-body  { padding:1.25rem; }
  .status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:.4rem; }
  .dot-ok     { background:#22c55e; }
  .dot-miss   { background:#f59e0b; }
  .code-block { font-family:ui-monospace,"SF Mono",monospace; font-size:.78rem; background:#0f172a;
                color:#e2e8f0; border-radius:8px; padding:1rem 1.25rem; line-height:1.7;
                overflow-x:auto; margin:.75rem 0; }
  .code-block .cmt { color:#64748b; }
  .code-block .key { color:#7dd3fc; }
  .code-block .val { color:#86efac; }
</style>

<div class="page-head">
  <h2 class="page-title">DNS Records</h2>
  <span class="page-sub"><?= htmlspecialchars(DOMAIN) ?></span>
</div>

<?php if ($flash['msg']): ?>
<div class="flash flash-<?= $flash['type'] ?>">
  <?= $flash['type'] === 'ok' ? '✔' : '✖' ?> <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Required records ──────────────────────────────────────────────────────── -->
<div class="panel-head" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px 10px 0 0;margin-bottom:0;border-bottom:none">
  <h3 style="font-size:.875rem;font-weight:600;color:#334155;margin:0;padding:.75rem 1.25rem">Required Records</h3>
</div>
<div class="rec-grid" style="border:1px solid #e2e8f0;border-top:none;border-radius:0 0 10px 10px;padding:1.25rem;background:#fff;margin-bottom:1.25rem">
  <?php foreach ($records as $r): ?>
  <div class="rec-card">
    <div class="rec-head">
      <div>
        <span class="rec-type rec-type-<?= $r['type'] ?>"><?= $r['type'] ?></span>
        <?php if (!empty($r['warn'])): ?>
        <span class="warn-tag">⚠ not resolved</span>
        <?php endif; ?>
      </div>
      <span style="font-size:.8rem;font-weight:600;color:#64748b"><?= htmlspecialchars($r['label']) ?></span>
    </div>
    <div class="rec-body">
      <div class="rec-label">Name / Host</div>
      <span class="rec-name"><?= htmlspecialchars($r['name']) ?></span>

      <div class="rec-label" style="margin-top:.6rem">Value</div>
      <div class="rec-value-wrap">
        <textarea class="rec-value" rows="<?= strlen($r['value']) > 60 ? 3 : 1 ?>" readonly
          onclick="this.select()"><?= htmlspecialchars($r['value']) ?></textarea>
        <button class="copy-btn" onclick="copyVal(this)">Copy</button>
      </div>
      <div class="rec-note"><?= htmlspecialchars($r['note']) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- DKIM ──────────────────────────────────────────────────────────────────── -->
<div class="dkim-panel">
  <div class="dkim-head">
    <h3>
      <span class="status-dot <?= $dkimExists ? 'dot-ok' : 'dot-miss' ?>"></span>
      DKIM — <?= $dkimSelector ?>._domainkey.<?= htmlspecialchars(DOMAIN) ?>
    </h3>
    <?php if (!$dkimExists): ?>
    <form method="post" style="margin:0">
      <input type="hidden" name="action" value="gen-dkim">
      <button type="submit" class="ax-btn ax-btn--sm ax-btn--primary"
        style="min-height:30px;padding:.2rem .85rem;font-size:.8rem">
        Generate Key
      </button>
    </form>
    <?php else: ?>
    <form method="post" style="margin:0">
      <input type="hidden" name="action" value="gen-dkim">
      <button type="submit" class="ax-btn ax-btn--sm ax-btn--outline-danger"
        style="min-height:30px;padding:.2rem .85rem;font-size:.8rem"
        onclick="return confirm('Regenerate DKIM key? Old key will be replaced — update DNS after.')">
        Regenerate
      </button>
    </form>
    <?php endif; ?>
  </div>
  <div class="dkim-body">

    <?php if ($dkimError): ?>
    <div class="flash flash-err">✖ <?= htmlspecialchars($dkimError) ?></div>

    <?php elseif (!$dkimExists): ?>
    <p style="color:#64748b;font-size:.875rem;margin:0 0 1rem">
      No DKIM key found for <strong><?= htmlspecialchars(DOMAIN) ?></strong>.
      Click <strong>Generate Key</strong> to create a 2048-bit RSA key pair.
      The private key is stored inside the Maddy container at
      <code style="font-size:.78rem">/data/dkim_keys/<?= htmlspecialchars(DOMAIN) ?>/<?= $dkimSelector ?>.key</code>.
    </p>
    <p style="color:#94a3b8;font-size:.78rem;margin:0">
      After generating, add the DNS TXT record shown here, then update
      <code style="font-size:.78rem">maddy_data/maddy.conf</code> — see snippet below.
    </p>

    <?php else: ?>
    <div style="display:grid;grid-template-columns:auto 1fr;gap:.75rem 1.25rem;align-items:start;margin-bottom:1rem">
      <div style="font-size:.72rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;padding-top:.6rem">Type</div>
      <div><span class="rec-type rec-type-TXT">TXT</span></div>

      <div style="font-size:.72rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;padding-top:.4rem">Name&nbsp;/&nbsp;Host</div>
      <div>
        <div class="rec-value-wrap">
          <textarea class="rec-value" rows="1" readonly
            onclick="this.select()"><?= htmlspecialchars($dkimSelector . '._domainkey') ?></textarea>
          <button class="copy-btn" onclick="copyVal(this)">Copy</button>
        </div>
      </div>

      <div style="font-size:.72rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;padding-top:.4rem">Value</div>
      <div>
        <div class="rec-value-wrap">
          <textarea class="rec-value" rows="4" readonly
            onclick="this.select()"><?= htmlspecialchars($dkimValue ?? '') ?></textarea>
          <button class="copy-btn" onclick="copyVal(this)">Copy</button>
        </div>
        <div class="rec-note">Paste the entire string as a single DNS TXT record value.</div>
      </div>
    </div>
    <?php endif; ?>

    <!-- maddy.conf snippet ──────────────────────────────────────────────── -->
    <details style="margin-top:<?= $dkimExists ? '1rem' : '.5rem' ?>">
      <summary style="cursor:pointer;font-size:.825rem;font-weight:600;color:#475569;
        user-select:none;padding:.4rem 0">
        ▸ maddy.conf — DKIM signing config
      </summary>
      <p style="font-size:.8rem;color:#94a3b8;margin:.6rem 0">
        Add the <code style="font-size:.75rem">modify.dkim</code> block inside your
        <code style="font-size:.75rem">submission</code> block in
        <code style="font-size:.75rem">maddy_data/maddy.conf</code>, then restart Maddy.
      </p>
      <div class="code-block">
<span class="cmt"># --- 4. Submission ---</span>
<span class="key">submission</span> <span class="val">tls://0.0.0.0:465 tcp://0.0.0.0:587</span> {
    auth &amp;local_authdb
    <span class="cmt"># Add this block:</span>
    <span class="key">modify.dkim</span> {
        <span class="key">domains</span>   { <span class="val"><?= htmlspecialchars(DOMAIN) ?></span> }
        <span class="key">selector</span>  <span class="val"><?= $dkimSelector ?></span>
        <span class="key">key_path</span>  <span class="val">/data/dkim_keys/{domain}/{selector}.key</span>
        <span class="key">allow_body_subset</span> <span class="val">true</span>
    }
    default_source {
        check { authorize_sender }
        deliver_to remote
    }
}</div>
      <p style="font-size:.78rem;color:#94a3b8;margin:.5rem 0 0">
        After editing maddy.conf, restart: <code style="font-size:.75rem">docker compose restart maddy</code>
      </p>
    </details>

  </div>
</div>

<!-- Summary table ─────────────────────────────────────────────────────────── -->
<div class="panel">
  <div class="panel-head"><h3>Port Reference</h3></div>
  <table class="clean-table">
    <thead>
      <tr><th>Port</th><th>Protocol</th><th>Purpose</th></tr>
    </thead>
    <tbody>
      <tr><td class="mono">25</td>  <td>SMTP</td>    <td style="color:#64748b;font-size:.82rem">Inbound mail from the internet</td></tr>
      <tr><td class="mono">465</td> <td>SMTPS</td>   <td style="color:#64748b;font-size:.82rem">Submission — TLS (mail clients)</td></tr>
      <tr><td class="mono">587</td> <td>SMTP</td>    <td style="color:#64748b;font-size:.82rem">Submission — STARTTLS (mail clients)</td></tr>
      <tr><td class="mono">993</td> <td>IMAPS</td>   <td style="color:#64748b;font-size:.82rem">IMAP — TLS (mail clients)</td></tr>
      <tr><td class="mono">143</td> <td>IMAP</td>    <td style="color:#64748b;font-size:.82rem">IMAP — STARTTLS (mail clients)</td></tr>
    </tbody>
  </table>
</div>

<script>
function copyVal(btn) {
  const textarea = btn.previousElementSibling;
  navigator.clipboard.writeText(textarea.value).then(() => {
    btn.textContent = 'Copied!';
    btn.classList.add('copied');
    setTimeout(() => { btn.textContent = 'Copy'; btn.classList.remove('copied'); }, 2000);
  });
}
</script>

<?php require '_foot.php'; ?>
