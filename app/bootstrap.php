<?php
declare(strict_types=1);

/**
 * Shared setup: loads config, connects to the database, starts the session
 * and provides small helper functions used by every page.
 */

$GLOBALS['APP_CONFIG'] = require __DIR__ . '/config.php';

function cfg(string $key, $default = null)
{
    return $GLOBALS['APP_CONFIG'][$key] ?? $default;
}

date_default_timezone_set((string) cfg('timezone', 'UTC'));

// Never show PHP errors to visitors; they go to the server log instead.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (PHP_SAPI !== 'cli') {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('VISITORSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
}

/* ---------- Database ---------- */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . cfg('db_host') . ';dbname=' . cfg('db_name') . ';charset=utf8mb4';
    $pdo = new PDO($dsn, (string) cfg('db_user'), (string) cfg('db_pass'), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
    ]);
    // Make MySQL NOW()/CURDATE() agree with the PHP time zone, e.g. "+05:30"
    $offset = (new DateTime('now', new DateTimeZone((string) cfg('timezone', 'UTC'))))->format('P');
    $pdo->exec("SET time_zone = '" . $offset . "'");
    return $pdo;
}

/* ---------- Output / security helpers ---------- */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid($token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Homeowner login ---------- */

function is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

/* ---------- Validation ---------- */

function clean_name(string $raw): string
{
    return trim(preg_replace('/\s+/u', ' ', $raw));
}

function name_error(string $name): ?string
{
    if ($name === '') {
        return 'Please enter your name.';
    }
    if (!preg_match('/^[\p{L}\p{M}][\p{L}\p{M} .\'\-]{1,59}$/u', $name)) {
        return 'Use 2-60 letters only (spaces, . \' - are fine).';
    }
    return null;
}

function normalize_phone(string $raw): string
{
    return preg_replace('/[\s\-().]/', '', trim($raw));
}

function phone_error(string $phone): ?string
{
    if ($phone === '') {
        return 'Please enter your mobile number.';
    }
    if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        return 'Enter a valid mobile number (7-15 digits, optional + at the start).';
    }
    return null;
}

/**
 * Validates an uploaded photo and saves a clean, re-encoded copy.
 * Returns the stored file name. Throws RuntimeException with a
 * visitor-friendly message when the photo is not acceptable.
 */
function save_visitor_photo(?array $file): string
{
    if ($file === null || !isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Please take a photo.');
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Please take a photo.');
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('That photo is too large. Please try again.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The photo upload failed. Please try again.');
    }
    if ($file['size'] <= 0 || $file['size'] > (int) cfg('max_photo_bytes')) {
        throw new RuntimeException('That photo is too large. Please try again.');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The photo upload failed. Please try again.');
    }

    // 1. Real file type from the file contents (not the name the browser claims)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new RuntimeException('Only JPEG, PNG or WebP photos are allowed.');
    }

    // 2. Must be a real image with sane dimensions (guards against huge "image bombs")
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || $info[0] < 50 || $info[1] < 50 || $info[0] * $info[1] > 40000000) {
        throw new RuntimeException('That does not look like a valid photo.');
    }

    // 3. Decode, then re-encode as a fresh JPEG. This removes any hidden
    //    data (EXIF, embedded scripts) and fixes the size.
    $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if ($src === false) {
        throw new RuntimeException('That does not look like a valid photo.');
    }

    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
        if ($angle !== 0) {
            $rotated = imagerotate($src, $angle, 0);
            if ($rotated !== false) {
                imagedestroy($src);
                $src = $rotated;
            }
        }
    }

    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1, 800 / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // white behind transparent PNGs
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($src);

    $dir = (string) cfg('photo_dir');
    if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
        throw new RuntimeException('Could not save the photo. Please tell the homeowner.');
    }
    $name = bin2hex(random_bytes(16)) . '.jpg';     // random name: not guessable
    $ok = imagejpeg($dst, $dir . '/' . $name, 82);
    imagedestroy($dst);
    if (!$ok) {
        throw new RuntimeException('Could not save the photo. Please tell the homeowner.');
    }
    @chmod($dir . '/' . $name, 0640);
    return $name;
}

function delete_photo_file(string $name): void
{
    if (preg_match('/^[a-f0-9]{32}\.jpg$/', $name)) {
        @unlink(rtrim((string) cfg('photo_dir'), '/') . '/' . $name);
    }
}
