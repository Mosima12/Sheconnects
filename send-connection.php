<?php
// send-connection.php — send a connection or mentorship request
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo    = require __DIR__ . '/config/db.php';
$userId = current_user_id();

$receiverId = (int)($_GET['id'] ?? $_POST['receiver_id'] ?? 0);
if ($receiverId <= 0) {
    flash('error', 'That member could not be found.');
    header('Location: ' . BASE_URL . '/mentors.php');
    exit;
}
if ($receiverId === $userId) {
    flash('error', 'You can\'t send a connection request to yourself.');
    header('Location: ' . BASE_URL . '/mentors.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, name, industry, bio, skills, is_mentor
       FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$receiverId]);
$receiver = $stmt->fetch();
if (!$receiver) {
    flash('error', 'That member could not be found.');
    header('Location: ' . BASE_URL . '/mentors.php');
    exit;
}

// Prevent duplicate pending requests in either direction.
$stmt = $pdo->prepare(
    'SELECT id FROM connections
      WHERE status = "pending"
        AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
      LIMIT 1'
);
$stmt->execute([$userId, $receiverId, $receiverId, $userId]);
$existingPending = $stmt->fetch();

$errors  = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $message = trim((string)($_POST['message'] ?? ''));

    if ($existingPending) {
        $errors[] = 'There is already a pending connection request between you.';
    }
    if ($err = validate_length($message, 10, 1000, 'Message')) {
        $errors[] = $err;
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO connections (sender_id, receiver_id, message)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $receiverId, $message]);
        flash('success', 'Request sent to ' . $receiver['name'] . '. You\'ll see updates in My Requests.');
        header('Location: ' . BASE_URL . '/my-requests.php');
        exit;
    }
}

$pageTitle = 'Send a connection request';
$activeNav = 'mentors';
require __DIR__ . '/includes/header.php';
?>

<h1>Connect with <?= e($receiver['name']) ?></h1>
<p class="meta" style="margin-bottom:14px;">
  <?= $receiver['is_mentor'] ? '<span class="pill pill-accepted">Mentor</span> ' : '' ?>
  <?= $receiver['industry'] ? e($receiver['industry']) : 'Industry not set' ?>
</p>

<?php if ($receiver['bio']): ?>
  <div class="card" style="margin-bottom:20px;">
    <p style="margin-top:0;"><?= nl2br(e($receiver['bio'])) ?></p>
    <?php if ($receiver['skills']): ?>
      <p class="meta" style="margin-bottom:0;"><strong>Skills:</strong> <?= e($receiver['skills']) ?></p>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($existingPending): ?>
  <div class="flash flash-error">
    There is already a pending connection request between you.
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
  <input type="hidden" name="receiver_id" value="<?= (int)$receiverId ?>">

  <div class="field">
    <label for="message">Message to <?= e($receiver['name']) ?></label>
    <textarea id="message" name="message" required maxlength="1000"><?= e($message) ?></textarea>
    <div class="hint">Introduce yourself and mention what you'd like to talk about or learn.</div>
  </div>

  <button type="submit" class="btn btn-primary" <?= $existingPending ? 'disabled' : '' ?>>Send request</button>
  <a class="btn btn-outline" href="<?= BASE_URL ?>/mentors.php" style="margin-left:8px;">Back to mentors</a>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>