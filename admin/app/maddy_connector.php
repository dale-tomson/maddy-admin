<?php
// Central Maddy connector: wrapper helpers used across admin pages.

if (!defined('CONTAINER')) {
    // CONTAINER should be defined by the including bootstrap (_auth.php)
    define('CONTAINER', getenv('MADDY_CONTAINER') ?: 'maddy');
}

if (!defined('DOMAIN')) {
    // Resolve domain: prefer environment, then try maddy_data/maddy.conf, otherwise empty.
    function read_primary_domain_from_conf(): string {
        $conf_file = __DIR__ . '/../../maddy_data/maddy.conf';
        if (!is_readable($conf_file)) return '';
        $c = file_get_contents($conf_file);
        if (preg_match('/^\$\(primary_domain\)\s*=\s*(\S+)/m', $c, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    $resolved = getenv('MADDY_DOMAIN') ?: read_primary_domain_from_conf();
    define('DOMAIN', $resolved ?: '');
}

// Run a command inside the maddy container. If $stdin is provided, pipe it to the command.
function maddy(string $cmd, ?string $stdin = null): string {
    $c = escapeshellarg(CONTAINER);
    if ($stdin !== null) {
        $full = "echo " . escapeshellarg($stdin) . " | docker exec -i $c $cmd 2>&1";
    } else {
        $full = "docker exec $c $cmd 2>&1";
    }
    return trim(shell_exec($full) ?? '');
}

// Return array of credential accounts (one per line from `maddy creds list`).
function listAccounts(): array {
    $raw = maddy('maddy creds list');
    $accounts = [];
    foreach (explode("\n", $raw) as $line) {
        $line = trim($line);
        if ($line !== '') $accounts[] = $line;
    }
    return $accounts;
}

// Flash helpers (store simple msg/type in session)
function popFlash(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f ?? ['msg' => '', 'type' => ''];
}

function setFlash(string $msg, string $type = 'ok'): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

?>
