<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

/**
 * Receives the visitor form (name, phone, photo) and saves it.
 * Always answers with JSON: {"ok":true} or {"ok":false,"errors":{...}}
 */
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['ok' => false, 'errors' => ['general' => 'Invalid request.']], 405);
    }

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        json_out(['ok' => false, 'errors' => ['general' => 'This page expired. Please reload it and try again.']], 403);
    }

    // Hidden "website" field: real people leave it empty, spam bots fill it in.
    if (!empty($_POST['website'])) {
        json_out(['ok' => true, 'name' => 'Guest']);
    }

    $pdo = db();
    $ip  = client_ip();

    // Simple spam brake: limit entries per device/IP
    if ($ip !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM visitors WHERE ip_address = ? AND entry_time > (NOW() - INTERVAL 10 MINUTE)');
        $stmt->execute([$ip]);
        if ((int) $stmt->fetchColumn() >= (int) cfg('max_registrations_per_10min', 10)) {
            json_out(['ok' => false, 'errors' => ['general' => 'Too many entries from this device. Please wait a few minutes.']], 429);
        }
    }

    $name  = clean_name((string) ($_POST['name'] ?? ''));
    $phone = normalize_phone((string) ($_POST['phone'] ?? ''));

    $errors = [];
    if (($msg = name_error($name)) !== null) {
        $errors['name'] = $msg;
    }
    if (($msg = phone_error($phone)) !== null) {
        $errors['phone'] = $msg;
    }

    // Only save the photo once name and phone are fine, so we never keep a file for a rejected form.
    $photoFile = null;
    if (!$errors) {
        try {
            $photoFile = save_visitor_photo($_FILES['photo'] ?? null);
        } catch (RuntimeException $e) {
            $errors['photo'] = $e->getMessage();
        }
    } elseif (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['photo'] = 'Please take a photo.';
    }

    if ($errors) {
        json_out(['ok' => false, 'errors' => $errors], 422);
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO visitors (name, phone, photo_file, ip_address, entry_time) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$name, $phone, $photoFile, $ip !== '' ? $ip : null]);
    } catch (Throwable $e) {
        delete_photo_file((string) $photoFile);   // don't leave an orphan photo behind
        throw $e;
    }

    json_out(['ok' => true, 'name' => $name]);

} catch (Throwable $e) {
    error_log('[visitor-entry] submit.php: ' . $e->getMessage());
    json_out(['ok' => false, 'errors' => ['general' => 'Something went wrong on our side. Please try again.']], 500);
}
