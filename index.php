<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Welcome'; $activeNav = 'home';
require __DIR__ . '/includes/header.php';
?>

<div class="page-header header-home" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 40px;">
  <div style="flex: 1; min-width: 300px;">
      <span class="pill" style="margin-bottom: 16px;">A community built on shared possibility</span>
      <h1 style="font-size: 3.5rem; margin-bottom: 16px;">Where women move <br><span style="color: var(--primary);">forward, together.</span></h1>
      <p style="font-size: 1.2rem; max-width: 500px; margin-bottom: 32px;">Bloom is a community where women share resources, find mentors, and discover opportunities — all in one place.</p>
      <div class="cta" style="display: flex; gap: 16px;">
        <?php if (is_logged_in()): ?>
          <a class="btn btn-primary" href="<?= BASE_URL ?>/dashboard.php">Go to your dashboard</a>
        <?php else: ?>
          <a class="btn btn-primary" href="<?= BASE_URL ?>/register.php">Join Bloom</a>
          <a class="btn btn-outline" href="<?= BASE_URL ?>/login.php">Log in</a>
        <?php endif; ?>
      </div>
  </div>
  <div style="flex: 1; min-width: 300px;">
      <img src="https://images.unsplash.com/photo-1573164713988-8665fc963095?auto=format&fit=crop&w=800&q=80" alt="Women collaborating" style="border-radius: 40px; box-shadow: var(--shadow);">
  </div>
</div>

<section class="grid grid-3">
  <article class="card">
    <h3>Share Resources</h3>
    <p class="meta" style="margin-bottom: 16px;">Textbooks, laptops, professional attire, and more — offered by women, for women.</p>
    <a href="<?= BASE_URL ?>/resources.php" class="btn btn-outline btn-sm">Browse resources</a>
  </article>
  <article class="card">
    <h3>Find a Mentor</h3>
    <p class="meta" style="margin-bottom: 16px;">Connect with mentors and peers who understand your goals and can help you reach them.</p>
    <a href="<?= BASE_URL ?>/mentors.php" class="btn btn-outline btn-sm">Meet the community</a>
  </article>
  <article class="card">
    <h3>Discover Opportunities</h3>
    <p class="meta" style="margin-bottom: 16px;">Internships, scholarships, workshops, and events — curated by members like you.</p>
    <a href="<?= BASE_URL ?>/opportunities.php" class="btn btn-outline btn-sm">See opportunities</a>
  </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>