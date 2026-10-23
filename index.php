<?php
// index.php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Welcome';
$activeNav = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <h1>She Connects</h1>
  <p>
    A community where women share resources, find mentors, and discover opportunities —
    all in one place. Join us and grow together.
  </p>
  <div class="cta">
    <?php if (is_logged_in()): ?>
      <a class="btn btn-primary" href="<?= BASE_URL ?>/dashboard.php">Go to your dashboard</a>
    <?php else: ?>
      <a class="btn btn-primary" href="<?= BASE_URL ?>/register.php">Join She Connects</a>
      <a class="btn btn-outline" href="<?= BASE_URL ?>/login.php">Log in</a>
    <?php endif; ?>
  </div>
</section>

<section class="grid grid-3">
  <article class="card">
    <h3>Share Resources</h3>
    <p class="meta">Textbooks, laptops, professional attire, and more — offered by women, for women.</p>
    <p><a href="<?= BASE_URL ?>/resources.php">Browse resources →</a></p>
  </article>
  <article class="card">
    <h3>Find a Mentor</h3>
    <p class="meta">Connect with mentors and peers who understand your goals and can help you reach them.</p>
    <p><a href="<?= BASE_URL ?>/mentors.php">Meet the community →</a></p>
  </article>
  <article class="card">
    <h3>Discover Opportunities</h3>
    <p class="meta">Internships, scholarships, workshops, and events — curated by members like you.</p>
    <p><a href="<?= BASE_URL ?>/opportunities.php">See opportunities →</a></p>
  </article>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>s