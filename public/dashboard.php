<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require_login();

$pdo = db();

/* ---------- Filters ---------- */
$q = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($q) > 50) {
    $q = mb_substr($q, 0, 50);
}
$range   = (($_GET['range'] ?? '') === 'today') ? 'today' : 'all';
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$where = [];
$bind  = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR phone LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $bind[] = $like;
    $bind[] = $like;
}
if ($range === 'today') {
    $where[] = 'entry_time >= CURDATE()';
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ---------- Numbers at the top ---------- */
$stats = $pdo->query(
    'SELECT COUNT(*) AS total, COALESCE(SUM(entry_time >= CURDATE()), 0) AS today, COALESCE(MAX(id), 0) AS latest FROM visitors'
)->fetch();

/* ---------- Visitor list (paged) ---------- */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM visitors $sqlWhere");
$stmt->execute($bind);
$matching = (int) $stmt->fetchColumn();
$pages  = max(1, (int) ceil($matching / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT id, name, phone, entry_time FROM visitors $sqlWhere ORDER BY entry_time DESC, id DESC LIMIT ? OFFSET ?");
$i = 1;
foreach ($bind as $value) {
    $stmt->bindValue($i++, $value);
}
$stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($i, $offset, PDO::PARAM_INT);
$stmt->execute();
$visitors = $stmt->fetchAll();

/* ---------- Small helpers for this page ---------- */
function page_link(int $p, string $q, string $range): string
{
    return 'dashboard.php?' . http_build_query(array_filter(['q' => $q, 'range' => $range === 'today' ? 'today' : '', 'page' => $p > 1 ? $p : ''], function ($v) { return (string) $v !== ''; }));
}

function whatsapp_number(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if (strpos($phone, '+') === 0) {
        return $digits;
    }
    $digits = ltrim($digits, '0');
    return strlen($digits) <= 10 ? cfg('default_country_code', '91') . $digits : $digits;
}

function when_label(string $entryTime): array
{
    $ts   = strtotime($entryTime);
    $diff = time() - $ts;
    $time = date('g:i A', $ts);
    if (date('Y-m-d', $ts) === date('Y-m-d')) {
        $day = 'Today';
    } elseif (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) {
        $day = 'Yesterday';
    } else {
        $day = date('d M Y', $ts);
    }
    return ['text' => "$day, $time", 'is_new' => $diff >= 0 && $diff < 600];
}

$pageTitle  = 'Dashboard';
$activeNav  = 'dashboard';
$bodyAttrs  = 'data-latest-id="' . (int) $stats['latest'] . '"';
$extraScripts = ['assets/js/dashboard.js'];
$returnQuery = http_build_query(array_filter(['q' => $q, 'range' => $range === 'today' ? 'today' : '', 'page' => $page > 1 ? $page : ''], function ($v) { return (string) $v !== ''; }));

require __DIR__ . '/../app/header.php';
?>

<div id="newVisitorBanner" class="alert alert-success d-none d-flex align-items-center justify-content-between" role="alert">
  <span><strong>New visitor!</strong> Someone just registered at the door.</span>
  <a class="btn btn-sm btn-success" href="<?= e(page_link(1, '', 'all')) ?>">Show</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-label">Today</div>
      <div class="stat-value"><?= (int) $stats['today'] ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-label">All time</div>
      <div class="stat-value"><?= (int) $stats['total'] ?></div>
    </div>
  </div>
  <div class="col-12 col-md-6">
    <form class="card card-soft h-100" method="get" action="dashboard.php">
      <div class="card-body d-flex flex-column flex-sm-row gap-2 align-items-stretch">
        <input type="search" name="q" class="form-control" placeholder="Search name or number" value="<?= e($q) ?>" aria-label="Search visitors" maxlength="50">
        <select name="range" class="form-select" style="max-width: 9rem" aria-label="Date range">
          <option value="all"<?= $range === 'all' ? ' selected' : '' ?>>All days</option>
          <option value="today"<?= $range === 'today' ? ' selected' : '' ?>>Today only</option>
        </select>
        <button class="btn btn-brand" type="submit">Search</button>
      </div>
    </form>
  </div>
</div>

<?php if (!$visitors): ?>
  <div class="empty-state text-center">
    <div class="empty-icon" aria-hidden="true">🚪</div>
    <?php if ($q !== '' || $range === 'today'): ?>
      <h2 class="h5">No visitors match your search</h2>
      <p class="text-muted mb-3">Try a different name, number or date range.</p>
      <a class="btn btn-outline-secondary" href="dashboard.php">Clear filters</a>
    <?php else: ?>
      <h2 class="h5">No visitors yet</h2>
      <p class="text-muted mb-3">When someone scans the QR code and registers, they will show up here.</p>
      <a class="btn btn-brand" href="qr.php">Get the QR code</a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($visitors as $v):
        $when = when_label($v['entry_time']);
        $wa = whatsapp_number($v['phone']);
    ?>
    <div class="col-12 col-md-6 col-xl-4">
      <article class="visitor-card">
        <button type="button" class="photo-btn" data-bs-toggle="modal" data-bs-target="#photoModal"
                data-photo="photo.php?id=<?= (int) $v['id'] ?>" data-name="<?= e($v['name']) ?>" aria-label="Enlarge photo of <?= e($v['name']) ?>">
          <img src="photo.php?id=<?= (int) $v['id'] ?>" alt="Photo of <?= e($v['name']) ?>" loading="lazy" width="88" height="88">
        </button>
        <div class="visitor-info">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <h2 class="visitor-name"><?= e($v['name']) ?></h2>
            <?php if ($when['is_new']): ?><span class="badge text-bg-success">Just now</span><?php endif; ?>
          </div>
          <div class="visitor-phone"><?= e($v['phone']) ?></div>
          <div class="visitor-time"><?= e($when['text']) ?></div>
          <div class="visitor-actions">
            <a class="btn btn-sm btn-brand" href="tel:<?= e($v['phone']) ?>">Call</a>
            <a class="btn btn-sm btn-outline-success" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
            <form method="post" action="delete.php" class="ms-auto" onsubmit="return confirm('Delete this visitor entry and photo?');">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
              <input type="hidden" name="return" value="<?= e($returnQuery) ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete entry for <?= e($v['name']) ?>">Delete</button>
            </form>
          </div>
        </div>
      </article>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($pages > 1): ?>
  <nav class="mt-4" aria-label="Pages">
    <ul class="pagination justify-content-center flex-wrap">
      <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(page_link($page - 1, $q, $range)) ?>">Previous</a></li>
      <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $pages ?></span></li>
      <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(page_link($page + 1, $q, $range)) ?>">Next</a></li>
    </ul>
  </nav>
  <?php endif; ?>
<?php endif; ?>

<!-- Big photo viewer -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="photoModalTitle">Visitor</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="photoModalImg" src="" alt="" class="img-fluid rounded-3">
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../app/footer.php'; ?>
