<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
// This is the page the QR code opens. Visitors do not need to log in.
$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="robots" content="noindex">
  <meta name="theme-color" content="#0f766e">
  <title>Visitor sign-in</title>
  <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="visitor-bg">
<main class="container visitor-shell">

  <header class="text-center mb-4">
    <div class="brand-mark mx-auto mb-3" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>
    </div>
    <h1 class="display-6 fw-bold mb-1">Welcome!</h1>
    <p class="lead text-muted mb-0">Please sign in so we know you're here.</p>
  </header>

  <!-- ===== Form ===== -->
  <section id="formCard" class="card card-soft">
    <div class="card-body p-4">

      <div id="formAlert" class="alert alert-danger d-none" role="alert"></div>

      <form id="visitorForm" novalidate>
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <!-- Honeypot: hidden from people, bots fill it in -->
        <div class="hp-field" aria-hidden="true">
          <label for="website">Leave this empty</label>
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="mb-3">
          <label for="name" class="form-label fw-semibold">Your name</label>
          <input type="text" class="form-control form-control-lg" id="name" name="name" maxlength="60"
                 autocomplete="name" placeholder="e.g. Rahul Sharma" required>
          <div class="invalid-feedback" id="nameError"></div>
        </div>

        <div class="mb-4">
          <label for="phone" class="form-label fw-semibold">Mobile number</label>
          <input type="tel" class="form-control form-control-lg" id="phone" name="phone" maxlength="20"
                 inputmode="tel" autocomplete="tel" placeholder="e.g. 98765 43210" required>
          <div class="invalid-feedback" id="phoneError"></div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold d-block">Your photo</label>

          <div class="camera-frame" id="cameraFrame">
            <div class="camera-placeholder" id="cameraPlaceholder">
              <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              <span>No photo yet</span>
            </div>
            <video id="video" class="d-none" playsinline muted autoplay></video>
            <img id="preview" class="d-none" alt="Your photo preview">
          </div>

          <div id="photoError" class="text-danger small mt-2 d-none" role="alert"></div>
          <div id="cameraMsg" class="text-muted small mt-2 d-none"></div>

          <div class="d-grid gap-2 mt-3">
            <button type="button" class="btn btn-brand btn-lg" id="btnStart">Open camera</button>
            <button type="button" class="btn btn-brand btn-lg d-none" id="btnCapture">Take photo</button>
            <button type="button" class="btn btn-outline-secondary btn-lg d-none" id="btnRetake">Retake photo</button>
            <button type="button" class="btn btn-link" id="btnFile">Camera not working? Use your phone's camera app</button>
          </div>
          <input type="file" id="photoFile" class="d-none" accept="image/*" capture="user">
          <p class="form-text mt-2 mb-0">Your browser will ask permission to use the camera. Your photo is only shown to the homeowner.</p>
        </div>

        <button type="submit" class="btn btn-accent btn-lg w-100" id="btnSubmit">
          <span class="spinner-border spinner-border-sm d-none me-2" id="submitSpinner" aria-hidden="true"></span>
          <span id="submitLabel">Submit</span>
        </button>
      </form>
    </div>
  </section>

  <!-- ===== Success ===== -->
  <section id="successCard" class="card card-soft d-none" aria-live="polite">
    <div class="card-body p-4 text-center">
      <div class="success-mark mx-auto mb-3" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
      </div>
      <h2 class="h3 fw-bold">Thank you, <span id="successName"></span>!</h2>
      <p class="text-muted mb-4">You're signed in. The homeowner can now see your details.</p>
      <button type="button" class="btn btn-outline-secondary" id="btnAnother">Sign in another visitor</button>
    </div>
  </section>

  <p class="text-center text-muted small mt-4 mb-0">
    <a href="login.php" class="text-muted">Homeowner login</a>
  </p>
</main>

<script src="assets/js/visitor.js"></script>
</body>
</html>
