<?php
/**
 * Creates (or resets the password of) a homeowner login.
 *
 *   php create_admin.php yourname
 *
 * Command line only - it is outside the public web folder.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this from the command line.\n");
}

require __DIR__ . '/app/bootstrap.php';

function ask_hidden(string $prompt): string
{
    echo $prompt;
    $tty = function_exists('posix_isatty') && posix_isatty(STDIN);
    if ($tty) {
        system('stty -echo');
    }
    $value = trim((string) fgets(STDIN));
    if ($tty) {
        system('stty echo');
        echo "\n";
    }
    return $value;
}

$username = $argv[1] ?? '';
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    fwrite(STDERR, "Usage: php create_admin.php <username>\n");
    fwrite(STDERR, "Username: 3-50 characters (letters, numbers, _ . -)\n");
    exit(1);
}

$password = ask_hidden('Password (at least 8 characters): ');
$confirm  = ask_hidden('Repeat password: ');
if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}
if (!hash_equals($password, $confirm)) {
    fwrite(STDERR, "Passwords do not match.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo  = db();

$stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
$stmt->execute([$username]);
$id = $stmt->fetchColumn();

if ($id) {
    $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
    echo "Password updated for '$username'.\n";
} else {
    $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')->execute([$username, $hash]);
    echo "Homeowner login '$username' created.\n";
}
