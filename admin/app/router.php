<?php
// Simple router for PHP built-in server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');

// Map root to index
if ($uri === '' || $uri === 'index.php') {
    require __DIR__ . '/index.php';
    return;
}

// Only allow known PHP pages
$allowed = ['accounts.php', 'passwd.php', 'dns.php', 'logout.php'];
if (in_array($uri, $allowed, true) && file_exists(__DIR__ . '/' . $uri)) {
    require __DIR__ . '/' . $uri;
    return;
}

// Static files (css, js, images) — let built-in server handle them
return false;
