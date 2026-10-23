<?php
// add-resource.php — publish a new resource listing
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo    = require __DIR__ . '/config/db.php';
$userId = current_user_id();

$allowedCategories = ['Books & Study Materials', 'Technology', 'Clothing', 'Business Supplies', 'Other'];
$allowedExchange   = ['give', 'lend', 'exchange'];

$errors = [];
$old = ['title' => '', 'description' => '', 'category' => '', 'exchange_type' => 'give', 'location' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $old['title']         = trim((string)($_POST['title'] ?? ''));
    $old['description']   = trim((string)($_POST['description'] ?? ''));
    $old['category']      = (string)($_POST['category'] ?? '');
    $old['exchange_type'] = (string)($_POST['exchange_type'] ?? '');
    $old['location']      = trim((string)($_POST['location'] ?? ''));

    if ($err = validate_length($old['title'], 3, 150, 'Title'))               $errors[] = $err;
    if ($err = validate_length($old['description'], 10, 5000, 'Description')) $errors[] = $err;
    if (!in_array($old['category'], $allowedCategories, true))                $errors[] = 'Please choose a valid category.';
    if (!in_array($old['exchange_type'], $allowedExchange, true))              $errors[] = 'Please choose a valid exchange type.';
    if ($old['location'] !== '' && mb_strlen($old['location']) > 120)          $errors[] = 'Location must be 120 characters or fewer.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO resources (user_id, title, description, category, exchange_type, location)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $old['title'],
            $old['description'],
            $old['category'],
            $old['exchange_type'],
            $old['location'] !== '' ? $old['location'] : null,
        ]);
        flash('success', 'Your resource is now visible to the community.');
        header('Location: ' . BASE_URL . '/resources.php');
        exit;
    }
}

$pageTitle = 'Share a resource';
$activeNav = 'resources';
require __DIR__ . '/includes/header.php';
?>

<h1>Share a resource</h1>
<p class="meta" style="margin-bottom:16px;">Help another woman by sharing something you no longer need — or something you're willing to lend or exchange.</p>

<?php if ($errors): ?>
  <div class="flash flash-error">
    <ul style="margin:0;padding-left:18px;">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form class="form" method="post" novalidate>
  <?php csrf_field(); ?>

  <div class="field">
    <label for="title">Title</label>
    <input type="text" id="title" name="title" value="<?= e($old['title']) ?>" required maxlength="150">
    <div class="hint">e.g. "Matric maths textbook set (2019–2023)"</div>
  </div>

  <div class="field">
    <label for="description">Description</label>
    <textarea id="description" name="description" required maxlength="5000"><?= e($old['description']) ?></textarea>
    <div class="hint">Condition, what it's useful for, any conditions of the exchange.</div>
  </div>

  <div class="row">
    <div class="field">
      <label for="category">Category</label>
      <select id="category" name="category" required>
        <option value="">Choose…</option>
        <?php foreach ($allowedCategories as $c): ?>
          <option value="<?= e($c) ?>" <?= $old['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="exchange_type">Exchange type</label>
      <select id="exchange_type" name="exchange_type" required>
        <option value="give"     <?= $old['exchange_type'] === 'give'     ? 'selected' : '' ?>>Give away</option>
        <option value="lend"     <?= $old['exchange_type'] === 'lend'     ? 'selected' : '' ?>>Lend</option>
        <option value="exchange" <?= $old['exchange_type'] === 'exchange' ? 'selected' : '' ?>>Exchange</option>
      </select>
    </div>
  </div>

  <div class="field">
    <label for="location">Location (optional)</label>
    <input type="text" id="location" name="location" value="<?= e($old['location']) ?>" maxlength="120">
    <div class="hint">A suburb, city, or "Pickup only" note. Leave blank if not relevant.</div>
  </div>

  <button type="submit" class="btn btn-primary">Publish resource</button>
  <a class="btn btn-outline" href="<?= BASE_URL ?>/resources.php" style="margin-left:8px;">Cancel</a>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>