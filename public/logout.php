<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

// Logout only works as a POST with a valid token, so other sites can't log you out.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf'] ?? null)) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}
redirect('login.php');
