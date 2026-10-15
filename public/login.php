<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $ip = client_ip();

    try {
        $pdo = db();
        $max = (int) cfg('max_login_failures', 5);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)');
        $stmt->execute([$ip]);
        $recentFailures = (int) $stmt->fetchColumn();

        if (!csrf_valid($_POST['csrf'] ?? null)) {
            $error = 'This page expired. Please try again.';
        } elseif ($recentFailures >= $max) {
            $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
        } else {
            $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            // Always run password_verify so timing does not reveal whether the user exists
            $hash = $admin ? $admin['password_hash'] : '$2y$10$pJv50utmGuQHUAfOh3PcCuymdFh1yKFSzUf10RWtEgHXJbruUw9ju';
            $valid = password_verify($password, $hash) && $admin;

            if ($valid) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_name'] = $username;
                $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ip]);
                redirect('dashboard.php');
            }

            $pdo->prepare('INSERT INTO login_attempts (ip_address) VALUES (?)')->execute([$ip]);
            $error = 'Wrong username or password.';
        }
    } catch (Throwable $e) {
        error_log('[visitor-entry] login.php: ' . $e->getMessage());
        $error = 'Something went wrong. Please try again.';
    }
}

$pageTitle = 'Homeowner login';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title><?= e($pageTitle) ?> · Visitor Entry</title>
  <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="visitor-bg">
  <main class="container py-5">
    <div class="auth-wrap">
      <div class="brand-mark mx-auto mb-3" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>
      </div>
      <h1 class="h3 text-center fw-bold mb-1">Homeowner login</h1>
      <p class="text-center text-muted mb-4">Sign in to see who visited.</p>

      <div class="card card-soft">
        <div class="card-body p-4">
          <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
          <?php endif; ?>
          <form method="post" action="login.php" autocomplete="on">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
              <label for="username" class="form-label fw-semibold">Username</label>
              <input type="text" class="form-control form-control-lg" id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
            </div>
            <div class="mb-4">
              <label for="password" class="form-label fw-semibold">Password</label>
              <input type="password" class="form-control form-control-lg" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-brand btn-lg w-100">Sign in</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
