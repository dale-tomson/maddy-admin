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
// include centralized connector for Maddy operations and helpers
require_once __DIR__ . '/maddy_connector.php';
