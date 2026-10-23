<?php
// opportunities.php — browse opportunities with category filter + search
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo    = require __DIR__ . '/config/db.php';
$userId = current_user_id();

$search   = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));

$allowedCategories = [
    'Internship', 'Job', 'Scholarship', 'Workshop', 'Networking Event', 'Entrepreneurship',
];

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = '(o.title LIKE ? OR o.organization LIKE ? OR o.description LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($category !== '' && in_array($category, $allowedCategories, true)) {
    $where[]  = 'o.category = ?';
    $params[] = $category;
}

$sql = 'SELECT o.id, o.title, o.description, o.category, o.organization,
               o.application_url, o.deadline, o.created_at,
               o.user_id, u.name AS poster_name
          FROM opportunities o
          JOIN users u ON u.id = o.user_id
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY (o.deadline IS NULL), o.deadline ASC, o.created_at DESC
         LIMIT 60';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$opportunities = $stmt->fetchAll();

$pageTitle = 'Opportunities';
$activeNav = 'opportunities';
require __DIR__ . '/includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
  <h1 style="margin:0;">Opportunities</h1>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/add-opportunity.php">+ Share an opportunity</a>
</div>
<p class="meta">Internships, jobs, scholarships, workshops, events, and business opportunities from the community.</p>

<form method="get" action="<?= BASE_URL ?>/opportunities.php" class="toolbar" style="margin-top:16px;">
  <input type="text" name="q" placeholder="Search title, organisation, description" value="<?= e($search) ?>">
  <select name="category">
    <option value="">All categories</option>
    <?php foreach ($allowedCategories as $c): ?>
      <option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-outline">Filter</button>
  <?php if ($search !== '' || $category !== ''): ?>
    <a class="btn btn-outline" href="<?= BASE_URL ?>/opportunities.php">Clear</a>
  <?php endif; ?>
</form>

<?php if (!$opportunities): ?>
  <div class="card">
    <p class="meta" style="margin:0;">
      No opportunities match your filters.
      <?php if ($search === '' && $category === ''): ?>
        <a href="<?= BASE_URL ?>/add-opportunity.php">Be the first to share one.</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/opportunities.php">Clear the filters.</a>
      <?php endif; ?>
    </p>
  </div>
<?php else: ?>
  <section class="grid grid-2">
    <?php foreach ($opportunities as $o):
      $deadlinePassed = $o['deadline'] && strtotime($o['deadline']) < strtotime('today');
    ?>
      <article class="card">
        <h3 style="margin-bottom:4px;"><?= e($o['title']) ?></h3>
        <p class="meta" style="margin:0 0 10px;">
          <?= e($o['organization']) ?> · <?= e($o['category']) ?>
          <?php if ($o['deadline']): ?>
            · closes <?= e(date('j M Y', strtotime($o['deadline']))) ?>
            <?php if ($deadlinePassed): ?>
              <span class="pill pill-declined" style="margin-left:6px;">Closed</span>
            <?php endif; ?>
          <?php endif; ?>
        </p>
        <p style="margin:0 0 12px;"><?= nl2br(e($o['description'])) ?></p>
        <p class="meta" style="margin:0 0 12px;">Shared by <?= e($o['poster_name']) ?></p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <?php if ($o['application_url']): ?>
            <a class="btn btn-primary btn-sm"
               href="<?= e($o['application_url']) ?>"
               target="_blank" rel="noopener noreferrer">Apply / learn more ↗</a>
          <?php else: ?>
            <span class="meta">No external link provided.</span>
          <?php endif; ?>
          <?php if ((int)$o['user_id'] === $userId): ?>
            <span class="meta" style="align-self:center;">Your post</span>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>