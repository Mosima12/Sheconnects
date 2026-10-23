<?php
// request-resource.php — submit a request for a resource
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo    = require __DIR__ . '/config/db.php';
$userId = current_user_id();

$resourceId = (int)($_GET['id'] ?? $_POST['resource_id'] ?? 0);
if ($resourceId <= 0) {
    flash('error', 'That resource could not be found.');
    header('Location: ' . BASE_URL . '/resources.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT r.*, u.name AS owner_name
       FROM resources r
       JOIN users u ON u.id = r.user_id
      WHERE r.id = ? LIMIT 1'
);
$stmt->execute([$resourceId]);
$resource = $stmt->fetch();

if (!$resource) {
    flash('error', 'That resource could not be found.');
    header('Location: ' . BASE_URL . '/resources.php');
    exit;
}

// Guard: cannot request your own resource.
if ((int)$resource['user_id'] === $userId) {
    flash('error', 'You can\'t request your own listing.');
    header('Location: ' . BASE_URL . '/resources.php');
    exit;
}

// Guard: resource must still be available.
if ($resource['status'] !== 'available') {
    flash('error', 'That resource is no longer available.');
    header('Location: ' . BASE_URL . '/resources.php');
    exit;
}

// Check for an existing pending request from this user for this resource.
$stmt = $pdo->prepare(
    'SELECT id FROM resource_requests
      WHERE resource_id = ? AND requester_id = ? AND status = "pending"
      LIMIT 1'
);
$stmt->execute([$resourceId, $userId]);
$existingPending = $stmt->fetch();

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $message = trim((string)($_POST['message'] ?? ''));

    if ($existingPending) {
        $errors[] = 'You already have a pending request for this resource.';
    }

    if ($err = validate_length($message, 10, 1000, 'Message')) {
        $errors[] = $err;
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO resource_requests (resource_id, requester_id, message)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([$resourceId, $userId, $message]);
        flash('success', 'Request sent to ' . $resource['owner_name'] . '. You\'ll see updates in My Requests.');
        header('Location: ' . BASE_URL . '/my-requests.php');
        exit;
    }
}

$pageTitle = 'Request a resource';
$activeNav = 'resources';
require __DIR__ . '/includes/header.php';
?>

<h1>Request: <?= e($resource['title']) ?></h1>
<p class="meta" style="margin-bottom:14px;">
  Offered by <?= e($resource['owner_name']) ?>
  · <?= e($resource['category']) ?>
  · <span class="pill pill-type"><?= e($resource['exchange_type']) ?></span>
</p>

<div class="card" style="margin-bottom:20px;">
  <p style="margin-top:0;"><?= nl2br(e($resource['description'])) ?></p>
  <?php if ($resource['location']): ?>
    <p class="meta" style="margin-bottom:0;">Location: <?= e($resource['location']) ?></p>
  <?php endif; ?>
</div>

<?php if ($existingPending): ?>
  <div class="flash flash-error">
    You already have a pending request for this resource.
    <a href="<?= BASE_URL ?>/my-requests.php">View it in My Requests →</a>
  </div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="flash flash-error">
    <ul style="margin:0;padding-left:18px;">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form class="form" method="post" novalidate>
  <?php csrf_field(); ?>
  <input type="hidden" name="resource_id" value="<?= (int)$resourceId ?>">

  <div class="field">
    <label for="message">Message to <?= e($resource['owner_name']) ?></label>
    <textarea id="message" name="message" required maxlength="1000"><?= e($message) ?></textarea>
    <div class="hint">A short note: why you need it, when you could collect, any conditions.</div>
  </div>

  <button type="submit" class="btn btn-primary" <?= $existingPending ? 'disabled' : '' ?>>Send request</button>
  <a class="btn btn-outline" href="<?= BASE_URL ?>/resources.php" style="margin-left:8px;">Back to resources</a>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>