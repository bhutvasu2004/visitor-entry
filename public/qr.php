<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require_login();

// Address the QR code will open: the visitor registration page.
$base = rtrim((string) cfg('base_url', ''), '/');
if ($base === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    // Only accept a plain host[:port] so a forged Host header can't inject anything odd
    if (!preg_match('/^[A-Za-z0-9.\-]+(:[0-9]{1,5})?$/', $host)) {
        $host = 'localhost';
    }
    $dir  = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    $base = $scheme . '://' . $host . $dir;
}
$registerUrl = $base . '/';
$isLocalOnly = (bool) preg_match('#^https?://(localhost|127\.0\.0\.1|\[::1\])(:|/|$)#i', $registerUrl);

$pageTitle    = 'QR code';
$activeNav    = 'qr';
$extraScripts = ['assets/vendor/qrcode.js', 'assets/js/qr.js'];
require __DIR__ . '/../app/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-7 col-xl-6">
    <div class="no-print mb-3">
      <h1 class="h3 fw-bold mb-1">Entrance QR code</h1>
      <p class="text-muted mb-0">Print this and stick it near your door. Visitors scan it with their phone camera to open the sign-in page.</p>
    </div>

    <?php if ($isLocalOnly): ?>
      <div class="alert alert-warning no-print" role="alert">
        <strong>Heads up:</strong> this address only works on this computer. Phones cannot open
        <code>localhost</code>. Open this page using your PC's network address (for example
        <code>http://192.168.1.50:8000/qr.php</code>), or type that address below.
      </div>
    <?php endif; ?>

    <div class="card card-soft qr-card">
      <div class="card-body p-4 text-center">
        <div class="qr-print-title">Welcome! Please sign in</div>
        <canvas id="qrCanvas" width="400" height="400" role="img" aria-label="QR code for the visitor sign-in page"></canvas>
        <div class="qr-print-sub">Scan with your phone camera</div>
        <div id="qrUrlText" class="small text-muted mt-2 text-break"></div>
      </div>
    </div>

    <div class="card card-soft mt-3 no-print">
      <div class="card-body p-4">
        <label for="qrUrl" class="form-label fw-semibold">Sign-in page address</label>
        <input type="url" class="form-control form-control-lg" id="qrUrl" value="<?= e($registerUrl) ?>" spellcheck="false" autocomplete="off">
        <div class="form-text mb-3">The QR code updates as you type. Use the address your phones can reach.</div>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-brand" id="btnDownload">Download PNG</button>
          <button type="button" class="btn btn-outline-secondary" id="btnPrint">Print</button>
          <a class="btn btn-outline-secondary" href="<?= e($registerUrl) ?>" target="_blank" rel="noopener">Open sign-in page</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../app/footer.php'; ?>
