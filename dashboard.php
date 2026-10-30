<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = require __DIR__ . '/config/db.php'; 
$userId = current_user_id();

$stmt = $pdo->prepare('SELECT name FROM users WHERE id = ? LIMIT 1'); 
$stmt->execute([$userId]); 
$me = $stmt->fetch();

$stmt = $pdo->prepare('SELECT id, title, deadline FROM opportunities WHERE deadline >= CURDATE() ORDER BY deadline ASC LIMIT 1'); 
$stmt->execute(); 
$nextDeadline = $stmt->fetch();

$stmt = $pdo->prepare('SELECT id, title, organization, category, deadline FROM opportunities ORDER BY created_at DESC LIMIT 1'); 
$stmt->execute(); 
$featuredOpportunity = $stmt->fetch();

$stmt = $pdo->prepare('SELECT id, title, category, exchange_type FROM resources WHERE status = "available" AND user_id <> ? ORDER BY created_at DESC LIMIT 3'); 
$stmt->execute([$userId]); 
$recentResources = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM connections WHERE receiver_id = ? AND status = "pending"'); 
$stmt->execute([$userId]); 
$pendingConnections = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM resource_requests rr JOIN resources r ON r.id = rr.resource_id WHERE r.user_id = ? AND rr.status = "pending"'); 
$stmt->execute([$userId]); 
$pendingResourceRequests = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE is_mentor = 1 AND id <> ?'); 
$stmt->execute([$userId]); 
$mentorCount = (int)$stmt->fetchColumn();

$pageTitle = 'Dashboard'; 
$activeNav = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="page-header header-home" style="padding: 30px;">
  <h1>Welcome back, <?= e($me['name']) ?> 👋</h1>
  <p>Here's your personalised view of Bloom.</p>
</div>

<?php if ($nextDeadline): ?>
  <div class="flash flash-success" style="display:block; border-radius: var(--radius); margin-bottom: 24px; padding: 16px; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;">
    🔔 <strong>Upcoming deadline:</strong> <a href="<?= BASE_URL ?>/explore.php?tab=opportunities" style="color: #15803d; text-decoration: underline;"><?= e($nextDeadline['title']) ?></a> closes on <strong><?= e(date('j M Y', strtotime($nextDeadline['deadline']))) ?></strong>.
  </div>
<?php endif; ?>

<div class="quick-links" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:16px; margin-bottom:32px;">
  <a href="<?= BASE_URL ?>/explore.php?tab=resources" class="card" style="text-decoration:none; padding: 20px;">
    Share &amp; borrow <span style="display:block;color:var(--muted);font-weight:400;font-size:.85rem;margin-top:4px;">Books, laptops, supplies</span>
  </a>
  <a href="<?= BASE_URL ?>/explore.php?tab=academy" class="card" style="text-decoration:none; padding: 20px;">
    Learn a skill <span style="display:block;color:var(--muted);font-weight:400;font-size:.85rem;margin-top:4px;">Free short courses</span>
  </a>
  <a href="<?= BASE_URL ?>/community.php?tab=mentors" class="card" style="text-decoration:none; padding: 20px;">
    Find a mentor <span style="display:block;color:var(--muted);font-weight:400;font-size:.85rem;margin-top:4px;"><?= $mentorCount ?> available</span>
  </a>
  <a href="<?= BASE_URL ?>/community.php?tab=wellness" class="card" style="text-decoration:none; padding: 20px;">
    Wellness <span style="display:block;color:var(--muted);font-weight:400;font-size:.85rem;margin-top:4px;">Mental health support</span>
  </a>
</div>

<section class="grid grid-3">
  <article class="card">
    <h3>Recently shared</h3>
    <ul style="list-style:none;padding:0;margin:0;">
      <?php foreach ($recentResources as $r): ?>
        <li style="padding:12px 0;border-bottom:1px solid var(--border);">
          <strong><?= e($r['title']) ?></strong><br>
          <span class="pill pill-type" style="margin-top:8px;"><?= e($r['exchange_type']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <a href="<?= BASE_URL ?>/explore.php?tab=resources" style="display:inline-block; margin-top:16px; font-size:0.9rem; font-weight:600;">View all resources &rarr;</a>
  </article>
  
  <article class="card">
    <h3>Featured opportunity</h3>
    <?php if ($featuredOpportunity): ?>
      <p><strong><?= e($featuredOpportunity['title']) ?></strong></p>
      <p class="meta"><?= e($featuredOpportunity['organization']) ?> &middot; <?= e($featuredOpportunity['category']) ?></p>
    <?php else: ?>
      <p class="meta">No opportunities posted yet.</p>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/explore.php?tab=opportunities" style="display:inline-block; margin-top:16px; font-size:0.9rem; font-weight:600;">View all opportunities &rarr;</a>
  </article>
  
  <article class="card">
    <h3>Your pending items</h3>
    <ul style="list-style:none;padding:0;margin:0;">
      <li style="padding:12px 0;border-bottom:1px solid var(--border);">
        <strong style="font-size: 1.2rem; color: var(--primary);"><?= $pendingResourceRequests ?></strong> resource requests
      </li>
      <li style="padding:12px 0;">
        <strong style="font-size: 1.2rem; color: var(--primary);"><?= $pendingConnections ?></strong> connection requests
      </li>
    </ul>
    <a href="<?= BASE_URL ?>/my-requests.php" style="display:inline-block; margin-top:16px; font-size:0.9rem; font-weight:600;">Manage requests &rarr;</a>
  </article>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>