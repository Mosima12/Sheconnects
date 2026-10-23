<?php
// mentors.php — browse mentors and members, filter by skill/industry
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo    = require __DIR__ . '/config/db.php';
$userId = current_user_id();

$search       = trim((string)($_GET['q'] ?? ''));
$industry     = trim((string)($_GET['industry'] ?? ''));
$mentorsOnly  = isset($_GET['mentors_only']) && $_GET['mentors_only'] === '1';

$where  = ['u.id <> ?'];
$params = [$userId];

if ($search !== '') {
    $where[] = '(u.name LIKE ? OR u.skills LIKE ? OR u.interests LIKE ? OR u.bio LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($industry !== '') {
    $where[]  = 'u.industry = ?';
    $params[] = $industry;
}
if ($mentorsOnly) {
    $where[] = 'u.is_mentor = 1';
}

$sql = 'SELECT u.id, u.name, u.bio, u.skills, u.interests, u.industry,
               u.career_goals, u.is_mentor
          FROM users u
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY u.is_mentor DESC, u.name ASC
         LIMIT 60';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Distinct industries for the filter dropdown (only those actually used).
$industryRows = $pdo->query(
    'SELECT DISTINCT industry FROM users
      WHERE industry IS NOT NULL AND industry <> ""
      ORDER BY industry ASC'
)->fetchAll(PDO::FETCH_COLUMN);

// Existing pending connection pairs for this user (to disable buttons).
$stmt = $pdo->prepare(
    'SELECT receiver_id FROM connections
      WHERE sender_id = ? AND status = "pending"'
);
$stmt->execute([$userId]);
$pendingTo = array_flip(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));

$stmt = $pdo->prepare(
    'SELECT sender_id FROM connections
      WHERE receiver_id = ? AND status = "pending"'
);
$stmt->execute([$userId]);
$pendingFrom = array_flip(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));

$pageTitle = 'Mentors & members';
$activeNav = 'mentors';
require __DIR__ . '/includes/header.php';
?>

<h1>Mentors &amp; members</h1>
<p class="meta">Connect with women who can support your growth — or offer your own experience to others.</p>

<form method="get" action="<?= BASE_URL ?>/mentors.php" class="toolbar" style="margin-top:16px;">
  <input type="text" name="q" placeholder="Search name, skill, or interest" value="<?= e($search) ?>">
  <select name="industry">
    <option value="">All industries</option>
    <?php foreach ($industryRows as $ind): ?>
      <option value="<?= e($ind) ?>" <?= $industry === $ind ? 'selected' : '' ?>><?= e($ind) ?></option>
    <?php endforeach; ?>
  </select>
  <label style="display:flex;align-items:center;gap:6px;font-weight:500;">
    <input type="checkbox" name="mentors_only" value="1" <?= $mentorsOnly ? 'checked' : '' ?>>
    Mentors only
  </label>
  <button type="submit" class="btn btn-outline">Filter</button>
  <?php if ($search !== '' || $industry !== '' || $mentorsOnly): ?>
    <a class="btn btn-outline" href="<?= BASE_URL ?>/mentors.php">Clear</a>
  <?php endif; ?>
</form>

<?php if (!$members): ?>
  <div class="card"><p class="meta" style="margin:0;">No members match your search.</p></div>
<?php else: ?>
  <section class="grid grid-3">
    <?php foreach ($members as $m):
      $mid = (int)$m['id'];
      $iSentPending = isset($pendingTo[$mid]);
      $theySentPending = isset($pendingFrom[$mid]);
    ?>
      <article class="card">
        <h3 style="margin-bottom:4px;">
          <?= e($m['name']) ?>
          <?php if ($m['is_mentor']): ?>
            <span class="pill pill-accepted" style="font-size:.7rem;vertical-align:middle;">Mentor</span>
          <?php endif; ?>
        </h3>
        <p class="meta" style="margin:0 0 8px;">
          <?= $m['industry'] ? e($m['industry']) : 'Industry not set' ?>
        </p>
        <?php if ($m['bio']): ?>
          <p style="margin:0 0 10px;"><?= nl2br(e($m['bio'])) ?></p>
        <?php endif; ?>
        <?php if ($m['skills']): ?>
          <p class="meta" style="margin:0 0 6px;"><strong>Skills:</strong> <?= e($m['skills']) ?></p>
        <?php endif; ?>
        <?php if ($m['career_goals']): ?>
          <p class="meta" style="margin:0 0 6px;"><strong>Goals:</strong> <?= e($m['career_goals']) ?></p>
        <?php endif; ?>
        <p style="margin-top:14px;">
          <?php if ($iSentPending): ?>
            <span class="meta">Request pending — see <a href="<?= BASE_URL ?>/my-requests.php">My Requests</a></span>
          <?php elseif ($theySentPending): ?>
            <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/my-requests.php">Respond to their request</a>
          <?php else: ?>
            <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/send-connection.php?id=<?= $mid ?>">Connect</a>
          <?php endif; ?>
        </p>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>