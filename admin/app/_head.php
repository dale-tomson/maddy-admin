<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title ?? 'Maddy Admin') ?> — Maddy Admin</title>
<link rel="stylesheet" href="https://unpkg.com/axis-twelve@2.0.2/dist/axis-twelve.min.css">
<style>
  body { background:#f8fafc; }

  /* Nav */
  .nav-bar { background:#0f172a; border-bottom:1px solid #1e293b; padding:.6rem 0; }
  .nav-brand { color:#f1f5f9; font-weight:700; font-size:.95rem; letter-spacing:.01em;
               text-decoration:none; display:flex; align-items:center; gap:.45rem; }
  .nav-brand:hover { text-decoration:none; color:#fff; }
  .nav-pill { display:flex; align-items:center; gap:.25rem; background:#1e293b;
              border-radius:999px; padding:.2rem .3rem; }
  .nav-link { color:#94a3b8; font-size:.82rem; font-weight:500; padding:.28rem .75rem;
              border-radius:999px; transition:background .15s,color .15s; text-decoration:none; }
  .nav-link:hover { color:#f1f5f9; text-decoration:none; }
  .nav-link.active { color:#fff; background:#3b82f6; }
  .nav-logout { font-size:.8rem; font-weight:500; color:#fca5a5; padding:.28rem .75rem;
                border-radius:999px; border:none; background:transparent; cursor:pointer;
                transition:background .15s,color .15s; }
  .nav-logout:hover { background:#7f1d1d; color:#fff; }

  /* Layout */
  main    { padding-top:2rem; padding-bottom:4rem; }
  .wrap   { max-width:860px; margin:0 auto; }

  /* Page heading */
  .page-head { display:flex; align-items:center; justify-content:space-between;
               margin-bottom:1.5rem; }
  .page-title { font-size:1.15rem; font-weight:700; color:#0f172a; margin:0; }
  .page-sub   { font-size:.8rem; color:#94a3b8; background:#e2e8f0;
                padding:.15rem .6rem; border-radius:999px; }

  /* Cards */
  .panel { background:#fff; border:1px solid #e2e8f0; border-radius:10px;
           overflow:hidden; margin-bottom:1.25rem; }
  .panel-head { display:flex; align-items:center; justify-content:space-between;
                padding:.75rem 1.25rem; border-bottom:1px solid #f1f5f9;
                background:#fafafa; }
  .panel-head h3 { font-size:.875rem; font-weight:600; color:#334155; margin:0; }
  .panel-body { padding:1.25rem; }

  /* Badge */
  .badge { background:#e0e7ff; color:#4338ca; font-size:.72rem; font-weight:700;
           padding:.15rem .55rem; border-radius:999px; }

  /* Flash */
  .flash { padding:.65rem 1rem; border-radius:8px; font-size:.875rem;
           margin-bottom:1.25rem; display:flex; align-items:center; gap:.5rem; }
  .flash-ok  { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }
  .flash-err { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; }

  /* Input group */
  .input-group { display:flex; }
  .input-group .ax-input { flex:1; border-radius:.375rem 0 0 .375rem; }
  .input-addon { display:flex; align-items:center; padding:0 .85rem;
                 background:#f1f5f9; border:1px solid #d1d5db; border-left:none;
                 border-radius:0 .375rem .375rem 0; color:#64748b;
                 font-size:.82rem; white-space:nowrap; }

  /* Form row */
  .form-row { display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap; }
  .form-row .ax-form-group { flex:1; min-width:180px; margin-bottom:0; }

  /* Table */
  .clean-table { width:100%; border-collapse:collapse; }
  .clean-table th { font-size:.72rem; font-weight:600; text-transform:uppercase;
                    letter-spacing:.05em; color:#94a3b8; padding:.6rem 1.25rem;
                    border-bottom:1px solid #f1f5f9; text-align:left; }
  .clean-table td { padding:.8rem 1.25rem; border-bottom:1px solid #f8fafc;
                    font-size:.875rem; color:#1e293b; }
  .clean-table tr:last-child td { border-bottom:none; }
  .clean-table tbody tr:hover td { background:#fafafa; }
  .mono { font-family:ui-monospace,"SF Mono",monospace; font-size:.82rem; }

  /* Action buttons inline */
  .act-cell { text-align:right; white-space:nowrap; }
  .act-cell .ax-btn { margin-left:.4rem; }

  /* Empty state */
  .empty { text-align:center; padding:3rem; color:#94a3b8; font-size:.875rem; }
</style>
</head>
<body>
<nav class="nav-bar">
  <div class="ax-container">
    <div style="display:flex;align-items:center;justify-content:space-between">
      <a href="/accounts.php" class="nav-brand">
        <span style="font-size:1.1rem">📬</span> Maddy Admin
      </a>
      <div style="display:flex;align-items:center;gap:.5rem">
        <div class="nav-pill">
          <a href="/accounts.php" class="nav-link <?= ($page??'')==='accounts'?'active':'' ?>">Accounts</a>
          <a href="/passwd.php"   class="nav-link <?= ($page??'')==='passwd'  ?'active':'' ?>">Passwords</a>
          <a href="/dns.php"      class="nav-link <?= ($page??'')==='dns'     ?'active':'' ?>">DNS Records</a>
        </div>
        <form method="post" action="/logout.php" style="margin:0">
          <button type="submit" class="nav-logout">Sign out</button>
        </form>
      </div>
    </div>
  </div>
</nav>
<main>
<div class="ax-container">
<div class="wrap">
