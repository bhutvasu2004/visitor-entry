<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

// Photos are NOT in the public folder. Only a logged-in homeowner can see them,
// and only through this script: photo.php?id=123

if (!is_logged_in()) {
    http_response_code(403);
    exit('Forbidden');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit('Not found');
}

$stmt = db()->prepare('SELECT photo_file FROM visitors WHERE id = ?');
$stmt->execute([$id]);
$file = $stmt->fetchColumn();

if (!is_string($file) || !preg_match('/^[a-f0-9]{32}\.jpg$/', $file)) {
    http_response_code(404);
    exit('Not found');
}

$path = rtrim((string) cfg('photo_dir'), '/') . '/' . $file;
if (!is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');
header('Content-Disposition: inline; filename="visitor-' . $id . '.jpg"');
readfile($path);
