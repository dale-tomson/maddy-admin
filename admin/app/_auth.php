<?php
// Shared bootstrap: session, constants, auth guard
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

if (!defined('ADMIN_PASSWORD')) {
    define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'changeme');
    define('DOMAIN',         getenv('MADDY_DOMAIN')   ?: 'febinanddale.com');
    define('CONTAINER',      getenv('MADDY_CONTAINER') ?: 'maddy');
}

if (empty($_SESSION['auth'])) {
    header('Location: /');
    exit;
}

// Run a maddy command inside the container, optionally piping $stdin
function maddy(string $cmd, ?string $stdin = null): string {
    $c = escapeshellarg(CONTAINER);
    if ($stdin !== null) {
        $full = "echo " . escapeshellarg($stdin) . " | docker exec -i $c $cmd 2>&1";
    } else {
        $full = "docker exec $c $cmd 2>&1";
    }
    return trim(shell_exec($full) ?? '');
}

// List all credential accounts
function listAccounts(): array {
    $raw = maddy('maddy creds list');
    $accounts = [];
    foreach (explode("\n", $raw) as $line) {
        $line = trim($line);
        if ($line) $accounts[] = $line;
    }
    return $accounts;
}

// Read flash from session once
function popFlash(): array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f ?? ['msg' => '', 'type' => ''];
}

function setFlash(string $msg, string $type = 'ok'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
