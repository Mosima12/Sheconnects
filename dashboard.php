<?php
// dashboard.php — real dashboard, driven by DB
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$pdo     = require __DIR__ . '/config/db.php';
$userId  = current_user_id();

// Greeting name
$stmt = $pdo->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$me = $stmt->fetch();
if (!$me) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

// Next upcoming deadline (alert strip)
$stmt = $pdo->prepare(
    'SELECT id, title, deadline
       FROM opportunities
      WHERE deadline IS NOT NULL AND deadline >= CURDATE()
      ORDER BY deadline ASC
      LIMIT 1'
);
$stmt->execute();
$nextDeadline = $stmt->fetch();

// Featured opportunity (fallback: latest)
$stmt = $pdo->prepare(
    'SELECT id, title, organization, category, deadline
       FROM opportunities
      ORDER BY created_at DESC
      LIMIT 1'
);
$stmt->execute();
$featuredOpportunity = $stmt->fetch();

// Recent available resources (not the user's own)
$stmt = $pdo->prepare(
    'SELECT id, title, category, exchange_type
       FROM resources
      WHERE status = "available" AND user_id <> ?
      ORDER BY created_at DESC
      LIMIT 3'
);
$stmt->execute([$userId]);
$recentResources = $stmt->fetchAll();

// Pending counts
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM connections WHERE receiver_id = ? AND status = "pending"'
);
$stmt->execute([$userId]);
$pendingConnections = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM resource_requests rr
       JOIN resources r ON r.id = rr.resource_id
      WHERE r.user_id = ? AND rr.status = "pending"'
);
$stmt->execute([$userId]);
$pendingResourceRequests = (int)$stmt->fetchColumn();

// Mentor count
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE is_mentor = 1 AND id <> ?');
$stmt->execute([$userId]);
$mentorCount = (int)$stmt->fetchColumn();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="hero" style="text-align:left; padding:28px 32px;">
  <h1>Welcome back, <?= e($me['name']) ?> 👋</h1>
  <p>Here's your personalised view of She Connects — new resources, mentors, and opportunities picked from your community.</p>
</section>

<?php if ($nextDeadline): ?>
  <div class="flash flash-success" style="display:block;">
    🔔 <strong>Upcoming deadline:</strong>
    <a href="<?= BASE_URL ?>/opportunities.php"><?= e($nextDeadline['title']) ?></a>
    closes on <strong><?= e(date('j M Y', strtotime($nextDeadline['deadline']))) ?></strong>.
  </div>
<?php endif; ?>

<div class="quick-links">
  <a href="<?= BASE_URL ?>/resources.php">
    Share &amp; borrow <span>Books, laptops, business supplies, and more</span>
  </a>
  <a href="<?= BASE_URL ?>/mentors.php">
    Find a mentor <span><?= $mentorCount ?> member<?= $mentorCount === 1 ? '' : 's' ?> offering mentorship</span>
  </a>
  <a href="<?= BASE_URL ?>/opportunities.php">
    Opportunities <span>Internships, scholarships, workshops</span>
  </a>
</div>

<section class="grid grid-3" style="margin-top:8px;">

  <article class="card">
    <h3>Recently shared</h3>
    <?php if (!$recentResources): ?>
      <p class="meta">No resources yet. <a href="<?= BASE_URL ?>/add-resource.php">Be the first to share one.</a></p>
    <?php else: ?>
      <ul style="list-style:none;padding:0;margin:0;">
        <?php foreach ($recentResources as $r): ?>
          <li style="padding:8px 0;border-bottom:1px dotted var(--border);">
            <strong><?= e($r['title']) ?></strong><br>
            <span class="meta">
              <span class="pill pill-type"><?= e($r['exchange_type']) ?></span>
              <span class="pill"><?= e($r['category']) ?></span>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
      <p><a href="<?= BASE_URL ?>/resources.php">Browse all resources →</a></p>
    <?php endif; ?>
  </article>

  <article class="card">
    <h3>Featured opportunity</h3>
    <?php if (!$featuredOpportunity): ?>
      <p class="meta">No opportunities posted yet. <a href="<?= BASE_URL ?>/add-opportunity.php">Share one.</a></p>
    <?php else: ?>
      <p><strong><?= e($featuredOpportunity['title']) ?></strong></p>
      <p class="meta">
        <?= e($featuredOpportunity['organization']) ?>
        &middot; <?= e($featuredOpportunity['category']) ?>
        <?php if ($featuredOpportunity['deadline']): ?>
          &middot; closes <?= e(date('j M Y', strtotime($featuredOpportunity['deadline']))) ?>
        <?php endif; ?>
      </p>
      <p><a href="<?= BASE_URL ?>/opportunities.php">See all opportunities →</a></p>
    <?php endif; ?>
  </article>

  <article class="card">
    <h3>Your pending items</h3>
    <ul style="list-style:none;padding:0;margin:0;">
      <li style="padding:8px 0;border-bottom:1px dotted var(--border);">
        <strong><?= $pendingResourceRequests ?></strong>
        resource request<?= $pendingResourceRequests === 1 ? '' : 's' ?> waiting for you
      </li>
      <li style="padding:8px 0;border-bottom:1px dotted var(--border);">
        <strong><?= $pendingConnections ?></strong>
        connection request<?= $pendingConnections === 1 ? '' : 's' ?> to review
      </li>
    </ul>
    <p><a href="<?= BASE_URL ?>/my-requests.php">Open My Requests →</a></p>
  </article>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>