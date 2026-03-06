<?php
// Session flash messages — set before a redirect, pop once on the next page.

class Flash
{
    public static function set(string $msg, string $type = 'ok'): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    }

    // Reads and clears the stored flash. Returns ['msg'=>'','type'=>''] if none.
    public static function pop(): array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f ?? ['msg' => '', 'type' => ''];
    }
}
