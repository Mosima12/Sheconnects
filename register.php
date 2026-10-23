<?php
// register.php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in? No need to register.
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$pdo = require __DIR__ . '/config/db.php';

$errors = [];
$old    = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $name     = trim((string)($_POST['name'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');

    $old['name']  = $name;
    $old['email'] = $email;

    if ($err = validate_length($name, 2, 100, 'Name'))        $errors[] = $err;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))           $errors[] = 'Please enter a valid email address.';
    if ($err = validate_length($email, 5, 190, 'Email'))      $errors[] = $err;
    if ($err = validate_length($password, 8, 200, 'Password')) $errors[] = $err;
    if ($password !== $confirm)                                $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists. Try logging in instead.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $email, $hash]);

        // Auto-login after successful registration.
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$pdo->lastInsertId();

        flash('success', 'Welcome to She Connects! Your account is ready.');
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

$pageTitle = 'Create your account';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>

<h1>Create your account</h1>
<p class="meta" style="margin-bottom:18px;">Join a community of women supporting women.</p>

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
    <label for="name">Full name</label>
    <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
  </div>

  <div class="field">
    <label for="email">Email address</label>
    <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
  </div>

  <div class="row">
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <div class="hint">At least 8 characters.</div>
    </div>
    <div class="field">
      <label for="password_confirm">Confirm password</label>
      <input type="password" id="password_confirm" name="password_confirm" required>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Create account</button>
  <p class="hint" style="margin-top:14px;">
    Already have an account? <a href="<?= BASE_URL ?>/login.php">Log in</a>.
  </p>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>