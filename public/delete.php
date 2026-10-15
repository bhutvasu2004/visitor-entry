<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid($_POST['csrf'] ?? null)) {
    flash('danger', 'That request was not valid. Please try again.');
    redirect('dashboard.php');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    flash('danger', 'Visitor not found.');
    redirect('dashboard.php');
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT photo_file FROM visitors WHERE id = ?');
    $stmt->execute([$id]);
    $file = $stmt->fetchColumn();

    if ($file === false) {
        flash('danger', 'Visitor not found.');
    } else {
        $pdo->prepare('DELETE FROM visitors WHERE id = ?')->execute([$id]);
        delete_photo_file((string) $file);
        flash('success', 'Visitor entry deleted.');
    }
} catch (Throwable $e) {
    error_log('[visitor-entry] delete.php: ' . $e->getMessage());
    flash('danger', 'Could not delete that entry.');
}

$back = 'dashboard.php';
if (!empty($_POST['return']) && preg_match('/^[a-z0-9=&%_.\-]*$/i', (string) $_POST['return'])) {
    $back .= '?' . $_POST['return'];
}
redirect($back);
