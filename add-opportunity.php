<?php
// add-opportunity.php — publish an opportunity (internship, scholarship, etc.)
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo    = require __DIR__ . '/config/db.php';
$userId = current_user_id();

$allowedCategories = [
    'Internship', 'Job', 'Scholarship', 'Workshop', 'Networking Event', 'Entrepreneurship',
];

$errors = [];
$old = [
    'title' => '', 'description' => '', 'category' => '',
    'organization' => '', 'application_url' => '', 'deadline' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $old['title']           = trim((string)($_POST['title'] ?? ''));
    $old['description']     = trim((string)($_POST['description'] ?? ''));
    $old['category']        = (string)($_POST['category'] ?? '');
    $old['organization']    = trim((string)($_POST['organization'] ?? ''));
    $old['application_url'] = trim((string)($_POST['application_url'] ?? ''));
    $old['deadline']        = trim((string)($_POST['deadline'] ?? ''));

    if ($err = validate_length($old['title'], 3, 150, 'Title'))               $errors[] = $err;
    if ($err = validate_length($old['description'], 10, 5000, 'Description')) $errors[] = $err;
    if ($err = validate_length($old['organization'], 2, 150, 'Organisation')) $errors[] = $err;
    if (!in_array($old['category'], $allowedCategories, true))                $errors[] = 'Please choose a valid category.';

    // Optional URL — validate strictly if provided.
    $safeUrl = null;
    if ($old['application_url'] !== '') {
        $safeUrl = safe_http_url($old['application_url']);
        if ($safeUrl === null) {
            $errors[] = 'Application link must be a valid http:// or https:// URL.';
        }
    }

    // Optional deadline — must parse to a valid date if given.
    $deadline = null;
    if ($old['deadline'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $old['deadline']);
        if (!$dt || $dt->format('Y-m-d') !== $old['deadline']) {
            $errors[] = 'Deadline must be a valid date (YYYY-MM-DD).';
        } else {
            $deadline = $old['deadline'];
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO opportunities
                (user_id, title, description, category, organization, application_url, deadline)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $old['title'],
            $old['description'],
            $old['category'],
            $old['organization'],
            $safeUrl,
            $deadline,
        ]);
        flash('success', 'Opportunity published — the community can see it now.');
        header('Location: ' . BASE_URL . '/opportunities.php');
        exit;
    }
}

$pageTitle = 'Share an opportunity';
$activeNav = 'opportunities';
require __DIR__ . '/includes/header.php';
?>

<h1>Share an opportunity</h1>
<p class="meta" style="margin-bottom:16px;">Post an internship, job, scholarship, workshop, event, or business opportunity for other women to discover.</p>

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
    <div class="hint">e.g. "Women in Tech Scholarship 2027"</div>
  </div>

  <div class="field">
    <label for="organization">Organisation</label>
    <input type="text" id="organization" name="organization" value="<?= e($old['organization']) ?>" required maxlength="150">
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
      <label for="deadline">Deadline (optional)</label>
      <input type="date" id="deadline" name="deadline" value="<?= e($old['deadline']) ?>">
    </div>
  </div>

  <div class="field">
    <label for="description">Description</label>
    <textarea id="description" name="description" required maxlength="5000"><?= e($old['description']) ?></textarea>
    <div class="hint">What it is, who it's for, requirements, and how to apply.</div>
  </div>

  <div class="field">
    <label for="application_url">Application link (optional)</label>
    <input type="url" id="application_url" name="application_url"
           value="<?= e($old['application_url']) ?>" maxlength="500"
           placeholder="https://example.org/apply">
    <div class="hint">Must start with http:// or https://</div>
  </div>

  <button type="submit" class="btn btn-primary">Publish opportunity</button>
  <a class="btn btn-outline" href="<?= BASE_URL ?>/opportunities.php" style="margin-left:8px;">Cancel</a>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>