<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

// Tiny endpoint the dashboard polls to learn whether a new visitor has arrived.
if (!is_logged_in()) {
    json_out(['ok' => false], 401);
}

try {
    $row = db()->query('SELECT COALESCE(MAX(id), 0) AS latest, COUNT(*) AS total FROM visitors')->fetch();
    json_out(['ok' => true, 'latest' => (int) $row['latest'], 'total' => (int) $row['total']]);
} catch (Throwable $e) {
    error_log('[visitor-entry] api_latest.php: ' . $e->getMessage());
    json_out(['ok' => false], 500);
}
