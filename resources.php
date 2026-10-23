<?php
// resources.php — browse available resources with search + category filter
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo    = require __DIR__ . '/config/db.php';
$userId = current_user_id();

$search   = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));

$allowedCategories = ['Books & Study Materials', 'Technology', 'Clothing', 'Business Supplies', 'Other'];
$where   = ['r.status = "available"'];
$params  = [];

if ($search !== '') {
    $where[]  = '(r.title LIKE ? OR r.description LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($category !== '' && in_array($category, $allowedCategories, true)) {
    $where[]  = 'r.category = ?';
    $params[] = $category;
}

$sql = 'SELECT r.id, r.title, r.description, r.category, r.exchange_type, r.location, r.created_at,
               r.user_id, u.name AS owner_name
          FROM resources r
          JOIN users u ON u.id = r.user_id
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY r.created_at DESC
         LIMIT 50';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resources = $stmt->fetchAll();

$pageTitle = 'Resources';
$activeNav = 'resources';
require __DIR__ . '/includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
  <h1 style="margin:0;">Shared resources</h1>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/add-resource.php">+ Share a resource</a>
</div>
<p class="meta">Books, laptops, business supplies and more — offered by women in the community.</p>

<form method="get" action="<?= BASE_URL ?>/resources.php" class="toolbar">
  <input type="text" name="q" placeholder="Search title or description" value="<?= e($search) ?>">
  <select name="category">
    <option value="">All categories</option>
    <?php foreach ($allowedCategories as $c): ?>
      <option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-outline">Filter</button>
  <?php if ($search !== '' || $category !== ''): ?>
    <a class="btn btn-outline" href="<?= BASE_URL ?>/resources.php">Clear</a>
  <?php endif; ?>
</form>

<?php if (!$resources): ?>
  <div class="card">
    <p class="meta">No resources match your search yet.
      <?= $search === '' && $category === '' ? '<a href="' . BASE_URL . '/add-resource.php">Be the first to share one.</a>' : '<a href="' . BASE_URL . '/resources.php">Clear the filters</a> to see everything.' ?>
    </p>
  </div>
<?php else: ?>
  <section class="grid grid-3">
    <?php foreach ($resources as $r): ?>
      <article class="card">
        <h3 style="margin-bottom:4px;"><?= e($r['title']) ?></h3>
        <p class="meta" style="margin:0 0 8px;">
          shared by <?= e($r['owner_name']) ?>
          <?php if ($r['location']): ?> · <?= e($r['location']) ?><?php endif; ?>
        </p>
        <p style="margin:0 0 10px;"><?= nl2br(e($r['description'])) ?></p>
        <p class="meta" style="margin:0;">
          <span class="pill"><?= e($r['category']) ?></span>
          <span class="pill pill-type"><?= e($r['exchange_type']) ?></span>
        </p>
        <p style="margin-top:14px;">
          <?php if ((int)$r['user_id'] === $userId): ?>
            <span class="meta">Your listing</span>
          <?php else: ?>
            <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/request-resource.php?id=<?= (int)$r['id'] ?>">Request this</a>
          <?php endif; ?>
        </p>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>