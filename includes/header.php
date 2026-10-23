<?php
// includes/header.php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$pageTitle  = $pageTitle  ?? SITE_NAME;
$activeNav  = $activeNav  ?? '';
$navItems = [
    'resources'     => ['Resources',     BASE_URL . '/resources.php'],
    'mentors'       => ['Mentors',       BASE_URL . '/mentors.php'],
    'opportunities' => ['Opportunities', BASE_URL . '/opportunities.php'],
    'dashboard'     => ['Dashboard',     BASE_URL . '/dashboard.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · <?= e(SITE_NAME) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <a class="brand" href="<?= BASE_URL ?>/index.php"><?= e(SITE_NAME) ?></a>
  <nav class="site-nav">
    <?php foreach ($navItems as $key => [$label, $href]): ?>
      <a href="<?= e($href) ?>" class="<?= $activeNav === $key ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="auth-nav">
    <?php if (is_logged_in()): ?>
      <a href="<?= BASE_URL ?>/profile.php">Profile</a>
      <a href="<?= BASE_URL ?>/my-requests.php">My Requests</a>
      <a class="btn btn-outline" href="<?= BASE_URL ?>/logout.php">Log out</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/login.php">Log in</a>
      <a class="btn btn-primary" href="<?= BASE_URL ?>/register.php">Join us</a>
    <?php endif; ?>
  </div>
</header>
<main class="container">
<?php
$__err = get_flash('error');
$__ok  = get_flash('success');
if ($__err): ?><div class="flash flash-error"><?= e($__err) ?></div><?php endif;
if ($__ok):  ?><div class="flash flash-success"><?= e($__ok) ?></div><?php endif;