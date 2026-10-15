<?php
// Shared top of every homeowner page. Set $pageTitle and (optionally) $activeNav before including.
$pageTitle = $pageTitle ?? 'Visitor Entry';
$activeNav = $activeNav ?? '';
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
<body class="admin-bg"<?= !empty($bodyAttrs) ? ' ' . $bodyAttrs : '' ?>>
<nav class="navbar navbar-expand-sm navbar-dark topbar">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="dashboard.php">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>
      Visitor Entry
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link<?= $activeNav === 'dashboard' ? ' active' : '' ?>" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link<?= $activeNav === 'qr' ? ' active' : '' ?>" href="qr.php">QR code</a></li>
      </ul>
      <form method="post" action="logout.php" class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <span class="text-white-50 small d-none d-md-inline"><?= e($_SESSION['admin_name'] ?? '') ?></span>
        <button type="submit" class="btn btn-sm btn-outline-light">Log out</button>
      </form>
    </div>
  </div>
</nav>
<main class="container py-4">
<?php if ($f = take_flash()): ?>
  <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
    <?= e($f['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
