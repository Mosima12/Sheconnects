<?php
// profile.php — view and edit the current user's profile
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo    = require __DIR__ . '/config/db.php';
$userId = current_user_id();

$errors = [];
$old = [];

// Load the current user (used for display + defaults).
$stmt = $pdo->prepare(
    'SELECT id, name, email, bio, skills, interests, career_goals, industry, is_mentor, created_at
       FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$userId]);
$me = $stmt->fetch();
if (!$me) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

$editing = isset($_GET['edit']) && $_GET['edit'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $editing = true;

    $name         = trim((string)($_POST['name'] ?? ''));
    $bio          = trim((string)($_POST['bio'] ?? ''));
    $skills       = trim((string)($_POST['skills'] ?? ''));
    $interests    = trim((string)($_POST['interests'] ?? ''));
    $career_goals = trim((string)($_POST['career_goals'] ?? ''));
    $industry     = trim((string)($_POST['industry'] ?? ''));
    $isMentor     = isset($_POST['is_mentor']) ? 1 : 0;

    if ($err = validate_length($name, 2, 100, 'Name'))                  $errors[] = $err;
    if ($bio          !== '' && mb_strlen($bio) > 5000)                 $errors[] = 'Bio must be 5000 characters or fewer.';
    if ($skills       !== '' && mb_strlen($skills) > 500)               $errors[] = 'Skills must be 500 characters or fewer.';
    if ($interests    !== '' && mb_strlen($interests) > 500)            $errors[] = 'Interests must be 500 characters or fewer.';
    if ($career_goals !== '' && mb_strlen($career_goals) > 500)         $errors[] = 'Career goals must be 500 characters or fewer.';
    if ($industry     !== '' && mb_strlen($industry) > 120)             $errors[] = 'Industry must be 120 characters or fewer.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE users
                SET name = ?, bio = ?, skills = ?, interests = ?,
                    career_goals = ?, industry = ?, is_mentor = ?
              WHERE id = ?'
        );
        $stmt->execute([
            $name,
            $bio          !== '' ? $bio          : null,
            $skills       !== '' ? $skills       : null,
            $interests    !== '' ? $interests    : null,
            $career_goals !== '' ? $career_goals : null,
            $industry     !== '' ? $industry     : null,
            $isMentor,
            $userId,
        ]);
        flash('success', 'Profile updated.');
        header('Location: ' . BASE_URL . '/profile.php');
        exit;
    }

    // Keep what the user typed so nothing is lost on error.
    $old = compact('name', 'bio', 'skills', 'interests', 'career_goals', 'industry');
    $old['is_mentor'] = $isMentor;
}

// Defaults for the edit form: prefer submitted values, then DB values.
$form = [
    'name'         => $old['name']         ?? $me['name'],
    'bio'          => $old['bio']          ?? $me['bio']          ?? '',
    'skills'       => $old['skills']       ?? $me['skills']       ?? '',
    'interests'    => $old['interests']    ?? $me['interests']    ?? '',
    'career_goals' => $old['career_goals'] ?? $me['career_goals'] ?? '',
    'industry'     => $old['industry']     ?? $me['industry']     ?? '',
    'is_mentor'    => $old['is_mentor']    ?? (int)$me['is_mentor'],
];

$pageTitle = 'My profile';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>

<?php if (!$editing): ?>

  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
    <h1 style="margin:0;">My profile</h1>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/profile.php?edit=1">Edit profile</a>
  </div>

  <div class="card">
    <h2 style="margin-top:0;margin-bottom:4px;">
      <?= e($me['name']) ?>
      <?php if ($me['is_mentor']): ?>
        <span class="pill pill-accepted" style="font-size:.75rem;vertical-align:middle;">Mentor</span>
      <?php endif; ?>
    </h2>
    <p class="meta" style="margin:0 0 14px;">
      <?= e($me['email']) ?>
      · Member since <?= e(date('F Y', strtotime($me['created_at']))) ?>
      <?php if ($me['industry']): ?> · <?= e($me['industry']) ?><?php endif; ?>
    </p>

    <?php if ($me['bio']): ?>
      <p><?= nl2br(e($me['bio'])) ?></p>
    <?php else: ?>
      <p class="meta"><em>No bio yet — tell the community a little about yourself.</em></p>
    <?php endif; ?>

    <dl style="display:grid;grid-template-columns:max-content 1fr;gap:8px 16px;margin:20px 0 0;">
      <dt style="font-weight:600;">Skills</dt>
      <dd style="margin:0;"><?= $me['skills'] ? e($me['skills']) : '<span class="meta">Not set</span>' ?></dd>

      <dt style="font-weight:600;">Interests</dt>
      <dd style="margin:0;"><?= $me['interests'] ? e($me['interests']) : '<span class="meta">Not set</span>' ?></dd>

      <dt style="font-weight:600;">Career goals</dt>
      <dd style="margin:0;"><?= $me['career_goals'] ? e($me['career_goals']) : '<span class="meta">Not set</span>' ?></dd>

      <dt style="font-weight:600;">Industry</dt>
      <dd style="margin:0;"><?= $me['industry'] ? e($me['industry']) : '<span class="meta">Not set</span>' ?></dd>

      <dt style="font-weight:600;">Available to mentor</dt>
      <dd style="margin:0;"><?= $me['is_mentor'] ? 'Yes' : 'No' ?></dd>
    </dl>
  </div>

<?php else: ?>

  <h1><?= $errors ? 'Edit your profile — please fix the errors below' : 'Edit your profile' ?></h1>
  <p class="meta" style="margin-bottom:16px;">Update how the community sees you. Your email and password are not changed here.</p>

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
      <label for="name">Full name</label>
      <input type="text" id="name" name="name" value="<?= e($form['name']) ?>" required maxlength="100">
    </div>

    <div class="field">
      <label for="bio">Short bio</label>
      <textarea id="bio" name="bio" maxlength="5000"><?= e($form['bio']) ?></textarea>
      <div class="hint">Who you are, what you do, and what you'd like others to know.</div>
    </div>

    <div class="row">
      <div class="field">
        <label for="skills">Skills</label>
        <input type="text" id="skills" name="skills" value="<?= e($form['skills']) ?>" maxlength="500" placeholder="e.g. Graphic design, Python, bookkeeping">
      </div>
      <div class="field">
        <label for="interests">Interests</label>
        <input type="text" id="interests" name="interests" value="<?= e($form['interests']) ?>" maxlength="500" placeholder="e.g. Fintech, education, community building">
      </div>
    </div>

    <div class="field">
      <label for="career_goals">Career goals</label>
      <input type="text" id="career_goals" name="career_goals" value="<?= e($form['career_goals']) ?>" maxlength="500" placeholder="e.g. Become a data analyst within 2 years">
    </div>

    <div class="field">
      <label for="industry">Industry / field of study</label>
      <input type="text" id="industry" name="industry" value="<?= e($form['industry']) ?>" maxlength="120" placeholder="e.g. Technology, Marketing, Public Health">
    </div>

    <div class="field">
      <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
        <input type="checkbox" name="is_mentor" value="1" <?= $form['is_mentor'] ? 'checked' : '' ?>>
        I'm available to mentor other women
      </label>
      <div class="hint">When checked, you appear in the Mentors filter on the Mentorship page.</div>
    </div>

    <button type="submit" class="btn btn-primary">Save profile</button>
    <a class="btn btn-outline" href="<?= BASE_URL ?>/profile.php" style="margin-left:8px;">Cancel</a>
  </form>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>