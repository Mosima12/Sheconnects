<?php
// login.php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$pdo = require __DIR__ . '/config/db.php';

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $oldEmail = $email;

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Use password_verify even for "user not found" paths? No — but keep
        // the error message identical either way so attackers can't enumerate accounts.
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];

            flash('success', 'Welcome back!');
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }

        $errors[] = 'Those credentials don’t match our records. Please try again.';
    }
}

$pageTitle = 'Log in';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>

<h1>Log in</h1>

<?php if ($errors): ?>
  <div class="flash flash-error">
    <ul style="margin:0;padding-left:18px;">
      <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form class="form" method="post" novalidate>
  <?php csrf_field(); ?>

  <div class="field">
    <label for="email">Email address</label>
    <input type="email" id="email" name="email" value="<?= e($oldEmail) ?>" required>
  </div>

  <div class="field">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
  </div>

  <button type="submit" class="btn btn-primary">Log in</button>
  <p class="hint" style="margin-top:14px;">
    New here? <a href="<?= BASE_URL ?>/register.php">Create an account</a>.
  </p>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>